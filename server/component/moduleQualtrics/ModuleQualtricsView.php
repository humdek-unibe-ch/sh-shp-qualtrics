<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../../component/BaseView.php";
require_once __DIR__ . "/../../../../../component/style/BaseStyleComponent.php";

/**
 * The view class of the asset select component.
 */
class ModuleQualtricsView extends BaseView
{
    /* Constructors ***********************************************************/

    /**
     * The constructor.
     *
     * @param object $model
     *  The model instance of the component.
     */
    public function __construct($model, $controller)
    {
        parent::__construct($model, $controller);
    }

    /* Private Methods ********************************************************/


    /* Public Methods *********************************************************/

    /**
     * Render the footer view.
     */
    public function output_content()
    {
        require __DIR__ . "/tpl_moduleQualtrics.php";
    }
	
	public function output_content_mobile()
    {
        echo 'mobile';
    }

    /**
     * Render the navbar
     */
    public function output_navbar($title)
    {
        $res = array();
        $res[] = array(
            "title" => "Qualtrics",
            "active" => $title == "Qualtrics" ? "active" : "",
            "url" => $this->model->get_link_url("moduleQualtrics")
        );
        $res[] = array(
            "title" => "Projects",
            "active" => $title == "Projects" ? "active" : "",
            "url" => $this->model->get_link_url("moduleQualtricsProject")
        );
        $res[] = array(
            "title" => "Surveys",
            "active" => $title == "Surveys" ? "active" : "",
            "url" => $this->model->get_link_url("moduleQualtricsSurvey")
        );        
        $navbar = new BaseStyleComponent("navigationBar", array(
            "items" => $res,
            "css" => "navbar-light bg-light"
        ));
        $navbar->output_content();
    }

    /**
     * render the page content
     */
    public function output_page_content()
    {
        //echo round(stats_cdf_normal(3.7, 3.32, 0.53, 1) * 100);
        echo 'Dashboard - TODO';        
    }

    /**
     * Render the sidebar buttons
     */
    public function output_side_buttons()
    {
        //dummy
    }

    /**
     * Render the alert message.
     */
    protected function output_alert()
    {
        $this->output_controller_alerts_fail();
        $this->output_controller_alerts_success();
    }
}
?>
