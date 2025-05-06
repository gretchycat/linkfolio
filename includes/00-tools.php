<?PHP

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
