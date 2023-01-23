-- add plugin entry in the plugin table
UPDATE `plugins`
SET version = 'v1.3.0'
WHERE `name` = 'qualtrics';

SET @id_modules_page = (SELECT id FROM pages WHERE keyword = 'sh_modules');

UPDATE pages
SET parent = @id_modules_page, nav_position = 95
WHERE keyword = 'moduleQualtrics';

-- set DB version
UPDATE version
SET version = 'v5.11.0';

DELIMITER //
DROP PROCEDURE IF EXISTS drop_table_column //
CREATE PROCEDURE drop_table_column(param_table VARCHAR(100), param_column VARCHAR(100))
BEGIN	
    SET @sqlstmt = (SELECT IF(
		(
			SELECT COUNT(*) 
			FROM information_schema.COLUMNS
			WHERE `table_schema` = DATABASE()
			AND `table_name` = param_table
			AND `COLUMN_NAME` = param_column 
		) = 0,
        "SELECT 'Column does not exist'",
        CONCAT('ALTER TABLE ', param_table, ' DROP COLUMN ', param_column, ' ;')
    ));
	PREPARE st FROM @sqlstmt;
	EXECUTE st;
	DEALLOCATE PREPARE st;	
END

//

DELIMITER ;

CALL drop_table_column('qualtricsProjects', 'qualtrics_api');
CALL drop_table_column('qualtricsSurveys', 'participant_variable');

DROP VIEW IF EXISTS view_qualtricsSurveys;
CREATE VIEW view_qualtricsSurveys
AS
SELECT s.*, typ.lookup_value as survey_type, typ.lookup_code as survey_type_code
FROM qualtricsSurveys s 
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

