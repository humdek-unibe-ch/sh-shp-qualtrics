<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../moduleQualtrics/ModuleQualtricsView.php";
require_once __DIR__ . "/../../../../../component/style/BaseStyleComponent.php";

/**
 * The view class of the asset select component.
 */
class ModuleQualtricsActionView extends ModuleQualtricsView
{

    /* Private Properties *****************************************************/
    /**
     * action id,
     * if it is > 0  edit/delete action page     
     */
    private $aid;

    /**
     * The mode type of the form EDIT, DELETE, INSERT, VIEW     
     */
    private $mode;

    /**
     * the current selected action
     */
    private $action;

    /**
     * all actions
     */
    private $actions;

    /* Constructors ***********************************************************/

    /**
     * The constructor.
     *
     * @param object $model
     *  The model instance of the component.
     */
    public function __construct($model, $controller, $aid, $mode)
    {
        parent::__construct($model, $controller);
        $this->aid = $aid;
        $this->mode = $mode;
        $this->action = $this->model->get_services()->get_db()->select_by_uid("view_qualtricsActions", $this->aid);
        $this->actions = $this->model->get_actions();
        if ($this->action) {
            $this->action['schedule_info'] = json_decode($this->action['schedule_info'], true);
            if (isset($this->action['schedule_info']['config'])) {
                $this->action['schedule_info']['config'] = json_encode($this->action['schedule_info']['config'], JSON_PRETTY_PRINT);
            }
        }
    }

    /* Private Methods ********************************************************/

    /**
     * Render the asset list.
     *
     * @param string $mode
     *  Specifies the insert mode (either 'css' or 'asset').
     */
    private function output($mode)
    {
        echo $mode;
    }

    /**
     * get time intervals from 0 to 60
     *
     *  @retval array
     *  value int,
     *  text string
     */
    private function get_time_intervals()
    {
        $arr = array();
        foreach (range(1, 60) as $range) {
            array_push($arr, array("value" => $range, "text" => $range));
        }
        return $arr;
    }

    /**
     * get time intervals from first to tenth
     *
     *  @retval array
     *  value int,
     *  text string
     */
    private function get_time_intervals_text()
    {
        $arr = array();
        array_push($arr, array("value" => 1, "text" => '1st'));
        array_push($arr, array("value" => 2, "text" => '2nd'));
        array_push($arr, array("value" => 3, "text" => '3rd'));
        foreach (range(4, 20) as $range) {
            array_push($arr, array("value" => $range, "text" => $range . 'th'));
        }
        return $arr;
    }

