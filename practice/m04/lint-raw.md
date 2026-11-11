# Вывод линтера PHP_CodeSniffer

Версия: PHP_CodeSniffer version 4.0.4 (stable) by Squiz and PHPCSStandards. Правила — `phpcs.xml` в корне проекта. Запуск 11.11.2026 из корня проекта:

```
C:\xampp\php\php.exe ..\PP03\tools\php\phpcs.phar --report=summary
C:\xampp\php\php.exe ..\PP03\tools\php\phpcs.phar --report=source
C:\xampp\php\php.exe ..\PP03\tools\php\phpcs.phar src\ReportService.php
C:\xampp\php\php.exe ..\PP03\tools\php\phpcbf.phar
C:\xampp\php\php.exe ..\PP03\tools\php\phpcs.phar --report=full
```

## Проверка до автоисправления (`phpcs --report=summary`)

```
PHP CODE SNIFFER REPORT SUMMARY
--------------------------------------------------------------------------------
FILE                                                            ERRORS  WARNINGS
--------------------------------------------------------------------------------
config\config.php                                               1       0
public\api.php                                                  3       0
public\index.php                                                2       0
src\helpers.php                                                 3       0
--------------------------------------------------------------------------------
A TOTAL OF 9 ERRORS AND 0 WARNINGS WERE FOUND IN 4 FILES
--------------------------------------------------------------------------------
PHPCBF CAN FIX 9 OF THESE SNIFF VIOLATIONS AUTOMATICALLY
--------------------------------------------------------------------------------
```

## Какие правила нарушались (`phpcs --report=source`)

```
PHP CODE SNIFFER VIOLATION SOURCE SUMMARY
----------------------------------------------------------------------
    SOURCE                                                       COUNT
----------------------------------------------------------------------
[x] Generic.ControlStructures.InlineControlStructure.NotAllowed  3
[x] PSR12.Files.FileHeader.SpacingAfterTagBlock                  3
[x] PSR12.Files.FileHeader.SpacingAfterDocblockBlock             2
[x] Squiz.WhiteSpace.ControlStructureSpacing.SpacingAfterOpen    1
----------------------------------------------------------------------
A TOTAL OF 9 SNIFF VIOLATIONS WERE FOUND IN 4 SOURCES
----------------------------------------------------------------------
PHPCBF CAN FIX THE 4 MARKED SOURCES AUTOMATICALLY (9 VIOLATIONS IN TOTAL)
----------------------------------------------------------------------
```

## Автоисправление (`phpcbf`)

```
PHPCBF RESULT SUMMARY
--------------------------------------------------------------------------------
FILE                                                            FIXED  REMAINING
--------------------------------------------------------------------------------
src\helpers.php                                                 3       0
public\api.php                                                  3       0
public\index.php                                                2       0
config\config.php                                               1       0
--------------------------------------------------------------------------------
A TOTAL OF 9 ERRORS WERE FIXED IN 4 FILES
--------------------------------------------------------------------------------
```

## Отчёт по одному файлу: `src/ReportService.php`

Вывод пустой — замечаний нет: файл приведён к стилю PSR-12 ещё в модуле 3 (отступы пробелами).

## Проверка после автоисправления

Повторный запуск `phpcs --report=full` и `phpcs --report=summary` — вывод пустой, **0 ошибок, 0 предупреждений**.
