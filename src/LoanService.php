<?php

/**
 * Выдача и возврат книг.
 */
class LoanService
{
    /**
     * Выдать экземпляр книги читателю.
     * Возвращает ['ok' => bool, 'message' => string, 'due_at' => date, 'warnings' => []]
     */
    public static function issue($readerEmail, $inventoryNumber, $librarianId)
    {
        $warnings = [];

        $reader = Database::fetchOne('SELECT * FROM users WHERE email = ?', [$readerEmail]);
        if (!$reader) {
            return ['ok' => false, 'message' => 'Читатель не найден'];
        }
        if ($reader['is_blocked'] == 1) {
            return ['ok' => false, 'message' => 'Читатель заблокирован'];
        }

        $copy = Database::fetchOne('SELECT * FROM copies WHERE inventory_number = ?', [$inventoryNumber]);
        if (!$copy) {
            return ['ok' => false, 'message' => 'Экземпляр не найден'];
        }
        $copyError = self::copyStatusError($copy);
        if ($copyError !== null) {
            return ['ok' => false, 'message' => $copyError];
        }

        // лимит книг на руках
        $active = Database::fetchOne(
            'SELECT COUNT(*) AS cnt FROM loans WHERE user_id = ? AND returned_at IS NULL',
            [$reader['id']]
        );
        if ($reader['category'] == 'student') {
            $limit = MAX_BOOKS_STUDENT;
        } else {
            $limit = MAX_BOOKS_TEACHER;
        }
        if ($active['cnt'] > $limit) {
            return ['ok' => false, 'message' => 'Превышен лимит книг на руках (' . $limit . ')'];
        }

        // просроченные книги
        $overdue = Database::fetchOne(
            'SELECT COUNT(*) AS cnt FROM loans WHERE user_id = ? AND returned_at IS NULL AND due_at < ?',
            [$reader['id'], date('Y-m-d')]
        );
        if ($overdue['cnt'] > 0) {
            if ($reader['category'] == 'student') {
                return ['ok' => false, 'message' => 'У читателя есть просроченные книги'];
            } else {
                $warnings[] = 'У читателя есть просроченные книги (' . $overdue['cnt'] . ')';
            }
        }

        // бронирования: свободный экземпляр нельзя выдать, если его ждут другие
        $reservations = Database::fetchAll(
            "SELECT * FROM reservations WHERE book_id = ? AND status = 'active' ORDER BY created_at",
            [$copy['book_id']]
        );
        $ownReservation = null;
        $othersBefore = 0;
        foreach ($reservations as $reservation) {
            if ($reservation['user_id'] == $reader['id']) {
                $ownReservation = $reservation;
                break;
            }
            $othersBefore++;
        }
        if ($othersBefore > 0) {
            $available = BookRepository::availableCount($copy['book_id']);
            if ($available <= $othersBefore) {
                return ['ok' => false, 'message' => 'Все свободные экземпляры забронированы другими читателями'];
            }
        }

        // срок возврата
        if ($reader['category'] == 'student') {
            $days = LOAN_DAYS_STUDENT;
            $month = (int)date('n');
            if ($month >= 6 && $month <= 8) {
                $days = $days * 2;
            }
        } else {
            $days = LOAN_DAYS_TEACHER;
        }
        $due = strtotime('+' . $days . ' days');
        $weekday = (int)date('w', $due);
        if ($weekday == 0) {
            $due = strtotime('+1 day', $due);
        } elseif ($weekday == 6) {
            $due = strtotime('+2 days', $due);
        }
        $dueAt = date('Y-m-d', $due);

        Database::insert(
            'INSERT INTO loans (copy_id, user_id, librarian_id, issued_at, due_at) VALUES (?, ?, ?, ?, ?)',
            [$copy['id'], $reader['id'], $librarianId, date('Y-m-d'), $dueAt]
        );
        Database::execute("UPDATE copies SET status = 'issued' WHERE id = ?", [$copy['id']]);
        if ($ownReservation) {
            Database::execute("UPDATE reservations SET status = 'done' WHERE id = ?", [$ownReservation['id']]);
        }

        return [
            'ok' => true,
            'message' => 'Книга выдана до ' . format_date($dueAt),
            'due_at' => $dueAt,
            'warnings' => $warnings,
        ];
    }

    /**
     * Причина, по которой экземпляр нельзя выдать, или null, если он свободен.
     */
    private static function copyStatusError($copy)
    {
        if ($copy['status'] == 'available') {
            return null;
        }
        if ($copy['status'] == 'issued') {
            return 'Экземпляр уже выдан';
        }
        if ($copy['status'] == 'repair') {
            return 'Экземпляр на ремонте';
        }
        return 'Экземпляр списан или утерян';
    }

    /**
     * Принять книгу обратно, начислить штраф за просрочку.
     */
    public static function returnBook($loanId)
    {
        $loan = Database::fetchOne(
            'SELECT l.*, u.category FROM loans l JOIN users u ON u.id = l.user_id WHERE l.id = ?',
            [$loanId]
        );
        if (!$loan) {
            return ['ok' => false, 'message' => 'Выдача не найдена'];
        }
        if ($loan['returned_at']) {
            return ['ok' => false, 'message' => 'Книга уже возвращена'];
        }

        $fine = 0;
        $today = strtotime(date('Y-m-d'));
        $due = strtotime($loan['due_at']);
        if ($today > $due && $loan['category'] == 'student') {
            $daysLate = floor(($today - $due) / 86400);
            $fine = $daysLate * FINE_PER_DAY;
        }

        Database::execute(
            'UPDATE loans SET returned_at = ?, fine = ? WHERE id = ?',
            [date('Y-m-d'), $fine, $loanId]
        );
        Database::execute("UPDATE copies SET status = 'available' WHERE id = ?", [$loan['copy_id']]);

        $msg = 'Книга принята';
        if ($fine > 0) {
            $msg .= '. Штраф: ' . $fine . ' руб.';
        }
        return ['ok' => true, 'message' => $msg, 'fine' => $fine];
    }

    public static function reserve($bookId, $userId)
    {
        $exists = Database::fetchOne(
            "SELECT id FROM reservations WHERE book_id = ? AND user_id = ? AND status = 'active'",
            [$bookId, $userId]
        );
        if ($exists) {
            return ['ok' => false, 'message' => 'Вы уже забронировали эту книгу'];
        }
        Database::insert(
            "INSERT INTO reservations (book_id, user_id, created_at, status) VALUES (?, ?, ?, 'active')",
            [$bookId, $userId, date('Y-m-d H:i:s')]
        );
        return ['ok' => true, 'message' => 'Книга забронирована'];
    }

    public static function activeLoans()
    {
        return Database::fetchAll(
            'SELECT l.*, u.full_name, u.email, b.title, c.inventory_number
             FROM loans l
             JOIN users u ON u.id = l.user_id
             JOIN copies c ON c.id = l.copy_id
             JOIN books b ON b.id = c.book_id
             WHERE l.returned_at IS NULL
             ORDER BY l.due_at'
        );
    }

    public static function userLoans($userId)
    {
        return Database::fetchAll(
            'SELECT l.*, b.title, b.author, c.inventory_number
             FROM loans l
             JOIN copies c ON c.id = l.copy_id
             JOIN books b ON b.id = c.book_id
             WHERE l.user_id = ?
             ORDER BY l.issued_at DESC',
            [$userId]
        );
    }
}
