# М09 · Изменение характеристик кода в двух IDE

- Сценарий М08 и ссылка на протокол — «Возврат книги», [algorithm.md](../m08/algorithm.md), [characterization.md](../m08/characterization.md) (C01–C08), PR М08 — [#9](https://github.com/Vova-star5/kait-library/pull/9)
- Файл, исходная функция и базовый SHA — `src/LoanService.php`, `LoanService::returnBook()` (строки 189–221); базовый SHA `fbaffb09caec7cb4cb478b653c884633204e5800` (main после вливания М08; код совпадает с исследованным в М08 SHA `2fbcb70`)
- Итоговый SHA кода, версии PHP или среды выполнения, анализатора и линтера — TODO
- IDE 1 и IDE 2, версии и расширения — TODO
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
| Навигация к объявлению | TODO | TODO |
| Переименование | TODO | TODO |
| Выделение функции или метода | TODO | TODO |
| Форматирование | TODO | TODO |
| Диагностика | TODO | TODO |

| Объект и метрика | До | После | Изменение и объяснение |
|---|---|---|---|
| Целевая функция, CCN | 6 | TODO | TODO |
| Целевая функция, NLOC | 30 | TODO | TODO |
| Новые методы, CCN и NLOC каждого | — | TODO | TODO |
| Целевой файл, EOL и отступы | LF, 4 пробела | TODO | TODO |
| Целевой файл, диагностика линтера и синтаксиса | PHPCS 0, `php -l` без ошибок | TODO | TODO |

- [Сырые замеры проекта](metrics-raw.md) · вывод анализатора для целевого файла — [lizard-before.txt](lizard-before.txt), TODO
- Ссылки на коммиты и доказательства работы в обеих IDE — TODO
- Вывод о свойствах кода и выборе среды для каждой операции — TODO
