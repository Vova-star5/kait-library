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

## Сценарий 1: Регистрация читателя (процесс 1 → D2)

Обычная HTML-форма с `method="post"`, без JavaScript и fetch — браузер отправляет
`application/x-www-form-urlencoded` и получает редирект.

| Шаг | Где (файл:строка) | Функция / endpoint | Какие данные передаются |
|---|---|---|---|
| 1. Читатель заполняет форму | `templates/register.php:3–28` | `<form method="post" action="index.php?page=register">` | full_name, email, password, category, group_name, phone |
| 2. Браузер отправляет запрос | — (сабмит формы) | `POST /index.php?page=register` | те же поля в теле запроса |
| 3. Сервер принимает и проверяет | `public/index.php:38–52` | `case 'register'` + `new Validator($_POST)` | required: full_name, email, password; minLength password = 6 |
| 4. Запись в БД | `src/Auth.php:47–63` | `Auth::register($_POST)` → `Database::insert` | INSERT в `users`; пароль — `md5($data['password'])`, роль жёстко `reader` |
| 5. Старт сессии и ответ | `public/index.php:46–49`, `src/helpers.php:8–13` | `$_SESSION['user_id'] = $id`; `redirect('catalog')` | `302 Found`, `Location: index.php?page=catalog`, cookie `PHPSESSID` |

Запрос из DevTools (HAR — `scenario1.har`, запись `index.php?page=register`):
метод **POST**, URL `http://localhost/kait-library/public/index.php?page=register`,
код ответа **302 Found**, `Location: index.php?page=catalog`, тело (Form data) —
`full_name`, `email`, `password` (в HAR заменён на `***`), `category`, `group_name`, `phone`.
Заголовок ответа `Set-Cookie: PHPSESSID=***` — так создаётся запись в хранилище D1 «Сессии».

## Сценарий 2: Поиск книги в каталоге (процесс 3 → D5)

AJAX-сценарий: страница вызывает JSON-API через `fetch`, без перезагрузки.

| Шаг | Где (файл:строка) | Функция / endpoint | Какие данные передаются |
|---|---|---|---|
| 1. Читатель вводит запрос и жмёт «Найти» | `templates/catalog.php:4–18` | форма `#search-form`, поля `q`, `genre`, `sort` | строка поиска, id жанра, поле сортировки |
| 2. Клиент собирает параметры и шлёт запрос | `public/assets/js/catalog.js:40–49`, `api.js:4–13` | `api('books.search', { params })` → `GET api.php?action=books.search&q=&genre=&sort=&p=` | q, genre, sort, p (номер страницы) |
| 3. Сервер разбирает запрос | `public/api.php:12–20` | `case 'books.search'` | `$_GET['q']`, `$_GET['genre']`, `$_GET['sort']`, `$_GET['p']` |
| 4. Запрос к БД | `src/BookRepository.php:8–27` | `BookRepository::search($q, $genreId, $sort)` | SQL `SELECT … FROM books … WHERE …`; q/genre/sort **вставляются в строку запроса** |
| 5. Ответ клиенту | `public/api.php:21–27`, `catalog.js:53–56` | `json_response([...])` → `renderPager`, `bookCard` | JSON: items, total, pages, page; список карточек книг |

## Расхождения

- **Пагинация выполняется не в БД, а в PHP.** По интерфейсу (постраничный вывод) ожидался
  `LIMIT/OFFSET` в SQL, но `src/BookRepository.php:8–27` всегда выбирает все книги, а срез страницы
  делает `array_slice` в `public/api.php:20`. На диаграмме это по-прежнему один поток «список книг» D5 → процесс 3.
- **Поиск собирает SQL конкатенацией строк** (`BookRepository.php:14,17,19`): `q`, `genre` и `sort`
  подставляются в запрос напрямую, тогда как остальные обращения к БД идут через подготовленные
  выражения (`Database::fetchAll/fetchOne` с `?`). Это расхождение зафиксировано в реестре точек ввода
  как риск SQL-инъекции (точка № 5) и уйдёт в модуль 10.
- **Пароль хранится как `md5` без соли** (`src/Auth.php:37,55`) — по стеку (современный PHP 8) ожидался
  `password_hash`. Поток «учётная запись» в D2 остаётся, но помечен как находка для модуля 10.
- Регистрация **не проверяет формат email и значение `category`** на сервере (`Validator` вызывает только
  `required` и `minLength`), хотя класс `Validator` умеет `email()` и `in()` — методы есть, но не вызваны.
