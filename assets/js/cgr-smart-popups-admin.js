(function ($) {
    function toggleNextFields($mode) {
        var $card = $mode.closest('.cgr-card');
        var $interval = $card.find('[data-cgr-next-interval]');
        var $fixed = $card.find('[data-cgr-next-fixed]');
        var value = $mode.val();

        $interval.toggle(value === 'interval');
        $fixed.toggle(value === 'fixed');
    }

    function toggleTargetFields($mode) {
        var $card = $mode.closest('.cgr-card');
        var value = $mode.val();
        $card.find('[data-cgr-target-ids]').toggle(value === 'specific');
        $card.find('[data-cgr-target-urls]').toggle(value === 'url');
    }

    $(function () {
        if ($.fn.select2) {
            $('.cgr-popup-select2').select2({
                width: '100%',
                placeholder: 'Select pages or posts'
            });
        }

        if (window.flatpickr) {
            $('.cgr-popup-datetime').each(function () {
                window.flatpickr(this, {
                    enableTime: true,
                    time_24hr: true,
                    dateFormat: 'Y-m-d H:i',
                    allowInput: false
                });
            });
        }

        $('[data-cgr-next-mode]').each(function () {
            var $mode = $(this);
            toggleNextFields($mode);
            $mode.on('change', function () {
                toggleNextFields($mode);
            });
        });

        $('[data-cgr-target-mode]').each(function () {
            var $mode = $(this);
            toggleTargetFields($mode);
            $mode.on('change', function () {
                toggleTargetFields($mode);
            });
        });
    });
})(jQuery);
