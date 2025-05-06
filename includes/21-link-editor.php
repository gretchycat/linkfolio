<?php
// link-editor.php
defined('ABSPATH') || exit;

if (!defined('LM_PLUGIN_PATH')) {
    define('LM_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('LM_PLUGIN_URL')) {
    define('LM_PLUGIN_URL', plugin_dir_url(__FILE__));
}

function lf_render_link_row_editor($link) {
    global $wpdb;
    $id = $link->id;
    $is_new = ($id === 'new');
    $save_name = $is_new ? 'save_link_new' : "save_link_$id";
    $cancel_name = $is_new ? 'cancel_link_new' : "cancel_link_$id";

    $cat_table = $wpdb->prefix . 'custom_link_categories';
    $categories = $wpdb->get_results("SELECT slug, name FROM $cat_table ORDER BY name ASC");

    $icon_id = "icon_$id";
    $icon_url = esc_attr($link->icon_url);
    $placeholder = LM_PLUGIN_URL . 'assets/placeholder.png';

    echo '<div id="edit_link_' . $id . '" class="lf-link-editor">';
    
    // Row 1: Icon, label, category
    echo '<div class="lf-link-top">';
    echo '<div class="lf-icon-wrapper" onclick="openMediaSelector(\'' . $icon_id . '\')">';
    echo '<img id="' . $icon_id . '_preview" src="' . ($icon_url ?: $placeholder) . '" alt="icon preview" width=96 height=96>';
    echo '</div>';
    echo '<input type="hidden" name="' . $icon_id . '" id="' . $icon_id . '" value="' . $icon_url . '">';
    echo '<input type="hidden" name="' . "status_code_$id" . '"" value="' . $link->status_code . '">';
    echo '<label class="lf-label">Label:&nbsp;<input type="text" name="label_' . $id . '" value="' . esc_attr($link->label) . '" maxlength="100" size="10" class="lf-input-short"></label>';
    echo '&nbsp;<label class="lf-label">Category:&nbsp;<select name="category_' . $id . '" class="lf-select">';
    foreach ($categories as $cat) {
        $selected = ($cat->slug === $link->category_slug) ? 'selected' : '';
        echo '<option value="' . esc_attr($cat->slug) . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
    }
    echo '</select></label>';
    echo '</div>';

    // Row 2: URL
    echo '<div class="lf-link-middle">';
    echo '<label class="lf-label">URL:&nbsp;<input type="url" name="url_' . $id . '" size="30" value="' . esc_attr($link->url) . '" class="lf-input-long"></label>';
    echo '</div>';

    // Row 3: Description
    echo '<div class="lf-link-desc">';
    echo '<label class="lf-label">Description:</label><br>';
    echo '<textarea name="desc_' . $id . '" rows="2" cols="40" class="lf-textarea">' . esc_textarea($link->description) . '</textarea>';
    echo '</div>';

    // Row 4: Controls
    echo '<div class="lf-link-controls">';
    echo '<button type="submit" name="' . $save_name . '" class="button button-primary">Save</button> ';
    echo '<button type="submit" name="' . $cancel_name . '" class="button">Cancel</button>';
    echo '</div>';

    echo '</div>';
}

// Attach media selector script
add_action('admin_footer', function () {
    ?>
<script>
function openMediaSelector(targetId) {
    const frame = wp.media({
        title: 'Select or Upload Icon',
        button: { text: 'Use this image' },
        multiple: false
    });
    frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        document.getElementById(targetId).value = attachment.url;
        const preview = document.getElementById(targetId + '_preview');
        preview.src = attachment.url;
        preview.style.display = 'inline-block';
    });
    frame.open();
}
</script>
<?php
});
