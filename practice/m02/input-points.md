# Реестр точек ввода данных

Этот файл используется в модулях 10, 11 и 13. Заполнен честно: «нет» — тоже ответ.

Как проверялось: **клиент** — атрибуты формы (`required`, `type`, `minlength`, `maxlength`,
`pattern`, `accept`) и код в `public/assets/js/*`; **сервер** — вызовы класса `Validator`,
приведение типов `(int)`, подготовленные выражения PDO и проверки ролей `Auth::hasRole` в
`public/index.php` и `public/api.php`. «Да» на сервере ставится, только если значение реально
проверяется до использования.

| № | Точка ввода (форма / endpoint / файл) | Поля | Проверка на клиенте | Проверка на сервере | Риск |
|---|---|---|---|---|---|
| 1 | Регистрация → `POST index.php?page=register` (`templates/register.php`) | full_name, email, password, category, group_name, phone | да: `required`, `type=email`, `minlength=6`, `pattern` группы | **частично**: `Validator` только `required(full_name,email,password)` и `minLength(password,6)` (`index.php:42`); email, category, group_name, phone не проверяются | средний: в БД попадает любой email и произвольная `category`/группа; пароль хранится как `md5` без соли (`Auth.php:55`) |
| 2 | Вход → `POST index.php?page=login` (`templates/login.php`) | email, password | да: `required`, `type=email` | нет отдельной валидации; сверка через подготовленный запрос `WHERE email = ?` (`Auth.php:33`) | средний: нет ограничения числа попыток — перебор пароля; пароль сравнивается по `md5` (`Auth.php:37`) |
| 3 | Добавление книги → `POST index.php?page=admin_books` | title, author, genre_id, year, isbn, copies, description | да: `required` у title/author, `type=number` у year/copies | **частично**: `Validator` только `required(title,author)` (`index.php:109`); genre_id, year, isbn, copies, description — без проверки, запись через подготовленный запрос | низкий: доступ только `admin`/`librarian` (`index.php:105`); некорректные year/copies попадают в БД как есть |
| 4 | Поиск / каталог → `GET api.php?action=books.search` | q, genre, sort, p | нет (значения берутся из полей формы как есть) | **нет**: `q`, `genre`, `sort` подставляются в SQL конкатенацией (`BookRepository.php:14,17,19`); `p` безопасен — `(int)` (`api.php:18`) | **высокий**: SQL-инъекция через `q`, `genre`, `sort` |
| 5 | Добавление отзыва → `POST api.php?action=reviews.add` (JSON) | book_id, rating, text | да: JS проверяет длину text ≥ 3 (`book.js:37`) | **нет**: `rating` и `text` сохраняются без проверки (`api.php:47–52`, `ReviewRepository.php:14`); book_id приводится к `(int)` | **высокий**: XSS — `text` выводится в DOM без экранирования через `innerHTML` (`book.js:26`); любой `rating` (в т.ч. 0, 999, отрицательный) |
| 6 | Загрузка обложки → `POST api.php?action=cover.upload` (multipart) | cover (файл), book_id | да: JS проверяет размер ≤ 2 МБ и `accept="image/*"` (`admin.js:23`, `admin_books.php:40`) | **нет**: имя и тип файла не проверяются, `move_uploaded_file` с именем из запроса (`api.php:112–113`) | **высокий**: загрузка произвольного файла и путь из имени (возможен `.php` в `public/uploads/`) |
| 7 | Выдача книги → `POST api.php?action=loan.issue` (JSON) | email, inventory | да: `type=email`, `pattern="INV-…"`, `required` (`librarian.php:14–16`) | да (по смыслу): читатель и экземпляр ищутся подготовленными запросами, проверяются статусы, лимиты, брони (`LoanService::issue`) | низкий: доступ по `Auth::check` — вход есть, но роль не проверяется (см. № 12) |
| 8 | Возврат книги → `POST api.php?action=loan.return` (JSON) | loan_id | нет (id берётся из data-атрибута строки) | да: `(int)` + проверка существования и статуса выдачи (`LoanService::returnBook`); роль проверяется `Auth::hasRole` (`api.php:83`) | низкий |
| 9 | Бронирование → `POST api.php?action=reserve` (JSON) | book_id | нет | да: `(int)`, проверка повторной брони подготовленным запросом (`LoanService::reserve`); требуется вход (`api.php:57`) | низкий |
| 10 | Отмена брони → `POST api.php?action=reservation.cancel` (JSON) | id | нет | **частично**: `(int)` есть, но **владелец брони не проверяется** (`api.php:69`, `ReservationRepository::cancel`) — любой вошедший отменит чужую бронь | средний: небезопасная прямая ссылка на объект (IDOR) |
| 11 | Смена роли / блокировка → `POST api.php?action=user.role` / `user.block` (JSON) | user_id, role / blocked | нет (select и checkbox в `admin_users`) | да: роль `admin` (`api.php:119,128`); user_id `(int)`; `role` подставляется в подготовленный запрос, но из набора значений не ограничивается | низкий: `role` может принять любое значение (нет `in()`-проверки) |
| 12 | Параметры URL карточки и API → `GET index.php?page=book&id=`, `api.php?action=book.get&id=`, `reviews.list&book_id=` | id, book_id | нет | да: везде `(int)` (`index.php:18`, `api.php:31,39`) | низкий |
| 13 | Экспорт должников → `GET index.php?page=debtors&export` | export (флаг) | нет | да: только факт наличия параметра; роль `librarian`/`admin` (`index.php:80`) | низкий |

**Итого точек: 13, без серверной проверки (полностью или частично): 6 — № 1, 3, 4, 5, 6, 10.**

Самые рискованные — № 4 (SQL-инъекция в поиске), № 5 (XSS в отзывах) и № 6 (загрузка произвольного
файла): данные из запроса попадают в SQL, в DOM и в файловую систему без серверной проверки. Эти строки
станут задачами в модулях 10 (безопасность), 11 (серверная проверка) и 13 (клиентская проверка).
Правило ревьюера: клиентская проверка (атрибуты формы, JS) защищает только честного пользователя —
запрос легко отправить в обход формы, поэтому главной считается серверная проверка.
