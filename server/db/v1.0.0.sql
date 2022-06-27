-- add plugin entry in the plugin table
INSERT IGNORE INTO plugins (name, version) 
VALUES ('qualtrics', 'v1.0.0');

--
-- Table structure for table `qualtricsProjects`
--
CREATE TABLE IF NOT EXISTS `qualtricsProjects` (
  `id` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `qualtrics_api` varchar(100) DEFAULT NULL,
  `api_library_id` varchar(100) DEFAULT NULL,
  `api_mailing_group_id` varchar(100) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `qualtricsSurveys`
--
CREATE TABLE IF NOT EXISTS `qualtricsSurveys` (
  `id` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `qualtrics_survey_id` varchar(100) DEFAULT NULL,
  `id_qualtricsSurveyTypes` int(10) UNSIGNED ZEROFILL NOT NULL,
  `participant_variable` varchar(100) DEFAULT NULL,
  `group_variable` int(11) DEFAULT '0',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `config` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qualtrics_survey_id` (`qualtrics_survey_id`),
  CONSTRAINT `qualtricsSurveys_fk_id_qualtricsSurveyTypes` FOREIGN KEY (`id_qualtricsSurveyTypes`) REFERENCES `lookups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `qualtricsActions`
--
CREATE TABLE IF NOT EXISTS `qualtricsActions` (
  `id` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `id_qualtricsProjects` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_qualtricsSurveys` int(10) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(200) NOT NULL,
  `id_qualtricsProjectActionTriggerTypes` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_qualtricsActionScheduleTypes` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_qualtricsSurveys_reminder` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `schedule_info` text,
  `id_qualtricsActions` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `qualtricsActions_fk_id_lookups_qualtricsProjectActionTriggerType` FOREIGN KEY (`id_qualtricsProjectActionTriggerTypes`) REFERENCES `lookups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qualtricsActions_fk_id_qualtricsActionScheduleTypes` FOREIGN KEY (`id_qualtricsActionScheduleTypes`) REFERENCES `lookups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qualtricsActions_fk_id_qualtricsProjects` FOREIGN KEY (`id_qualtricsProjects`) REFERENCES `qualtricsProjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qualtricsActions_fk_id_qualtricsSurveys` FOREIGN KEY (`id_qualtricsSurveys`) REFERENCES `qualtricsSurveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qualtricsActions_fk_id_qualtricsSurveys_reminder` FOREIGN KEY (`id_qualtricsSurveys_reminder`) REFERENCES `qualtricsSurveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `qualtricsActions_functions`
--
CREATE TABLE IF NOT EXISTS `qualtricsActions_functions` (
  `id_qualtricsActions` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_lookups` int(10) UNSIGNED ZEROFILL NOT NULL,
  PRIMARY KEY (`id_qualtricsActions`,`id_lookups`),
  CONSTRAINT `qualtricsActions_functions_fk_id_lookups` FOREIGN KEY (`id_lookups`) REFERENCES `lookups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qualtricsActions_functions_fk_id_qualtricsActions` FOREIGN KEY (`id_qualtricsActions`) REFERENCES `qualtricsActions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `qualtricsActions_groups`
--
CREATE TABLE IF NOT EXISTS `qualtricsActions_groups` (
  `id_qualtricsActions` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_groups` int(10) UNSIGNED ZEROFILL NOT NULL,
  PRIMARY KEY (`id_qualtricsActions`,`id_groups`),
  CONSTRAINT `qualtricsActions_groups_fk_id_groups` FOREIGN KEY (`id_groups`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qualtricsActions_groups_fk_id_qualtricsActions` FOREIGN KEY (`id_qualtricsActions`) REFERENCES `qualtricsActions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `qualtricsReminders`
--
CREATE TABLE IF NOT EXISTS `qualtricsReminders` (
  `id_qualtricsSurveys` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_users` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_scheduledJobs` int(10) UNSIGNED ZEROFILL NOT NULL,
  PRIMARY KEY (`id_qualtricsSurveys`,`id_users`,`id_scheduledJobs`),
  CONSTRAINT `qualtricsReminders_fk_id_qualtricsSurveys` FOREIGN KEY (`id_qualtricsSurveys`) REFERENCES `qualtricsSurveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qualtricsReminders_fk_id_scheduledJobs` FOREIGN KEY (`id_scheduledJobs`) REFERENCES `scheduledJobs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qualtricsReminders_fk_id_users` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `qualtricsSurveysResponses`
--
CREATE TABLE IF NOT EXISTS `qualtricsSurveysResponses` (
  `id` int(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
  `id_users` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_surveys` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_qualtricsProjectActionTriggerTypes` int(10) UNSIGNED ZEROFILL NOT NULL,
  `survey_response_id` varchar(100) DEFAULT NULL,
  `started_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `survey_response_id` (`survey_response_id`),
  CONSTRAINT `qSurveysResponses_fk_id_qualtricsProjectActionTriggerTypes` FOREIGN KEY (`id_qualtricsProjectActionTriggerTypes`) REFERENCES `lookups` (`id`),
  CONSTRAINT `qSurveysResponses_fk_id_surveys` FOREIGN KEY (`id_surveys`) REFERENCES `qualtricsSurveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `qSurveysResponses_fk_id_users` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `scheduledJobs_qualtricsActions`
--
CREATE TABLE IF NOT EXISTS `scheduledJobs_qualtricsActions` (
  `id_scheduledJobs` int(10) UNSIGNED ZEROFILL NOT NULL,
  `id_qualtricsActions` int(10) UNSIGNED ZEROFILL NOT NULL,
  PRIMARY KEY (`id_scheduledJobs`,`id_qualtricsActions`),
  CONSTRAINT `scheduledJobs_qualtricsActions_fk_id_scheduledJobs` FOREIGN KEY (`id_scheduledJobs`) REFERENCES `scheduledJobs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `scheduledJobs_qualtricsActions_fk_iid_qualtricsActions` FOREIGN KEY (`id_qualtricsActions`) REFERENCES `qualtricsActions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- add qualtricsSurveyTypes
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsSurveyTypes', 'baseline', 'Baseline', 'Baselin surveys are the leadign surveys. They record the user in the contact list');
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsSurveyTypes', 'follow_up', 'Follow-up', 'Folloup surveys get a user from the contact list and use it.');
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsSurveyTypes', 'anonymous', 'Anonymous', 'Anonymous survey. No code or user is used.');

-- add qualtricsProjectActionAdditionalFunction
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsProjectActionAdditionalFunction', 'workwell_evaluate_personal_strenghts', '[Workwell] Evaluate personal strengths', 'Function that will evaluate the personal strengths and it will send an email for project workwell');
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsProjectActionAdditionalFunction', 'bmz_evaluate_motive', '[BMZ] Evaluate motive', 'Function that will evaluate the motive and genrate PDF file as a feedback');
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsProjectActionAdditionalFunction', 'workwell_cg_ap_4', '[Workwell] CG Action plan Week 4 (Reminder or notification is required)', '[Workwell] CG Action plan Week 4 (Reminder or notification is required)');
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsProjectActionAdditionalFunction', 'workwell_cg_ap_5', '[Workwell] CG Action plan Week 5 (Reminder or notification is required)', '[Workwell] CG Action plan Week 5 (Reminder or notification is required)');
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsProjectActionAdditionalFunction', 'workwell_eg_ap_4', '[Workwell] EG Action plan Week 4 (Reminder or notification is required)', '[Workwell] EG Action plan Week 4 (Reminder or notification is required)');
INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('qualtricsProjectActionAdditionalFunction', 'workwell_eg_ap_5', '[Workwell] EG Action plan Week 5 (Reminder or notification is required)', '[Workwell] EG Action plan Week 5 (Reminder or notification is required)');

INSERT IGNORE INTO lookups (type_code, lookup_code, lookup_value, lookup_description) values ('transactionBy', 'by_qualtrics_callback', 'By qualtrics callback', 'The action was done by a qualtrics callback');

-- Add new style QualtricsSurvey
INSERT IGNORE INTO `styles` (`name`, `id_type`, id_group, description) VALUES ('qualtricsSurvey', '2', (select id from styleGroup where `name` = 'Form' limit 1), 'Visualize a qualtrics survey. It is shown in iFrame.');

-- Add new field type `select-qualtrics-survey` and field `survey` in style qualtricsSurvey
INSERT IGNORE INTO `fieldType` (`id`, `name`, `position`) VALUES (NULL, 'select-qualtrics-survey', '7');
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'qualtricsSurvey', get_field_type_id('select-qualtrics-survey'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) 
VALUES (get_style_id('qualtricsSurvey'), get_field_id('qualtricsSurvey'), '', 'Select a survey. TIP: A Survey should be assigned to a project (added as a action)');

INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `help`)
SELECT `id`, (SELECT `id` FROM `fields` WHERE `name` = 'css'), "Allows to assign CSS classes to the root item of the style." FROM `styles` WHERE `name` = 'qualtricsSurvey';

-- add field once_per_schedule to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'once_per_schedule', get_field_type_id('checkbox'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('once_per_schedule'), 0, 'If checked the survey can be done once per schedule');

-- add field once_per_user to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'once_per_user', get_field_type_id('checkbox'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('once_per_user'), 0, 'If checked the survey can be done only once by an user. The checkbox `once_per_schedule` is ignore if this is checked');

-- add field start_time to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'start_time', get_field_type_id('time'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('start_time'), '00:00', 'Start time when the survey should be available');

-- add field end_time to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'end_time', get_field_type_id('time'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('end_time'), '00:00', 'End time when the survey should be not available anymore');

-- add field label_survey_done to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'label_survey_done', get_field_type_id('markdown'), 1);
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('label_survey_done'), null, 'Markdown text that is shown if the survey is done and it can be filled only once per schedule');

-- add field label_survey_not_active to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'label_survey_not_active', get_field_type_id('markdown'), 1);
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('label_survey_not_active'), null, 'Markdown text that is shown if the survey is not active right now.');

-- add field restart_on_refresh to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'restart_on_refresh', get_field_type_id('checkbox'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('restart_on_refresh'), 0, 'If checked the survey is restarted on refresh');

-- add field use_as_container to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'use_as_container', get_field_type_id('checkbox'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('use_as_container'), 0, 'If checked the style is used as container only and do not visualize the survey in iFrame');

-- add field children to style qualtricsSurvey
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('children'), 0, 'Children that can be added to the style. It is mainly used when the style is used as container');

-- add field close_modal_at_end to style qualtricsSurvey
INSERT IGNORE INTO `fields` (`id`, `name`, `id_type`, `display`) VALUES (NULL, 'close_modal_at_end', get_field_type_id('checkbox'), '0');
INSERT IGNORE INTO `styles_fields` (`id_styles`, `id_fields`, `default_value`, `help`) VALUES (get_style_id('qualtricsSurvey'), get_field_id('close_modal_at_end'), 0, '`Only for mobile` - if selected the modal form will be closed once the survey is done');


-- add Qualtrics module page
INSERT IGNORE INTO `pages` (`id`, `keyword`, `url`, `protocol`, `id_actions`, `id_navigation_section`, `parent`, `is_headless`, `nav_position`, `footer_position`, `id_type`) 
VALUES (NULL, 'moduleQualtrics', '/admin/qualtrics', 'GET|POST', '0000000002', NULL, '0000000009', '0', '90', NULL, '0000000001');
INSERT IGNORE INTO `pages_fields_translation` (`id_pages`, `id_fields`, `id_languages`, `content`) VALUES ((SELECT id FROM pages WHERE keyword = 'moduleQualtrics'), '0000000008', '0000000001', 'Module Qualtrics');
INSERT IGNORE INTO `acl_groups` (`id_groups`, `id_pages`, `acl_select`, `acl_insert`, `acl_update`, `acl_delete`) VALUES ('0000000001', (SELECT id FROM pages WHERE keyword = 'moduleQualtrics'), '1', '0', '1', '0');

-- add insert qualtrics projects page
INSERT IGNORE INTO `pages` (`id`, `keyword`, `url`, `protocol`, `id_actions`, `id_navigation_section`, `parent`, `is_headless`, `nav_position`, `footer_position`, `id_type`) 
VALUES (NULL, 'moduleQualtricsProject', '/admin/qualtrics/project/[select|update|insert|delete:mode]?/[i:pid]?', 'GET|POST', '0000000002', NULL, '0000000009', '0', NULL, NULL, '0000000001');
INSERT IGNORE INTO `pages_fields_translation` (`id_pages`, `id_fields`, `id_languages`, `content`) VALUES ((SELECT id FROM pages WHERE keyword = 'moduleQualtricsProject'), '0000000008', '0000000001', 'Qualtrics Projects');
INSERT IGNORE INTO `acl_groups` (`id_groups`, `id_pages`, `acl_select`, `acl_insert`, `acl_update`, `acl_delete`) VALUES ('0000000001', (SELECT id FROM pages WHERE keyword = 'moduleQualtricsProject'), '1', '1', '1', '1');

INSERT IGNORE INTO `pages` (`id`, `keyword`, `url`, `protocol`, `id_actions`, `id_navigation_section`, `parent`, `is_headless`, `nav_position`, `footer_position`, `id_type`) 
VALUES (NULL, 'moduleQualtricsSurvey', '/admin/qualtrics/survey/[select|update|insert|delete:mode]?/[i:sid]?', 'GET|POST', '0000000002', NULL, '0000000009', '0', NULL, NULL, '0000000001');
INSERT IGNORE INTO `pages_fields_translation` (`id_pages`, `id_fields`, `id_languages`, `content`) VALUES ((SELECT id FROM pages WHERE keyword = 'moduleQualtricsSurvey'), '0000000008', '0000000001', 'Qualtrics Survey');
INSERT IGNORE INTO `acl_groups` (`id_groups`, `id_pages`, `acl_select`, `acl_insert`, `acl_update`, `acl_delete`) VALUES ('0000000001', (SELECT id FROM pages WHERE keyword = 'moduleQualtricsSurvey'), '1', '1', '1', '1');

-- add action to project
INSERT IGNORE INTO `pages` (`id`, `keyword`, `url`, `protocol`, `id_actions`, `id_navigation_section`, `parent`, `is_headless`, `nav_position`, `footer_position`, `id_type`) 
VALUES (NULL, 'moduleQualtricsProjectAction', '/admin/qualtrics/action/[i:pid]/[select|update|insert|delete:mode]?/[i:sid]?', 'GET|POST', '0000000002', NULL, '0000000009', '0', NULL, NULL, '0000000001');
INSERT IGNORE INTO `pages_fields_translation` (`id_pages`, `id_fields`, `id_languages`, `content`) VALUES ((SELECT id FROM pages WHERE keyword = 'moduleQualtricsProjectAction'), '0000000008', '0000000001', 'Qualtrics Project Action');
INSERT IGNORE INTO `acl_groups` (`id_groups`, `id_pages`, `acl_select`, `acl_insert`, `acl_update`, `acl_delete`) VALUES ('0000000001', (SELECT id FROM pages WHERE keyword = 'moduleQualtricsProjectAction'), '1', '1', '1', '1');

-- add qualtricsSync page
INSERT IGNORE INTO `pages` (`id`, `keyword`, `url`, `protocol`, `id_actions`, `id_navigation_section`, `parent`, `is_headless`, `nav_position`, `footer_position`, `id_type`) 
VALUES (NULL, 'moduleQualtricsSync', '/admin/qualtrics/sync/[i:pid]/[i:aid]?', 'GET|POST', '0000000002', NULL, '0000000009', '0', NULL, NULL, '0000000001');
INSERT IGNORE INTO `pages_fields_translation` (`id_pages`, `id_fields`, `id_languages`, `content`) VALUES ((SELECT id FROM pages WHERE keyword = 'moduleQualtricsSync'), '0000000008', '0000000001', 'Qualtrics Synchronization');
INSERT IGNORE INTO `acl_groups` (`id_groups`, `id_pages`, `acl_select`, `acl_insert`, `acl_update`, `acl_delete`) VALUES ('0000000001', (SELECT id FROM pages WHERE keyword = 'moduleQualtricsSync'), '1', '0', '0', '0');
