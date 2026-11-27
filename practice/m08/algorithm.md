# М08 · Восстановление алгоритма

- **Сценарий и его границы.** Возврат книги библиотекарем. Вход: библиотекарь на странице «Выдача и возврат» (`index.php?page=librarian`) нажимает «Принять» у строки активной выдачи и подтверждает действие. Выход: JSON-ответ `api.php?action=loan.return`, HTTP-статус, видимое состояние строки (удаляется или остаётся) и окно с сообщением, а в БД — `loans.returned_at`, `loans.fine` и `copies.status`. Внутренняя граница — метод `LoanService::returnBook($loanId)`: от `loanId` до возвращаемого массива и двух `UPDATE`.
- **Исследованный SHA, версия среды и дата наблюдения.** `2fbcb700fbc2f4804cc317cce6b48c662c741934` (main после М07). macOS 26.6, PHP 8.4.23 (встроенный сервер `php -S localhost:8080 -t public`), MySQL 8.4.9, Google Chrome 158. Наблюдение 27.11.2026 12:34 (+04:00); дата приложения D = 2026-11-27, часовой пояс приложения `Europe/Moscow` (`config/config.php:55`).
- **Роли, входы, предусловия и тестовые данные.** Роль — `librarian` или `admin` (`librarian@kait20.ru`, учебный пароль из `database/seed.sql`). Вход — `loan_id` из атрибута `data-loan-id` строки таблицы «Книги на руках». Предусловие для кнопки — выдача активна (`returned_at IS NULL`), иначе строки в таблице нет. Учебные выдачи 101–107 создаются скриптом [test-data.sql](test-data.sql) относительно текущей даты (см. [протокол](characterization.md)).
- **Возвращаемые значения, ошибки и изменения состояния.** Успех: HTTP 200, `{"ok": true, "message": "Книга принята", "fine": 0}` или `"Книга принята. Штраф: N руб."` с `fine = N`; `loans.returned_at = D`, `loans.fine = N`, `copies.status = 'available'`, строка исчезает, при `fine > 0` показывается `alert`. Отказы: HTTP 422 `{"ok": false, "message": "Выдача не найдена"}` или `"Книга уже возвращена"` — данные не меняются; HTTP 403 `{"ok": false, "error": "Доступ запрещён"}` для других ролей; HTTP 500 при исключении БД.

## Трассировка

Один обычный случай — C03 (студент, срок вчера): `loan_id = 103`, ответ HTTP 200 `{"ok": true, "message": "Книга принята. Штраф: 10 руб.", "fine": 10}`. Запрос и ответ сохранены в [return-c03-network.json](return-c03-network.json) (без cookies и заголовков авторизации).

| Шаг | Файл:строка на исследованном SHA | Что передаётся | Что получается |
|---|---|---|---|
| 1. Страница библиотекаря | `public/index.php:72–79` → `src/LoanService.php:239–250` (`activeLoans()`) | роль из сессии (`Auth::requireRole`) | список выдач с `returned_at IS NULL`, отсортированный по `due_at` |
| 2. Строка таблицы | `templates/librarian.php:30–31` | `$l['id']` → `data-loan-id="103"`; `strtotime(due_at) < time()` → класс `row-late` | строка с кнопкой |
| 3. Кнопка | `templates/librarian.php:37` | класс `return-btn` | кнопка «Принять» |
| 4. Обработчик | `public/assets/js/librarian.js:32–36` | `confirm('Принять книгу?')`; при «Отмена» выход без запроса; `row.dataset.loanId` (строка `"103"`) | вызов `api('loan.return', {method: 'POST', body: {loan_id: "103"}})` |
| 5. Клиент API | `public/assets/js/api.js:4–21` | `POST api.php?action=loan.return`, `Content-Type: application/json`, тело `{"loan_id":"103"}` | разобранный JSON; если ответ не JSON — `{ok: false, error: 'Ошибка сервера'}` |
| 6. Маршрут API | `public/api.php:9`, `:83–86` | `action=loan.return`; `Auth::hasRole(['librarian','admin'])` | иначе HTTP 403 `{"ok": false, "error": "Доступ запрещён"}` |
| 7. Тело запроса | `public/api.php:87–88`, `src/helpers.php:84–89` | `request_json()` → массив; `(int)$data['loan_id']` | `returnBook(103)` |
| 8. Чтение выдачи | `src/LoanService.php:191–194` | `SELECT l.*, u.category FROM loans l JOIN users u … WHERE l.id = ?` | строка выдачи с категорией читателя или `null` |
| 9. Ранние выходы | `src/LoanService.php:195–200` | `!$loan`; `$loan['returned_at']` | `['ok' => false, 'message' => 'Выдача не найдена' / 'Книга уже возвращена']` |
| 10. Штраф | `src/LoanService.php:202–208`, `config/config.php:48` | `today = strtotime(date('Y-m-d'))`, `due = strtotime(due_at)`; `today > due && category == 'student'` | `fine = floor((today − due) / 86400) × FINE_PER_DAY` = 1 × 10 = 10 |
| 11. Запись | `src/LoanService.php:210–214` | `UPDATE loans SET returned_at = D, fine = 10 WHERE id = 103`; `UPDATE copies SET status = 'available' WHERE id = copy_id` | выдача закрыта, экземпляр свободен |
| 12. Ответ сервиса | `src/LoanService.php:216–220` | `$msg` + `. Штраф: 10 руб.` при `fine > 0` | `['ok' => true, 'message' => …, 'fine' => 10]` |
| 13. HTTP-ответ | `public/api.php:89`, `src/helpers.php:15–21` | `$result['ok'] ? 200 : 422` | HTTP 200, `application/json` |
| 14. Интерфейс | `public/assets/js/librarian.js:37–42` | `data.ok`, `data.fine` | `row.remove()`; `alert(data.message)` только при `fine > 0`; при `ok = false` — `alert(message или error)` |

