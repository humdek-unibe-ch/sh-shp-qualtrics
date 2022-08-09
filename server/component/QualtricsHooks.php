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
     * @param object $args
     * Params passed to the method
     * @param int $disabled 0 or 1
     * If the field is in edit mode or view mode (disabled)
     * @return object
     * Return a BaseStyleComponent object
     */
    private function returnSelectQualtricsField($args, $disabled){
        $field = $this->get_param_by_name($args, 'field');
        $res = $this->execute_private_method($args);                
        if ($field['name'] == 'qualtricsSurvey') {            
            $field_name_prefix = "fields[" . $field['name'] . "][" . $field['id_language'] . "]" . "[" . $field['id_gender'] . "]";
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
     * @param object $args
     * Params passed to the method
     * @return object
     * Return a BaseStyleComponent object
     */
    public function outputFieldQualtricsSurveyEdit($args)
    {
        return $this->returnSelectQualtricsField($args, 0);
    }

    /**
     * Return a BaseStyleComponent object
     * @param object $args
     * Params passed to the method
     * @return object
     * Return a BaseStyleComponent object
     */
    public function outputFieldQualtricsSurveyView($args)
    {
        return $this->returnSelectQualtricsField($args, 1);
    }

    /**
     * Set csp rules for Qualtrics     
     * @return string
     * Return csp_rules
     */
    public function setCspRules($args)
    {
        $res = $this->execute_private_method($args);
        $resArr = explode(';', $res);
        $frameRuleExists = false;
        foreach ($resArr as $key => $value) {
            if (strpos($value, 'frame-src') !== false) {
                str_replace($value, ';', '');
                $resArr[$key] = $value . ' https://eu.qualtrics.com/;';
                $frameRuleExists = true;
                break;
            }
        }
        if ($frameRuleExists) {
            return implode(";", $resArr);
        } else {
            return $res . 'frame-src https://eu.qualtrics.com/;';
        }
    }
}
?>
