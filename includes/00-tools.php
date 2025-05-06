<?PHP

function lf_scroll_to_element_script($id)
{
    if (!empty($id)) 
    {
        echo <<<JS
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('edit_link_$id');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('link-highlight');
    }
});
</script>
JS;
    }
}
