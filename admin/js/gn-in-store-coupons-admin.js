(function ($) {
    'use strict';
    $('.gn-action').on('submit', function () {
        return window.confirm(this.dataset.confirm);
    });
    $('#gn-select-logo').on('click', function () {
        var frame = wp.media({ title: 'Store logo', multiple: false, library: { type: 'image' } });
        frame.on('select', function () {
            var logo = frame.state().get('selection').first().toJSON();
            $('#gn-logo').val(logo.id);
            $('#gn-logo-preview').empty().append($('<img>', { src: logo.url, alt: logo.alt || '' }));
        });
        frame.open();
    });
    $('#gn-remove-logo').on('click', function () {
        $('#gn-logo').val(0);
        $('#gn-logo-preview').empty();
    });
})(jQuery);
