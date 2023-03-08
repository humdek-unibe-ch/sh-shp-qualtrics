<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../../component/BaseModel.php";
require_once __DIR__ . "/../../component/moduleQualtricsSurvey/ModuleQualtricsSurveyModel.php";
/**
 * This class extrat fields if they are configured and save them in SelfHelp DB
 */
class SaveDataModel extends BaseModel
{

    /* Private Properties *****************************************************/


    /* Constructors ***********************************************************/

    /**
     * The constructor.
     *
     * @param array $services
     *  An associative array holding the differnt available services. See the
     *  class definition BasePage for a list of all services.
     * @param array $survey_response
     * An array with the result comming from a qualtrics survey
     * @param int $uid 
     * user_id
     * @param string $qualtrics_survey_id
     * qualtrics survey id from Qualtrics
     */
    public function __construct($services)
    {
        parent::__construct($services);
    }

    /* Private Methods ***********************************************************/


    /* Public Methods *********************************************************/

    /**
     * Insert the generated data in the static tables. It should be one row.     
     * @param array $data 
     * the data that will be used to insert the row
     * @return string or false 
     * the result of the execution
     */
    public function insert_into_db($data)
    {
        $table_name = $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE]; //suevey code id is used as table name
        $id_table = $this->services->get_user_input()->get_form_id($table_name, FORM_EXTERNAL);
        if ($id_table) {
            // if table exist; check if the entry exist already; if the repsonse was delayed and qualtrics sent multiple callbacks
            $filter = "AND " . ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE . " = '" . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . "'";
            $entry = $this->services->get_user_input()->get_data($id_table, $filter, false, FORM_EXTERNAL);
            if ($entry) {
                return "Response: " . $data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE] . ' was already added to DB';
            }
        }
        unset($data[ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE]);
        unset($data[ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_KEY_VARIABLE]);
        try {
            $this->db->begin_transaction();
            if (!$id_table) {
                // does not exists yet; try to create it
                $id_table = $this->db->insert("uploadTables", array(
                    "name" => $table_name
                ));
            }
            if (!$id_table) {
                $this->db->rollback();
                return "postprocess: failed to create new data table";
            } else {
                if ($this->transaction->add_transaction(transactionTypes_insert, transactionBy_by_qualtrics_callback, null, $this->transaction::TABLE_uploadTables, $id_table) === false) {
                    $this->db->rollback();
                    return false;
                }
                $id_row = $this->db->insert("uploadRows", array(
                    "id_uploadTables" => $id_table
                ));
                if (!$id_row) {
                    $this->db->rollback();
                    return "postprocess: failed to add table rows";
                }
                foreach ($data as $col => $value) {
                    $id_col = $this->db->insert("uploadCols", array(
                        "name" => $col,
                        "id_uploadTables" => $id_table
                    ));
                    if (!$id_col) {
                        $this->db->rollback();
                        return "postprocess: failed to add table cols";
                    }
                    $res = $this->db->insert(
                        "uploadCells",
                        array(
                            "id_uploadRows" => $id_row,
                            "id_uploadCols" => $id_col,
                            "value" => $value
                        )
                    );
                    if (!$res) {
                        $this->db->rollback();
                        return "postprocess: failed to add data values";
                    }
                }
            }
            $this->db->commit();
            return 'Response for user : ' . $data['id_users'] . ' was successfully inserted in DB';
        } catch (Exception $e) {
            $this->db->rollback();
            return 'Error while inserting records in the uploadTables';
        }
    }
}