    /**
     * get notification card.
     *
     *  @retval card
     */
    private function get_schedule_info_card()
    {
        return new BaseStyleComponent("card", array(
            "css" => "mb-3 d-none",
            "is_expanded" => true,
            "is_collapsible" => true,
            "id" => "schedule_info",
            "title" => 'Schedule info',
            "children" => array(
                new BaseStyleComponent("select", array(
                    "label" => "Reminder for survey",
                    "value" => isset($this->action['id_qualtricsSurveys_reminder']) ? $this->action['id_qualtricsSurveys_reminder'] : "",
                    "is_required" => true,
                    "name" => "id_qualtricsSurveys_reminder",
                    "id" => "id_qualtricsSurveys_reminder",
                    "css" => "d-none",
                    "items" => $this->model->get_surveys(),
                    "live_search" => true
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Reminder for notification (Needed for surveys with sessions and multiple block schedules)",
                    "value" => isset($this->action['id_qualtricsActions']) ? $this->action['id_qualtricsActions'] : "",
                    "is_required" => false,
                    "name" => "id_qualtricsActions",
                    "id" => "id_qualtricsActions",
                    "css" => "d-none",
                    "items" => $this->model->get_notifications(),
                    "live_search" => true
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Type",
                    "is_required" => true,
                    "id" => "type",
                    "value" => isset($this->action["schedule_info"][notificationTypes]) ? $this->action["schedule_info"][notificationTypes] : '',
                    "name" => "schedule_info[notificationTypes]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('lookups', 'lookup_code', array('lookup_value'), 'WHERE type_code=:tcode', array(":tcode" => notificationTypes))
                )),
                new BaseStyleComponent("input", array(
                    "label" => "Valid after scheduled time (in minutes). [It is used for surveys with multiple sessions and reminders]",
                    "is_required" => false,
                    "id" => "valid",
                    "css" => "d-none",
                    "value" => isset($this->action["schedule_info"]["valid"]) ? $this->action["schedule_info"]["valid"] : '',
                    "name" => "schedule_info[valid]",
                    "type_input" => "number"
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Target group(s)",
                    "name" => "schedule_info[target_groups]",
                    "id" => "targetGroups",
                    "is_multiple" => true,
                    "is_required" => false,
                    "live_search" => true,
                    "value" => isset($this->action["schedule_info"]['target_groups']) ? $this->action["schedule_info"]['target_groups'] : '',
                    "items" => $this->model->get_groups(),
                    "css" => "mb-3",
                )),
                new BaseStyleComponent("select", array(
                    "label" => "When",
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"][actionScheduleTypes]) ? $this->action["schedule_info"][actionScheduleTypes] : '',
                    "name" => "schedule_info[actionScheduleTypes]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('lookups', 'lookup_code', array('lookup_value'), 'WHERE type_code=:tcode', array(":tcode" => actionScheduleTypes))
                )),
                new BaseStyleComponent("template", array(
                    "path" => __DIR__ . "/tpl_datepicker.php",
                    "items" => array(
                        "name" => 'schedule_info[custom_time]',
                        "value" => isset($this->action["schedule_info"]['custom_time']) ? $this->action["schedule_info"]['custom_time'] : '',
                        "id" => "custom_time"
                    )
                )),
                new BaseStyleComponent("input", array(
                    "label" => "Send After",
                    "css" => 'send_after d-none',
                    "id" => "send_after",
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"]['send_after']) ? $this->action["schedule_info"]['send_after'] : '',
                    "name" => "schedule_info[send_after]",
                    // "items" => $this->get_time_intervals(),
                    "type_input" => "number"
                )),
                new BaseStyleComponent("select", array(
                    "id" => "send_after_type",
                    "css" => 'd-none',
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"]['send_after_type']) ? $this->action["schedule_info"]['send_after_type'] : '',
                    "name" => "schedule_info[send_after_type]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('lookups', 'lookup_code', array('lookup_value'), 'WHERE type_code=:tcode', array(":tcode" => timePeriod))
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Send on",
                    "css" => 'd-none',
                    "id" => "send_on",
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"]['send_on']) ? $this->action["schedule_info"]['send_on'] : '',
                    "name" => "schedule_info[send_on]",
                    "items" => $this->get_time_intervals_text(),
                )),
                new BaseStyleComponent("select", array(
                    "id" => "send_on_day",
                    "css" => 'd-none mb-3',
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"]['send_on_day']) ? $this->action["schedule_info"]['send_on_day'] : '',
                    "name" => "schedule_info[send_on_day]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('lookups', 'lookup_code', array('lookup_value'), 'WHERE type_code=:tcode', array(":tcode" => weekdays))
                )),
                new BaseStyleComponent("template", array(
                    "path" => __DIR__ . "/tpl_timepicker.php",
                    "items" => array(
                        "name" => 'schedule_info[send_on_day_at]',
                        "label" => "At",
                        "value" => isset($this->action["schedule_info"]['send_on_day_at']) ? $this->action["schedule_info"]['send_on_day_at'] : '',
                        "id" => "send_on_day_at"
                    )
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Reminder for notification",
                    "is_required" => true,
                    "id" => "linked_action",
                    "value" => isset($this->action["schedule_info"]['linked_action']) ? $this->action["schedule_info"]['linked_action'] : '',
                    "name" => "schedule_info[linked_action]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('view_qualtricsActions', 'id', array('action_name'), 'WHERE action_schedule_type_code=:type', array(":type" => actionScheduleJobs_notification)),
                    "live_search" => 1
                )),
                new BaseStyleComponent("input", array(
                    "label" => "From email",
                    "type_input" => "email",
                    "css" => "mt-3",
                    "id" => "from_email",
                    "name" => "schedule_info[from_email]",
                    "value" => isset($this->action["schedule_info"]['from_email']) ? $this->action["schedule_info"]['from_email'] : '',
                    "is_required" => true,
                    "placeholder" => "From email",
                )),
                new BaseStyleComponent("input", array(
                    "label" => "From name",
                    "type_input" => "text",
                    "id" => "from_name",
                    "name" => "schedule_info[from_name]",
                    "value" => isset($this->action["schedule_info"]['from_name']) ? $this->action["schedule_info"]['from_name'] : '',
                    "is_required" => true,
                    "placeholder" => "From name",
                )),
                new BaseStyleComponent("input", array(
                    "label" => "Reply To",
                    "type_input" => "email",
                    "id" => "reply_to",
                    "name" => "schedule_info[reply_to]",
                    "value" => isset($this->action["schedule_info"]['reply_to']) ? $this->action["schedule_info"]['reply_to'] : '',
                    "is_required" => true,
                    "placeholder" => "reply to email",
                )),
                new BaseStyleComponent("input", array(
                    "label" => "URL",
                    "type_input" => "text",
                    "id" => "url",
                    "name" => "schedule_info[url]",
                    "value" => isset($this->action["schedule_info"]['url']) ? $this->action["schedule_info"]['url'] : '',
                    "is_required" => false,
                    "placeholder" => "Url of the page that should be opened",
                )),
                new BaseStyleComponent("input", array(
                    "label" => "To",
                    "type_input" => "text",
                    "id" => "to",
                    "name" => "schedule_info[recipient]",
                    "value" => isset($this->action["schedule_info"]['recipient']) ? $this->action["schedule_info"]['recipient'] : '',
                    "css" => "mt-3",
                    "is_required" => true,
                    "placeholder" => "Please enter the recipient(s). Use @user to retrive automaticaly phone or email. Use " . MAIL_SEPARATOR . " as separator",
                )),
                new BaseStyleComponent("input", array(
                    "label" => "Subject",
                    "type_input" => "text",
                    "id" => "subject",
                    "name" => "schedule_info[subject]",
                    "value" => isset($this->action["schedule_info"]['subject']) ? $this->action["schedule_info"]['subject'] : '',
                    "css" => "mt-3",
                    "is_required" => true,
                    "placeholder" => "Please enter the subject",
                )),
                new BaseStyleComponent("textarea", array(
                    "label" => "Body",
                    "type_input" => "text",
                    "name" => "schedule_info[body]",
                    "id" => "body",
                    "value" => isset($this->action["schedule_info"]['body']) ? $this->action["schedule_info"]['body'] : '',
                    "css" => "mb-3",
                    "placeholder" => "@user_name can be used for showing the user",
                )),
                new BaseStyleComponent("textarea", array(
                    "label" => "Attachments",
                    "type_input" => "json",
                    "name" => "schedule_info[attachments]",
                    "id" => "attachments",
                    "value" => isset($this->action["schedule_info"]['attachments']) ? $this->action["schedule_info"]['attachments'] : '',
                    "css" => "mb-3",
                    "placeholder" => "Add attachment files from assets in array",
                )),
                new BaseStyleComponent("ActionConfigBuilder", array(
                    "label" => "Config",
                    "type_input" => "json",
                    "id" => "config",
                    "name" => "schedule_info[config]",
                    "value" => isset($this->action["schedule_info"]['config']) ? $this->action["schedule_info"]['config'] : '',
                    "css" => "mb-3 actionConfig",
                    "placeholder" => "",
                ))
            )
        ));
    }

