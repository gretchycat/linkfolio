document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.lm-tab-button');
    const tabs = document.querySelectorAll('.lm-tab-content');

    // Check if we have any buttons
    if (buttons.length === 0 || tabs.length === 0) return;

    buttons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // prevent anchor fallback if JS works

            const target = button.getAttribute('data-tab');
            tabs.forEach(tab => tab.style.display = 'none');
            buttons.forEach(btn => btn.classList.remove('active'));

            document.getElementById(target)?.style?.setProperty('display', 'block');
            button.classList.add('active');

            // Optional: Update hash in URL
            history.replaceState(null, '', '#' + target);
        });
    });

    // Activate tab based on URL hash (fallback-friendly)
    const initialTab = location.hash?.replace('#', '') || buttons[0]?.getAttribute('data-tab');
    const targetButton = document.querySelector(`.lm-tab-button[data-tab="${initialTab}"]`);
    if (targetButton) targetButton.click();
});