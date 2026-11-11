<?php

/**
 * JSON API для клиентских скриптов: api.php?action=...
 */

require_once __DIR__ . '/../src/bootstrap.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'books.search':
            $books = BookRepository::search(
                $_GET['q'] ?? '',
                $_GET['genre'] ?? '',
                $_GET['sort'] ?? 'b.title'
            );
            $page = max(1, (int)($_GET['p'] ?? 1));
            $total = count($books);
            $items = array_slice($books, ($page - 1) * ITEMS_PER_PAGE, ITEMS_PER_PAGE);
            json_response([
                'ok' => true,
                'items' => $items,
                'total' => $total,
                'pages' => (int)ceil($total / ITEMS_PER_PAGE),
                'page' => $page,
            ]);
            break;

        case 'book.get':
            $book = BookRepository::find((int)($_GET['id'] ?? 0));
            if (!$book) {
                json_error('Книга не найдена', 404);
            }
            json_response(['ok' => true, 'book' => $book]);
            break;

        case 'reviews.list':
            json_response(['ok' => true, 'items' => ReviewRepository::forBook((int)$_GET['book_id'])]);
            break;

        case 'reviews.add':
            if (!Auth::check()) {
                json_error('Нужно войти', 401);
            }
            $data = request_json();
            $id = ReviewRepository::add(
                (int)$data['book_id'],
                Auth::user()['id'],
                $data['rating'],
                $data['text']
            );
            json_response(['ok' => true, 'id' => $id]);
            break;

        case 'reserve':
            if (!Auth::check()) {
                json_error('Нужно войти', 401);
            }
            $data = request_json();
            json_response(LoanService::reserve((int)$data['book_id'], Auth::user()['id']));
            break;

        case 'reservation.cancel':
            if (!Auth::check()) {
                json_error('Нужно войти', 401);
            }
            $data = request_json();
            ReservationRepository::cancel((int)$data['id']);
            json_response(['ok' => true]);
            break;

        case 'loan.issue':
            if (!Auth::check()) {
                json_error('Нужно войти', 401);
            }
            $data = request_json();
            $result = LoanService::issue($data['email'] ?? '', $data['inventory'] ?? '', Auth::user()['id']);
            json_response($result, $result['ok'] ? 200 : 422);
            break;

        case 'loan.return':
            if (!Auth::hasRole(['librarian', 'admin'])) {
                json_error('Доступ запрещён', 403);
            }
            $data = request_json();
            $result = LoanService::returnBook((int)$data['loan_id']);
            json_response($result, $result['ok'] ? 200 : 422);
            break;

        case 'loans.my':
            if (!Auth::check()) {
                json_error('Нужно войти', 401);
            }
            json_response(['ok' => true, 'items' => LoanService::userLoans(Auth::user()['id'])]);
            break;

        case 'readers':
            if (!Auth::hasRole(['librarian', 'admin'])) {
                json_error('Доступ запрещён', 403);
            }
            json_response(['ok' => true, 'items' => UserRepository::readers()]);
            break;

        case 'cover.upload':
            if (!Auth::hasRole(['librarian', 'admin'])) {
                json_error('Доступ запрещён', 403);
            }
            if (empty($_FILES['cover'])) {
                json_error('Файл не передан');
            }
            $name = $_FILES['cover']['name'];
            move_uploaded_file($_FILES['cover']['tmp_name'], UPLOAD_DIR . $name);
            BookRepository::setCover((int)$_POST['book_id'], $name);
            json_response(['ok' => true, 'file' => 'uploads/' . $name]);
            break;

        case 'user.role':
            if (!Auth::hasRole('admin')) {
                json_error('Доступ запрещён', 403);
            }
            $data = request_json();
            UserRepository::setRole((int)$data['user_id'], $data['role']);
            json_response(['ok' => true]);
            break;

        case 'user.block':
            if (!Auth::hasRole('admin')) {
                json_error('Доступ запрещён', 403);
            }
            $data = request_json();
            UserRepository::setBlocked((int)$data['user_id'], !empty($data['blocked']));
            json_response(['ok' => true]);
            break;

        default:
            json_error('Неизвестное действие', 404);
    }
} catch (Exception $ex) {
    json_error($ex->getMessage(), 500);
}
