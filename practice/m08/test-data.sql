-- Учебные данные для проверок C01–C08 (М08, повтор в М09).
-- Загружать после database/schema.sql и database/seed.sql (или поверх исходного снимка учебной БД).
-- D — дата приложения (PHP, Europe/Moscow). Сроки задаются относительно CURDATE() MySQL,
-- поэтому загружайте данные и выполняйте проверки в один день и не между 00:00 и 01:00 по Москве,
-- если часовой пояс MySQL отличается от московского.
SET @D = CURDATE();

DELETE FROM loans WHERE id BETWEEN 101 AND 107;

INSERT INTO loans (id, copy_id, user_id, librarian_id, issued_at, due_at, returned_at, fine) VALUES
(101, 2, 4, 2, @D - INTERVAL 10 DAY, @D + INTERVAL 4 DAY, NULL, 0),                    -- C01 студент, срок после D
(102, 3, 6, 2, @D - INTERVAL 14 DAY, @D, NULL, 0),                                     -- C02 студент, срок = D
(103, 5, 4, 2, @D - INTERVAL 15 DAY, @D - INTERVAL 1 DAY, NULL, 0),                    -- C03 студент, срок D − 1
(104, 6, 6, 2, @D - INTERVAL 20 DAY, @D - INTERVAL 6 DAY, @D - INTERVAL 7 DAY, 0),     -- C04 уже возвращена
(105, 8, 5, 2, @D - INTERVAL 33 DAY, @D - INTERVAL 3 DAY, NULL, 0),                    -- C05 преподаватель, срок D − 3
(107, 9, 4, 2, @D - INTERVAL 24 DAY, @D - INTERVAL 10 DAY, NULL, 0);                   -- C07 студент, срок D − 10

UPDATE copies SET status = 'issued' WHERE id IN (2, 3, 5, 8, 9);
UPDATE copies SET status = 'available' WHERE id = 6;
