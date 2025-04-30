jQuery(document).ready(function ($) {
  $(document).on('click', '.lm-select-icon, .icon-preview img', function (e) {
    e.preventDefault();
    const wrapper = $(this).closest('.icon-preview');
    const input = wrapper.siblings('.link-icon-url');

    const frame = wp.media({
      title: 'Select Icon',
      button: { text: 'Use this icon' },
      multiple: false
    });

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      input.val(attachment.url);
      wrapper.html(`<img src="${attachment.url}" alt="Icon">`);
    });

    frame.open();
  });
});
