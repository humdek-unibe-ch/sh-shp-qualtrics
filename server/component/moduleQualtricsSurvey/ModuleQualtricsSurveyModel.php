<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../moduleQualtrics/ModuleQualtricsModel.php";
require_once __DIR__ . "/qualtrics_api_templates.php";

/**
 * This class is used to prepare all data related to the cmsPreference component such
 * that the data can easily be displayed in the view of the component.
 */
class ModuleQualtricsSurveyModel extends ModuleQualtricsModel
{

    /* Constants ************************************************/

    /* API calls */
    const QUALTRICS_API_GET_SET_SURVEY_FLOW = 'https://eu.qualtrics.com/API/v3/survey-definitions/:survey_api_id/flow';
    const QUALTRICS_API_GET_SET_SURVEY_RESPONSE = 'https://eu.qualtrics.com/API/v3/surveys/:survey_api_id/responses/:survey_response';
    const QUALTRICS_API_GET_SET_SURVEY_OPTIONS = 'https://eu.qualtrics.com/API/v3/survey-definitions/:survey_api_id/options';
    const QUALTRICS_API_CREATE_CONTACT = 'https://eu.qualtrics.com/API/v3/mailinglists/:api_mailing_group_id/contacts';
    const QUALTRICS_API_PUBLISH_SURVEY = 'https://eu.qualtrics.com/API/v3/survey-definitions/:survey_api_id/versions';
    const QUALTRICS_API_POST_START_EXPORT_SURVEY = 'https://eu.qualtrics.com/API/v3/surveys/:survey_api_id/export-responses';
    const QUALTRICS_API_GET_CHECK_EXPORT_STATUS = 'https://eu.qualtrics.com/API/v3/surveys/:survey_api_id/export-responses/:export_id';
    const QUALTRICS_API_GET_EXPORTED_SURVEY = 'https://eu.qualtrics.com/API/v3/surveys/:survey_api_id/export-responses/:file_id/file';

    /* Qualtrics flow types */
    const FLOW_TYPE_EMBEDDED_DATA = 'EmbeddedData';
    const FLOW_TYPE_WEB_SERVICE = 'WebService';
    const FLOW_TYPE_AUTHENTICATOR = 'Authenticator';

    /* Content Type */
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    /* Flow IDs start with FL_ then max 15 characters*/
    const FLOW_ID_EMBEDED_DATA = 'FL_embedded_data';
    const FLOW_ID_WEB_SERVICE_CONTACTS = 'FL_ws_contacts';
    const FLOW_ID_WEB_SERVICE_GROUP = 'FL_ws_group';
    const FLOW_ID_WEB_SERVICE_START = 'FL_ws_start';
    const FLOW_ID_WEB_SERVICE_SAVE_DATA = 'FL_ws_save_data';
    const FLOW_ID_WEB_SERVICE_END = 'FL_ws_end';
    const FLOW_ID_AUTHENTICATOR = 'FL_auth';
    const FLOW_ID_AUTHENTICATOR_CONTACT = 'FL_999999'; //'FL_auth_cont'; // THIS SHOULD BE ONLY NUMBERS AFTER FL_
    const FLOW_ID_AUTHENTICATOR_CONTACT_EMBEDDED_DATA = 'FL_auth_cont_ed';
    const FLOW_ID_BRANCH_CONTACT_EXIST = 'FL_br_cont_ex';
    const QUALTRICS_API_SUCCESS = '200 - OK';

    /* values */
    const QUALTRICS_PARTICIPANT_VARIABLE = 'code';
    const QUALTRICS_GROUP_VARIABLE = 'group';
    const QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE = 'ResponseID';
    const QUALTRICS_SURVEY_ID_VARIABLE = 'SurveyID';
    const QUALTRICS_ADDITIONAL_FUNCTIONS_VARIABLE = 'additional_functions';
    const QUALTRICS_CALLBACK_KEY_VARIABLE = 'callback_key';
    const QUALTRICS_TRIGGER_TYPE_VARIABLE = 'trigger_type';
    const QUALTRICS_SAVE_DATA = 'save_data';
    const QUALTRICS_EMBEDED_SESSION_ID_VAR = '${e://Field/ResponseID}';
    const QUALTRICS_EMBEDED_SURVEY_ID_VAR = '${e://Field/SurveyID}';
    const QUALTRICS_CALLBACK_STATUS = 'callback_status';
    const SELFEHLP_HEADER_HIDE_QUALTRIC_LOGO = 'selfhelp_hideQualtricsLogo';
    const SELFEHLP_HEADER_IFRAME_RESIZER = 'selfhelp_iFrameResizer';

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
     * PRIVATE METHODS *************************************************************************************************************
     */

    /**
     * Return Qualtrics headers
     * @return object
     * Return the qualtrics headers structure
     */
    private function get_qualtrics_api_headers()
    {
        $headers = array();
        $header = array(
            "key" => "X-API-TOKEN",
            "value" => $this->get_user_qualtrics_api_key()
        );
        $headers[] = $header;
        return $headers;
    }

