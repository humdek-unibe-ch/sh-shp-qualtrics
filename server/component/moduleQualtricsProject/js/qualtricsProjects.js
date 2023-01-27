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
    if (window.history.replaceState) {
        //prevent resend of the post ************ IMPORTANT *****************************
        window.history.replaceState(null, null, window.location.href);
    }

    //datatable projects
    var tableProjects = $('#qualtrics-projects').DataTable({
        "order": [[0, "asc"]]
    });

    tableProjects.on('click', 'tr[id|="project-url"]', function (e) {
        var ids = $(this).attr('id').split('-');
        document.location = 'project/select/' + parseInt(ids[2]);
    });

    var actionOptionsProjects = {
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
                    return rows.length + ' projects selected';
                } else if (rows.length > 0) {
                    let row = rows[0];
                    return 'Project ' + row[0] + ' selected';
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
            // Empty starter separator to demonstrate that it won't render
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
                    window.open('project/select/' + parseInt(ids[2]), '_blank')
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
                    window.open('project/update/' + parseInt(ids[2]), '_blank')
                },
                isDisabled: function (row) {
                },
            },

            

            // Empty ending separator to demonstrate that it won't render
            {
                type: 'divider',
            },
        ],
    };

    tableProjects.contextualActions(actionOptionsProjects);

    $(function () {
        $('[data-toggle="popover"]').popover({ html: true });
    });    

});