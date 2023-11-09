-- add plugin entry in the plugin table
UPDATE `plugins`
SET version = 'v2.2.0'
WHERE `name` = 'qualtrics';

-- add field redirect_at_end to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'redirect_at_end', get_field_type_id('text'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('redirect_at_end'), null, 'Redirect to this url at the end of the survey');
