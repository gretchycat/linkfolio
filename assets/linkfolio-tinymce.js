(function() {
    tinymce.create('tinymce.plugins.linkfolio_shortcode', {
        init: function(editor, url) {
            editor.addButton('linkfolio_shortcode', {
                text: 'Linkfolio',
                icon: false,
                onclick: function() {
                    // Fetch categories via AJAX (REST API)
                    jQuery.get(wpApiSettings.root + 'linkfolio/v1/categories', function(data) {
                        var select = '<select id="linkfolio-cat">';
                        data.forEach(function(cat) {
                            select += '<option value="' + cat.slug + '">' + cat.name + '</option>';
                        });
                        select += '</select>';
                        editor.windowManager.open({
                            title: 'Insert Linkfolio Category',
                            body: [{type: 'container', html: select}],
                            onsubmit: function(e) {
                                var slug = jQuery('#linkfolio-cat').val();
                                editor.insertContent('[linkfolio category="' + slug + '"]');
                            }
                        });
                    });
                }
            });
        }
    });
    tinymce.PluginManager.add('linkfolio_shortcode', tinymce.plugins.linkfolio_shortcode);
})();

(function() {
    tinymce.PluginManager.add('linkfolio', function(editor, url) {
        editor.addButton('linkfolio', {
            text: 'Linkfolio',
            icon: false,
            onclick: function() {
                // Insert the shortcode at the cursor
                editor.insertContent('[linkfolio]');
            }
        });
    });
})();
