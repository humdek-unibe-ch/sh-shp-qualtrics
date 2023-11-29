-- add plugin entry in the plugin table
UPDATE `plugins`
SET version = 'v2.3.0'
WHERE `name` = 'qualtrics';

-- delete field `jquery_builder_json`
DELETE FROM `fields`
WHERE `name` = 'jquery_builder_json';