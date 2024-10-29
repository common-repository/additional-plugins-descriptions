
jQuery( document ).ready( function( $ ) {
    $('table.plugins td.plugin-title').live('click',function (e) {
        if (e.target.tagName.toLowerCase()=='a')
            return true;
        $(e.target).closest('tr').find('table.apd-table').toggle();
    })



    $('td.apd-editable').on('focusout keyup',function (e) {
        //save only on press enter
        if (e.type=='keyup') {
            if (e.which!=13) {
                return;
            }
        }

        data={
            'action':'set_plugin_descriptions',
            'plugin_name':$(this).closest('table.apd-table').data('plugin_name'),
            'description_temporary':$(this).closest('table.apd-table').find('td.apd-editable-temporary').html(),
            'description_permanent':$(this).closest('table.apd-table').find('td.apd-editable-permanent').html()
        };

        $.post(ajaxurl,data)
            .done(function (response) {})
            .fail(function () {})
    });


} );
