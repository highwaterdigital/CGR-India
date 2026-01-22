(function ($) {
    function toggleNextFields($mode) {
        var $card = $mode.closest('.cgr-card');
        var $interval = $card.find('[data-cgr-next-interval]');
        var $fixed = $card.find('[data-cgr-next-fixed]');
        var value = $mode.val();

        $interval.toggle(value === 'interval');
        $fixed.toggle(value === 'fixed');
    }

    $(function () {
        if ($.fn.select2) {
            $('.cgr-popup-select2').select2({
                width: '100%',
                placeholder: 'Select pages or posts'
            });
        }

        $('[data-cgr-next-mode]').each(function () {
            var $mode = $(this);
            toggleNextFields($mode);
            $mode.on('change', function () {
                toggleNextFields($mode);
            });
        });
    });
})(jQuery);
