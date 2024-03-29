<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../../component/BaseModel.php";
/**
 * This class is used to prepare all data related to the cmsPreference component such
 * that the data can easily be displayed in the view of the component.
 */
class ModuleQualtricsModel extends BaseModel
{

    /* Constructors ***********************************************************/

    /**
     * The constructor.
     *
     * @param array $services
     *  An associative array holding the differnt available services. See the
     *  class definition BasePage for a list of all services.
     */
    public function __construct($services)
    {
        parent::__construct($services);
    }

    /**
     * Check if the qualtricsActions table exists and return true of exists for some legacy projects
     * @return bool
     * Return if the table qualtricsActions exists
     */
    public function qualtrics_actions_exists(){
        $res = $this->db->query_db_first("SELECT COUNT(*) as res FROM information_schema.`tables`
                                WHERE table_schema = DATABASE()
                                AND `table_name` = 'qualtricsActions'");
        return $res['res'] > 0;
    }

}