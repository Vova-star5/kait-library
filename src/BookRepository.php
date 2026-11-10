<?php

class BookRepository
{
    /**
     * Поиск книг по названию или автору с фильтром по жанру.
     */
    public static function search($query = '', $genreId = '', $sort = 'b.title')
    {
        $sql = "SELECT b.*, g.name AS genre FROM books b
                LEFT JOIN genres g ON g.id = b.genre_id
                WHERE 1=1";
        if ($query != '') {
            $sql .= " AND (b.title LIKE '%" . $query . "%' OR b.author LIKE '%" . $query . "%')";
        }
        if ($genreId != '') {
            $sql .= " AND b.genre_id = " . $genreId;
        }
        $sql .= " ORDER BY " . $sort;

        $books = Database::get()->query($sql)->fetchAll();

        // количество свободных экземпляров для каждой книги
        foreach ($books as $i => $book) {
            $books[$i]['available'] = self::availableCount($book['id']);
        }
        return $books;
    }

    public static function find($id)
    {
        $book = Database::fetchOne(
            'SELECT b.*, g.name AS genre FROM books b LEFT JOIN genres g ON g.id = b.genre_id WHERE b.id = ?',
            [$id]
        );
        if ($book) {
            $book['available'] = self::availableCount($book['id']);
            $book['copies'] = Database::fetchAll('SELECT * FROM copies WHERE book_id = ? ORDER BY inventory_number', [$id]);
            $book['rating'] = Database::fetchOne(
                'SELECT ROUND(AVG(rating), 1) AS avg, COUNT(*) AS cnt FROM reviews WHERE book_id = ?',
                [$id]
            );
        }
        return $book;
    }

    public static function availableCount($bookId)
    {
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS cnt FROM copies WHERE book_id = ? AND status = 'available'",
            [$bookId]
        );
        return (int)$row['cnt'];
    }

    public static function genres()
    {
        return Database::fetchAll('SELECT * FROM genres ORDER BY name');
    }

    public static function create($data)
    {
        return Database::insert(
            'INSERT INTO books (title, author, genre_id, year, isbn, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['author'],
                $data['genre_id'] ?: null,
                $data['year'] ?: null,
                $data['isbn'] ?: null,
                $data['description'] ?? '',
                date('Y-m-d H:i:s'),
            ]
        );
    }

    public static function addCopy($bookId, $inventoryNumber)
    {
        return Database::insert(
            "INSERT INTO copies (book_id, inventory_number, status) VALUES (?, ?, 'available')",
            [$bookId, $inventoryNumber]
        );
    }

    public static function setCover($bookId, $fileName)
    {
        return Database::execute('UPDATE books SET cover = ? WHERE id = ?', [$fileName, $bookId]);
    }

    public static function popular($limit = 5)
    {
        return Database::fetchAll(
            'SELECT b.id, b.title, b.author, COUNT(l.id) AS loans
             FROM books b
             JOIN copies c ON c.book_id = b.id
             JOIN loans l ON l.copy_id = c.id
             GROUP BY b.id, b.title, b.author
             ORDER BY loans DESC
             LIMIT ' . (int)$limit
        );
    }
}
