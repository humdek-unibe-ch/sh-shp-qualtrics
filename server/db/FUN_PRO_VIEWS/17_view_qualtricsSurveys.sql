DROP VIEW IF EXISTS view_qualtricsSurveys;
CREATE VIEW view_qualtricsSurveys
AS
SELECT s.*, typ.lookup_value AS survey_type, typ.lookup_code AS survey_type_code, p.`name` AS project_name, p.api_library_id, p.api_mailing_group_id
FROM qualtricsSurveys s 
INNER JOIN qualtricsProjects p ON (s.id_qualtricsProjects = p.id)
INNER JOIN lookups typ ON (typ.id = s.id_qualtricsSurveyTypes);
