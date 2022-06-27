<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<?php

/**
 * The class to define the asset select component.
 */
class QualtricsSurveyInit
{

    /* Private Properties *****************************************************/

    /**
     * All params required for an initialization 
     */
    private $params;

    /* Constructors ***********************************************************/

    /**
     * The constructor creates an instance of the Model class and the View
     * class and passes them to the constructor of the parent class.
     *
     * @param object $params
     *  All params required for an initialization     
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /* Public Methods *********************************************************/

    public function initFieldType()
    {
        return new BaseStyleComponent("select", array(
            "value" => $this->params['field']['content'],
            "name" => $this->params['field_name_prefix'] . "[content]",
            "max" => 10,
            "live_search" => 1,
            "is_required" => 1,
            "items" => $this->params['services']->get_db()->fetch_table_as_select_values('qualtricsSurveys', 'id', array('name', 'qualtrics_survey_id'))
        ));
    }
}
?>