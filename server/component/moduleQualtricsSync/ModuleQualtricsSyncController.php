<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../../component/BaseController.php";
/**
 * The controller class of the group insert component.
 */
class ModuleQualtricsSyncController extends BaseController
{
    /* Private Properties *****************************************************/


    /* Constructors ***********************************************************/

    /**
     * The constructor.
     *
     * @param object $model 
     *  The model instance of the component.
     * @param int $sid
     * survey id
     */
    public function __construct($model, $sid)
    {
        parent::__construct($model);
        if (isset($_POST['mode']) && isset($_POST['type'])) {
            if (!$this->check_acl($_POST['mode'])) {
                $this->fail = true;
                $this->error_msgs[] = "Cannot synchronize this project with Qualtrics. Permission denied.";
                return;
            }
            if ($sid) {
                $this->syncSurvey($sid);
            } else {
                $this->syncSurveys();
            }
        }
    }

    /**
     * Check the acl for the current user and the current page
     * @retval bool
     * true if access is granted, false otherwise.
     */
    private function check_acl($mode)
    {
        if (!$this->model->get_services()->get_acl()->has_access($_SESSION['id_user'], $this->model->get_services()->get_db()->fetch_page_id_by_keyword("moduleQualtricsSync"), $mode)) {
            $this->fail = true;
            $this->error_msgs[] = "You dont have rights to synchronize this project";
            return false;
        } else {
            return true;
        }
    }

    /**
     * synchronize all surveys  with  Qualtrics
     */
    private function syncSurveys()
    {
        foreach ($this->model->get_surveys() as $survey) {
            $res = $this->model->syncSurvey($survey);
            if ($res['result']) {
                $this->success = true;
                $this->success_msgs[] = 'Survey ' . $survey['name'] . ': ' . $res['description'];
            } else {
                $this->fail = true;
                $this->error_msgs[] = $res['description'];
            }
        }
    }

    /**
     * synchronize the selected survey with  Qualtrics
     * @param int $sid
     *  Survey id
     */
    private function syncSurvey($sid)
    {
        $survey = $this->model->get_survey($sid);
        if(!$survey){
            $this->fail = true;
            $this->error_msgs[] = 'No survey';
            return;
        }
        $res = $this->model->syncSurvey($survey);
        if ($res['result']) {
            $this->success = true;
            $this->success_msgs[] = 'Survey ' . $survey['name'] . ': ' . $res['description'];
        } else {
            $this->fail = true;
            $this->error_msgs[] = $res['description'];
        }
    }

    /* Public Methods *********************************************************/
}
?>
