-- add plugin entry in the plugin table
UPDATE `plugins`
SET version = 'v1.3.0'
WHERE `name` = 'qualtrics';

SET @id_modules_page = (SELECT id FROM pages WHERE keyword = 'sh_modules');

UPDATE pages
SET parent = @id_modules_page, nav_position = 95
WHERE keyword = 'moduleQualtrics';

CALL drop_table_column('qualtricsProjects', 'qualtrics_api');
CALL drop_table_column('qualtricsSurveys', 'participant_variable');

-- add Qualtrics API key to the profile page - make the key personal
INSERT IGNORE INTO `sections` (`id_styles`, `name`, `owner`) VALUES (0000000012, 'profile-qualtrics-settings-card', NULL);
INSERT IGNORE INTO `sections_fields_translation` (`id_sections`, `id_fields`, `id_languages`, `id_genders`, `content`) VALUES
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0000000022, 0000000002, 0000000001, 'Qualtrics Vorlieben'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0000000022, 0000000003, 0000000001, 'Qualtrics Preferences'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0000000023, 0000000001, 0000000001, 'mb-3 mt-3'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0000000028, 0000000001, 0000000001, 'light'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0000000046, 0000000001, 0000000001, '1'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0000000047, 0000000001, 0000000001, '0'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0000000048, 0000000001, 0000000001, ''),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0000000091, 0000000001, 0000000001, '{"and":[{"==":[true,"$admin"]}]}'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 00000000180, 0000000001, 0000000001, '{"condition":"AND","rules":[{"id":"user_group","field":"user_group","type":"string","input":"select","operator":"in","value":["admin"]}],"valid":true}');

INSERT IGNORE INTO `sections` (`id_styles`, `name`, `owner`) VALUES (get_style_id('formUserInputRecord'), 'profile-qualtrics-settings-formUserInputRecord', NULL);
INSERT IGNORE INTO `sections_fields_translation` (`id_sections`, `id_fields`, `id_languages`, `id_genders`, `content`) VALUES
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0000000008, 0000000002, 0000000001, 'Speichern'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0000000008, 0000000003, 0000000001, 'Save'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0000000023, 0000000001, 0000000001, ''),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0000000028, 0000000001, 0000000001, 'primary'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0000000057, 0000000001, 0000000001, 'qualtrics-settings'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0000000087, 0000000001, 0000000001, '0'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0000000035, 0000000002, 0000000001, 'Die Einstellungen für Qualtrics wurden erfolgreich gespeichert'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), get_field_id('internal'), 0000000001, 0000000001, '1'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0000000035, 0000000003, 0000000001, 'The Qualtrics settings were successfully saved');

INSERT IGNORE INTO `sections` (`id_styles`, `name`, `owner`) VALUES (0000000016, 'profile-qualtrics-settings-input', NULL);
INSERT IGNORE INTO `sections_fields_translation` (`id_sections`, `id_fields`, `id_languages`, `id_genders`, `content`) VALUES
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000008, 0000000002, 0000000001, 'Qualtrics API'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000008, 0000000003, 0000000001, 'Qualtrics API'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000023, 0000000001, 0000000001, ''),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000054, 0000000001, 0000000001, 'text'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000055, 0000000002, 0000000001, ''),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000055, 0000000003, 0000000001, ''),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000056, 0000000001, 0000000001, '0'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000057, 0000000001, 0000000001, 'qualtrics-api'),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0000000058, 0000000001, 0000000001, '');

