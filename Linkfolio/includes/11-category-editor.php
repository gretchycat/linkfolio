<?php
// category-editor.php
defined('ABSPATH') || exit;
function lm_render_category_row_editor($cat) {
    $id = $cat->id;
    $is_new = ($id === 'new');
    $save_name = $is_new ? 'save_new' : "save_$id";
    $cancel_name = $is_new ? 'cancel_new' : "cancel_$id";
    $is_default = ($cat->is_default ?? 0) == 1;

    echo '<div id="cat_edit_' . $id . '" class="lm-cat-row">';

    // Top row: name, layout, separator
    echo '<div class="lm-cat-top">';
    if ($is_default) {
        echo '<label class="lm-cat-label">Name:&nbsp;<span class="lm-readonly">' . esc_html($cat->name) . '</span></label>';
        echo '&nbsp;<input type="hidden" name="name_' . $id . '" value="' . esc_attr($cat->name) . '">';
    } else {
        echo '<label class="lm-cat-label">Name:&nbsp;<input type="text" name="name_' . $id . '" value="' . esc_attr($cat->name) . '" maxlength="100" size="10" class="lm-cat-input-short"></label>';
    }

    echo '&nbsp;<label class="lm-cat-label">Layout:&nbsp;<select name="layout_' . $id . '" class="lm-cat-input-select">';
    echo '<option value="vertical"' . ($cat->layout === 'vertical' ? ' selected' : '') . '>↓</option>';
    echo '<option value="horizontal"' . ($cat->layout === 'horizontal' ? ' selected' : '') . '>→</option>';
    echo '</select></label>&nbsp;';

    echo '<label class="lm-cat-label">Separator:&nbsp;<select name="separator_' . $id . '" class="lm-cat-input-select">';
    $separators = ['•', '★', '*', '–', '◯', '■', '◆', '◇', '✔', '♠', '♥', '♦', '♣', '⬤', '⬛', '⬜', '⬟', '⬢', '⬡', '⬠', '⬣'];
    foreach ($separators as $sep) {
        $selected = ($cat->separator === $sep) ? ' selected' : '';
        echo '<option value="' . esc_attr($sep) . '"' . $selected . '>' . esc_html($sep) . '</option>';
    }
    echo '</select></label>';
    echo '</div>'; // .lm-cat-top

    // Bottom row: toggles + buttons
    echo '<div class="lm-cat-bottom">';
    echo '<label class="lm-cat-label">Icon: <input type="checkbox" name="icon_' . $id . '"' . ($cat->show_icon ? ' checked' : '') . '></label>';
    echo '<label class="lm-cat-label">URL: <input type="checkbox" name="url_' . $id . '"' . ($cat->show_url ? ' checked' : '') . '></label>';
    echo '<label class="lm-cat-label">Description: <input type="checkbox" name="desc_' . $id . '"' . ($cat->show_description ? ' checked' : '') . '></label>';
    echo '<button type="submit" name="' . $save_name . '" class="button button-primary">Save</button>';
    echo '<button type="submit" name="' . $cancel_name . '" class="button">Cancel</button>';
    echo '</div>'; // .lm-cat-bottom

    echo '</div>'; // .lm-cat-row
}