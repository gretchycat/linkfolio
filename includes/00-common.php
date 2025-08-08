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

function template_render($template_file, $vars = array())
{
    $template_path = plugin_dir_path(__FILE__) . 'templates/' . $template_file;
    if (!file_exists($template_path)) {
        error_log( "<!-- Template $template_path not found -->");
        return "<!-- Template $template_file not found -->";
    }
    $template = file_get_contents($template_path);

    // Replace placeholders like __VARNAME__ with the variable's value
    foreach ($vars as $key => $value) {
        $template = str_replace('__' . strtoupper($key) . '__', $value, $template);
    }

    return $template;
}
