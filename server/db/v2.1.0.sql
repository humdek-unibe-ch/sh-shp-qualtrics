-- add plugin entry in the plugin table
UPDATE `plugins`
SET version = 'v2.1.0'
WHERE `name` = 'qualtrics';

-- field save_labels_data for the data save
CALL add_table_column('qualtricsSurveys', 'save_labels_data', "INT(11) DEFAULT '0'");

-- for already existing save the labels as it was the default in the past
UPDATE qualtricsSurveys
SET save_labels_data = 1
WHERE save_data = 1;

DROP VIEW IF EXISTS view_qualtricsSurveys;
CREATE VIEW view_qualtricsSurveys
AS
SELECT s.*, typ.lookup_value AS survey_type, typ.lookup_code AS survey_type_code, p.`name` AS project_name, p.api_library_id, p.api_mailing_group_id
FROM qualtricsSurveys s 
INNER JOIN qualtricsProjects p ON (s.id_qualtricsProjects = p.id)
INNER JOIN lookups typ ON (typ.id = s.id_qualtricsSurveyTypes);
