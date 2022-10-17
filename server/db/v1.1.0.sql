-- add plugin entry in the plugin table
UPDATE `plugins`
SET version = 'v1.1.0'
WHERE `name` = 'qualtrics';

-- register hook  for select-qualtrics-survey field
INSERT IGNORE INTO `hooks` (`id_hookTypes`, `name`, `description`, `class`, `function`, `exec_class`, `exec_function`) VALUES ((SELECT id FROM lookups WHERE lookup_code = 'hook_overwrite_return' LIMIT 0,1), 'clear-qualtrics-responses', 'Clear Qualtrics responses', 'UserModel', 'clean_user_data', 'QualtricsHooks', 'clear_qualtrics_responses');