    /**
     * get notification card view.
     *
     *  @retval card
     */
    private function get_schedule_info_card_view()
    {
        return new BaseStyleComponent("card", array(
            "css" => "mb-3",
            "id" => "schedule_info",
            "is_expanded" => true,
            "is_collapsible" => true,
            "title" => 'Schedule info',
            "children" => array(
                new BaseStyleComponent("select", array(
                    "label" => "Reminder for survey",
                    "value" => isset($this->action['id_qualtricsSurveys_reminder']) ? $this->action['id_qualtricsSurveys_reminder'] : "",
                    "is_required" => true,
                    "name" => "id_qualtricsSurveys_reminder",
                    "id" => "id_qualtricsSurveys_reminder",
                    "css" => "d-none",
                    "items" => $this->model->get_surveys(),
                    "disabled" => true
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Reminder for notification (Needed for surveys with sessions and multiple block schedules)",
                    "value" => isset($this->action['id_qualtricsActions']) ? $this->action['id_qualtricsActions'] : "",
                    "is_required" => false,
                    "name" => "id_qualtricsActions",
                    "id" => "id_qualtricsActions",
                    "css" => "d-none",
                    "items" => $this->model->get_notifications(),
                    "disabled" => true
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Type",
                    "is_required" => true,
                    "id" => "type",
                    "value" => isset($this->action["schedule_info"][notificationTypes]) ? $this->action["schedule_info"][notificationTypes] : '',
                    "name" => "schedule_info[notificationTypes]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('lookups', 'lookup_code', array('lookup_value'), 'WHERE type_code=:tcode', array(":tcode" => notificationTypes)),
                    "disabled" => true
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Valid after scheduled time (in minutes). [It is used for surveys with multiple sessions and reminders]",
                    "id" => "valid",
                    "locale" => "",
                    "css" => "d-none mt-3",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['valid']) ? $this->action["schedule_info"]['valid'] : ''
                    ))),
                )),
                new BaseStyleComponent("select", array(
                    "label" => "When",
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"][actionScheduleTypes]) ? $this->action["schedule_info"][actionScheduleTypes] : '',
                    "name" => "schedule_info[actionScheduleTypes]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('lookups', 'lookup_code', array('lookup_value'), 'WHERE type_code=:tcode', array(":tcode" => actionScheduleTypes)),
                    "disabled" => true
                )),
                new BaseStyleComponent("template", array(
                    "path" => __DIR__ . "/tpl_datepicker.php",
                    "items" => array(
                        "name" => 'schedule_info[custom_time]',
                        "value" => isset($this->action["schedule_info"]['custom_time']) ? $this->action["schedule_info"]['custom_time'] : '',
                        "disabled" => "disabled",
                        "id" => "custom_time"
                    )
                )),
                // new BaseStyleComponent("input", array(
                //     "label" => "Send After",
                //     "css" => 'send_after d-none',
                //     "id" => "send_after",
                //     "is_required" => true,
                //     "value" => isset($this->action["schedule_info"]['send_after']) ? $this->action["schedule_info"]['send_after'] : '',
                //     "name" => "schedule_info[send_after]",
                //     // "items" => $this->get_time_intervals(),
                //     "type" => "numbe",
                //     "disabled" => true
                // )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Send After",
                    "id" => "send_after",
                    "locale" => "",
                    "css" => "send_after d-none",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['send_after']) ? $this->action["schedule_info"]['send_after'] : ''
                    ))),
                )),
                new BaseStyleComponent("select", array(
                    "id" => "send_after_type",
                    "css" => 'd-none',
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"]['send_after_type']) ? $this->action["schedule_info"]['send_after_type'] : '',
                    "name" => "schedule_info[send_after_type]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('lookups', 'lookup_code', array('lookup_value'), 'WHERE type_code=:tcode', array(":tcode" => timePeriod)),
                    "disabled" => true
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Send on",
                    "css" => 'd-none',
                    "id" => "send_on",
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"]['send_on']) ? $this->action["schedule_info"]['send_on'] : '',
                    "name" => "schedule_info[send_on]",
                    "items" => $this->get_time_intervals_text(),
                    "disabled" => true
                )),
                new BaseStyleComponent("select", array(
                    "id" => "send_on_day",
                    "css" => 'd-none mb-3',
                    "is_required" => true,
                    "value" => isset($this->action["schedule_info"]['send_on_day']) ? $this->action["schedule_info"]['send_on_day'] : '',
                    "name" => "schedule_info[send_on_day]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('lookups', 'lookup_code', array('lookup_value'), 'WHERE type_code=:tcode', array(":tcode" => weekdays)),
                    "disabled" => true
                )),
                new BaseStyleComponent("template", array(
                    "path" => __DIR__ . "/tpl_timepicker.php",
                    "items" => array(
                        "name" => 'schedule_info[send_on_day_at]',
                        "label" => "At",
                        "id" => "send_on_day_at",
                        "value" => isset($this->action["schedule_info"]['send_on_day_at']) ? $this->action["schedule_info"]['send_on_day_at'] : '',
                        "disabled" => "disabled",
                    )
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Reminder for notification",
                    "is_required" => true,
                    "id" => "linked_action",
                    "value" => isset($this->action["schedule_info"]['linked_action']) ? $this->action["schedule_info"]['linked_action'] : '',
                    "name" => "schedule_info[linked_action]",
                    "items" => $this->model->get_services()->get_db()->fetch_table_as_select_values('qualtricsActions', 'id', array('name')),
                    "disabled" => true
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "From Email",
                    "id" => "from_email",
                    "locale" => "",
                    "css" => "mt-3",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['from_email']) ? $this->action["schedule_info"]['from_email'] : ''
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "URL",
                    "locale" => "",
                    "id" => "url",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['url']) ? $this->action["schedule_info"]['url'] : ''
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "From Name",
                    "locale" => "",
                    "id" => "from_name",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['from_name']) ? $this->action["schedule_info"]['from_name'] : ''
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Reply To",
                    "locale" => "",
                    "id" => "reply_to",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['reply_to']) ? $this->action["schedule_info"]['reply_to'] : ''
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "To",
                    "locale" => "",
                    "css" => "mt-3",
                    "id" => "to",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['recipient']) ? $this->action["schedule_info"]['recipient'] : ''
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Subject",
                    "locale" => "",
                    "id" => "subject",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['subject']) ? $this->action["schedule_info"]['subject'] : ''
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Body",
                    "locale" => "",
                    "id" => "body",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['body']) ? $this->action["schedule_info"]['body'] : ''
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Attachments",
                    "locale" => "",
                    "id" => "attachments",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['attachments']) ? $this->action["schedule_info"]['attachments'] : ''
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Config",
                    "locale" => "",
                    "id" => "config",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => isset($this->action["schedule_info"]['config']) ? $this->action["schedule_info"]['config'] : ''
                    ))),
                ))
            )
        ));
    }

    /**
     * Render the delete form
     */
    private function output_delete_action()
    {
        $form = new BaseStyleComponent("card", array(
            "css" => "mb-3",
            "is_expanded" => false,
            "is_collapsible" => true,
            "title" => "Delete Action",
            "type" => "danger",
            "children" => array(
                new BaseStyleComponent("plaintext", array(
                    "text" => "You must be absolutely certain that this is what you want. This operation cannot be undone! To verify, enter the action name.",
                    "is_paragraph" => true,
                )),
                new BaseStyleComponent("form", array(
                    "id" => 'deleteForm',
                    "label" => "Delete Action",
                    "url" => $this->model->get_link_url("moduleQualtricsAction"),
                    "type" => "danger",
                    "children" => array(
                        new BaseStyleComponent("input", array(
                            "type_input" => "text",
                            "name" => "deleteActionName",
                            "is_required" => true,
                            "css" => "mb-3",
                            "placeholder" => "Enter action name",
                        )),
                        new BaseStyleComponent("input", array(
                            "type_input" => "hidden",
                            "name" => "deleteActionId",
                            "value" => $this->aid,
                        )),
                        new BaseStyleComponent("input", array(
                            "type_input" => "hidden",
                            "name" => "mode",
                            "value" => DELETE
                        )),
                    )
                )),
            )
        ));
        $form->output_content();
    }

    private function get_lookup($id)
    {
        return $this->model->get_services()->get_db()->select_by_uid("lookups", $id)['lookup_value'];
    }

    /* Public Methods *********************************************************/

    /**
     * Render the footer view.
     */
    public function output_content()
    {
        require __DIR__ . "/../moduleQualtrics/tpl_moduleQualtrics.php";
    }

    public function output_content_mobile()
    {
        echo 'mobile';
    }

    /**
     * render the page content
     */
    public function output_page_content()
    {
        if ($this->mode === SELECT && $this->aid > 0) {
            require __DIR__ . "/tpl_moduleQualtricsAction.php";
        }else if ($this->mode === UPDATE && $this->aid > 0) {
            require __DIR__ . "/tpl_moduleQualtricsAction.php";
        } else if ($this->mode === INSERT) {
            require __DIR__ . "/tpl_moduleQualtricsAction.php";
        } else {
            require __DIR__ . "/tpl_moduleQualtricsActions.php";
        }
    }

    /**
     * Render the add action entry form.
     */
    public function output_add_action()
    {
        $form = new BaseStyleComponent("card", array(
            "css" => "mb-3",
            "is_expanded" => true,
            "is_collapsible" => false,
            "type" => "warning",
            "title" => $this->mode === INSERT ? 'Add action ' : ('Update action ' . $this->action['id']),
            "children" => array(
                new BaseStyleComponent("form", array(
                    "id" => 'entryForm',
                    "label" => $this->mode === INSERT ? 'Add' : 'Update',
                    "url" => $this->model->get_link_url("moduleQualtricsAction"),
                    "url_cancel" => $this->model->get_link_url("moduleQualtricsAction"),
                    "label_cancel" => 'Cancel',
                    "url_type" => 'warning',
                    "type" => $this->mode === INSERT ? 'warning' : 'primary',
                    "children" => array(

                        new BaseStyleComponent("input", array(
                            "label" => "Action name",
                            "type_input" => "text",
                            "name" => "name",
                            "value" => $this->action ? $this->action['action_name'] : '',
                            "is_required" => true,
                            "css" => "mb-3",
                            "placeholder" => "Enter action name",
                        )),
                        new BaseStyleComponent("select", array(
                            "label" => "When survey",
                            "value" => $this->action ? $this->action['survey_id'] : '',
                            "is_required" => true,
                            "live_search" => true,
                            "name" => "id_qualtricsSurveys",
                            "items" => $this->model->get_surveys(),
                        )),
                        new BaseStyleComponent("select", array(
                            "label" => "Is (trigger type)",
                            "value" => $this->action ? $this->action['id_qualtricsProjectActionTriggerTypes'] : '',
                            "is_required" => true,
                            "name" => "id_qualtricsProjectActionTriggerTypes",
                            "items" => $this->get_lookups(actionTriggerTypes),
                        )),
                        new BaseStyleComponent("select", array(
                            "label" => "For group(s)",
                            "name" => "id_groups",
                            "is_multiple" => true,
                            "is_required" => false,
                            "live_search" => true,
                            "value" => $this->action && $this->action['id_groups'] ? explode(',', $this->action['id_groups']) : '',
                            "items" => $this->model->get_groups(),
                            "css" => "mb-3",
                        )),
                        new BaseStyleComponent("select", array(
                            "label" => "Schedule",
                            "name" => "id_qualtricsActionScheduleTypes",
                            "id" => "id_qualtricsActionScheduleTypes",
                            "value" => isset($this->action['id_qualtricsActionScheduleTypes']) ? $this->action['id_qualtricsActionScheduleTypes'] : $this->model->get_services()->get_db()->get_lookup_id_by_value(actionScheduleJobs, 'nothing'),
                            "items" => $this->get_lookups(actionScheduleJobs),
                            "css" => "mb-3",
                        )),
                        new BaseStyleComponent("select", array(
                            "label" => "Additional functions",
                            "is_required" => false,
                            "name" => "id_functions",
                            "is_multiple" => true,
                            "live_search" => true,
                            "value" => $this->action && $this->action['id_functions'] ?  explode(';', $this->action['id_functions']) : '',
                            "items" => $this->get_lookups(qualtricsProjectActionAdditionalFunction),
                        )),
                        $this->get_schedule_info_card(),
                        new BaseStyleComponent("input", array(
                            "type_input" => "hidden",
                            "name" => "id",
                            "value" => $this->aid,
                        )),
                        new BaseStyleComponent("input", array(
                            "type_input" => "hidden",
                            "name" => "mode",
                            "value" => $this->mode
                        ))
                    )
                )),
            )
        ));
        $form->output_content();
    }

    /**
     * Render the add action entry form view.
     */
    public function output_add_action_view()
    {
        $form = new BaseStyleComponent("card", array(
            "css" => "mb-3",
            "is_expanded" => true,
            "is_collapsible" => false,
            "url_edit" => $this->model->get_link_url("moduleQualtricsAction", array("mode" => UPDATE, "aid" => $this->aid)),
            "title" => 'Action &nbsp;<code>' . $this->action['action_name'] . '</code>',
            "children" => array(
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Action name",
                    "locale" => "",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => $this->action['action_name']
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "When survey",
                    "locale" => "",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => $this->action['survey_name']
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Is (trigger type)",
                    "locale" => "",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => $this->action['trigger_type']
                    ))),
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "For groups",
                    "locale" => "",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => $this->action['groups']
                    ))),
                )),
                new BaseStyleComponent("select", array(
                    "label" => "Schedule",
                    "name" => "id_qualtricsActionScheduleTypes",
                    "id" => "id_qualtricsActionScheduleTypes",
                    "value" => isset($this->action['id_qualtricsActionScheduleTypes']) ? $this->action['id_qualtricsActionScheduleTypes'] : $this->model->get_services()->get_db()->get_lookup_id_by_value(actionScheduleJobs, 'nothing'),
                    "items" => $this->get_lookups(actionScheduleJobs),
                    "css" => "mb-3",
                    "disabled" => true
                )),
                new BaseStyleComponent("descriptionItem", array(
                    "title" => "Additional functions",
                    "locale" => "",
                    "children" => array(new BaseStyleComponent("rawText", array(
                        "text" => $this->action['functions']
                    ))),
                )),
                $this->get_schedule_info_card_view()
            )
        ));
        $form->output_content();
    }

    /**
     * Get js include files required for this component. This overrides the
     * parent implementation.
     *
     * @retval array
     *  An array of js include files the component requires.
     */
    public function get_js_includes($local = array())
    {
        if (empty($local)) {
            if (DEBUG) {
                $local = array(__DIR__ . "/js/qualtricsAction.js", __DIR__ . "/../moduleQualtricsProject/js/qualtricsProjects.js");
            } else {
                $local = array(__DIR__ . "/../../../../qualtrics/js/ext/qualtrics.min.js?v=" . rtrim(shell_exec("git describe --tags")));
            }
        }
        return parent::get_js_includes($local);
    }

    /**
     * Render the sidebar buttons
     */
    public function output_side_buttons()
    {
        if ($this->aid) {
            $backToProject = new BaseStyleComponent("button", array(
                "label" => "Back to actions",
                "url" => $this->model->get_link_url("moduleQualtricsAction"),
                "type" => "secondary",
                "css" => "d-block mb-3",
            ));
            $backToProject->output_content();
        } 
    }

    /**
     * Render the qualtrics actions table content for the selected project.
     */
    protected function output_actions_rows()
    {
        foreach ($this->actions as $action) {
            require __DIR__ . "/tpl_qualtricsActionsRow.php";
        }
    }
}
?>
