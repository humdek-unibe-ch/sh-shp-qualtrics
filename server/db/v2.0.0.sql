-- add plugin entry in the plugin table
UPDATE `plugins`
SET version = 'v2.0.0'
WHERE `name` = 'qualtrics';

SET @id_modules_page = (SELECT id FROM pages WHERE keyword = 'sh_modules');

UPDATE pages
SET parent = @id_modules_page, nav_position = 95
WHERE keyword = 'moduleQualtrics';

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

-- remove the option to insert action in the legacy quealtrics method
UPDATE pages
SET url = '/admin/qualtrics/action/[select|update|delete:mode]?/[i:aid]?'
WHERE keyword = 'moduleQualtricsAction';

UPDATE pages
SET url = '/admin/qualtrics/sync/[i:sid]?'
WHERE keyword = 'moduleQualtricsSync';

UPDATE pages
SET url = '/admin/qualtrics/action/[select|update|insert|delete:mode]?/[i:aid]?'
WHERE keyword = 'moduleQualtricsProjectAction';

UPDATE pages
SET keyword = 'moduleQualtricsAction'
WHERE keyword = 'moduleQualtricsProjectAction';

UPDATE styles_fields
SET hidden = 1
WHERE id_fields = get_field_id('jquery_builder_json') AND id_styles = get_style_id('qualtricsSurvey');

-- add field `extra-params`
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'extra_params', get_field_type_id('text'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) 
VALUES (get_style_id('qualtricsSurvey'), get_field_id('extra_params'), '', 'Add extra paramters to the survey. The format is: `var1=value1&var2=value2`. The values can be dynamically loaded with `dataConfig`');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) 
VALUES (get_style_id('qualtricsSurvey'), get_field_id('data_config'), '', 
'In this ***JSON*** field we can configure a data retrieve params from the DB, either `static` or `dynamic` data. Example: 
 ```
 [
	{
		"type": "static|dynamic",
		"table": "table_name | #url_param1",
        "retrieve": "first | last | all",
		"fields": [
			{
				"field_name": "name | #url_param2",
				"field_holder": "@field_1",
				"not_found_text": "my field was not found"				
			}
		]
	}
]
```
If the page supports parameters, then the parameter can be accessed with `#` and the name of the paramer. Example `#url_param_name`. 

In order to inlcude the retrieved data in the input `value`, include the `field_holder` that wa defined in the markdown text.

We can access multiple tables by adding another element to the array. The retrieve data from the column can be: `first` entry, `last` entry or `all` entries (concatenated with ;);

`It is used for prefil of the default value`');

DELIMITER //
DROP PROCEDURE IF EXISTS qualtrics_rework //
CREATE PROCEDURE qualtrics_rework()
BEGIN
	SET @actions_exists := (SELECT COUNT(*) FROM information_schema.`tables`
	WHERE table_schema = DATABASE()
	AND `table_name` = 'qualtricsActions');
    IF @actions_exists > 0 THEN		
    
		CALL add_table_column('qualtricsSurveys', 'id_qualtricsProjects', 'INT(10) UNSIGNED ZEROFILL NOT NULL');
		UPDATE qualtricsSurveys
		SET id_qualtricsProjects = (SELECT project_id FROM view_qualtricsActions WHERE survey_id = qualtricsSurveys.id LIMIT 0, 1)
		WHERE id_qualtricsProjects = 0;
        
        -- unused surveys delete them
        DELETE FROM qualtricsSurveys
        WHERE id_qualtricsProjects = 0;
        
        CALL drop_table_column('qualtricsProjects', 'qualtrics_api');
		CALL drop_table_column('qualtricsSurveys', 'participant_variable');

		CALL add_table_column('qualtricsSurveys', 'id_users_last_sync', 'INT(10) UNSIGNED ZEROFILL NULL');
		CALL add_foreign_key('qualtricsSurveys', 'qualtricsSurveys_fk_id_users_last_sync', 'id_users_last_sync', 'users (id)');

		CALL add_table_column('qualtricsSurveys', 'save_data', "INT(11) DEFAULT '0'");
		CALL add_foreign_key('qualtricsSurveys', 'qualtricsSurveys_fk_id_qualtricsProjects', 'id_qualtricsProjects', 'qualtricsProjects (id)');

		CALL drop_foreign_key('qualtricsActions', 'qualtricsActions_fk_id_qualtricsProjects');
		CALL drop_table_column('qualtricsActions', 'id_qualtricsProjects');
    
		DROP VIEW IF EXISTS view_qualtricsActions;
		CREATE VIEW view_qualtricsActions
		AS
		SELECT st.id as id, st.name as action_name, s.id_qualtricsProjects as project_id, p.name as project_name, p.api_mailing_group_id,
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
		INNER JOIN qualtricsSurveys s ON (st.id_qualtricsSurveys = s.id)
		INNER JOIN qualtricsProjects p ON (s.id_qualtricsProjects = p.id)
		INNER JOIN lookups typ ON (typ.id = s.id_qualtricsSurveyTypes)
		INNER JOIN lookups trig ON (trig.id = st.id_qualtricsProjectActionTriggerTypes)
		INNER JOIN lookups action_type ON (action_type.id = st.id_qualtricsActionScheduleTypes)
		LEFT JOIN qualtricsSurveys s_reminder ON (st.id_qualtricsSurveys_reminder = s_reminder.id)
		LEFT JOIN qualtricsActions_groups sg on (sg.id_qualtricsActions = st.id)
		LEFT JOIN `groups` g on (sg.id_groups = g.id)
		LEFT JOIN qualtricsActions_functions f on (f.id_qualtricsActions = st.id)
		LEFT JOIN lookups l on (f.id_lookups = l.id)
		GROUP BY st.id, st.name, s.id_qualtricsProjects, p.name,
		st.id_qualtricsSurveys, s.name, s.id_qualtricsSurveyTypes, typ.lookup_value, 
		id_qualtricsProjectActionTriggerTypes, trig.lookup_value;
    
		BEGIN
			SET @existing_actions := (SELECT COUNT(*) FROM qualtricsActions);
			IF @existing_actions = 0 THEN
					DROP TABLE IF EXISTS qualtricsActions_functions;
                    DROP TABLE IF EXISTS qualtricsActions_groups;
                    DROP TABLE IF EXISTS qualtricsReminders;
                    DROP TABLE IF EXISTS scheduledJobs_qualtricsActions;
                    DROP TABLE IF EXISTS qualtricsActions;
                    DROP VIEW IF EXISTS view_qualtricsreminders;
                    DROP VIEW IF EXISTS view_qualtricsactions;     
                    DELETE FROM pages
                    WHERE keyword = 'moduleQualtricsAction';
			END IF;
        END;
    END IF;
END

//
DELIMITER ;
CALL qualtrics_rework();
DROP PROCEDURE IF EXISTS qualtrics_rework;

DROP VIEW IF EXISTS view_qualtricsSurveys;
CREATE VIEW view_qualtricsSurveys
AS
SELECT s.*, typ.lookup_value AS survey_type, typ.lookup_code AS survey_type_code, p.`name` AS project_name, p.api_library_id, p.api_mailing_group_id
FROM qualtricsSurveys s 
INNER JOIN qualtricsProjects p ON (s.id_qualtricsProjects = p.id)
INNER JOIN lookups typ ON (typ.id = s.id_qualtricsSurveyTypes);