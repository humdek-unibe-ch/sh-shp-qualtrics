<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../moduleQualtrics/ModuleQualtricsModel.php";

/**
 * This class is used to prepare all data related to the cmsPreference component such
 * that the data can easily be displayed in the view of the component.
 */
class ModuleQualtricsProjectModel extends ModuleQualtricsModel
{
    /* Callback result variables */

    /* Constructors ***********************************************************/

    /* Private Properties *****************************************************/
    /**
     * project id,
     */
    private $pid;

    /**
     * project object
     */
    private $project;

    /**
     * The constructor.
     *
     * @param array $services
     *  An associative array holding the differnt available services. See the
     *  class definition BasePage for a list of all services.
     */
    public function __construct($services, $pid)
    {
        parent::__construct($services);
        $this->pid = $pid;
        $this->project = $this->db->select_by_uid("qualtricsProjects", $this->pid);
    }

    /**
     * PUBLIC METHODS *************************************************************************************************************
     */

    /**
     * Insert a new qualtrics project to the DB.
     *
     * @param array $data
     *  name, description, api_mailing_group_id
     * @retval int
     *  The id of the new project or false if the process failed.
     */
    public function insert_new_project($data)
    {
        return $this->db->insert("qualtricsProjects", array(
            "name" => $data['name'],
            "description" => $data['description'],
            "api_library_id" => $data['api_library_id'],
            "api_mailing_group_id" => $data['api_mailing_group_id']
        ));
    }

    /**
     * Update qualtrics project.
     *
     * @param array $data
     *  id, name, description, api_mailing_group_id
     * @retval int
     *  The number of the updated rows
     */
    public function update_project($data)
    {
        return $this->db->update_by_ids(
            "qualtricsProjects",
            array(
                "name" => $data['name'],
                "description" => $data['description'],
                "api_library_id" => $data['api_library_id'],
                "api_mailing_group_id" => $data['api_mailing_group_id']
            ),
            array('id' => $data['id'])
        );
    }

    /**
     * Fetch all qualtrics projects from the database
     *
     * @retval array $project
     * id
     * name
     * description
     * api_mailing_group_id
     */
    public function get_projects()
    {
        return $this->db->select_table('qualtricsProjects');
    }

    /**
     * get db
     */
    public function get_db()
    {
        return $this->db;
    }    

    /**
     * Get all the actions for the project that should be synced, with distinct
     * @param int $pid
     * project id
     * @retval array $actions
     */
    public function get_actions_for_sync($pid)
    {
        $sql = "SELECT distinct project_id, api_mailing_group_id, survey_id, survey_name, qualtrics_survey_id,
                id_qualtricsSurveyTypes, group_variable, survey_type, survey_type_code, functions_code, trigger_type_code
                FROM view_qualtricsActions
                WHERE project_id = :pid";
        return $this->db->query_db($sql, array(":pid" => $pid));
    }

    /**
     * Get all the actions for the project that should be synced, with distinct
     * @param int $pid
     * project id
     * @param int $aid
     * action id
     * @retval array $actions
     */
    public function get_action_for_sync($pid, $aid)
    {
        $sql = "SELECT distinct project_id, api_mailing_group_id, survey_id, survey_name, qualtrics_survey_id,
                id_qualtricsSurveyTypes, group_variable, survey_type, survey_type_code, functions_code, trigger_type_code
                FROM view_qualtricsActions
                WHERE project_id = :pid AND id = :aid";
        return $this->db->query_db($sql, array(":pid" => $pid, ":aid" => $aid));
    }    

    public function get_project()
    {
        return $this->project;
    }
}
