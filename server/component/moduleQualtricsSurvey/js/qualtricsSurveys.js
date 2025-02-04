// jquery extend function for post submit
$.extend(
    {
        redirectPost: function (location, args) {
            var form = '';
            $.each(args, function (key, value) {
                value = value.split('"').join('\"')
                form += '<input type="hidden" name="' + key + '" value="' + value + '">';
            });
            $('<form action="' + location + '" method="POST">' + form + '</form>').appendTo($(document.body)).submit();
        }
    });

$(document).ready(function () {
    var table = $('#qualtrics-surveys').DataTable({
        "order": [[0, "asc"]]
    });
    table.on('click', 'tr[id|="survey-url"]', function (e) {
        var ids = $(this).attr('id').split('-');
        document.location = 'survey/select/' + parseInt(ids[2]);
    });

    var actionOptions = {
        iconPrefix: 'fas fa-fw',
        classes: [],
        contextMenu: {
            enabled: true,
            isMulti: false,
            xoffset: -10,
            yoffset: -10,
            headerRenderer: function (rows) {
                if (rows.length > 1) {
                    // For when we have contextMenu.isMulti enabled and have more than 1 row selected
                    return rows.length + ' surveys selected';
                } else if (rows.length > 0) {
                    let row = rows[0];
                    return 'Survey ' + row[0] + ' selected';
                }
            },
        },
        showConfirmationMethod: (confirmation) => {
            $.confirm({
                title: confirmation.title,
                content: confirmation.content,
                buttons: {
                    confirm: function () {
                        return confirmation.callback(true);
                    },
                    cancel: function () {
                        return confirmation.callback(false);
                    }
                }
            });
        },
        buttonList: {
            enabled: true,
            iconOnly: false,
            containerSelector: '#my-button-container',
            groupClass: 'btn-group',
            disabledOpacity: 0.4,
            dividerSpacing: 10,
        },
        deselectAfterAction: false,
        items: [
            // Empty starter seperator to demonstrate that it won't render
            {
                type: 'divider',
            },

            {
                type: 'option',
                multi: false,
                title: 'View',
                iconClass: 'fa-eye',
                buttonClasses: ['btn', 'btn-outline-secondary'],
                contextMenuClasses: ['text-secondary'],
                action: function (row) {
                    var ids = row[0].DT_RowId.split('-');
                    window.open('survey/select/' + parseInt(ids[2]), '_blank')
                },
                isDisabled: function (row) {
                },
            },

            {
                type: 'divider',
            },

            {
                type: 'option',
                multi: false,
                title: 'Edit',
                iconClass: 'fa-edit',
                buttonClasses: ['btn', 'btn-outline-secondary'],
                contextMenuClasses: ['text-secondary'],
                action: function (row) {
                    var ids = row[0].DT_RowId.split('-');
                    window.open('survey/update/' + parseInt(ids[2]), '_blank')
                },
                isDisabled: function (row) {
                },
            },



            // Empty ending seperator to demonstrate that it won't render
            {
                type: 'divider',
            },
        ],
    };

    table.contextualActions(actionOptions);

    $(function () {
        $('[data-bs-toggle="popover"]').popover({ html: true });
    });

    //confirmation for Qualtrics sync on survey
    var qualtricsSycnButton = $('.style-section-syncQualtricsSurvey').first();
    var hrefSingleSurvey = $(qualtricsSycnButton).attr('href');
    qualtricsSycnButton.click(function (e) {
        e.preventDefault();
        $.confirm({
            title: 'Qualtrics Synchronization',
            content: 'Are you sure that you want to synchronize this survey?',
            buttons: {
                confirm: function () {                    
                    $(qualtricsSycnButton).attr('href', '#');
                    event.stopPropagation();
                    $.redirectPost(hrefSingleSurvey, { mode: 'select', type: 'qualtricsSync' });
                },
                cancel: function () {

                }
            }
        });
    });

    //confirmation for Qualtrics sync on survey
    var qualtricsSycnButton = $('.style-section-syncAndPublishQualtricsSurvey').first();
    var hrefSingleSurvey = $(qualtricsSycnButton).attr('href');
    qualtricsSycnButton.click(function (e) {
        e.preventDefault();
        $.confirm({
            title: 'Qualtrics Sync & Publish',
            content: 'Are you sure that you want to synchronize this survey and publish it?',
            buttons: {
                confirm: function () {                    
                    $(qualtricsSycnButton).attr('href', '#');
                    event.stopPropagation();
                    $.redirectPost(hrefSingleSurvey, { mode: 'select', type: 'qualtricsSyncAndPublish' });
                },
                cancel: function () {

                }
            }
        });
    });

    //confirmation for pull unsaved data
    var qualtricsPullDataButton = $('.style-section-pullUnsavedData').first();
    var hrefSingleSurvey = $(qualtricsPullDataButton).attr('href');
    qualtricsPullDataButton.click(function (e) {
        e.preventDefault();
        $.confirm({
            title: 'Qualtrics Pull Unsaved Data',
            content: 'Are you sure that you want to pull all data that is not already saved? It will pull the data only for existing users.',
            buttons: {
                confirm: function () {                    
                    $(qualtricsPullDataButton).attr('href', '#');
                    event.stopPropagation();
                    $.redirectPost(hrefSingleSurvey, { mode: 'select', type: 'qualtricsPullUnsavedData' });
                },
                cancel: function () {

                }
            }
        });
    });

    //confirmation for Qualtrics sync for all surveys
    var qualtricsSycnButton = $('.style-section-syncQualtricsSurveys').first();
    qualtricsSycnButton.click(function (e) {
        e.preventDefault();
        $.confirm({
            title: 'Qualtrics Synchronization',
            content: 'Are you sure that you want to synchronize all surveys?',
            buttons: {
                confirm: function () {
                    var href = $(qualtricsSycnButton).attr('href');
                    $(qualtricsSycnButton).attr('href', '#');
                    event.stopPropagation();  
                    console.log(href);                  
                    $.redirectPost(href, { mode: 'select', type: 'qualtricsSync' });
                },
                cancel: function () {

                }
            }
        });
    });

    //confirmation for Qualtrics sync for all surveys
    var qualtricsSycnButton = $('.style-section-syncQualtricsSurveysAndPublish').first();
    qualtricsSycnButton.click(function (e) {
        e.preventDefault();
        $.confirm({
            title: 'Qualtrics Sync & Publish',
            content: 'Are you sure that you want to synchronize all surveys and then publish them?',
            buttons: {
                confirm: function () {
                    var href = $(qualtricsSycnButton).attr('href');
                    $(qualtricsSycnButton).attr('href', '#');
                    event.stopPropagation();  
                    console.log(href);                  
                    $.redirectPost(href, { mode: 'select', type: 'qualtricsSyncAndPublish' });
                },
                cancel: function () {

                }
            }
        });
    });

});
