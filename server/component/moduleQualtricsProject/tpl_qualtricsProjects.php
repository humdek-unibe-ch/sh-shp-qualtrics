<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
?>
<div class="card mb-3 card-secondary">
    <div class="card-header collapsible">
        <div class="d-flex align-items-center">
            Projects
            <div class="ms-auto">
                <i class="card-icon-collapse ms-3 fas fa-angle-double-up"></i>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="qualtrics-projects" class="table table-sm table-hover">
            <thead>
                <tr>
                    <th scope="col">Project ID</th>
                    <th scope="col">Project Name</th>
                    <th scope="col">API Mailing Group</th>
                    <th scope="col">Project Description</th>
                </tr>
            </thead>
            <tbody>
                <?php $this->output_projects_rows(); ?>
            </tbody>
        </table>
    </div>
</div>