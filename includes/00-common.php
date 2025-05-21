<?PHP

// Enqueue admin styles
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'linkfolio') !== false) {
        wp_enqueue_style('linkfolio-style', plugin_dir_url(__DIR__) . 'assets/linkfolio.css');
    }
});

// Define available bullet/separator characters globally
if (!defined('LF_SEPARATORS'))
{
    define('LF_SEPARATORS', [' ','•','★','*','–','◯','■','◆','◇','✔','♠','♥','♦','♣','⬤','⬛','⬜','⬟','⬢','⬡','⬠','⬣']);
}

add_action('admin_footer', function () {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('a[data-scroll-to]').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.getAttribute('data-scroll-to');
                const target = document.getElementById(id);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    target.classList.add('link-highlight');
                    setTimeout(() => target.classList.remove('link-highlight'), 1000);
                }
            });
        });
    });
    </script>
    <?php
});

function lf_scroll_to_element_script($id)
{
    if (!empty($id)) {
        add_action('admin_footer', function () use ($id) {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('<?php echo esc_js($id); ?>');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('link-highlight');
                }
            });
            </script>
            <?php
        });
    }
}

function lf_url_with_wbr($url) {
    // Insert <wbr> after every slash
    return str_replace('/', '/<wbr>', esc_html($url));
}

function linkfolio_register_block()
{
    wp_register_script(
        'linkfolio-block-script',
        plugins_url('assets/block/index.js', __FILE__),
        [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-api-fetch' ],
        filemtime(plugin_dir_path(__FILE__) . 'assets/block/index.js')
    );

    register_block_type(
        'linkfolio/shortcode',
        array(
            'editor_script' => 'linkfolio-block-script',
        )
    );
}
add_action('init', 'linkfolio_register_block');

add_action('rest_api_init', function() {
    register_rest_route('linkfolio/v1', '/categories', array(
        'methods' => 'GET',
        'callback' => function() {
            global $wpdb;
            $table = $wpdb->prefix . 'linkfolio_link_categories';
            $rows = $wpdb->get_results("SELECT name, slug FROM $table ORDER BY name ASC");
            return $rows;
        },
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
});

add_action('admin_init', function () {
    add_filter('mce_external_plugins', function ($plugins) {
        $plugins['linkfolio_shortcode'] = plugins_url('assets/linkfolio-tinymce.js', __FILE__);
        return $plugins;
    });
    add_filter('mce_buttons', function ($buttons) {
        array_push($buttons, 'linkfolio_shortcode');
        return $buttons;
    });
});

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('wp-api');
});
