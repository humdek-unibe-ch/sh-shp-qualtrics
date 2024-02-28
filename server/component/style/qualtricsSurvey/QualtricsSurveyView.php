<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../../../component/style/StyleView.php";

/**
 * The view class of the asset select component.
 */
class QualtricsSurveyView extends StyleView
{

    /* Private Properties *****************************************************/

    /**
     * Markdown text that is shown if the survey is done and it can be filled only once per schedule.
     */
    private $label_survey_done;

    /**
     * Markdown text that is shown if the survey is not active right now.
     */
    private $label_survey_not_active;

    /* Constructors ***********************************************************/

    /**
     * The constructor.
     *
     * @param object $model
     *  The model instance of the component.
     */
    public function __construct($model)
    {
        parent::__construct($model);
        $this->label_survey_done = $this->model->get_db_field('label_survey_done', '');
        $this->label_survey_not_active = $this->model->get_db_field('label_survey_not_active', '');
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

    /* Public Methods *********************************************************/

    /**
     * Render the footer view.
     */
    public function output_content()
    {
        if ($this->model->is_survey_active()) {
            if ($this->model->is_survey_done()) {
                if ($this->label_survey_done != '') {
                    $alert = new BaseStyleComponent("alert", array(
                        "type" => "danger",
                        "is_dismissable" => false,
                        "children" => array(new BaseStyleComponent("markdown", array(
                            "text_md" => $this->label_survey_done,
                        )))
                    ));
                    $alert->output_content();
                }
            } else {
                require __DIR__ . "/tpl_qualtricsSurvey.php";
            }
        } else {
            if ($this->label_survey_not_active != '') {
                $alert = new BaseStyleComponent("alert", array(
                    "type" => "danger",
                    "is_dismissable" => false,
                    "children" => array(new BaseStyleComponent("markdown", array(
                        "text_md" => $this->label_survey_not_active,
                    )))
                ));
                $alert->output_content();
            }
        }
    }

    /**
     * Load the survey link for the iFrame
     */
    public function get_survey_link()
    {
        return $this->model->get_survey_link();
    }

    public function output_iframe()
    {
        if ($this->model->get_db_field('use_as_container', 0)) {
            return;
        } else {
            require __DIR__ . "/tpl_qualtricsSurvey_iframe.php";
        }
    }

    public function output_content_mobile()
    {
        $style = parent::output_content_mobile();
        $style['qualtrics_url'] = $this->model->get_survey_link();
        $style['alert'] = '';
        $style['time'] = date('Y-m-d H:i:s');
        $style['show_survey'] = true;
        if ($this->model->is_survey_active()) {
            if ($this->model->is_survey_done()) {
                $style['alert'] = $this->label_survey_done;
                $style['show_survey'] = false;
            }
        } else {
            $style['alert'] = $this->label_survey_not_active;
            $style['show_survey'] = false;
        }
        return $style;
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
                $local = array(__DIR__ . "/js/iframeResizer.min.js", __DIR__ . "/js/qualtricsSurvey.js");
            } else {
                $local = array(__DIR__ . "/../../../../js/ext/qualtrics.min.js?v=" . rtrim(shell_exec("git describe --tags")));
            }
        }
        return parent::get_js_includes($local);
    }

    /**
     * Get css include files required for this component. This overrides the
     * parent implementation.
     *
     * @retval array
     *  An array of css include files the component requires.
     */
    public function get_css_includes($local = array())
    {
        if (empty($local)) {
            if (DEBUG) {
                $local = array(__DIR__ . "/css/qualtricsSurvey.css");
            } else {
                $local = array(__DIR__ . "/../../../../css/ext/qualtrics.min.css?v=" . rtrim(shell_exec("git describe --tags")));
            }
        }
        return parent::get_css_includes($local);
    }
}
?>