    /**
     * Get survey flow via qualtrics api
     * @param string $survey_api_id qualtrics survey id
     * @retval array with flow structure
     */
    private function get_survey_flow($survey_api_id)
    {
        $data = array(
            "request_type" => "GET",
            "URL" => str_replace(':survey_api_id', $survey_api_id, ModuleQualtricsSurveyModel::QUALTRICS_API_GET_SET_SURVEY_FLOW),
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $this->get_user_qualtrics_api_key()
            )
        );
        $result = $this->execute_curl_call($data);
        if (isset($result['meta']['error'])) {
            return $this->return_info(false, $result['meta']['error']['errorMessage']);
        }
        return $result ? $result['result'] : $result;
    }

    /**
     * Synchronize survey header; Get the header and check if the selfhelp is appended; if it is not we add it.
     * It adds hideQualtrics logo and iFrame resizer
     * @param string $survey_api_id qualtrics survey id
     * @retval array with result
     */
    private function sync_survey_header($survey_api_id)
    {
        //get survey options; they contain the survey header
        $data = array(
            "request_type" => "GET",
            "URL" => str_replace(':survey_api_id', $survey_api_id, ModuleQualtricsSurveyModel::QUALTRICS_API_GET_SET_SURVEY_OPTIONS),
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $this->get_user_qualtrics_api_key()
            )
        );
        $survey_options = $this->execute_curl_call($data);
        if (isset($survey_options['meta']['error'])) {
            return $this->return_info(false, $survey_options['meta']['error']['errorMessage']);
        }
        if ($survey_options !== false) {
            $survey_header = $survey_options['result']['Header'];
            $html = ''; //init no header we still need emty string
            if ($survey_header != '') {
                $dom = new DOMDocument();
                $dom->validateOnParse = true;
                $dom->loadHTML($survey_header);
                $dom->preserveWhiteSpace = false;
                /* Remove hideQualtrticsLogo if exists*/
                $hideQualtricsLogo = $dom->getElementById(ModuleQualtricsSurveyModel::SELFEHLP_HEADER_HIDE_QUALTRIC_LOGO);
                if ($hideQualtricsLogo) {
                    $hideQualtricsLogo->parentNode->removeChild($hideQualtricsLogo);
                }
                /* Remove iFramreResizer if exists */
                $iFrameResizer = $dom->getElementById(ModuleQualtricsSurveyModel::SELFEHLP_HEADER_IFRAME_RESIZER);
                if ($iFrameResizer) {
                    $iFrameResizer->parentNode->removeChild($iFrameResizer);
                }
                $html = $dom->saveHTML(); //save the html value of the header
            }
            $html = $html . QulatricsAPITemplates::hideQualtricsLogo . QulatricsAPITemplates::iFrameResizer;
            $survey_options['result']['Header'] = $html;
            return $this->set_survey_options($survey_api_id, $survey_options['result']);
        } else {
            return $this->return_info(false, 'Get survey options failed');
        }
    }

    /**
     * helper function to show the info from the requests
     * @param bool $result
     * @param string $text
     *  description
     * @return array
     */
    private function return_info($result, $text)
    {
        $res = array(
            "result" => $result,
            "description" => '[' . date('h:i:s') . '] ' . (is_string($text) ? $text : ''),
            "data" => $text
        );
        return $res;
    }

    /**
     * helper function to show the info from the requests which combine multiple results
     * @param array $resultsArray
     * @retval array
     */
    private function multi_return_info($resultsArray)
    {
        $res = array(
            "result" => true,
            "description" => ''
        );
        foreach ($resultsArray as $key => $arr) {
            $res['result'] = $res['result'] && $arr['result'];
            if ($res['description'] == '') {
                $res['description'] = $arr['description'];
            } else {
                $res['description'] = $res['description'] . '; ' . $arr['description'];
            }
        }
        return $res;
    }

    /**
     * Set survey flow via qualtrics api
     * @param string $survey_api_id qualtrics survey id
     * @param array $flow the flow structure
     * @retval array
     */
    private function set_survey_flow($survey_api_id, $flow)
    {
        $data = array(
            "request_type" => "PUT",
            "URL" => str_replace(':survey_api_id', $survey_api_id, ModuleQualtricsSurveyModel::QUALTRICS_API_GET_SET_SURVEY_FLOW),
            "post_params" => json_encode($flow),
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $this->get_user_qualtrics_api_key()
            )
        );
        $result = $this->execute_curl_call($data);
        if (!$result) {
            return $this->return_info(false, "Something went wrong assigning the survey flow");
        } else {
            if ($result['meta']['httpStatus'] === ModuleQualtricsSurveyModel::QUALTRICS_API_SUCCESS) {
                return $this->return_info(true, "The survey flow was synchronized");
            } else {
                return $this->return_info(false, json_encode($result));
            }
        }
    }

    /**
     * Set survey options via qualtrics api
     * @param string $survey_api_id qualtrics survey id
     * @param array $options the options structure
     * @retval array
     */
    private function set_survey_options($survey_api_id, $options)
    {
        $data = array(
            "request_type" => "PUT",
            "URL" => str_replace(':survey_api_id', $survey_api_id, ModuleQualtricsSurveyModel::QUALTRICS_API_GET_SET_SURVEY_OPTIONS),
            "post_params" => json_encode($options),
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $this->get_user_qualtrics_api_key()
            )
        );
        $result = $this->execute_curl_call($data);
        if (!$result) {
            return $this->return_info(false, "Something went wrong with assigning survey options");
        } else {
            if ($result['meta']['httpStatus'] === ModuleQualtricsSurveyModel::QUALTRICS_API_SUCCESS) {
                return $this->return_info(true, "The survey options were synchronized");
            } else {
                return $this->return_info(false, json_encode($result));
            }
        }
    }

    /**
     * generate the  Authenticator flow adn return nested array
     * @param object $survey
     * Survey info
     * @retval array
     */
    private function get_authenticator($survey, $flow_id = ModuleQualtricsSurveyModel::FLOW_ID_AUTHENTICATOR, $max_attempts = 100)
    {
        $authenticator = json_decode(QulatricsAPITemplates::authenticator, true);
        $authenticator['FlowID'] = $flow_id;
        $authenticator['PanelData']['LibraryID'] = $survey['api_library_id'];
        $authenticator['PanelData']['PanelID'] = $survey['api_mailing_group_id'];
        $authenticator['FieldData'][0][0]['embeddedDataField'] = ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE;
        $authenticator['Options']['maxAttempts'] = $max_attempts;
        return $authenticator;
    }

    /**
     * generate the web service flow adn return nested array
     * @param array $embedded_vars
     * @param string $flowId
     * @param string $url     
     * @param bool $is_callback 
     * @param string $fireAndForget if true qulatrics do not wait for a repsonse from the callback otherwise it waits
     * @param array $callbackResultStructure the variale that the callback can return
     * @retval array
     */
    private function get_webService_flow($embedded_vars, $flowId, $url, $is_callback, $fireAndForget = true, $callbackResultStructure = array())
    {
        $body = array();
        $body = array_merge(array("externalDataRef" => '${e://Field/' . ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE . '}'));
        if ($is_callback) {
            //for callbacks different structure
            $body = $embedded_vars;
        }
        $webService = array(
            "Type" => ModuleQualtricsSurveyModel::FLOW_TYPE_WEB_SERVICE,
            "FlowID" => $flowId,
            "URL" => $url,
            "Method" => "POST",
            "RequestParams" => array(),
            "EditBodyParams" =>  $embedded_vars,
            "Body" => $body,
            "ContentType" => "application/json",
            "Headers" => $is_callback ? array() : $this->get_qualtrics_api_headers(),
            "ResponseMap" => array(),
            "FireAndForget" => $fireAndForget,
            "SchemaVersion" => 0,
            "StringifyValues" => true
        );
        if ($is_callback) {
            $webService['ContentType'] = ModuleQualtricsSurveyModel::CONTENT_TYPE_FORM;
            //$webService['ResponseMap'] = array();
            $webService['ResponseMap'][] = array(
                "key" => ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS,
                "value" => ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_STATUS . "_" . $flowId,
            );
            foreach ($callbackResultStructure as $responseMap) {
                $webService['ResponseMap'][] = $responseMap;
            }
        }
        return $webService;
    }

    /**
     * Generate a webservice flow for finish survey
     * @param array $survey
     * survey flow
     * @retval array
     * return the finish web service flow
     */
    private function get_webService_finish_flow($survey)
    {
        $editBodyParamsEnd[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE,
            "value" => '${e://Field/' . ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE . '}'
        );
        $editBodyParamsEnd[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE,
            "value" => ModuleQualtricsSurveyModel::QUALTRICS_EMBEDED_SURVEY_ID_VAR
        );
        $editBodyParamsEnd[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE,
            "value" => ModuleQualtricsSurveyModel::QUALTRICS_EMBEDED_SESSION_ID_VAR
        );
        $editBodyParamsEnd[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_KEY_VARIABLE,
            "value" => $this->db->get_callback_key()
        );
        $editBodyParamsEnd[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE,
            "value" => actionTriggerTypes_finished
        );
        if (isset($survey['functions_code']) && $survey['functions_code']) {
            $editBodyParamsEnd[] = array(
                "key" => ModuleQualtricsSurveyModel::QUALTRICS_ADDITIONAL_FUNCTIONS_VARIABLE,
                "value" => $survey['functions_code']
            );
        }
        $fireAndFroget = true;
        $callbackResultStructure = array();
        if (
            isset($survey['functions_code']) && $survey['functions_code'] && strpos($survey['functions_code'], qualtricsProjectActionAdditionalFunction_bmz_evaluate_motive) !== false &&
            $survey['trigger_type_code'] === actionTriggerTypes_finished
        ) {
            $fireAndFroget = true;
        }
        return $this->get_webService_flow(
            $editBodyParamsEnd,
            ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_END,
            $this->get_protocol() . $_SERVER['HTTP_HOST'] . $this->get_link_url("callback", array("class" => "CallbackQualtrics", "method" => "add_survey_response")),
            true,
            $fireAndFroget,
            $callbackResultStructure
        );
    }

    /**
     * Generate a webservice flow for start survey
     * @param array $survey
     * survey flow
     * @retval array
     * return the start web service flow
     */
    private function get_webService_start_flow($survey)
    {
        $editBodyParamsStart[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE,
            "value" => '${e://Field/' . ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE . '}'
        );
        $editBodyParamsStart[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE,
            "value" => ModuleQualtricsSurveyModel::QUALTRICS_EMBEDED_SURVEY_ID_VAR
        );
        $editBodyParamsStart[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE,
            "value" => ModuleQualtricsSurveyModel::QUALTRICS_EMBEDED_SESSION_ID_VAR
        );
        $editBodyParamsStart[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_KEY_VARIABLE,
            "value" => $this->db->get_callback_key()
        );
        $editBodyParamsStart[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_TRIGGER_TYPE_VARIABLE,
            "value" => actionTriggerTypes_started
        );
        if (isset($survey['functions_code']) && $survey['functions_code']) {
            $editBodyParamsStart[] = array(
                "key" => ModuleQualtricsSurveyModel::QUALTRICS_ADDITIONAL_FUNCTIONS_VARIABLE,
                "value" => $survey['functions_code']
            );
        }
        $fireAndFroget = true;
        $callbackResultStructure = array();
        if (
            isset($survey['functions_code']) && $survey['functions_code'] && strpos($survey['functions_code'], qualtricsProjectActionAdditionalFunction_bmz_evaluate_motive) !== false &&
            $survey['trigger_type_code'] === actionTriggerTypes_started
        ) {
            // if bmz funcion is needed we wait for the result
            $fireAndFroget = false;
        }
        return $this->get_webService_flow(
            $editBodyParamsStart,
            ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_START,
            $this->get_protocol() . $_SERVER['HTTP_HOST'] . $this->get_link_url("callback", array("class" => "CallbackQualtrics", "method" => "add_survey_response")),
            true,
            $fireAndFroget,
            $callbackResultStructure
        );
    }

    /**
     * Generate a web service flow for saving data
     * @param array $fields
     * the variable names that will be saved
     * @return array
     * return the start web service flow
     */
    private function get_webService_save_data($fields)
    {
        // deprecated
        $editBodyParamsSave[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE,
            "value" => '${e://Field/' . ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE . '}'
        );
        $editBodyParamsSave[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_ID_VARIABLE,
            "value" => ModuleQualtricsSurveyModel::QUALTRICS_EMBEDED_SURVEY_ID_VAR
        );
        $editBodyParamsSave[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_SURVEY_RESPONSE_ID_VARIABLE,
            "value" => ModuleQualtricsSurveyModel::QUALTRICS_EMBEDED_SESSION_ID_VAR
        );
        $editBodyParamsSave[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_KEY_VARIABLE,
            "value" => $this->db->get_callback_key()
        );
        $save_data_fields = array();
        foreach ($fields as $key => $field) {
            $save_data_fields[$field] = '${e://Field/' . $field . '}';
        }
        $editBodyParamsSave[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_SAVE_DATA,
            "value" => json_encode($save_data_fields, JSON_UNESCAPED_SLASHES)
        );
        $fireAndFroget = true;
        $callbackResultStructure = array();
        return $this->get_webService_flow(
            $editBodyParamsSave,
            ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_SAVE_DATA,
            $this->get_protocol() . $_SERVER['HTTP_HOST'] . $this->get_link_url("callback", array("class" => "CallbackQualtrics", "method" => "save_data")),
            true,
            $fireAndFroget,
            $callbackResultStructure
        );
    }

    private function get_webService_setGroup_flow($survey)
    {

        $editBodyParamsGroup[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE,
            "value" => '${e://Field/' . ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE . '}'
        );
        $editBodyParamsGroup[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE,
            "value" => '${e://Field/' . ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE . '}'
        );
        $editBodyParamsGroup[] = array(
            "key" => ModuleQualtricsSurveyModel::QUALTRICS_CALLBACK_KEY_VARIABLE,
            "value" => $this->db->get_callback_key()
        );
        return $this->get_webService_flow(
            $editBodyParamsGroup,
            ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_GROUP,
            $this->get_protocol() . $_SERVER['HTTP_HOST'] . $this->get_link_url("callback", array("class" => "CallbackQualtrics", "method" => "set_group")),
            true,
            false
        );
    }

    /**
     * Get the protocol. If it is debug it returns http otherwise https
     * @retval string
     * it returns the protocol
     */
    private function get_protocol()
    {
        return DEBUG ? 'http://' : 'https://';
    }

    /**
     * Synchronize baseline survey to qualtrics via the API
     * @param array $survey
     * @param object $surveyFlow
     * @retval array
     */
    private function sync_baseline_survey($survey, $surveyFlow)
    {
        if ($surveyFlow) {

            /** EMBEDED DATA variables *************************************************************************************************************************************/
            $baseline_embedded_flow = json_decode(QulatricsAPITemplates::embedded_data, true);
            $baseline_embedded_flow['FlowID'] = ModuleQualtricsSurveyModel::FLOW_ID_EMBEDED_DATA;
            $baseline_embedded_flow['EmbeddedData'][] = array(
                "Description" => ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE,
                "Type" => "Recipient",
                "Field" => ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE,
                "VariableType" => "String",
                "DataVisibility" => array(),
                "AnalyzeText" => false
            );
            $baseline_embedded_flow['EmbeddedData'][] = array(
                "Description" => 'user_registered',
                "Type" => "Custom",
                "Field" => 'user_registered',
                "VariableType" => "String",
                "DataVisibility" => array(),
                "AnalyzeText" => false,
                "Value" => "false"
            );
            if ($survey['group_variable'] == 1) {
                //there is a randomization in the survey, prepare the group variable
                $baseline_embedded_flow['EmbeddedData'][] = array(
                    "Description" => ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE,
                    "Type" => "Recipient",
                    "Field" => ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE,
                    "VariableType" => "String",
                    "DataVisibility" => array(),
                    "AnalyzeText" => false
                );
            }

            /** AUTHENTICATOR is the user registered *************************************************************************************************************************************/
            $baseline_authenticator = $this->get_authenticator($survey, ModuleQualtricsSurveyModel::FLOW_ID_AUTHENTICATOR_CONTACT, '1');
            $embeded_data_authenticator_contact = json_decode(QulatricsAPITemplates::embedded_data, true);
            $embeded_data_authenticator_contact['EmbeddedData'][] = array(
                "Description" => 'user_registered',
                "Type" => "Custom",
                "Field" => 'user_registered',
                "VariableType" => "String",
                "DataVisibility" => array(),
                "AnalyzeText" => false,
                "Value" => "true"
            );
            $embeded_data_authenticator_contact['FlowID'] = ModuleQualtricsSurveyModel::FLOW_ID_AUTHENTICATOR_CONTACT_EMBEDDED_DATA;
            $baseline_authenticator['Flow'][] = $embeded_data_authenticator_contact;

            /** BRANCH if user is not registered, add him/her to to the list *************************************************************************************************************************************/

            $editBodyParams[] = array(
                "key" => 'externalDataRef',
                "value" => '${e://Field/' . ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE . '}'
            );

            $baseline_webService_contacts = $this->get_webService_flow(
                $editBodyParams,
                ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_CONTACTS,
                str_replace(
                    ':api_mailing_group_id',
                    $survey['api_mailing_group_id'],
                    ModuleQualtricsSurveyModel::QUALTRICS_API_CREATE_CONTACT
                ),
                false,
                false
            );
            $branch_contact_exists = json_decode(QulatricsAPITemplates::branch_contact_exist, true);
            $branch_contact_exists['FlowID'] = ModuleQualtricsSurveyModel::FLOW_ID_BRANCH_CONTACT_EXIST;
            $branch_contact_exists['Flow'][] = $baseline_webService_contacts;

            /** START SURVEY WEB SERVICE *******************************************************************************************************************************/

            $baseline_webService_start = $this->get_webService_start_flow($survey);

            $config = json_decode($survey['config'], true);
            if (isset($config['save_data']) && isset($config['save_data']['fields'])) {
                /** SAVE DATA WEB SERVICE *******************************************************************************************************************************/

                // $baseline_webService_save_data = $this->get_webService_save_data($config['save_data']['fields']);
            }

            /** END SURVEY WEB SERVICE *******************************************************************************************************************************/

            $baseline_webService_end = $this->get_webService_finish_flow($survey);

            /** GROUP WEB SERVICE if there is grouping *************************************************************************************************************************************/
            if ($survey['group_variable'] == 1) {
                // web service for setting group                
                $baseline_webService_group = $this->get_webService_setGroup_flow($survey);
            }

            /** LOOP IF FLOWS EXISTS, EDIT THEM **********************************************************************************************************************************/
            foreach ($surveyFlow['Flow'] as $key => $flow) {
                if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_EMBEDED_DATA) {
                    //already exist; overwirite
                    $surveyFlow['Flow'][$key] = $baseline_embedded_flow;
                    $baseline_embedded_flow = false; //not needed anymore later when we check is it assign
                } else if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_AUTHENTICATOR_CONTACT) {
                    //already exist; overwirite
                    $surveyFlow['Flow'][$key] = $baseline_authenticator;
                    $baseline_authenticator = false; //not needed anymore later when we check is it assign
                } else if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_BRANCH_CONTACT_EXIST) {
                    //already exist; overwirite
                    $surveyFlow['Flow'][$key] = $branch_contact_exists;
                    $branch_contact_exists = false; //not needed anymore later when we check is it assign
                } else if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_START) {
                    //already exist; overwirite
                    $surveyFlow['Flow'][$key] = $baseline_webService_start;
                    $baseline_webService_start = false; //not needed anymore later when we check is it assign
                } else if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_SAVE_DATA) {
                    //already exist; overwirite
                    if (isset($baseline_webService_save_data)) {
                        $surveyFlow['Flow'][$key] = $baseline_webService_save_data;
                    } else {
                        //remove the save data service, not needed
                        unset($surveyFlow['Flow'][$key]);
                    }
                    $baseline_webService_save_data = false; //not needed anymore later when we check is it assign
                } else if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_END) {
                    //already exist; overwirite
                    // This flow whoudl be allways at the end. Remove it now and allways add it at the end
                    unset($surveyFlow['Flow'][$key]);
                } else if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_GROUP) {
                    //already exist; overwirite
                    if (!isset($baseline_webService_group)) {
                        //should not exist; remove it
                        unset($surveyFlow['Flow'][$key]);
                    } else {
                        // add it
                        $surveyFlow['Flow'][$key] = $baseline_webService_group;
                    }
                    $baseline_webService_group = false; //not needed anymore later when we check is it assign
                }
            }

            /** IF FLOW DOESN NOT EXIST, ADD THEM **********************************************************************************************************************************/

            //check do we still have to add flows
            // order is important as we add as first. We should add the element that should be first as last call
            if ($baseline_webService_start) {
                // add baseline webService for starting the survey
                array_unshift($surveyFlow['Flow'], $baseline_webService_start);
            }
            if ($branch_contact_exists) {
                // add baseline webService with the branch check
                array_unshift($surveyFlow['Flow'], $branch_contact_exists);
            }
            if ($baseline_authenticator) {
                // add baseline authenticaotr
                array_unshift($surveyFlow['Flow'], $baseline_authenticator);
            }
            if ($baseline_embedded_flow) {
                // add baseline embeded data
                array_unshift($surveyFlow['Flow'], $baseline_embedded_flow);
            }
            // at at the end of the list
            if (isset($baseline_webService_save_data) && $baseline_webService_save_data) {
                // add baseline group web service
                array_push($surveyFlow['Flow'], $baseline_webService_save_data);
            }
            // at at the end of the list
            if (isset($baseline_webService_group) && $baseline_webService_group) {
                // add baseline group web service
                array_push($surveyFlow['Flow'], $baseline_webService_group);
            }
            if ($baseline_webService_end) {
                // add baseline webService for finishing the survey
                array_push($surveyFlow['Flow'], $baseline_webService_end);
            }

            /** EXECUTE THE FLOW **********************************************************************************************************************************/
            $surveyFlow['Flow'] = array_values($surveyFlow['Flow']); // rebase the array indexes
            return $this->set_survey_flow($survey['qualtrics_survey_id'], $surveyFlow);
        } else {
            return $this->return_info(false, "Something went wrong");
        }
    }

    /**
     * Synchronize followup survey to qualtrics via the API
     * @param array $survey
     * @param object $surveyFlow
     * @retval array
     */
    private function sync_followup_survey($survey, $surveyFlow)
    {
        if ($surveyFlow) {
            $followup_embedded_flow = json_decode(QulatricsAPITemplates::embedded_data, true);
            $followup_embedded_flow['FlowID'] = ModuleQualtricsSurveyModel::FLOW_ID_EMBEDED_DATA;
            $followup_embedded_flow['EmbeddedData'][] = array(
                "Description" => ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE,
                "Type" => "Recipient",
                "Field" => ModuleQualtricsSurveyModel::QUALTRICS_PARTICIPANT_VARIABLE,
                "VariableType" => "String",
                "DataVisibility" => array(),
                "AnalyzeText" => false
            );
            if ($survey['group_variable'] == 1) {
                //there is a randomization in the survey, prepare the group variable
                $followup_embedded_flow['EmbeddedData'][] = array(
                    "Description" => ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE,
                    "Type" => "Recipient",
                    "Field" => ModuleQualtricsSurveyModel::QUALTRICS_GROUP_VARIABLE,
                    "VariableType" => "String",
                    "DataVisibility" => array(),
                    "AnalyzeText" => false
                );
            }

            //flag is the authenticator created
            $followup_authenticator_exists = false;

            /** START SURVEY WEB SERVICE *******************************************************************************************************************************/

            $followup_webService_start = $this->get_webService_start_flow($survey);

            $config = json_decode($survey['config'], true);
            if (isset($config['save_data']) && isset($config['save_data']['fields'])) {
                /** SAVE DATA WEB SERVICE *******************************************************************************************************************************/

                // $followup_webService_save_data = $this->get_webService_save_data($config['save_data']['fields']);
            }

            /** END SURVEY WEB SERVICE *******************************************************************************************************************************/

            $followup_webService_end = $this->get_webService_finish_flow($survey);

            /** GROUP WEB SERVICE if there is grouping *************************************************************************************************************************************/
            if ($survey['group_variable'] == 1) {
                // web service for setting group                
                $followup_webService_group = $this->get_webService_setGroup_flow($survey);
            }

            $followup_authenticator = $this->get_authenticator($survey);
            foreach ($surveyFlow['Flow'] as $key => $flow) {
                if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_AUTHENTICATOR) {
                    //already exist; overwirite
                    $followup_authenticator['Flow'] = $surveyFlow['Flow'][$key]['Flow']; // keep what is inside the authenticator if it exists                                        
                    foreach ($followup_authenticator['Flow'] as $keyAuth => $flowAuth) {
                        //loop inside the authenticator to cgeck for elements
                        if ($flowAuth['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_START) {
                            //already exist; overwirite
                            $followup_authenticator['Flow'][$keyAuth] = $followup_webService_start;
                            $followup_webService_start = false; //not needed anymore later when we check is it assign
                        } else if ($flowAuth['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_SAVE_DATA) {
                            //already exist; overwirite
                            if (isset($followup_webService_save_data)) {
                                $followup_authenticator['Flow'][$keyAuth] = $followup_webService_save_data;
                            } else {
                                //remove the save data service, not needed
                                unset($followup_authenticator['Flow'][$keyAuth]);
                            }
                            $followup_webService_save_data = false; //not needed anymore later when we check is it assign
                        } else if ($flowAuth['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_END) {
                            //already exist; overwirite
                            // This flow whoudl be allways at the end. Remove it now and allways add it at the end
                            unset($followup_authenticator['Flow'][$keyAuth]);
                        } else if ($flowAuth['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_GROUP) {
                            //already exist; overwirite
                            if (!isset($followup_webService_group)) {
                                //should not exist; remove it
                                unset($followup_authenticator['Flow'][$keyAuth]);
                            } else {
                                // add it
                                $followup_authenticator['Flow'][$keyAuth] = $followup_webService_group;
                            }
                            $followup_webService_group = false; //not needed anymore later when we check is it assign
                        } else if ($flowAuth['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_EMBEDED_DATA) {
                            //already exist; overwirite
                            if (!isset($followup_embedded_flow)) {
                                //should not exist; remove it
                                unset($followup_authenticator['Flow'][$keyAuth]);
                            } else {
                                // add it
                                $followup_authenticator['Flow'][$keyAuth] = $followup_embedded_flow;
                            }
                            $followup_embedded_flow = false; //not needed anymore later when we check is it assign
                        }
                    }
                    $followup_authenticator['Flow'] = array_values($followup_authenticator['Flow']); // rebase the array indexes
                    $surveyFlow['Flow'][$key] = $followup_authenticator; //assign the new authenticator                    
                    $followup_authenticator_exists = true; //not needed anymore later when we check, is it assign
                }
            }
            //check do we still have to add flows
            // order is important as we add as first. We should add the element that should be first as last call            
            if (!$followup_authenticator_exists) {
                // add followup authenticaotr
                $followup_authenticator['Flow'] = $surveyFlow['Flow']; //move all blocks inside the authenticator                                
            }
            if ($followup_webService_start) {
                // add followup webService for starting the survey
                array_unshift($followup_authenticator['Flow'], $followup_webService_start);
            }
            if (isset($followup_embedded_flow) && $followup_embedded_flow) {
                // add followup embeded data
                array_unshift($followup_authenticator['Flow'], $followup_embedded_flow);
            }
            // at at the end of the list
            if (isset($followup_webService_save_data) && $followup_webService_save_data) {
                // add baseline group web service
                array_push($followup_authenticator['Flow'], $followup_webService_save_data);
            }
            // at at the end of the list
            if (isset($followup_webService_group) && $followup_webService_group) {
                // add followup group web service
                array_push($followup_authenticator['Flow'], $followup_webService_group);
            }
            if ($followup_webService_end) {
                // add followup webService for finishing the survey
                array_push($followup_authenticator['Flow'], $followup_webService_end);
            }
            //assign authenticator on top
            unset($surveyFlow['Flow']); // clear the flow before assing the authenticator
            $surveyFlow['Flow'][] = $followup_authenticator; // assign the authenticator to the flow, now the authenticator keeps the rest of the flow inside              
            return $this->set_survey_flow($survey['qualtrics_survey_id'], $surveyFlow);
        } else {
            return $this->return_info(false, "Something went wrong");
        }
    }

    /**
     * Synchronize anonymous survey to qualtrics via the API
     * @param array $survey
     * @param object $surveyFlow
     * @retval array
     */
    private function sync_anonymous_survey($survey, $surveyFlow)
    {
        if (!isset($surveyFlow['result'])) {

            /** START SURVEY WEB SERVICE *******************************************************************************************************************************/

            $webService_start = $this->get_webService_start_flow($survey);

            $config = json_decode($survey['config'], true);
            if (isset($config['save_data']) && isset($config['save_data']['fields'])) {
                /** SAVE DATA WEB SERVICE *******************************************************************************************************************************/

                // $webService_save_data = $this->get_webService_save_data($config['save_data']['fields']);
            }

            /** END SURVEY WEB SERVICE *******************************************************************************************************************************/

            $webService_end = $this->get_webService_finish_flow($survey);

            /** LOOP IF FLOWS EXISTS, EDIT THEM **********************************************************************************************************************************/
            foreach ($surveyFlow['Flow'] as $key => $flow) {
                if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_START) {
                    //already exist; overwirite
                    $surveyFlow['Flow'][$key] = $webService_start;
                    $webService_start = false; //not needed anymore later when we check is it assign
                } else if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_SAVE_DATA) {
                    //already exist; overwirite
                    if (isset($webService_save_data)) {
                        $surveyFlow['Flow'][$key] = $webService_save_data;
                    } else {
                        //remove the save data service, not needed
                        unset($surveyFlow['Flow'][$key]);
                    }
                    $webService_save_data = false; //not needed anymore later when we check is it assign
                } else if ($flow['FlowID'] === ModuleQualtricsSurveyModel::FLOW_ID_WEB_SERVICE_END) {
                    //already exist; overwirite
                    // This flow whould be allways at the end. Remove it now and allways add it at the end
                    unset($surveyFlow['Flow'][$key]);
                }
            }

            /** IF FLOW DOESN NOT EXIST, ADD THEM **********************************************************************************************************************************/

            //check do we still have to add flows
            // order is important as we add as first. We should add the element that should be first as last call
            if ($webService_start) {
                // add webService for starting the survey
                array_unshift($surveyFlow['Flow'], $webService_start);
            }
            // at at the end of the list
            if (isset($webService_save_data) && $webService_save_data) {
                // add baseline group web service
                array_push($surveyFlow['Flow'], $webService_save_data);
            }
            if ($webService_end) {
                // add webService for finishing the survey
                array_push($surveyFlow['Flow'], $webService_end);
            }

            /** EXECUTE THE FLOW **********************************************************************************************************************************/
            $surveyFlow['Flow'] = array_values($surveyFlow['Flow']); // rebase the array indexes
            return $this->set_survey_flow($survey['qualtrics_survey_id'], $surveyFlow);
        } else {
            return $this->return_info(false, "Something went wrong");
        }
    }

    /**
     * Start Qualtrics export
     * @param array $survey
     * The survey data
     * @return object
     * Return object with the result and description 
     */
    private function startQualtricsExport($survey)
    {
        $post_params = array(
            "format" => "json",
            "compress" => false
        );
        $data = array(
            "request_type" => "POST",
            "URL" => str_replace(':survey_api_id', $survey['qualtrics_survey_id'], ModuleQualtricsSurveyModel::QUALTRICS_API_POST_START_EXPORT_SURVEY),
            "post_params" => json_encode($post_params),
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $this->get_user_qualtrics_api_key()
            )
        );
        $startExport = $this->execute_curl_call($data);
        if (isset($publishResult['meta']['error'])) {
            return $this->return_info(false, $publishResult['meta']['error']['errorMessage']);
        } else {
            return $this->return_info(true, $startExport);
        }
    }

    /**
     * Check the export status and once ready return the file id
     * @param string $export_id
     * The export id that was queued with start export
     * @param array $survey
     * The survey data
     * @return object
     * Return object with the result and description 
     */
    private function getExportFileId($export_id, $survey)
    {
        $url = str_replace(':survey_api_id', $survey['qualtrics_survey_id'], ModuleQualtricsSurveyModel::QUALTRICS_API_GET_CHECK_EXPORT_STATUS);
        $url = str_replace(':export_id', $export_id, $url);
        $data = array(
            "request_type" => "GET",
            "URL" => $url,
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $this->get_user_qualtrics_api_key()
            )
        );
        $exportStatus = $this->execute_curl_call($data);
        if (isset($exportStatus['meta']['error'])) {
            return $this->return_info(false, $exportStatus['meta']['error']['errorMessage']);
        } else {
            if ($exportStatus['result']['status'] == 'inProgress') {
                // recursive until it is finished
                usleep(500 * 1000); // Sleep for 500 milliseconds
                return $this->getExportFileId($export_id, $survey);
            } else {
                return $this->return_info(true, $exportStatus['result']);
            }
        }
    }

    /**
     * Check the export status and once ready return the file id
     * @param string $file_id
     * The file id send form qualtrics
     * @param array $survey
     * The survey data
     * @return object
     * Return object with the result and description 
     */
    private function getSurveyFile($file_id, $survey)
    {
        $url = str_replace(':survey_api_id', $survey['qualtrics_survey_id'], ModuleQualtricsSurveyModel::QUALTRICS_API_GET_EXPORTED_SURVEY);
        $url = str_replace(':file_id', $file_id, $url);
        $data = array(
            "request_type" => "GET",
            "URL" => $url,
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $this->get_user_qualtrics_api_key()
            )
        );
        $surveyFile = $this->execute_curl_call($data);
        if (isset($surveyFile['meta']['error'])) {
            return $this->return_info(false, $surveyFile['meta']['error']['errorMessage']);
        } else {
            return $this->return_info(true, $surveyFile['responses']);
        }
    }

    /**
     * Check if the response id already exists
     * @param array $all_records_from_survey
     * All the records form the survey
     * @param string $response_id
     * The response id
     * @return boolean
     * Return the result
     */
    private function does_response_exists($all_records_from_survey, $response_id)
    {
        foreach ($all_records_from_survey as $subArray) {
            if (isset($subArray['responseId']) && $subArray['responseId'] == $response_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Save the pulled data that is not already saved
     * @param array $survey
     * The survey info
     * @param array $surveyData
     * The survey data that should be added
     */
    private function save_pulled_data($survey, $surveyData)
    {
        try {
            $this->db->begin_transaction();
            $id_table = $this->user_input->get_dataTable_id($survey['qualtrics_survey_id']);
            if (!$id_table) {
                return $this->return_info(false, "The table " . $survey['qualtrics_survey_id'] . ' does not exists');
            }
            $all_records_from_survey = $this->user_input->get_data($id_table, '', false);
            foreach ($surveyData as $key => $surveyResponse) {
                if (isset($surveyResponse['values']['code'])) {
                    $user_id = $this->getUserId($surveyResponse['values']['code']);
                    if ($user_id > 0) {
                        // the user still exists
                        if (!$this->does_response_exists($all_records_from_survey, $surveyResponse['responseId'])) {
                            // the responseId is not saved
                            $prep_data = array(
                                "responseId" => $surveyResponse['responseId'],
                                "id_users" => $user_id
                            );
                            $prep_data = ModuleQualtricsSurveyModel::prepare_qualtrics_data_for_save($prep_data, $surveyResponse, $survey['save_labels_data']);
                            $this->user_input->save_data(transactionBy_by_qualtrics_callback, $survey['qualtrics_survey_id'], $prep_data);
                        }
                    }
                }
            }
            $this->db->commit();
            return $this->return_info(true, "The data was pulled successfully");
        } catch (Exception $e) {
            $this->db->rollback();
            return $this->return_info(false, "Error while saving the pulled data");
        };
    }

    /**
     * Insert a new qualtrics survey to the DB.
     *
     * @param array $data
     *  name, description, qualtrics_survey_id, group_variable
     * @retval int
     *  The id of the new survey or false if the process failed.
     */
    public function insert_new_survey($data)
    {
        return $this->db->insert("qualtricsSurveys", array(
            "name" => $data['name'],
            "description" => $data['description'],
            "qualtrics_survey_id" => $data['qualtrics_survey_id'],
            "id_qualtricsSurveyTypes" => $data['id_qualtricsSurveyTypes'],
            "id_qualtricsProjects" => $data['id_qualtricsProjects'],
            "config" => isset($data['config']) ? $data['config'] : '',
            "group_variable" => isset($data['group_variable']) ? 1 : 0,
            "save_data" => isset($data['save_data']) ? 1 : 0
        ));
    }

    /**
     * Update qualtrics survey.
     *
     * @param array $data
     *  id, name, description, qualtrics_survey_id, group_variable
     * @retval int
     *  The number of the updated rows
     */
    public function update_survey($data)
    {
        return $this->db->update_by_ids(
            "qualtricsSurveys",
            array(
                "name" => $data['name'],
                "description" => $data['description'],
                "qualtrics_survey_id" => $data['qualtrics_survey_id'],
                "id_qualtricsSurveyTypes" => $data['id_qualtricsSurveyTypes'],
                "id_qualtricsProjects" => $data['id_qualtricsProjects'],
                "config" => $data['config'],
                "group_variable" => isset($data['group_variable']) ? 1 : 0,
                "save_data" => isset($data['save_data']) ? 1 : 0,
                "save_labels_data" => isset($data['save_labels_data']) ? 1 : 0
            ),
            array('id' => $data['id'])
        );
    }

    /**
     * Fetch all qualtrics surveys from the database
     *
     * @retval array $survey
     * id
     * name
     * description
     * api_mailing_group_id
     */
    public function get_surveys()
    {
        return $this->db->select_table('view_qualtricsSurveys');
    }

    /**
     * Fetch a survey based on its id
     * @param int $sid
     * Survey id
     * @return array $survey
     * id
     * name
     * description
     * api_mailing_group_id
     */
    public function get_survey($sid)
    {
        return $this->db->query_db_first(
            'SELECT * FROM view_qualtricsSurveys WHERE id = :id',
            array(":id" => $sid)
        );
    }

    /**
     * get db
     */
    public function get_db()
    {
        return $this->db;
    }

    /**
     * Synchfonize a survey to qualtrics using qualtrics API
     * @param array $survey
     * @retval bool 
     */
    public function syncSurvey($survey)
    {
        $res1 = $this->sync_survey_header($survey['qualtrics_survey_id']);
        $surveyFlow = $this->get_survey_flow($survey['qualtrics_survey_id']);
        if ($survey['survey_type_code'] === qualtricsSurveyTypes_baseline) {
            $res2 = $this->sync_baseline_survey($survey, $surveyFlow);
        } else if ($survey['survey_type_code'] === qualtricsSurveyTypes_follow_up) {
            $res2 = $this->sync_followup_survey($survey, $surveyFlow);
        } else if ($survey['survey_type_code'] === qualtricsSurveyTypes_anonymous) {
            $res2 = $this->sync_anonymous_survey($survey, $surveyFlow);
        }
        if ($res1['result'] && $res2['result']) {
            // sync was successful, set the last user who synced the survey. Later we will use this api key for pulling responses
            $this->db->update_by_ids(
                "qualtricsSurveys",
                array(
                    "id_users_last_sync" => $_SESSION['id_user']
                ),
                array("id" => $survey['id'])
            );
        }
        return $this->multi_return_info(array($res1, $res2));
    }

    /**
     * Publish survey in Qualtrics using qualtrics API
     * @param array $survey
     * The survey data
     * @return object
     * Return object with the result and description 
     */
    public function publishSurvey($survey)
    {
        $post_params = array(
            "Description" => "Published on: " . date('Y-m-d H:i:s') . " by " . $this->db->fetch_user_name(),
            "Published" => true
        );
        $data = array(
            "request_type" => "POST",
            "URL" => str_replace(':survey_api_id', $survey['qualtrics_survey_id'], ModuleQualtricsSurveyModel::QUALTRICS_API_PUBLISH_SURVEY),
            "post_params" => json_encode($post_params),
            "header" => array(
                "Content-Type: application/json",
                "X-API-TOKEN: " . $this->get_user_qualtrics_api_key()
            )
        );
        $publishResult = $this->execute_curl_call($data);
        if (isset($publishResult['meta']['error'])) {
            return $this->return_info(false, $publishResult['meta']['error']['errorMessage']);
        } else {
            return $this->return_info(true, $survey['qualtrics_survey_id'] . ' was successfully published!');
        }
    }

    /**
     * Pull unsaved data from Qualtrics using qualtrics API
     * @param array $survey
     * The survey data
     * @return object
     * Return object with the result and description 
     */
    public function pullUnsavedData($survey)
    {
        $startExport = $this->startQualtricsExport($survey);
        if ($startExport['result']) {
            $export_id = $startExport['data']['result']['progressId'];
        } else {
            return $this->return_info(false, $startExport['description']);
        }
        $checkExportStatus = $this->getExportFileId($export_id, $survey);
        if ($checkExportStatus['result']) {
            $export_file_id = $checkExportStatus['data']['fileId'];
        } else {
            return $this->return_info(false, $checkExportStatus['description']);
        }
        $surveyData = $this->getSurveyFile($export_file_id, $survey);
        return $this->save_pulled_data($survey, $surveyData['data']);
    }



    /**
     * Get the user qualtrics api key
     * @param int $id_user
     * If the user is set, we need the api key for a specific user
     * @return string
     * return the api key or empty string
     */
    public function get_user_qualtrics_api_key($id_users = null)
    {
        $form_id = $this->user_input->get_dataTable_id_by_displayName(QUALTRICS_SETTINGS);
        if ($form_id) {
            $user_qualtrics_api_key = $id_users ? $this->user_input->get_data($form_id, '', true, $id_users)  : $this->user_input->get_data($form_id, '');
            return $user_qualtrics_api_key && isset($user_qualtrics_api_key[0][QUALTRICS_API]) ? $user_qualtrics_api_key[0][QUALTRICS_API] : "";
        }
        return "";
    }

    /**
     * Prepare the Qualtrics data to be inserted in upload table
     * @param array $prep_data
     * The array with the prepared data
     * @param object $data
     * The quatrics data
     * @param boolean $save_labels_data
     * if enabled we will save the labels data too
     */
    public static function prepare_qualtrics_data_for_save($prep_data, $data, $save_labels_data)
    {
        if (isset($data['values'])) {
            foreach ($data['values'] as $key => $value) {
                // get all the values
                if (!is_array($value)) {
                    $prep_data[$key] = $value;
                }
            }
        }
        if ($save_labels_data) {
            if (isset($data['labels'])) {
                foreach ($data['labels'] as $key => $value) {
                    // get all the labels
                    if (!is_array($value)) {
                        $prep_data[$key . '_label'] = $value;
                    }
                }
            }
        }
        return $prep_data;
    }

    /**
     * Get the user id given a user code
     *
     * @param $code
     *  The code for which a user is searched
     * @retval $boolean
     *  The user id on success, -1 on failure
     */
    public function getUserId($code)
    {
        $sql = "SELECT u.id AS id_users, id_languages, 
                CASE
                    WHEN u.name = 'admin' THEN 'admin'
                    WHEN u.name = 'tpf' THEN 'tpf'    
                    ELSE IFNULL(vc.code, '-') 
                END AS code
                FROM users AS u
                LEFT JOIN validation_codes vc ON u.id = vc.id_users
                WHERE u.intern <> 1 AND u.id_status > 0
                AND code  = :code";
        $res = $this->db->query_db_first($sql, array(':code' => $code));
        $_SESSION['language'] = isset($res['id_languages']) && $res['id_languages'] > 1 ? $res['id_languages'] : LANGUAGE; //set the session language of this user in case we need it later
        return  !isset($res['id_users']) ? -1 : $res['id_users'];
    }
}
