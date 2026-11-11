# Как работать с проектом

Инструкция для нового участника: как развернуть «Библиотеку КАИТ» с нуля, какие настройки редактора
уже лежат в проекте и как проверять стиль кода перед коммитом.

## 1. Что установить

| Программа | Версия | Зачем |
|---|---|---|
| XAMPP (Apache + PHP + MySQL/MariaDB) | PHP 8.2, MariaDB 10.4 или новее | веб-сервер, PHP и база данных |
| Git | 2.40 или новее | клонирование и коммиты |
| Visual Studio Code | 1.90 или новее | основной редактор (расширения он предложит сам, см. п. 3) |
| PHP_CodeSniffer (`phpcs.phar`, `phpcbf.phar`) | 4.0 | проверка и автоисправление стиля PSR-12. Файлы лежат в репозитории практики `PP03/tools/php/` или скачиваются со страницы [PHP_CodeSniffer Releases](https://github.com/PHPCSStandards/PHP_CodeSniffer/releases) |
| Apache NetBeans (по желанию) | 31 | вторая IDE, настройки форматирования — в `nbproject/project.properties` |

Команды ниже даны для Windows и XAMPP в папке `C:\xampp`. PHP отдельно ставить не нужно — он есть в XAMPP
(`C:\xampp\php\php.exe`).

## 2. Как запустить проект

1. Запустите **XAMPP Control Panel** и нажмите **Start** у Apache и MySQL.
2. Склонируйте репозиторий **в папку `htdocs`** и перейдите на нужную ветку (Apache показывает только
   то, что лежит в `htdocs`):
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/Vova-star5/kait-library.git
   cd kait-library
   git switch main
   ```
3. Создайте базу и загрузите таблицы и тестовые данные (команды выполнять из папки проекта):
   ```
   C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE kait_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   C:\xampp\mysql\bin\mysql -u root --default-character-set=utf8mb4 kait_library -e "source database/schema.sql"
   C:\xampp\mysql\bin\mysql -u root --default-character-set=utf8mb4 kait_library -e "source database/seed.sql"
   ```
4. Файл `.env` для XAMPP **не нужен**, если папка называется `kait-library`, а база — `kait_library`.
   Если папка или база называются иначе (например, вторая копия для проверки), скопируйте `.env.example`
   в `.env` и поменяйте две строки:
   ```
   APP_URL=http://localhost/<имя папки>/public
   DB_NAME=<имя базы>
   ```
5. Откройте `http://localhost/kait-library/public/` и войдите. Пароль у всех тестовых пользователей —
   `password`:

   | Логин | Роль |
   |---|---|
   | `admin@kait20.ru` | администратор |
   | `librarian@kait20.ru` | библиотекарь |
   | `ivanov@student.kait20.ru` | студент (есть просроченная книга) |
   | `volkova@student.kait20.ru` | студент без долгов |
   | `kuznetsov@kait20.ru` | преподаватель |

## 3. Настройки редактора

- **`.editorconfig`** — общие правила для всех редакторов: UTF-8 без BOM, окончания строк LF, отступ
  4 пробела, пустая строка в конце файла, без пробелов в конце строк. VS Code читает его через расширение
  EditorConfig, PhpStorm — сам, NetBeans — не умеет (для него см. ниже).
- **`.gitattributes`** — git хранит и извлекает все текстовые файлы с LF, даже если у вас
  `core.autocrlf=true`. Ничего делать не нужно.
- **`.vscode/settings.json`** — настройки VS Code для этого проекта: те же отступы и LF, форматтер PHP —
  Intelephense в стиле PSR-12, форматирование при сохранении выключено.
- **`.vscode/extensions.json`** — список нужных расширений. При первом открытии папки VS Code покажет
  внизу справа «Установить рекомендуемые расширения?» — нажмите **Установить**. Если окно не появилось:
  панель «Расширения» (Ctrl+Shift+X) → фильтр `@recommended`.
- **NetBeans**: откройте папку как проект (File → Open Project). Настройки форматирования проекта
  (4 пробела, скобки `{` у классов и методов на новой строке) подхватятся из `nbproject/project.properties`
  — проверить: Properties проекта → Formatting → «Use project specific options».
- Форматировать файл: VS Code — **Shift+Alt+F**, NetBeans — **Alt+Shift+F**. Перед коммитом всё равно
  запускайте линтер (п. 4): именно он решает, правильно ли оформлен код.

## 4. Проверка стиля кода

Правила лежат в `phpcs.xml` (PSR-12 с исключениями для шаблонов). Команды выполнять из корня проекта,
`<PP03>` — путь к папке с `phpcs.phar`:

```
C:\xampp\php\php.exe <PP03>\tools\php\phpcs.phar --report=summary
C:\xampp\php\php.exe <PP03>\tools\php\phpcbf.phar
```

Первая команда проверяет и выводит число ошибок по файлам, вторая исправляет всё, что можно исправить
автоматически. Подробно по одному файлу: `C:\xampp\php\php.exe <PP03>\tools\php\phpcs.phar src\LoanService.php`.

Перед каждым коммитом: `phpcs` должен показать 0 ошибок. Если `phpcbf` что-то исправил — проверьте
`git diff` и откройте сайт: логика не должна измениться.

## 5. Ветки и коммиты

- Ветки: от `main`, по смыслу задачи — `practice/m04`, `fix/loan-limit`, `feature/reviews-export`.
- Сообщения коммитов: тип и коротко по-русски, что сделано — `style: …` (оформление), `refactor: …`
  (переделка без изменения поведения), `fix: …` (исправление ошибки), `feat: …` (новая возможность),
  `docs: …` (документация), `chore: …` (настройки и служебные файлы).
- Изменения форматирования и изменения логики — **разными коммитами**: сначала `style:`, потом остальное.
  Так в Pull Request видно, где менялся смысл кода.
- В `main` — только через Pull Request.

## 6. Чего не коммитить

- `.env` — пароли и личные адреса (в репозитории только `.env.example`).
- `public/uploads/*` — загруженные обложки.
- `nbproject/private/`, `.idea/workspace.xml`, `*.log` — личные настройки IDE: пути к программам на вашем
  компьютере, список открытых файлов.
- Личный профиль VS Code, ключи доступа к сервисам, дампы базы с реальными данными.
