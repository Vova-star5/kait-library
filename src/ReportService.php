<?php

class ReportService
{
    /**
     * Список должников: читатели с просроченными книгами и суммой штрафа.
     */
    public static function debtors()
    {
        $result = [];
        $users = Database::fetchAll("SELECT * FROM users WHERE role = 'reader' ORDER BY full_name");
        foreach ($users as $user) {
            $loans = Database::fetchAll(
                'SELECT * FROM loans WHERE user_id = ? AND returned_at IS NULL',
                [$user['id']]
            );
            $overdueBooks = [];
            $totalFine = 0;
            foreach ($loans as $loan) {
                if (strtotime($loan['due_at']) < strtotime(date('Y-m-d'))) {
                    $copy = Database::fetchOne('SELECT * FROM copies WHERE id = ?', [$loan['copy_id']]);
                    $book = Database::fetchOne('SELECT * FROM books WHERE id = ?', [$copy['book_id']]);
                    $daysLate = floor((strtotime(date('Y-m-d')) - strtotime($loan['due_at'])) / 86400);
                    $fine = 0;
                    if ($user['category'] == 'student') {
                        $fine = $daysLate * 15;
                    }
                    $totalFine += $fine;
                    $overdueBooks[] = [
                        'title' => $book['title'],
                        'inventory_number' => $copy['inventory_number'],
                        'due_at' => $loan['due_at'],
                        'days_late' => $daysLate,
                        'fine' => $fine,
                    ];
                }
            }
            if (count($overdueBooks) > 0) {
                $result[] = [
                    'user' => $user,
                    'books' => $overdueBooks,
                    'total_fine' => $totalFine,
                ];
            }
        }
        return $result;
    }

    /**
     * Выгрузка должников в CSV (открывается в Excel).
     */
    public static function debtorsCsv()
    {
        $rows = self::debtors();
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ФИО', 'Email', 'Группа', 'Книга', 'Инв. номер', 'Срок', 'Дней просрочки', 'Штраф'], ';');
        foreach ($rows as $row) {
            foreach ($row['books'] as $book) {
                fputcsv($out, [
                    $row['user']['full_name'],
                    $row['user']['email'],
                    $row['user']['group_name'],
                    $book['title'],
                    $book['inventory_number'],
                    format_date($book['due_at']),
                    $book['days_late'],
                    $book['fine'],
                ], ';');
            }
        }
        rewind($out);
        return stream_get_contents($out);
    }

    public static function stats()
    {
        return [
            'books' => Database::fetchOne('SELECT COUNT(*) AS cnt FROM books')['cnt'],
            'copies' => Database::fetchOne('SELECT COUNT(*) AS cnt FROM copies')['cnt'],
            'readers' => Database::fetchOne("SELECT COUNT(*) AS cnt FROM users WHERE role = 'reader'")['cnt'],
            'active_loans' => Database::fetchOne('SELECT COUNT(*) AS cnt FROM loans WHERE returned_at IS NULL')['cnt'],
        ];
    }
}