INSERT IGNORE INTO `sections_hierarchy` (`parent`, `child`, `position`) VALUES
((SELECT id FROM sections WHERE name = "profile-col1-div"), (SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), 0),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-card'), (SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), 0),
((SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-formUserInputRecord'), (SELECT id FROM sections WHERE `name` = 'profile-qualtrics-settings-input'), 0);

CALL add_table_column('qualtricsSurveys', 'id_qualtricsProjects', 'INT(10) UNSIGNED ZEROFILL NOT NULL');

UPDATE qualtricsSurveys
SET id_qualtricsProjects = (SELECT project_id FROM view_qualtricsActions WHERE survey_id = qualtricsSurveys.id LIMIT 0, 1);

CALL add_foreign_key('qualtricsSurveys', 'qualtricsSurveys_fk_id_qualtricsProjects', 'id_qualtricsProjects', 'qualtricsProjects (id)');

UPDATE pages
SET url = '/admin/qualtrics/sync/[i:sid]?'
WHERE keyword = 'moduleQualtricsSync';






DROP VIEW IF EXISTS view_qualtricsSurveys;
CREATE VIEW view_qualtricsSurveys
AS
SELECT s.*, typ.lookup_value AS survey_type, typ.lookup_code AS survey_type_code, p.`name` AS project_name, p.api_library_id, p.api_mailing_group_id
FROM qualtricsSurveys s 
INNER JOIN qualtricsProjects p ON (s.id_qualtricsProjects = p.id)
INNER JOIN lookups typ ON (typ.id = s.id_qualtricsSurveyTypes);


DROP VIEW IF EXISTS view_qualtricsActions;
CREATE VIEW view_qualtricsActions
AS
SELECT st.id as id, st.name as action_name, st.id_qualtricsProjects as project_id, p.name as project_name, p.api_mailing_group_id,
st.id_qualtricsSurveys as survey_id, s.qualtrics_survey_id, s.name as survey_name, s.id_qualtricsSurveyTypes, s.group_variable, typ.lookup_value as survey_type, typ.lookup_code as survey_type_code,
id_qualtricsProjectActionTriggerTypes, trig.lookup_value as trigger_type, trig.lookup_code as trigger_type_code,
GROUP_CONCAT(DISTINCT g.name SEPARATOR '; ') AS `groups`, 
GROUP_CONCAT(DISTINCT g.id*1 SEPARATOR ', ') AS id_groups, 
GROUP_CONCAT(DISTINCT l.lookup_value SEPARATOR '; ') AS functions,
GROUP_CONCAT(DISTINCT l.lookup_code SEPARATOR ';') AS functions_code,
GROUP_CONCAT(DISTINCT l.id SEPARATOR '; ') AS id_functions,
schedule_info, st.id_qualtricsActionScheduleTypes, action_type.lookup_code as action_schedule_type_code, action_type.lookup_value as action_schedule_type, id_qualtricsSurveys_reminder, 
CASE 
	WHEN action_type.lookup_value = 'Reminder' THEN s_reminder.name 
    ELSE NULL
END as survey_reminder_name, st.id_qualtricsActions
FROM qualtricsActions st 
INNER JOIN qualtricsProjects p ON (st.id_qualtricsProjects = p.id)
INNER JOIN qualtricsSurveys s ON (st.id_qualtricsSurveys = s.id)
INNER JOIN lookups typ ON (typ.id = s.id_qualtricsSurveyTypes)
INNER JOIN lookups trig ON (trig.id = st.id_qualtricsProjectActionTriggerTypes)
INNER JOIN lookups action_type ON (action_type.id = st.id_qualtricsActionScheduleTypes)
LEFT JOIN qualtricsSurveys s_reminder ON (st.id_qualtricsSurveys_reminder = s_reminder.id)
LEFT JOIN qualtricsActions_groups sg on (sg.id_qualtricsActions = st.id)
LEFT JOIN `groups` g on (sg.id_groups = g.id)
LEFT JOIN qualtricsActions_functions f on (f.id_qualtricsActions = st.id)
LEFT JOIN lookups l on (f.id_lookups = l.id)
GROUP BY st.id, st.name, st.id_qualtricsProjects, p.name,
st.id_qualtricsSurveys, s.name, s.id_qualtricsSurveyTypes, typ.lookup_value, 
id_qualtricsProjectActionTriggerTypes, trig.lookup_value;

