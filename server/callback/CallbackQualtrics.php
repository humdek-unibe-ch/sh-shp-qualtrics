<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../callback/BaseCallback.php";
require_once __DIR__ . "/../component/moduleQualtricsSurvey/ModuleQualtricsSurveyModel.php";
require_once __DIR__ . "/../../../../component/style/register/RegisterModel.php";
require_once __DIR__ . "/../service/ext/php-pdftk-0.8.1.0/vendor/autoload.php";
require_once __DIR__ . "/calculations/BMZSportModel.php";
require_once __DIR__ . "/calculations/SaveDataModel.php";
require_once __DIR__ . "/../ext/php-math/vendor/autoload.php";

use mikehaertl\pdftk\Pdf;
use MathPHP\Probability\Distribution\Continuous;

/**
 * A small class that handles callbak and set the group number for validation code
 * calls.
 */
class CallbackQualtrics extends BaseCallback
{

    /* Constants ************************************************/
    const VALIDATION_add_survey_response = 'add_survey_response';
    const VALIDATION_set_group = 'set_group';
    const VALIDATION_save_data = 'save_data';
    const CALLBACK_NEW = 'callback_new';
    const CALLBACK_ERROR = 'callback_error';
    const CALLBACK_SUCCESS = 'callback_success';

    /* Private Properties *****************************************************/

    /**
     * Services
     */
    private $services = null;

    /**
     * The Qualtrics survey result is kept here
     * It is used only when there is overwrite in the config settings
     */
    private $survey_response = [];

    /**
     * Instance of ModuleQualtricsSurveyModel
     */
    private $moduleQualtricsSurveyModel;

    /**
     * The constructor.
     *
     * @param object $services
     *  The service handler instance which holds all services
     */
    public function __construct($services)
    {
        parent::__construct($services);
        $this->services = $services;
        $this->moduleQualtricsSurveyModel = new ModuleQualtricsSurveyModel($this->services);
    }

    /**
     * Get survey info
     *
     * @param string $survey_id
     *  The survey_id
     * @retval $array
     *  The survey data
     */
    private function getSurvey($survey_id)
    {
        $sql = "SELECT *
                FROM view_qualtricsSurveys
                WHERE qualtrics_survey_id = :survey_id";
        return $this->db->query_db_first($sql, array(':survey_id' => $survey_id));
    }

    /**
     * Check if the code exist in validation_codes table
     *
     * @param $code
     *  The code for which a user is searched
     * @retval $boolean
     *  
     */
    private function code_exist($code)
    {
        $sql = "select code
                from view_user_codes
                where code  = :code";
        $res = $this->db->query_db_first($sql, array(':code' => $code));
        return  isset($res['code']);
    }

