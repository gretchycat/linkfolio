<?php
// 10-category-viewer.php
defined('ABSPATH') || exit;

function lf_render_category_row_view($cat) {
    $id = $cat->id;
    $can_delete = !$cat->is_default;

    echo '<div class="lf-category-row">';

    // First line: name, layout, separator
    echo '<div class="lf-category-line1">';
    echo '<strong>' . esc_html($cat->name) . '</strong>';
    echo ' &nbsp; Layout: <span>' . esc_html($cat->layout) . '</span>';
    echo ' &nbsp; Separator: <span>"' . esc_html($cat->separator) . '"</span>';
    echo '</div>';

    // Second line: toggles and buttons
    echo '<div class="lf-category-line2">';
    echo 'Icon: ' . ($cat->show_icon ? '✅' : '❌') . '&nbsp;&nbsp;';
    echo 'URL: ' . ($cat->show_url ? '✅' : '❌') . '&nbsp;&nbsp;';
    echo 'Description: ' . ($cat->show_description ? '✅' : '❌') . '&nbsp;&nbsp;';
    echo '<button type="submit" name="edit_' . $id . '" class="button">Edit</button> ';
    if ($can_delete) {
        echo '<button type="submit" name="delete_' . $id . '" class="button button-danger">Delete</button>';
    }
    echo '</div>';

    echo '</div>';
}