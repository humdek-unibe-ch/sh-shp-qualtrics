<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<div class="card mb-3 card-secondary">
    <div class="card-header collapsible">
        <div class="d-flex align-items-center">
            Surveys
            <div class="ml-auto">
                <i class="card-icon-collapse ml-3 fas fa-angle-double-up"></i>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="qualtrics-surveys" class="table table-sm table-hover">
            <thead>
                <tr>
                    <th scope="col">Survey ID</th>
                    <th scope="col">Survey Name</th>
                    <th scope="col">Qualtrics Survey ID</th>
                    <th scope="col">Project</th>
                    <th scope="col">Survey Type</th>
                    <th scope="col">Group variabe</th>
                    <th scope="col">Survey Description</th>
                </tr>
            </thead>
            <tbody>
                <?php $this->output_surveys_rows(); ?>
            </tbody>
        </table>
    </div>
</div>