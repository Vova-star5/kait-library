# М09 · Изменение характеристик кода в двух IDE

- Сценарий М08 и ссылка на протокол — «Возврат книги», [algorithm.md](../m08/algorithm.md), [characterization.md](../m08/characterization.md) (C01–C08), PR М08 — [#9](https://github.com/Vova-star5/kait-library/pull/9)
- Файл, исходная функция и базовый SHA — `src/LoanService.php`, `LoanService::returnBook()` (строки 189–221); базовый SHA `fbaffb09caec7cb4cb478b653c884633204e5800` (main после вливания М08; код совпадает с исследованным в М08 SHA `2fbcb70`)
- Итоговый SHA кода, версии PHP или среды выполнения, анализатора и линтера — `6bb8329` (после двух коммитов рефакторинга); PHP 8.4.23, Lizard 1.26.1, PHP_CodeSniffer 4.0.4
- IDE 1 и IDE 2, версии и расширения — IDE 1: Visual Studio Code 1.148.0 (macOS, arm64) с расширениями PHP Intelephense 1.18.5 (бесплатная версия) и EditorConfig 0.18.2, папка проекта отмечена как доверенная. IDE 2: Apache NetBeans 32 на JDK Temurin 21.0.13, файлы проекта `nbproject/` из М04
- Правила М04 и область замеров — `phpcs.xml` проекта (PSR-12), PHP_CodeSniffer 4.0.4 из ПП.03, `--standard=phpcs.xml`; Lizard 1.26.1 `-l php src/LoanService.php`; `php -l`; `git ls-files --eol`; `metrics.py` по всему проекту (сырые данные в [metrics-raw.md](metrics-raw.md)). Сравнивается только целевой файл и метод, а не весь проект.

## Исходный замер (27.11.2026, SHA `fbaffb0`)

| Показатель | Значение |
|---|---|
| `LoanService::returnBook`, Lizard | NLOC 30, CCN 6, токенов 182, параметров 1, длина 33 строки (189–221) |
| `src/LoanService.php`, Lizard | NLOC 212, 11 функций, средний CCN 3,7 |
| EOL | `i/lf w/lf attr/text=auto eol=lf` |
| Отступы | 4 пробела, табуляций и пробелов в конце строк нет (`git grep -P "^\t"`, `git grep -P " +$"` — пусто) |
| `php -l` (PHP 8.4.23) | No syntax errors detected |
| PHPCS 4.0.4, `--standard=phpcs.xml --report=summary` | 0 ошибок, 0 предупреждений |
| Проект, `metrics.py` «М09 до» | PHP 1414 строк кода, 98 функций, средний CCN 2,28, максимум 8 — `LoanService::issue` |

Вывод Lizard для файла — [lizard-before.txt](lizard-before.txt).

| Операция | IDE 1, команда и результат | IDE 2, команда и результат |
|---|---|---|
| Навигация к объявлению | Go to Definition (F12) входит в бесплатную часть Intelephense; в М09 файл открыт на строке вызова командой `code -g src/LoanService.php:203`, строка навигации показывает `LoanService › returnBook` (снимок) | Navigate → Go to Declaration (⌘B) и окно Navigator есть в меню; в М09 файл открыт на объявлении `lateFine` командой `netbeans --open src/LoanService.php:221` — курсор 221:1 на снимке |
| Переименование | Rename Symbol (F2) для PHP в Intelephense — только в Premium (ограничение отмечено ещё в М03), бесплатная версия команду не выполняет | Refactor → Rename (Ctrl+R) для локальной переменной доступен (проверено в М03). В М09 управлять NetBeans с клавиатуры не удалось (macOS разрешил только просмотр и клики), поэтому `$msg → $message` сделано вручную: 3 строки (216, 218, 220), проверено `git diff` и `php -l` (коммит `91864f2`) |
| Выделение функции или метода | Extract Method для PHP — только Intelephense Premium | В меню Refactor для PHP нет Extract Method (только Rename, Move, Copy, Safely Delete — М03). Метод `lateFine()` выделен вручную: перенесены строки 202–208 (вычисление штрафа), переданы только `due_at` и `category`, видимость `private static`, возвращается число; проверено `git diff`, `php -l`, повторным прогоном C01–C08 (коммит `6bb8329`) |
| Форматирование | Format Document (Shift+Alt+F, форматтер Intelephense) — в М09 не запускался, чтобы не трогать весь файл; новые строки набраны с отступом 4 пробела, PHPCS — 0 замечаний | Source → Format (Alt+Shift+F) — не запускался по той же причине; отступы проверены `git diff --check` и `git grep -P "^\t"` |
| Диагностика | Problems: 0 ошибок, 0 предупреждений, 20 информационных подсказок Intelephense (в основном «у параметра нет типа», в том числе у `$dueAt`, `$category`) — [vscode-returnbook.png](vscode-returnbook.png) | Полоса ошибок справа без красных отметок, подсветка синтаксиса и курсив статических вызовов корректны — [netbeans-latefine.png](netbeans-latefine.png) |

| Объект и метрика | До | После | Изменение и объяснение |
|---|---|---|---|
| Целевая функция, CCN | 6 | 4 | −2: условие `if ($today > $due && $category == 'student')` (ветвление + `&&`) перешло в `lateFine()` |
| Целевая функция, NLOC | 30 (длина 33 строки, 189–221) | 24 (длина 27 строк, 189–215) | −6: семь строк расчёта заменены одной строкой вызова |
| Новые методы, CCN и NLOC каждого | — | `LoanService::lateFine`: CCN 3, NLOC 10 (строки 221–230) | новый приватный метод; в сумме 24 + 10 = 34 NLOC против 30 — добавились объявление, скобки и `return 0` |
| Целевой файл, EOL и отступы | LF (`i/lf w/lf`), 4 пробела | LF (`i/lf w/lf`), 4 пробела, табуляций и пробелов в конце строк нет | без изменений; `git diff --check fbaffb0` — пусто |
| Целевой файл, диагностика линтера и синтаксиса | PHPCS 0, `php -l` без ошибок | PHPCS 0, `php -l` без ошибок | без изменений. Файл: NLOC 212 → 216, функций 11 → 12, средний CCN 3,7 → 3,5. Проект («М09 после»): PHP 1414 → 1418 строк, функций 98 → 99, средний CCN 2,28 → 2,26 |

- [Сырые замеры проекта](metrics-raw.md) (метки «М09 до» и «М09 после») · вывод анализатора для целевого файла — [lizard-before.txt](lizard-before.txt), [lizard-after.txt](lizard-after.txt)
- Ссылки на коммиты и доказательства работы в обеих IDE — `07d8a7d` (исходные замеры), `91864f2` (переименование), `6bb8329` (выделение метода); снимки [vscode-returnbook.png](vscode-returnbook.png) (VS Code: строка навигации, вызов `lateFine`, Problems 0/0/20) и [netbeans-latefine.png](netbeans-latefine.png) (NetBeans: метод `lateFine` на строке 221); регрессия — [regression.md](regression.md)
- Вывод о свойствах кода и выборе среды для каждой операции — `returnBook()` стал короче (NLOC 30 → 24) и проще (CCN 6 → 4): в нём остались только чтение, ранние выходы, записи и ответ, а правило штрафа читается отдельно в `lateFine()` с понятным контрактом «срок и категория → сумма». Поведение не изменилось — C01–C08 совпали полностью. Для переименования лучше NetBeans (Refactor → Rename бесплатно), для навигации и диагностики PHP — VS Code с Intelephense (Problems, переход к определению). Выделение метода для PHP бесплатно не умеет ни одна из двух сред: его приходится делать вручную и подтверждать регрессией.
