-- add plugin entry in the plugin table
UPDATE `plugins`
SET version = 'v1.3.0'
WHERE `name` = 'qualtrics';

SET @id_modules_page = (SELECT id FROM pages WHERE keyword = 'sh_modules');

UPDATE pages
SET parent = @id_modules_page, nav_position = 95
WHERE keyword = 'moduleQualtrics';

