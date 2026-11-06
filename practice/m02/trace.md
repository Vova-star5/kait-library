# Трассировка сценариев

Картинка DFD: `dfd.drawio` + `dfd-0.png` (контекстная) + `dfd-1.png` (уровень 1).

## Элементы DFD

| Тип | Название | Описание |
|---|---|---|
| Внешняя сущность | Читатель | роль `reader` и гость без входа: поиск, регистрация, отзывы, бронирование, кабинет |
| Внешняя сущность | Библиотекарь | роль `librarian`: выдача и возврат, отмена брони, книги и обложки, отчёты |
| Внешняя сущность | Администратор | роль `admin`: роли и блокировка пользователей, также все функции библиотекаря |
| Процесс | 1 Вести учётные записи | `page=login`, `register`, `logout`, `admin_users`, `user.role`, `user.block`; `src/Auth.php`, `src/UserRepository.php` |
| Процесс | 2 Вести отзывы | `reviews.list`, `reviews.add`; `src/ReviewRepository.php` |
| Процесс | 3 Найти книги | `page=catalog`, `page=book`, `books.search`, `book.get`; `src/BookRepository.php` |
| Процесс | 4 Забронировать книгу, показать кабинет | `reserve`, `loans.my`, `page=profile`; `LoanService::reserve`, `ReservationRepository::forUser` |
| Процесс | 5 Оформить выдачу и возврат | `page=librarian`, `loan.issue`, `loan.return`, `readers`, `reservation.cancel`; `src/LoanService.php` |
| Процесс | 6 Вести каталог | `page=admin_books`, `cover.upload`; `BookRepository::create`, `addCopy`, `setCover` |
| Процесс | 7 Сформировать отчёты | `page=debtors`, `debtors&export`, `page=reservations`, статистика на `page=librarian`; `src/ReportService.php` |
| Хранилище | D1 Сессии | PHP-сессия на сервере, `$_SESSION['user_id']`, `flash` |
| Хранилище | D2 Пользователи | таблица `users` |
| Хранилище | D3 Отзывы | таблица `reviews` |
| Хранилище | D4 Файлы обложек | папка `public/uploads/` |
| Хранилище | D5 Каталог книг | таблицы `books`, `genres`, `copies` |
| Хранилище | D6 Выдачи | таблица `loans` |
| Хранилище | D7 Бронирования | таблица `reservations` |

Внешних API, почты, кэша и `localStorage` в проекте нет: поиск по коду ничего не нашёл.

## Сценарий 1: TODO (например «Регистрация пользователя»)

| Шаг | Где (файл:строка) | Функция / endpoint | Какие данные передаются |
|---|---|---|---|
| 1. Пользователь заполняет форму | TODO | | |
| 2. Клиент отправляет запрос | TODO | `POST /api/...` | |
| 3. Сервер обрабатывает | TODO | | |
| 4. Запись в БД | TODO | | |
| 5. Ответ клиенту | TODO | | |

Запрос из DevTools (HAR сохранён как `scenario1.har`): метод, URL, код ответа, тело — TODO

## Сценарий 2: TODO

| Шаг | Где (файл:строка) | Функция / endpoint | Какие данные передаются |
|---|---|---|---|
| TODO | | | |

## Расхождения

Что в коде не совпало с тем, что вы ожидали по интерфейсу: TODO
