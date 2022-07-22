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

    /* Private Methods *********************************************************/

    /**
     * Output select qualtrics field
     * @param string $value
     * Value of the field
     * @param string $name
     * The name of the fields
     * @param int $disabled 0 or 1
     * If the field is in edit mode or view mode (disabled)
     * @return object
     * Return instance of BaseStyleComponent -> select style
     */
    private function outputSelectQualtricsField($value, $name, $disabled){
        return new BaseStyleComponent("select", array(
                "value" => $value,
                "name" => $name,
                "max" => 10,
                "live_search" => 1,
                "is_required" => 1,
                "disabled" => $disabled,
                "items" => $this->db->fetch_table_as_select_values('qualtricsSurveys', 'id', array('name', 'qualtrics_survey_id'))
            ));
    }

    /**
     * Return a BaseStyleComponent object
     * @param object hookedClassInstance
     * The class which was hooked
     * @param string $methodName
     * The name of the method that we want to execute
     * @param object $args
     * Params passed to the method
     * @param int $disabled 0 or 1
     * If the field is in edit mode or view mode (disabled)
     * @return object
     * Return a BaseStyleComponent object
     */
    private function returnSelectQualtricsField($hookedClassInstance, $methodName, $args, $disabled){
        $field = $args[0];
        $res = $this->execute_private_method($hookedClassInstance, $methodName, $field);
        $field_name_prefix = "fields[" . $field['name'] . "][" . $field['id_language'] . "]" . "[" . $field['id_gender'] . "]";
        if ($field['name'] == 'qualtricsSurvey') {
            $selectField = $this->outputSelectQualtricsField($field['content'], $field_name_prefix . "[content]", $disabled);
            if ($selectField && $res) {
                $children = $res->get_view()->get_children();
                $children[] = $selectField;
                $res->get_view()->set_children($children);
            }
        }
        return $res;
    }

    /* Public Methods *********************************************************/

    /**
     * Return a BaseStyleComponent object
     * @param object hookedClassInstance
     * The class which was hooked
     * @param string $methodName
     * The name of the method that we want to execute
     * @param object $args
     * Params passed to the method
     * @return object
     * Return a BaseStyleComponent object
     */
    public function outputFieldQualtricsSurveyEdit($hookedClassInstance, $methodName, $args)
    {
        return $this->returnSelectQualtricsField($hookedClassInstance, $methodName, $args, 0);
    }

    /**
     * Return a BaseStyleComponent object
     * @param object hookedClassInstance
     * The class which was hooked
     * @param string $methodName
     * The name of the method that we want to execute
     * @param object $args
     * Params passed to the method
     * @return object
     * Return a BaseStyleComponent object
     */
    public function outputFieldQualtricsSurveyView($hookedClassInstance, $methodName, $args)
    {
        return $this->returnSelectQualtricsField($hookedClassInstance, $methodName, $args, 1);
    }

    /**
     * Set csp rules for Qualtrics     
     * @return string
     * Return csp_rules
     */
    public function setCspRules($hookedClassInstance, $methodName)
    {
        $res = $this->execute_private_method($hookedClassInstance, $methodName);
        return $res . 'frame-src https://eu.qualtrics.com/;';
    }
}
?>