    /**
     * Check if the project is a legacy qualtrics project 
     * @return bool
     * Return true if the project is legacy
     */
    private function is_legacy()
    {
        $res = $this->db->query_db_first("SELECT COUNT(*) as res FROM information_schema.`tables`
                                        WHERE table_schema = DATABASE()
                                        AND `table_name` = 'qualtricsActions'");
        return $res['res'] > 0;
    }

    /**
     * Get the scheduled reminders for the user and this survey
     * @param int $uid 
     * user_id
     * @param string $qualtrics_survey_id
     * qualtrics survey id from Qualtrics
     * @retval array
     * all scheduled reminders
     */
    private function get_scheduled_reminders($uid, $qualtrics_survey_id)
    {

        if ($this->is_legacy()) {
            return $this->db->query_db(
                'SELECT id_scheduledJobs 
            FROM view_qualtricsReminders 
            WHERE `user_id` = :uid AND qualtrics_survey_id = :sid AND status_code = :status
            AND (valid_till IS NULL OR (NOW() BETWEEN session_start_date AND valid_till))',
                array(
                    ":uid" => $uid,
                    ":sid" => $qualtrics_survey_id,
                    ":status" => scheduledJobsStatus_queued
                )
            );
        } else {
            return array();
        }
    }

    /**
     * Change the status of the queueud mails to deleted
     * @param array $scheduled_reminders
     * Arra with reminders that should be deleted
     */
    private function delete_reminders($scheduled_reminders)
    {
        $result = array();
        foreach ($scheduled_reminders as $reminder) {
            $result[] = $this->job_scheduler->delete_job($reminder['id_scheduledJobs'], transactionBy_by_qualtrics_callback);
        }
        return $result;
    }

    /**
     * Add a new user to the DB.
     *
     * @param array $data
     *  the data from the callback.     
     * @param int $uid
     * user id
     * @retval int
     *  The id of the new record.
     */
    private function insert_survey_response($data, $uid)
    {
        return $this->db->insert("qualtricsSurveysResponses", array(
            "id_users" => $uid,
            "id_surveys" => $this->db->query_db_first(
                'SELECT id FROM qualtricsSurveys WHERE qualtrics_survey_id = :qualtrics_survey_id',
                array(":qualtrics_survey_id" => $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE])
            )['id'],
            "id_qualtricsProjectActionTriggerTypes" => $this->db->get_lookup_id_by_value(actionTriggerTypes, $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE]),
            "survey_response_id" => $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE]
        ));
    }

    /**
     * Get all actions for a survey and a trigger_type
     *
     * @param string $sid
     *  qualtrics survey id
     * @param string $trigger_type
     *  trigger type
     *  @return array
     * return all actions for that survey with this trigger_type
     */
    private function get_actions($sid, $trigger_type)
    {
        if (!$this->db->check_if_view_exists('view_qualtricsActions')) {
            return array(); // return empty array the view does not exists; It was a legacy
        }
        $sqlGetActions = "SELECT *
                FROM view_qualtricsActions
                WHERE qualtrics_survey_id = :sid AND trigger_type = :trigger_type 
                AND action_schedule_type <> 'Nothing'";
        return $this->db->query_db(
            $sqlGetActions,
            array(
                "sid" => $sid,
                "trigger_type" => $trigger_type
            )
        );
    }

    /**
     * Get all actions for a survey and a trigger_type which has functions
     *
     * @param string $sid
     *  qualtrics survey id
     * @param string $trigger_type
     *  trigger type
     *  @return array
     * return all actions for that survey with this trigger_type
     */
    private function get_actions_with_functions($sid, $trigger_type)
    {
        if (!$this->db->check_if_view_exists('view_qualtricsActions')) {
            return array(); // return empty array the view does not exists; It was a legacy
        }
        $sqlGetActions = "SELECT *
                FROM view_qualtricsActions
                WHERE qualtrics_survey_id = :sid AND trigger_type = :trigger_type AND functions IS NOT NULL";
        return $this->db->query_db(
            $sqlGetActions,
            array(
                "sid" => $sid,
                "trigger_type" => $trigger_type
            )
        );
    }

    /**
     * Get all users for selected groups
     *
     * @param array $groups
     *  Array with group ids
     *  @retval array
     * return all users for the selected groups or false
     */
    private function get_users_from_groups($groups)
    {
        $sql = "SELECT u.id
                    FROM users u
                    INNER JOIN users_groups g ON (u.id = g.id_users)
                    WHERE u.id_status = 3 AND g.id_groups IN (" . implode(",", $groups) . ");";
        return $this->db->query_db($sql);
    }

    /**
     * Check if the user belongs in group(s)
     * @param int $uid
     * user  id
     * @param string $id_groups
     * the grousp in coma separated string
     * @retval bool 
     * true if the user is in the group(s) or false if not
     */
    private function is_user_in_group($uid, $id_groups)
    {
        $sql = 'SELECT DISTINCT u.id
                FROM users AS u
                INNER JOIN users_groups AS ug ON ug.id_users = u.id
                INNER JOIN `groups` g ON g.id = ug.id_groups
                WHERE u.id = :uid and g.id in (' . $id_groups . ');';
        $user = $this->db->query_db_first(
            $sql,
            array(
                ":uid" => $uid
            )
        );
        return isset($user['id']);
    }

    /**
     * Calculate the date when the email should be sent when it is on weekday type
     * @param array $schedule_info
     * Schedule info from the action
     * @retval string
     * the date in sting format for MySQL
     */
    private function calc_date_on_weekday($schedule_info)
    {
        $now = date('Y-m-d H:i:s', time());
        $next_weekday = strtotime('next ' . $schedule_info['send_on_day'], strtotime($now));
        $d = new DateTime();
        $next_weekday = $d->setTimestamp($next_weekday);
        $at_time = explode(':', $schedule_info['send_on_day_at']);
        $next_weekday = $next_weekday->setTime($at_time[0], $at_time[1]);
        if ($schedule_info['send_on'] > 1) {
            return date('Y-m-d H:i:s', strtotime('+' . $schedule_info['send_on'] - 1 . ' weeks', $next_weekday->getTimestamp()));
        } else {
            $next_weekday = $next_weekday->getTimestamp();
            return date('Y-m-d H:i:s', $next_weekday);
        }
    }

    /**
     * Calculate the date when the email should be sent
     * @param array $schedule_info
     * Schedule info from the action
     * @param string $action_schedule_type_code
     * type notification or reminder
     * @retval string
     * the date in sting format for MySQL
     */
    private function calc_date_to_be_sent($schedule_info, $action_schedule_type_code)
    {
        $date_to_be_sent = 'undefined';
        if ($schedule_info[actionScheduleTypes] == actionScheduleTypes_immediately) {
            // send imediately
            $date_to_be_sent = date('Y-m-d H:i:s', time());
        } else if ($schedule_info[actionScheduleTypes] == actionScheduleTypes_on_fixed_datetime) {
            // send on specific date
            $date_to_be_sent = date('Y-m-d H:i:s', DateTime::createFromFormat('d-m-Y H:i', $schedule_info['custom_time'])->getTimestamp());
        } else if ($schedule_info[actionScheduleTypes] == actionScheduleTypes_after_period) {
            // send after time period 
            $now = date('Y-m-d H:i:s', time());
            $date_to_be_sent = date('Y-m-d H:i:s', strtotime('+' . $schedule_info['send_after'] . ' ' . $schedule_info['send_after_type'], strtotime($now)));
            if ($schedule_info['send_on_day_at']) {
                $at_time = explode(':', $schedule_info['send_on_day_at']);
                $d = new DateTime();
                $date_to_be_sent = $d->setTimestamp(strtotime($date_to_be_sent));
                $date_to_be_sent = $date_to_be_sent->setTime($at_time[0], $at_time[1]);
                $date_to_be_sent = date('Y-m-d H:i:s', $date_to_be_sent->getTimestamp());
            }
        } else if ($schedule_info[actionScheduleTypes] == actionScheduleTypes_after_period_on_day_at_time) {
            // send on specific weekday after 1,2,3, or more weeks at specific time
            $date_to_be_sent = $this->calc_date_on_weekday($schedule_info);
            if ($action_schedule_type_code == actionScheduleJobs_reminder) {
                // we have to check the linked notification and schedule the reminder always after the notification
                $schedule_info_notification = json_decode($this->db->query_db_first('SELECT schedule_info FROM qualtricsActions WHERE id = :id', array(':id' => $schedule_info['linked_action']))['schedule_info'], true);
                $base_schedule_info = $schedule_info;
                // $base_schedule_info['send_on'] = 1;
                // $schedule_info_notification['send_on'] = 1;
                $base_reminder_day = $this->calc_date_on_weekday($base_schedule_info);
                $base_notification_day = $this->calc_date_on_weekday($schedule_info_notification);
                if ($base_notification_day > $base_reminder_day) {
                    //reminder will be scheduled before the notification; it should be adjusted to 1 week later
                    $date_to_be_sent = date('Y-m-d H:i:s', strtotime('+1 weeks', strtotime($date_to_be_sent)));
                }
            }
        }
        return $date_to_be_sent;
    }

    /**
     * Add a reminder in qualtricsReminders
     *
     * @param int $sj_id
     *  the scheduled job id
     * @param int $uid
     * user id
     * @param array $action
     * the action info
     * @retval int
     *  The id of the new record.
     */
    private function add_reminder($sj_id, $uid, $action)
    {
        $res = $this->db->insert("qualtricsReminders", array(
            "id_users" => $uid,
            "id_qualtricsSurveys" => $action['id_qualtricsSurveys_reminder'],
            "id_scheduledJobs" => $sj_id
        ));
        return $res;
    }

    /**
     * Check if any of the actions assigne to this survey need the survey_response from the Qualtrics
     * The data request is not very fast that is why we call it when we need it
     * @param array $actions
     * All actions attached to the survey for which we recieved a survey_response
     * @retval boolean
     * true if we need the extra data and false if we dont need it
     */
    private function is_survey_response_needed($actions)
    {
        foreach ($actions as $key => $action) {
            $schedule_info = json_decode($action['schedule_info'], true);
            if ((isset($schedule_info['config']['type']) && $schedule_info['config']['type'] == "overwrite_variable") || (isset($schedule_info['config']['overwrite_variables']) && count($schedule_info['config']['overwrite_variables']) > 0)) {
                // data is needed
                return true;
            }
        }
        return false;
    }

    /**
     * Get the saved data from the survey 
     * 
     * @param array $data
     *  the data from the callback.
     * @return object
     * 
     */
    private function get_survey_saved_data($data)
    {
        $table_name = $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE]; //survey code id is used as table name
        $id_table = $this->services->get_user_input()->get_dataTable_id($table_name);
        $filter = "AND " . ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE . " = '" . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . "'";
        return $this->services->get_user_input()->get_data($id_table, $filter, false, null, true); // return db first
    }

    /**
     * Check if any event should be queued based on the actions
     *
     * @param array $data
     *  the data from the callback.
     * @param int $user_id
     * user id
     * @retval string
     *  log text what actions was done;
     */
    private function queue_event_from_actions($data, $user_id)
    {
        $result = array();
        //get all actions for this survey and trigger type
        $actions = $this->get_actions($data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE], $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE]);
        $this->survey_response = []; // always clear it, new response new data if we need it
        if ($this->is_survey_response_needed($actions)) {
            $start_time = microtime(true);
            $start_date = date("Y-m-d H:i:s");
            $this->survey_response = $this->get_survey_saved_data($data);
            $res['action'] = 'get_survey_response';
            $res['time'] = [];
            $end_time = microtime(true);
            $res['time']['exec_time'] = $end_time - $start_time;
            $res['time']['start_date'] = $start_date;
            array_push($result, $res);
        }
        foreach ($actions as $action) {
            //clear the mail generation data
            if ($this->is_user_in_group($user_id, $action['id_groups'])) {

                $global_values = $this->services->get_db()->get_global_values();
                if ($global_values) {
                    // replace global values if they are used
                    $action['schedule_info'] = $this->services->get_db()->replace_calced_values($action['schedule_info'],  $global_values);
                }

                $schedule_info = json_decode($action['schedule_info'], true);
                $res = array();
                if ($action['action_schedule_type_code'] == actionScheduleJobs_task) {
                    $users = array();
                    if (isset($schedule_info['target_groups'])) {
                        $users_from_groups = $this->get_users_from_groups($schedule_info['target_groups']);
                        if ($users_from_groups) {
                            foreach ($users_from_groups as $key => $user) {
                                array_push($users, $user['id']);
                            }
                            $users = array_unique($users);
                        }
                    } else {
                        array_push($users, $user_id);
                    }
                    $start_time = microtime(true);
                    $start_date = date("Y-m-d H:i:s");
                    $res = $this->queue_task($data, $users, $action);
                    $res['time'] = [];
                    $end_time = microtime(true);
                    $res['time']['exec_time'] = $end_time - $start_time;
                    $res['time']['start_date'] = $start_date;
                    array_push($result, $res);
                } else if (
                    $action['action_schedule_type_code'] == actionScheduleJobs_notification ||
                    $action['action_schedule_type_code'] == actionScheduleJobs_reminder
                ) {
                    if ($schedule_info['notificationTypes'] == notificationTypes_email) {
                        // the notification type is email                        
                        $start_time = microtime(true);
                        $start_date = date("Y-m-d H:i:s");
                        $res = $this->queue_mail($data, $user_id, $action);
                        $res['time'] = [];
                        $end_time = microtime(true);
                        $res['time']['exec_time'] = $end_time - $start_time;
                        $res['time']['start_date'] = $start_date;
                        array_push($result, $res);
                    } else if ($schedule_info['notificationTypes'] == notificationTypes_push_notification) {
                        // the notification type is push notification                        
                        $start_time = microtime(true);
                        $start_date = date("Y-m-d H:i:s");
                        $res = $this->queue_notification($data, $user_id, $action);
                        $res['time'] = [];
                        $end_time = microtime(true);
                        $res['time']['exec_time'] = $end_time - $start_time;
                        $res['time']['start_date'] = $start_date;
                        array_push($result, $res);
                    }
                }
                if (isset($res['sj_id'])) {
                    $this->db->insert('scheduledJobs_qualtricsActions', array(
                        "id_scheduledJobs" => $res['sj_id'],
                        "id_qualtricsActions" => $action['id'],
                    ));
                }
            }
        }

        if (count($result) == 0) {
            $result[] = "no event";
        }
        return $result;
    }

    /**
     * Queue mail
     *
     * @param array $data
     *  the data from the callback.
     * @param int $user_id
     * user id
     * @param array $action
     * the action information
     * @retval string
     *  log text what actions was done;
     */
    private function queue_mail($data, $user_id, $action)
    {
        //  {
        // 	"type": "overwrite_variable",
        // 	"variable": ["send_on_day_at", "var2"]    
        // }

        $schedule_info = json_decode($action['schedule_info'], true);
        $result = array();
        $check_config = $this->check_config($schedule_info);
        $schedule_info = $check_config['schedule_info'];
        $result = $check_config['result'];
        $mail = array();
        // *************************************** CHECK FOR ADDITIONAL FUNCTIONS THAT RETURN ATTACHMENTS *************************************************************
        $attachments = array();
        if ($action['functions_code']) {
            $functions = explode(';', $action['functions_code']);
            foreach ($functions as $key => $value) {
                if ($value == qualtricsProjectActionAdditionalFunction_workwell_evaluate_personal_strenghts) {
                    // WORKWELL evaluate strenghts function
                    $result[] = qualtricsProjectActionAdditionalFunction_workwell_evaluate_personal_strenghts;
                    $func_res = $this->workwell_evaluate_strenghts($data, $user_id);
                    $result[] = $func_res['output'];
                    if ($func_res['attachment']) {
                        $attachments[] = $func_res['attachment'];
                    }
                } else if (
                    $value == qualtricsProjectActionAdditionalFunction_workwell_cg_ap_4 ||
                    $value == qualtricsProjectActionAdditionalFunction_workwell_cg_ap_5 ||
                    $value == qualtricsProjectActionAdditionalFunction_workwell_eg_ap_4 ||
                    $value == qualtricsProjectActionAdditionalFunction_workwell_eg_ap_5
                ) {
                    // Fill PDF with qualtrics embeded data
                    $result[] = $value;
                    $func_res = $this->fill_pdf_with_qualtrics_embeded_data($value, $data, $user_id);
                    $result[] = $func_res['output'];
                    if ($func_res['attachment']) {
                        $attachments[] = $func_res['attachment'];
                    }
                }
            }
        }
        $mail_attachments_from_config = array();
        if (isset($schedule_info['attachments']) && $schedule_info['attachments']) {
            $mail_attachments_from_config = json_decode($schedule_info['attachments'], true);
        }
        foreach ($mail_attachments_from_config as $idx => $attachment) {
            $attachments[] = array(
                "attachment_name" => $attachment,
                "attachment_path" => ASSET_SERVER_PATH . "/" . $attachment,
                "attachment_url" => ASSET_PATH . "/" . $attachment
            );
        }
        // *************************************** END CHECK FOR ADDITIONAL FUNCTIONS THAT RETURN ATTACHMENTS *************************************************************
        $user_info = $this->db->select_by_uid('view_user_codes', $user_id);
        $body = str_replace('@user_name', $user_info['name'], $schedule_info['body']);
        $body = str_replace('@user_code', $user_info['code'], $body);
        $mail = array(
            "id_jobTypes" => $this->db->get_lookup_id_by_value(jobTypes, jobTypes_email),
            "id_jobStatus" => $this->db->get_lookup_id_by_value(scheduledJobsStatus, scheduledJobsStatus_queued),
            "date_to_be_executed" => $this->calc_date_to_be_sent($schedule_info, $action['action_schedule_type_code']),
            "from_email" => $schedule_info['from_email'],
            "from_name" => $schedule_info['from_name'],
            "reply_to" => $schedule_info['reply_to'],
            "recipient_emails" =>  str_replace('@user', $this->db->select_by_uid('users', $user_id)['email'], $schedule_info['recipient']),
            "subject" => $schedule_info['subject'],
            "body" => $body,
            "description" => "Schedule email by Qualtrics Callback",
            "condition" =>  isset($schedule_info['config']) && isset($schedule_info['config']['condition']) ? $schedule_info['config']['condition'] : null,
            "attachments" => $attachments
        );
        $sj_id = $this->job_scheduler->schedule_job($mail, transactionBy_by_qualtrics_callback);
        if ($sj_id > 0) {
            if ($action['action_schedule_type_code'] == actionScheduleJobs_reminder) {
                $this->add_reminder($sj_id, $user_id, $action);
            }
            $result[] = 'Mail was queued for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
            if (($schedule_info[actionScheduleTypes] == actionScheduleTypes_immediately)) {
                $job_entry = $this->db->query_db_first('SELECT * FROM view_scheduledJobs WHERE id = :sjid;', array(":sjid" => $sj_id));
                if ($this->job_scheduler->execute_job($job_entry, transactionBy_by_qualtrics_callback)) {
                    $result[] = 'Mail was sent for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                        ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                        ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
                } else {
                    $result[] = 'ERROR! Mail was not sent for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                        ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                        ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
                }
            }
        } else {
            $result[] = 'ERROR! Mail was not queued for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
        }
        return array(
            "result" => $result,
            "sj_id" => $sj_id
        );
    }

    /**
     * Check config field for extra modifications
     * @param array $schedule_info
     * the schedule info
     * @retval array 
     * return the info from the check
     */
    private function check_config($schedule_info)
    {
        $result = array();
        if (isset($schedule_info['config']['type']) && $schedule_info['config']['type'] == "overwrite_variable") {
            // check qualtrics for more groups coming as embedded data
            if (isset($schedule_info['config']['variable'])) {
                foreach ($schedule_info['config']['variable'] as $key => $variable) {
                    if (isset($this->survey_response[$variable])) {
                        $result[] = 'Overwrite variable `' . $variable . '` from ' . $schedule_info[$variable] . ' to ' . $this->survey_response[$variable];
                        $schedule_info[$variable] = $this->survey_response[$variable];
                    }
                }
            }
        }
        if (isset($schedule_info['config']['overwrite_variables']) && count($schedule_info['config']['overwrite_variables']) > 0) {
            // check qualtrics for custom variables that overwrite some data
            foreach ($schedule_info['config']['overwrite_variables'] as $key => $var_pairs) {
                if (isset($this->survey_response[$var_pairs['embeded_variable']])) {
                    $result[] = 'Overwrite variable `' . $var_pairs['embeded_variable'] . '` from ' . $schedule_info[$var_pairs['scheduled_variable']] . ' to ' . $this->survey_response[$var_pairs['embeded_variable']];
                    $schedule_info[$var_pairs['scheduled_variable']] = $this->survey_response[$var_pairs['embeded_variable']];
                }
            }
        }
        return array(
            "result" => $result,
            "schedule_info" => $schedule_info
        );
    }

    /**
     * Queue notification
     *
     * @param array $data
     *  the data from the callback.
     * @param int $user_id
     * user id
     * @param array $action
     * the action information
     * @retval string
     *  log text what actions was done;
     */
    private function queue_notification($data, $user_id, $action)
    {
        //  {
        // 	"type": "overwrite_variable",
        // 	"variable": ["send_on_day_at", "var2"]    
        // }

        $schedule_info = json_decode($action['schedule_info'], true);
        $result = array();
        $check_config = $this->check_config($schedule_info);
        $schedule_info = $check_config['schedule_info'];
        $result = $check_config['result'];

        $user_info = $this->db->select_by_uid('view_user_codes', $user_id);
        $body = str_replace('@user_name', $user_info['name'], $schedule_info['body']);
        $body = str_replace('@user_code', $user_info['code'], $body);
        $notification = array(
            "id_jobTypes" => $this->db->get_lookup_id_by_value(jobTypes, jobTypes_notification),
            "id_jobStatus" => $this->db->get_lookup_id_by_value(scheduledJobsStatus, scheduledJobsStatus_queued),
            "date_to_be_executed" => $this->calc_date_to_be_sent($schedule_info, $action['action_schedule_type_code']),
            "recipients" => array($user_id),
            "subject" => $schedule_info['subject'],
            "url" => isset($schedule_info['url']) ? $schedule_info['url'] : null,
            "condition" =>  isset($schedule_info['config']) && isset($schedule_info['config']['condition']) ? $schedule_info['config']['condition'] : null,
            "body" => $body,
            "description" => "Schedule notification by Qualtrics Callback",
        );
        $sj_id = $this->job_scheduler->schedule_job($notification, transactionBy_by_qualtrics_callback);
        if ($sj_id > 0) {
            if ($action['action_schedule_type_code'] == actionScheduleJobs_reminder) {
                $this->add_reminder($sj_id, $user_id, $action);
            }
            $result[] = 'Notification was queued for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
            if (($schedule_info[actionScheduleTypes] == actionScheduleTypes_immediately)) {
                $job_entry = $this->db->query_db_first('SELECT * FROM view_scheduledJobs WHERE id = :sjid;', array(":sjid" => $sj_id));
                if (($this->job_scheduler->execute_job($job_entry, transactionBy_by_qualtrics_callback))) {
                    $result[] = 'Notification was sent for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                        ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                        ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
                } else {
                    $result[] = 'ERROR! Notification was not sent for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                        ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                        ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
                }
            }
        } else {
            $result[] = 'ERROR! Notificaton was not queued for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
        }
        return array(
            "result" => $result,
            "sj_id" => $sj_id
        );
    }

    /**
     * Queue task
     * 
     *
     * @param array $data
     *  the data from the callback.
     * @param array $user_id
     * user id arrays
     * @param array $action
     * the action information
     * @retval string
     *  log text what actions was done;
     */
    private function queue_task($data, $users, $action)
    {
        //  {
        // 	"type": "add_group | remove_group",
        // 	"group": ["group_name1", "group_name2"]
        //  "description": "task description"
        // }

        $schedule_info = json_decode($action['schedule_info'], true);
        $result = array();
        $check_config = $this->check_config($schedule_info);
        $schedule_info = $check_config['schedule_info'];
        $result = $check_config['result'];
        $task = array(
            'id_jobTypes' => $this->db->get_lookup_id_by_value(jobTypes, jobTypes_task),
            "id_jobStatus" => $this->db->get_lookup_id_by_value(scheduledJobsStatus, scheduledJobsStatus_queued),
            "date_to_be_executed" => $this->calc_date_to_be_sent($schedule_info, $action['action_schedule_type_code']),
            "id_users" => $users,
            "config" => $schedule_info['config'],
            "description" => isset($schedule_info['config']['description']) ? $schedule_info['config']['description'] : "Schedule task by Qualtrics Callback",
        );
        $sj_id = $this->job_scheduler->schedule_job($task, transactionBy_by_qualtrics_callback);
        if ($sj_id > 0) {
            $result[] = 'Task was queued for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
            if (($schedule_info[actionScheduleTypes] == actionScheduleTypes_immediately)) {
                $job_entry = $this->db->query_db_first('SELECT * FROM view_scheduledJobs WHERE id = :sjid;', array(":sjid" => $sj_id));
                if (($this->job_scheduler->execute_job($job_entry, transactionBy_by_qualtrics_callback))) {
                    $result[] = 'Task was executed for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                        ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                        ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
                } else {
                    $result[] = 'ERROR! Task was not executed for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                        ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                        ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
                }
            }
        } else {
            $result[] = 'ERROR! Task was not queued for user: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] .
                ' when survey: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE] .
                ' ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE];
        }
        return array(
            "result" => $result,
            "sj_id" => $sj_id
        );
    }

    /**
     * Evaluate personal strenghts for WORKWELL project
     *
     * @param array $data
     *  the data from the callback.     
     * @param int $user_id
     * user id
     * @retval string
     *  log text what actions was done;
     */
    private function workwell_evaluate_strenghts($data, $user_id)
    {
        $result = [];
        $strengths = array(
            "creativity" => array(
                "coefficient_1" => 3.43,
                "coefficient_2" => 0.6,
                "label" => "Kreativitaet",
                "value" => 0
            ),
            "curiosity" => array(
                "coefficient_1" => 3.92,
                "coefficient_2" => 0.51,
                "label" => "Neugier",
                "value" => 0
            ),
            "open_mindedness" => array(
                "coefficient_1" => 3.7,
                "coefficient_2" => 0.48,
                "label" => "Urteilsvermoegen",
                "value" => 0
            ),
            "learning" => array(
                "coefficient_1" => 3.59,
                "coefficient_2" => 0.62,
                "label" => "Liebe zum Lernen",
                "value" => 0
            ),
            "perspektive" => array(
                "coefficient_1" => 3.46,
                "coefficient_2" => 0.47,
                "label" => "Weisheit",
                "value" => 0
            ),
            "bravery" => array(
                "coefficient_1" => 3.52,
                "coefficient_2" => 0.5,
                "label" => "Tapferkeit",
                "value" => 0
            ),
            "persistence" => array(
                "coefficient_1" => 3.47,
                "coefficient_2" => 0.59,
                "label" => "Ausdauer",
                "value" => 0
            ),
            "authenticity" => array(
                "coefficient_1" => 3.78,
                "coefficient_2" => 0.43,
                "label" => "Authentizitaet",
                "value" => 0
            ),
            "zest" => array(
                "coefficient_1" => 3.57,
                "coefficient_2" => 0.52,
                "label" => "Enthusiasmus",
                "value" => 0
            ),
            "love" => array(
                "coefficient_1" => 3.78,
                "coefficient_2" => 0.5,
                "label" => "Bindungsfaehigkeit",
                "value" => 0
            ),
            "kindness" => array(
                "coefficient_1" => 3.85,
                "coefficient_2" => 0.46,
                "label" => "Freundlichkeit",
                "value" => 0
            ),
            "social_intelligence" => array(
                "coefficient_1" => 3.62,
                "coefficient_2" => 0.44,
                "label" => "Soziale Intelligenz",
                "value" => 0
            ),
            "teamwork" => array(
                "coefficient_1" => 3.6,
                "coefficient_2" => 0.48,
                "label" => "Teamwork",
                "value" => 0
            ),
            "fairness" => array(
                "coefficient_1" => 3.9,
                "coefficient_2" => 0.47,
                "label" => "Fairness",
                "value" => 0
            ),
            "leadership" => array(
                "coefficient_1" => 3.57,
                "coefficient_2" => 0.48,
                "label" => "Fuehrungsvermoegen",
                "value" => 0
            ),
            "forgiveness" => array(
                "coefficient_1" => 3.52,
                "coefficient_2" => 0.52,
                "label" => "Vergebungsbereitschaft",
                "value" => 0
            ),
            "modesty" => array(
                "coefficient_1" => 3.32,
                "coefficient_2" => 0.56,
                "label" => "Bescheidenheit",
                "value" => 0
            ),
            "prudence" => array(
                "coefficient_1" => 3.32,
                "coefficient_2" => 0.53,
                "label" => "Vorsicht",
                "value" => 0
            ),
            "self_regulation" => array(
                "coefficient_1" => 3.25,
                "coefficient_2" => 0.55,
                "label" => "Selbstregulation",
                "value" => 0
            ),
            "appreciation" => array(
                "coefficient_1" => 3.51,
                "coefficient_2" => 0.54,
                "label" => "Sinn fuer das Schoene",
                "value" => 0
            ),
            "gratitude" => array(
                "coefficient_1" => 3.69,
                "coefficient_2" => 0.53,
                "label" => "Dankbarkeit",
                "value" => 0
            ),
            "hope" => array(
                "coefficient_1" => 3.54,
                "coefficient_2" => 0.55,
                "label" => "Hoffnung",
                "value" => 0
            ),
            "humor" => array(
                "coefficient_1" => 3.65,
                "coefficient_2" => 0.56,
                "label" => "Humor",
                "value" => 0
            ),
            "spirituality" => array(
                "coefficient_1" => 3.02,
                "coefficient_2" => 0.89,
                "label" => "Spiritualitaet",
                "value" => 0
            )
        );
        $result[] = qualtricsProjectActionAdditionalFunction_workwell_evaluate_personal_strenghts;
        $result[] = $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE];
        $result[] = $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE];
        $survey_response = $this->get_survey_saved_data($data);
        foreach ($strengths as $key => $value) {
            if (isset($survey_response[$key])) {
                //sudo apt install php-dev; pecl install stats-2.0.3 ; then added extension=stats.so to my php.ini
                $x = $survey_response[$key];
                $mu = $value["coefficient_1"];
                $sigma = $value["coefficient_2"];
                $normal = new Continuous\Normal($mu, $sigma);
                $strengths[$key]["value"] = round($normal->cdf($x) * 100);
            }
        }
        array_multisort(array_column($strengths, 'value'), SORT_DESC, $strengths);

        $fields = array();
        $i = 1;
        foreach ($strengths as $key => $value) {
            $fields['Strengths' . $i] = $value['label'];
            $i++;
        }
        $attachment = $this->get_attachment_info(qualtricsProjectActionAdditionalFunction_workwell_evaluate_personal_strenghts, $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]);
        $pdf = new Pdf($attachment['template_path']);
        $pdf->fillForm($fields)
            ->needAppearances()
            ->saveAs($attachment['attachment_path']);
        $ret_value = null;
        $ret_value['attachment'] = $attachment;
        $ret_value['output'] = $result;
        return $ret_value;
    }

    /**
     * Fill pdf form template with qualtrics embeded data. The name of the form's fields should be the same as the name of the embeded data fields
     *
     * @param string $function_name the
     *  name of the function - we use it to get the template
     * @param array $data
     *  the data from the callback.     
     * @param int $user_id
     * user id
     * @retval string
     *  log text what actions was done;
     */
    private function fill_pdf_with_qualtrics_embeded_data($function_name, $data, $user_id)
    {
        $result = [];
        $result[] = $function_name;
        $result[] = $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE];
        $survey_response = $this->get_survey_saved_data($data);
        $attachment = $this->get_attachment_info($function_name, $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]);
        $pdfTemplate = new Pdf($attachment['template_path']);
        $data_fields = $pdfTemplate->getDataFields()->__toArray();

        // generate fields dynamically from the template
        $fields = array();
        foreach ($data_fields as $key => $value) {
            if (isset($survey_response[$value['FieldName']])) {
                $fields[$value['FieldName']] = $survey_response[$value['FieldName']];
            }
        }
        $pdf = new Pdf($attachment['template_path']);
        $pdf->fillForm($fields)
            ->flatten()
            ->needAppearances()
            ->saveAs($attachment['attachment_path']);
        $ret_value = null;
        $ret_value['attachment'] = $attachment;
        $ret_value['output'] = $result;
        return $ret_value;
    }

    /**
     * Evaluate the survey results and insert them into the database
     *
     * @param array $data
     *  the data from the callback.     
     * @retval array
     *  result log array
     */
    private function bmz_evaluate_motive($data)
    {
        $survey_response = $this->get_survey_saved_data($data);
        $bmz_sport_model = new BMZSportModel($this->services, $survey_response, $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE]);
        return $bmz_sport_model->evaluate_survey($this->getSurvey($data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE])['config']);
    }

    /**
     * Get the attachment info
     * @param string $function_name
     * @param string $file_name
     * @retval array 
     * The attachment info properties
     */
    private function get_attachment_info($function_name, $file_name)
    {
        $genPdfFileName = $file_name . ".pdf";
        $genPdfFilePath = ASSET_SERVER_PATH . "/" . $function_name . "/" . $genPdfFileName;
        $genPdfFileUrl = ASSET_PATH . "/" . $function_name . "/" . $genPdfFileName;
        $templatePath = ASSET_SERVER_PATH . "/" . $function_name . ".pdf";
        $attachment = array(
            "attachment_name" => $genPdfFileName,
            "attachment_path" => $genPdfFilePath,
            "attachment_url" => $genPdfFileUrl,
            "template_path" => $templatePath
        );
        return $attachment;
    }

    /**
     * Check if any action has addtional function that should be executed
     *
     * @param array $data
     *  the data from the callback.
     * @param int $user_id
     * user id
     * @retval string
     *  log text what actions was done;
     */
    private function check_functions_from_actions($data, $user_id = -1)
    {
        $result = [];
        //get all actions for this survey and trigger type 
        $actions = $this->get_actions_with_functions($data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE], $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE]);
        foreach ($actions as $action) {
            if ($user_id > 0 && $this->is_user_in_group($user_id, $action['id_groups'])) {
                // Special Functions code here if it is not related to notifications or reminders
                if (strpos($action['functions_code'], qualtricsProjectActionAdditionalFunction_workwell_evaluate_personal_strenghts) !== false) {
                    // WORKWELL evaluate strenghts function
                    $result[] = qualtricsProjectActionAdditionalFunction_workwell_evaluate_personal_strenghts;
                    $result[] = $this->workwell_evaluate_strenghts($data, $user_id);
                }
            }
            if ($action['survey_type_code'] === qualtricsSurveyTypes_anonymous) {
                // anonymous survey
                if (strpos($action['functions_code'], qualtricsProjectActionAdditionalFunction_bmz_evaluate_motive) !== false) {
                    $result[] = $this->bmz_evaluate_motive($data);
                }
            }
        }
        return $result;
    }

    /**
     * Add a new user to the DB.
     *
     * @param array $data
     *  the data from the callback.     
     * @retval int
     *  The id of the new user.
     */
    private function update_survey_response($data)
    {
        return $this->db->update_by_ids(
            "qualtricsSurveysResponses",
            array(
                "id_qualtricsProjectActionTriggerTypes" => $this->db->get_lookup_id_by_value(
                    actionTriggerTypes,
                    $data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE]
                )
            ),
            array('survey_response_id' => $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE])
        );
    }

    /**
     * Get the group id
     *
     * @param $group
     *  The name of a group
     * @return $groupId
     *  the id of the group or -1 on failure
     */
    private function getGroupId($group)
    {
        $sql = "SELECT id FROM `groups`
            WHERE name = :group";
        $res = $this->db->query_db_first($sql, array(':group' => $group));
        return  !isset($res['id']) ? -1 : $res['id'];
    }

    /**
     * Assign group to code in the table validation codes
     *
     * @param $group
     *  The id of the group
     * @param $code
     *  The code to be assigned to the group
     * @retval boolean
     *  true an success, false on failure
     */
    private function assignGroupToCode($group, $code)
    {
        return (bool) $this->db->insert(
            'codes_groups',
            array(
                'id_groups' => $group,
                'code' => $code
            )
        );
    }

    /**
     * Assign group to user in the table validation codes
     *
     * @param $group
     *  The id of the group
     * @param $userId
     *  The id of the user to be assigned to the group
     * @retval boolean
     *  true an success, false on failure
     */
    private function assignUserToGroup($group, $userId)
    {
        return (bool) $this->db->insert(
            'users_groups',
            array('id_groups' => $group, 'id_users' => $userId)
        );
        return false;
    }

    /**
     * Validate all request parameters and return the results
     *
     * @param $data
     *  The POST data of the callback call:
     *   callbackKey is expected from where the callback is initialized
     * @param $type
     *  the type for which function should be validate the data
     * @retval array
     *  An array with the callback results
     */
    private function validate_callback($data, $type)
    {
        $result['selfhelpCallback'] = [];
        $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_SUCCESS;
        if (!isset($data[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_KEY_VARIABLE]) || $this->db->get_callback_key() !== $data[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_KEY_VARIABLE]) {
            //validation for the callback key; if wrong return not secured
            array_push($result['selfhelpCallback'], 'wrong callback key');
            $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            return $result;
        }
        if ($type == CallbackQualtrics::VALIDATION_add_survey_response) {
            // validate add_survey_response parameters
            $surveyInfo = $this->getSurvey($data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE]);
            if ($surveyInfo['survey_type_code'] !== qualtricsSurveyTypes_anonymous) {
                // validate participent variable only if it is not anonymous
                if (!isset($data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]) || $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] == '') {
                    array_push($result['selfhelpCallback'], 'misisng participant');
                    $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
                } else if (preg_match('/[^A-Za-z0-9]/', $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE])) {
                    array_push($result['selfhelpCallback'], 'wrong participant value (only numbers and laters are possible)');
                    $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
                } else if (!$this->code_exist($data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE])) {
                    //check if the code is in the table validation_codes
                    array_push($result['selfhelpCallback'], 'validation code: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] . ' does not exist');
                    $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
                }
            }
            if (!isset($data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE])) {
                array_push($result['selfhelpCallback'], 'misisng response id');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            }
            if (!isset($data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE])) {
                array_push($result['selfhelpCallback'], 'misisng survey id');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            }
            if (!isset($data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE])) {
                array_push($result['selfhelpCallback'], 'misisng trigger type');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            }
        }
        if ($type == CallbackQualtrics::VALIDATION_set_group) {
            // validate set_group parameters
            if (!isset($data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]) || $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] == '') {
                array_push($result['selfhelpCallback'], 'misisng participant');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            } else if (preg_match('/[^A-Za-z0-9]/', $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE])) {
                array_push($result['selfhelpCallback'], 'wrong participant value (only numbers and laters are possible)');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            } else if (!$this->code_exist($data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE])) {
                //check if the code is in the table validation_codes
                array_push($result['selfhelpCallback'], 'validation code: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] . ' does not exist');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            }
            if (!isset($data[ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE])) {
                array_push($result['selfhelpCallback'], 'misisng group');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            } else if (!preg_match('/^[\w-]+$/', $data[ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE])) {
                array_push($result['selfhelpCallback'], 'wrong group value (only numbers, laters, hyphens and underscores are possible)');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            }
            $result['groupId'] = $this->getGroupId($data[ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE]);
            if (!($result['groupId'] > 0)) {
                // validation for does the group exists
                array_push($result['selfhelpCallback'], 'group does not exist');
                $result['callback_status'] = CALLBACK_ERROR;
            }
        }
        if ($type == CallbackQualtrics::VALIDATION_save_data) {
            // validate save_data parameters
            if (!isset($data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]) || $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] == '') {
                array_push($result['selfhelpCallback'], 'misisng participant');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            } else if (preg_match('/[^A-Za-z0-9]/', $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE])) {
                array_push($result['selfhelpCallback'], 'wrong participant value (only numbers and laters are possible)');
                $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
            }
        }
        return $result;
    }

    /**
     * Add survey response for the user
     *
     * @param $data
     * The POST data of the callback call:
     * QUALTRICS_PARTICIPANT_VARIABLE,
     * QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE,
     * QUALTRICS_CALLBACK_KEY_VARIABLE,
     * QUALTRICS_TRIGGER_TYPE_VARIABLE
     */
    public function add_survey_response($data)
    {
        $start_time = microtime(true);
        $start_date = date("Y-m-d H:i:s");
        $callback_log_id = $this->insert_callback_log($_SERVER, $data);
        $result = $this->validate_callback($data, CallbackQualtrics::VALIDATION_add_survey_response);
        if ($result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] == CallbackQualtrics::CALLBACK_SUCCESS) {
            $surveyInfo = $this->getSurvey($data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE]);
            if ($surveyInfo['survey_type_code'] === qualtricsSurveyTypes_anonymous) {
                // anonymous survey, no user
                $result = array_merge($result, $this->check_functions_from_actions($data));
            } else {
                $user_id = $this->moduleQualtricsSurveyModel->getUserId($data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]);
                if ($user_id > 0) {
                    if ($data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE] === actionTriggerTypes_started) {
                        //insert survey response
                        $inserted_id = $this->insert_survey_response($data, $user_id);
                        if ($inserted_id > 0) {
                            //successfully inserted survey response
                            $result['selfhelpCallback'][] = "Success. Response " . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . " was inserted.";
                            if ($this->is_legacy()) {
                                $result['selfhelpCallback'][] = $this->queue_event_from_actions($data, $user_id);
                                $result = array_merge($result, $this->check_functions_from_actions($data, $user_id));
                            }
                        } else {
                            //something went wrong; survey response was not inserted
                            $result['selfhelpCallback'][] = "Error. Response " . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . " was not inserted.";
                            $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
                        }
                    } else if ($data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE] === actionTriggerTypes_finished) {
                        //update survey response
                        $update_id = $this->update_survey_response($data);
                        $scheduled_reminders = $this->get_scheduled_reminders($user_id, $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE]);
                        $result['selfhelpCallback']["delete_reminders"] = $scheduled_reminders;
                        if ($scheduled_reminders && count($scheduled_reminders) > 0) {
                            $result['selfhelpCallback']["delete_reminders_result"] = $this->delete_reminders($scheduled_reminders);
                        }
                        if ($update_id > 0) {
                            //successfully updated survey response
                            $result['selfhelpCallback'][] = "Success. Response " . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . " was updated.";
                            if ($this->is_legacy()) {
                                $result['selfhelpCallback'][] = $this->queue_event_from_actions($data, $user_id); //legacy actions
                                $result = array_merge($result, $this->check_functions_from_actions($data, $user_id));
                            }
                        } else {
                            //something went wrong; survey resposne was not updated
                            $result['selfhelpCallback'][] = "Error. Response " . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . " was not updated.";
                            $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
                        }
                    }
                }
            }
        }
        $end_time = microtime(true);
        $result['time'] = [];
        $result['time']['exec_time'] = $end_time - $start_time;
        $result['time']['start_date'] = $start_date;        
        if ($result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] == CallbackQualtrics::CALLBACK_SUCCESS) {
            //validation passed; try to execute            
            if ($data[ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE] === actionTriggerTypes_finished) {
                // save the data
                $this->save_qualtrics_response($surveyInfo, $data);

                // legacy qualtrics actions after the data is saved
                $scheduled_reminders = $this->get_scheduled_reminders($user_id, $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE]);
                $result['selfhelpCallback']["delete_reminders"] = $scheduled_reminders;
                if ($scheduled_reminders && count($scheduled_reminders) > 0) {
                    $result['selfhelpCallback']["delete_reminders_result"] = $this->delete_reminders($scheduled_reminders);
                }
                if ($update_id > 0) {
                    //successfully updated survey response
                    $result['selfhelpCallback'][] = "Success. Response " . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . " was updated.";
                    if ($this->is_legacy()) {
                        $result['selfhelpCallback'][] = $this->queue_event_from_actions($data, $user_id); //legacy actions
                        $result = array_merge($result, $this->check_functions_from_actions($data, $user_id));
                    }
                } else {
                    //something went wrong; survey resposne was not updated
                    $result['selfhelpCallback'][] = "Error. Response " . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . " was not updated.";
                    $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CallbackQualtrics::CALLBACK_ERROR;
                }
            }
        }
        $this->update_callback_log($callback_log_id, $result);
        echo json_encode($result);
    }

    /**
     * Add group for the user. If the group does not exist it is created.
     *
     * @param $data
     * QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE,
     * QUALTRICS_CALLBACK_KEY_VARIABLE,
     * QUALTRICS_TRIGGER_TYPE_VARIABLE
     */
    public function set_group($data)
    {
        $callback_log_id = $this->insert_callback_log($_SERVER, $data);
        $result = $this->validate_callback($data, CallbackQualtrics::VALIDATION_set_group);
        if ($result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] == CallbackQualtrics::CALLBACK_SUCCESS) {
            //validation passed; try to execute
            $user_id = $this->moduleQualtricsSurveyModel->getUserId($data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]);
            if ($user_id > 0) {
                // set group for user
                if ($this->assignUserToGroup($result['groupId'], $user_id)) {
                    $log = 'User with code: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] . ' was assigned to group: ' . $result['groupId'] . ' with name: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE];
                    $result['selfhelpCallback'][] = $log;
                    $this->transaction->add_transaction(transactionTypes_insert, transactionBy_by_qualtrics_callback, null, $this->transaction::TABLE_USERS_GROUPS, $user_id, false, $log);
                } else {
                    $result['selfhelpCallback'][] = 'Failed! User with code: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] . ' was not assigned to group: ' . $result['groupId'] . ' with name: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE];
                    $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CALLBACK_ERROR;
                }
            } else {
                // set group for code and once user is registered the group will be assigned
                if ($this->assignGroupToCode($result['groupId'], $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE])) {
                    $log = 'Code: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] . ' was assigned to group: ' . $result['groupId'] . ' with name: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE];
                    $result['selfhelpCallback'][] = $log;
                    $this->transaction->add_transaction(transactionTypes_insert, transactionBy_by_qualtrics_callback, null, $this->transaction::TABLE_CODES_GROUPS, $result['groupId'], false, $log);
                } else {
                    $result['selfhelpCallback'][] = 'Failed! Code: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE] . ' was not assigned to group: ' . $result['groupId'] . ' with name: ' . $data[ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE];
                    $result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] = CALLBACK_ERROR;
                }
            }
        }
        $this->update_callback_log($callback_log_id, $result);
        echo json_encode($result);
    }

    /**
     * Save data for the user. 
     *
     * @param $data
     * QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE,
     * QUALTRICS_CALLBACK_KEY_VARIABLE,
     */
    public function save_data($data)
    {
        echo "deprecated";
        return;
        $callback_log_id = $this->insert_callback_log($_SERVER, $data);
        $result = $this->validate_callback($data, CallbackQualtrics::VALIDATION_save_data);
        if ($result[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS] == CallbackQualtrics::CALLBACK_SUCCESS) {
            //validation passed; try to execute
            $data['id_users'] = $this->moduleQualtricsSurveyModel->getUserId($data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]);
            $fields = json_decode($data[ModuleQualtricsSurveyModel::QUALTRICS_SAVE_DATA]);
            unset($data[ModuleQualtricsSurveyModel::QUALTRICS_SAVE_DATA]);
            foreach ($fields as $key => $field) {
                $data[$key] = $field;
            }
            $moduleQualtrics = new SaveDataModel($this->services);
            $result['insert_into_db'] = $moduleQualtrics->insert_into_db($data);
        }
        $this->update_callback_log($callback_log_id, $result);
        echo json_encode($result);
    }

    /**
     * Get survey resposne via qualtrics api
     * 
     * @param string $survey_api_id 
     * qualtrics survey id
     * @param string $survey_response 
     * survey_response indetifier
     * @retval array with the survey response or false
     */
    private function get_survey_response($surveyInfo, $survey_response)
    {
        $url = str_replace(':survey_api_id', $surveyInfo['qualtrics_survey_id'], ModuleQualtricsSurveyModel::QUALTRICS_API_GET_SET_SURVEY_RESPONSE);
        $url = str_replace(':survey_response', $survey_response, $url);
        $qualtrics_api_key = $this->moduleQualtricsSurveyModel->get_user_qualtrics_api_key($surveyInfo['id_users_last_sync']);
        if (!$qualtrics_api_key || $qualtrics_api_key == '') {
            $this->transaction->add_transaction(transactionTypes_insert, transactionBy_by_qualtrics_callback, null, null, null, false, "no Qualtrics API key");
            return false;
        }
        $this->transaction->add_transaction(transactionTypes_insert, transactionBy_by_qualtrics_callback, null, null, null, false, "Get survey: " . $surveyInfo['qualtrics_survey_id'] . " Response: " . $survey_response . " with API: " . $qualtrics_api_key . " for user: " . $surveyInfo['id_users_last_sync']);
        $data = array(
            "request_type" => "GET",
            "URL" => $url,
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $qualtrics_api_key
            )
        );
        $result = ModuleQualtricsSurveyModel::execute_curl_call($data);
        $result = ($result['meta']['httpStatus'] === ModuleQualtricsSurveyModel::QUALTRICS_API_SUCCESS) ? $result['result'] : false;
        $loops = 0;
        while (!$result) {
            //it takes time for the response to be recorded
            sleep(1);
            $loops++;
            $result = ModuleQualtricsSurveyModel::execute_curl_call($data);
            $result = ($result['meta']['httpStatus'] === ModuleQualtricsSurveyModel::QUALTRICS_API_SUCCESS && isset($result['result'])) ? $result['result'] : false;
            if ($loops > 60) {
                // we wait maximum 1 minute for the response
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * Save the qulatrics data in upload tables
     * @param object $surveyInfo
     * The survey info
     * @param object $survey_response_data
     * The survey data comming from Qualtrics with the web service
     */
    private function save_qualtrics_response($surveyInfo, $survey_response_data)
    {
        $user_id = 1; //guest
        if (isset($survey_response_data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE])) {
            // it is not anonymous, get the user id
            $user_id = $this->moduleQualtricsSurveyModel->getUserId($survey_response_data[ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE]);
        }
        $prep_data = array(
            "responseId" => $survey_response_data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE],
            "id_users" => $user_id
        );
        if ($surveyInfo['save_data']) {
            // save the data only if it is enabled
            $data = $this->get_survey_response($surveyInfo, $survey_response_data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE]);
            $this->transaction->add_transaction(transactionTypes_insert, transactionBy_by_qualtrics_callback, $user_id, $this->transaction::TABLE_dataTables, null, false, $data);
            $prep_data = ModuleQualtricsSurveyModel::prepare_qualtrics_data_for_save($prep_data, $data, $surveyInfo['save_labels_data']);
        }
        $this->user_input->save_data(transactionBy_by_qualtrics_callback, $surveyInfo['qualtrics_survey_id'], $prep_data);
    }
}
?>
