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
        $('[data-toggle="popover"]').popover({ html: true });
    });
});
