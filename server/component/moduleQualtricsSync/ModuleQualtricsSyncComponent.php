<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../../component/BaseComponent.php";
require_once __DIR__ . "/../moduleQualtricsProjectAction/ModuleQualtricsProjectActionView.php";
require_once __DIR__ . "/../moduleQualtricsProject/ModuleQualtricsProjectModel.php";
require_once __DIR__ . "/ModuleQualtricsSyncController.php";

/**
 * The class to define the asset select component.
 */
class ModuleQualtricsSyncComponent extends BaseComponent
{
    /* Constructors ***********************************************************/

    /**
     * The constructor creates an instance of the Model class and the View
     * class and passes them to the constructor of the parent class.
     *
     * @param array $services
     *  An associative array holding the different available services. See the
     *  class definition BasePage for a list of all services.
     */
    public function __construct($services, $params)
    {
        $sid = isset($params['sid']) ? intval($params['sid']) : null;
        $model = new ModuleQualtricsSurveyModel($services);
        $controller = new ModuleQualtricsSyncController($model, $sid);
        $view = new ModuleQualtricsSurveyView($model, $controller, $sid, null);
        parent::__construct($model, $view, $controller);
    }
}
?>
