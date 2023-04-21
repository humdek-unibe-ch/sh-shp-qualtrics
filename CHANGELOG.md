# v2.0.3
### Bugfix
 - fix `v2.0.0.sql` script when it fails in some rare case when there are Qualtrics surveys created but never used and are not attached to actions

# v2.0.2
### Bugfix
 - add log when request is made for a survey response
 - properly load the API key for the user who last synced the survey


# v2.0.1
 - add transaction log when the survey data is pulled

# v2.0.0 - Require Selfhelp 6.0.0+
### Bugfix
 - Fix new project error (php8)

### New Features
 - add field `save_data` on a survey. When it is enabled it pulls all survey data once the survey is finished. Now the save use the universal `save_external_data` function
 - move `moduleQualtrics` to parent `modules`
 - move `qualtrics_api` from project to user (it is personal, now)
 - remove `participant_variable` column from table `qualtricsSurveys`. The value is always `code` 
 - move the relation between project and surveys to be saved in the surveys table instead through the actions table
 - surveys now are synchronized at `surveys` not at `actions`
 - add field `extra_params` to style `qualtricsSurveys`
 - add `publish` survey functionality
 - remove the functionality to create user form Qualtrics survey
 - use `global_values` for translation
 - remove `create action` - now all actions are scheduled by the universal `form action`

# v1.2.1
### Bugfix
 - check `reminders` when a Qualtrics action is created

# v1.2.0
### Bugfix
 - Fix delete reminders when survey is done for diary type reminders

### New Features
 - record in the `callbackLog` deleted reminders info

# v1.1.1
 ### Bugfix
 - Fix `config` field not empty when create a new survey.
 - Show field `valid` for notifications. The filed is used for notifications that have reminders attached to them.

# v1.1.0 (require Selfhelp v5.3.0)
 -  on clear user data now it clear the user's Qualtrics responses

# v1.0.5
 - adjust create action for PHP8.1
 - adjust `groups` for mysql 8

# v1.0.4
 - replace PHP stats library with [math-php](https://github.com/markrogoyski/math-php) in preparation for PHP 8 migration

# v1.0.3
### New Features
 - add an option to attach files when schedule a mail action
 - for `qualtricsProject` list on right click open edit or view action in a new tab
 - for `qualtricsActions` list on right click open edit or view action in a new tab
 - for `qualtricsSurveys` list on right click open edit or view action in a new tab
 - add `searchBuilder` for `qualtricsActions`;

# v1.0.2
### Bugfix
 - fix action type codes

# v1.0.1

### New Features

 - Rework how `hooks`  from type `hook_overwrite_return` are executed

# v1.0.0

### New Features

 - The Qualtrics related styles and components
