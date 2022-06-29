<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../component/BaseHooks.php";
require_once __DIR__ . "/../../../../component/style/BaseStyleComponent.php";

/**
 * The class to define the hooks for the plugin.
 */
class QualtricsHooks extends BaseHooks
{
    /* Constructors ***********************************************************/

    /**
     * The constructor creates an instance of the hooks.
     * @param object $services
     *  The service handler instance which holds all services
     * @param object $params
     *  Various params
     */
    public function __construct($services, $params = array())
    {
        parent::__construct($services, $params);
    }

    /* Public Methods *********************************************************/

    /**
     * Return a BaseStyleComponent object
     * @return object
     * Return a BaseStyleComponent object
     */
    public function outputStyleField()
    {
        return new BaseStyleComponent("select", array(
            "value" => $this->params['field']['content'],
            "name" => $this->params['field_name_prefix'] . "[content]",
            "max" => 10,
            "live_search" => 1,
            "is_required" => 1,
            "disabled"=> $this->params['disabled'],
            "items" => $this->db->fetch_table_as_select_values('qualtricsSurveys', 'id', array('name', 'qualtrics_survey_id'))
        ));
    }

    /**
     * Get csp rules for Qualtrics     
     * @return string
     * Return csp_rules
     */
    public function getCspRules()
    {
        return 'frame-src https://eu.qualtrics.com/;';
    }
}
?>
