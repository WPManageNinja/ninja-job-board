(function() {
    tinymce.PluginManager.add( 'wpjb_mce_job_button', function( editor, url ) {
        // Add Button to Visual Editor Toolbar
        editor.addButton('wpjb_mce_job_button', {
            title: 'Insert Job Application Form',
            cmd: 'wpjb_mce_job_command',
            image: url + '/tinymce_icon.png'
        });
        // Add Command when Button Clicked
        editor.addCommand('wpjb_mce_job_command', function() {
            editor.windowManager.open({
                title: window.wpjb_tinymce_vars.title,
                body: [
                    {
                        type   : 'listbox',
                        name   : 'wpjobboard_shortcode',
                        label  : window.wpjb_tinymce_vars.label,
                        values : window.wpjb_tinymce_vars.forms
                    },
                    {
                        type   : 'checkbox',
                        name   : 'wpjobboard_show_title',
                        label  : 'Show Form Title',
                        values : 'yes'
                    }
                ],
                width: 768,
                height: 150,
                onsubmit: function( e ) {
                    if( e.data.wpjobboard_shortcode ) {
                        let extraString = '';
                        if(e.data.wpjobboard_show_title) {
                            extraString += ' show_title="yes"';
                        }
                        let shortcodec = `[wp_job_form id="${e.data.wpjobboard_shortcode}"]`
                        if(extraString) {
                            shortcodec = `[wp_job_form id="${e.data.wpjobboard_shortcode}" ${extraString}]`;
                        }
                        editor.insertContent( shortcodec );
                    } else {
                        alert(window.wpjb_tinymce_vars.select_error);
                        return false;
                    }
                },
                buttons: [
                    {
                        text: window.wpjb_tinymce_vars.insert_text,
                        subtype: 'primary',
                        onclick: 'submit'
                    }
                ]
            }, {
                'tinymce': tinymce
            });
        });

    });
})();