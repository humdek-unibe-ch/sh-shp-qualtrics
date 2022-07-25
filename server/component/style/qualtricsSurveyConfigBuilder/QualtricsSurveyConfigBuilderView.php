<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../../../component/style/formField/FormFieldView.php";

/**
 * The view class of the QualtricsSurveyConfigBuilderBuilder style component.
 */
class QualtricsSurveyConfigBuilderView extends FormFieldView
{
    /* Private Properties *****************************************************/

    /**
     * DB field 'placeholder' (empty string).
     * The text to be displayed inside the input field.
     */
    private $placeholder;

    /**
     * The type of the text area
     */
    private $type_input;

    /* Constructors ***********************************************************/

    /**
     * The constructor.
     *
     * @param object $model
     *  The model instance of a base style component.
     */
    public function __construct($model)
    {
        parent::__construct($model);
        $this->placeholder = '';
        $this->type_input = 'textarea';
    }

    /* Protected Methods ********************************************************/

    /**
     * Render the textarea.
     */
    protected function output_form_field()
    {
        if ($this->value === null)
            $this->value = $this->default_value;
        require __DIR__ . "/tpl_textarea.php";
    }

    /* Public Methods *********************************************************/

    /**
     * Render the builder buttons and modal forms if they are needed
     */
    public function output_builder()
    {
        $modal = new BaseStyleComponent('modal', array(
            'title' => "Qualtrics Survey Config Builder",
            "css" => "qualtricsSurveyConfig_builder_modal_holder",
            'children' => array(
                new BaseStyleComponent("div", array(
                    "css" => "qualtricsSurveyConfig_builder"
                )),
                new BaseStyleComponent("div", array(
                    "css" => "modal-footer",
                    "children" => array(
                        new BaseStyleComponent("button", array(
                            "label" => "Save",
                            "url" => "#",
                            "type" => "secondary",
                            "css" => "savequaltricsSurveyConfigBuilder btn-sm"
                        )),
                    )
                ))
            ),
        ));
        $modal->output_content();
        require __DIR__ . "/tpl_qualtrics_survey_config_builder.php";
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
                $local = array( __DIR__ . "/js/qualtricsSurveyConfigBuilder.js");
            } else {
                $local = array(__DIR__ . "/../../../../qualtrics/js/ext/qualtrics.min.js?v=" . rtrim(shell_exec("git describe --tags")));
            }
        }
        return parent::get_js_includes($local);
    }
}
?>