Ошибки БД внутри `returnBook()` перехватываются в `public/api.php:140–141` и отдаются как HTTP 500 `{"ok": false, "error": <текст исключения>}`.

## Псевдокод

Номера в скобках — строки `src/LoanService.php` и `public/api.php` на SHA `2fbcb70`.

```text
ВХОД: библиотекарь нажал «Принять» у строки выдачи с data-loan-id = id
ЕСЛИ confirm("Принять книгу?") = «Отмена» ТО                       // librarian.js:35
    ВЫХОД (запрос не отправляется, строка остаётся)
ОТПРАВИТЬ POST api.php?action=loan.return, тело {"loan_id": id}     // librarian.js:36, api.js:4–13

// public/api.php
ЕСЛИ роль пользователя не librarian и не admin ТО                  // api.php:84
    ОТВЕТ 403 {ok: false, error: "Доступ запрещён"}; ВЫХОД          // api.php:85
loanId ← целое(тело.loan_id)  (нет поля или не число → 0)          // api.php:87–88
result ← returnBook(loanId)

// LoanService::returnBook(loanId)
loan ← SELECT l.*, u.category FROM loans l JOIN users u … WHERE l.id = loanId   // 191–194
ЕСЛИ loan не найдена ТО
    ВЕРНУТЬ {ok: false, message: "Выдача не найдена"}                 // 195–197
ЕСЛИ loan.returned_at заполнено ТО
    ВЕРНУТЬ {ok: false, message: "Книга уже возвращена"}              // 198–200
fine ← 0                                                            // 202
today ← полночь даты D (date('Y-m-d'), пояс Europe/Moscow)          // 203
due ← полночь loan.due_at                                           // 204
ЕСЛИ today > due (строго) И loan.category = "student" ТО            // 205
    late_days ← floor((today − due) / 86400)                        // 206
    fine ← late_days × FINE_PER_DAY  (10, config/config.php:48)     // 207
UPDATE loans SET returned_at = D, fine = fine WHERE id = loanId      // 210–213
UPDATE copies SET status = 'available' WHERE id = loan.copy_id       // 214
message ← "Книга принята"                                           // 216
ЕСЛИ fine > 0 ТО message ← message + ". Штраф: " + fine + " руб."   // 217–219
ВЕРНУТЬ {ok: true, message, fine}                                    // 220

// public/api.php
ОТВЕТ JSON result, статус 200 если result.ok иначе 422              // api.php:89
(исключение БД → 500 {ok: false, error: текст исключения})          // api.php:140–141

// librarian.js:37–42
ЕСЛИ ответ.ok ТО удалить строку; ЕСЛИ ответ.fine > 0 ТО alert(ответ.message)
ИНАЧЕ alert(ответ.message или ответ.error), строка остаётся
```

Другого правила здесь не предлагается: псевдокод описывает поведение исследованного SHA.

## Диаграммы

- Блок-схема — [flowchart.png](flowchart.png) · редактируемый исходник — [flowchart.drawio](flowchart.drawio) (draw.io 32.7.4)
- UML-диаграмма последовательности — [sequence.png](sequence.png) · редактируемый исходник — [sequence.drawio](sequence.drawio). Участники слева направо: библиотекарь → браузер → PHP API → LoanService → БД. Ранний выход по роли показан фрагментом `break`, отказ и успешный возврат — фрагментом `alt`. Повторяющихся сообщений в сценарии нет, поэтому `loop` не используется.
- Условия, выходы и связь с проверками по ID:

| Ветвь | Условие (код) | Выход | Проверки |
|---|---|---|---|
| Б1 Роль | `!Auth::hasRole(['librarian','admin'])` (api.php:84) | 403, `error` «Доступ запрещён», данные не меняются | C08 |
| Б2 Нет выдачи | `!$loan` (LoanService.php:195) | 422, «Выдача не найдена» | C06 |
| Б3 Уже возвращена | `$loan['returned_at']` (198) | 422, «Книга уже возвращена», данные не меняются | C04 |
| Б4 Без штрафа | `!(today > due && category == 'student')` (205) | 200, `fine = 0`, «Книга принята», окна нет | C01 (срок после D), C02 (срок = D, граница), C05 (преподаватель, просрочка) |
| Б5 Штраф | `today > due && category == 'student'` (205) | 200, `fine = дни × 10`, «Книга принята. Штраф: N руб.», `alert` | C03 (1 день, граница), C07 (10 дней) |

Сверка обеих моделей на одном случае. Возврат в день срока (C02): на блок-схеме после «returned_at заполнено? — нет» ромб «today > due_at (строго) и category = student?» даёт «нет», значит `fine = 0`, затем две записи и ответ «Книга принята». На UML это операнд `[выдача активна]`: сообщение 7 (`fine = 0`), 8 и 9 — к БД, 10–12 — ответ и удаление строки. Порядок совпадает с `LoanService.php:202–220`. Отказ (C04): ромб «returned_at заполнено? — да» → 422; на UML — операнд `[… returned_at заполнено]`, сообщения 7a–9a, обращений к БД после `SELECT` нет.

## Расхождения и вывод

TODO что подтверждено кодом и запуском, какие расхождения обнаружены, что проверять после изменений.
