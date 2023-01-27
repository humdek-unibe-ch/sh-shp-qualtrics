<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php
require_once __DIR__ . "/../../../../../component/BaseComponent.php";
require_once __DIR__ . "/ModuleQualtricsActionView.php";
require_once __DIR__ . "/ModuleQualtricsActionModel.php";
require_once __DIR__ . "/ModuleQualtricsActionController.php";

/**
 * The class to define the asset select component.
 */
class ModuleQualtricsActionComponent extends BaseComponent
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
        $aid = isset($params['aid']) ? intval($params['aid']) : null;
        $mode = isset($params['mode']) ? $params['mode'] : null;
        $model = new ModuleQualtricsActionModel($services, $aid);
        $controller = new ModuleQualtricsActionController($model, $aid);
        $view = new ModuleQualtricsActionView($model, $controller, $aid, $mode);
        parent::__construct($model, $view, $controller);
    }
}
?>
