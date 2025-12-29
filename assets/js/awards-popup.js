(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var grid = document.querySelector('.cgr-awards-grid');
        if (!grid) {
            return;
        }

        var popup = document.querySelector('.cgr-awards-popup');
        if (!popup) {
            return;
        }

        var titleEl = popup.querySelector('[data-award-title]');
        var yearEl = popup.querySelector('[data-award-year]');
        var typeEl = popup.querySelector('[data-award-type]');
        var issuerEl = popup.querySelector('[data-award-issuer]');
        var detailEl = popup.querySelector('[data-award-detail]');
        var linkEl = popup.querySelector('[data-award-link]');
        var closeBtn = popup.querySelector('.cgr-awards-popup__close');

        function openAward(data) {
            if (data.title) {
                titleEl.textContent = data.title;
            }
            yearEl.textContent = data.year || '';
            typeEl.textContent = data.type || '';
            issuerEl.textContent = data.issuer || '';
            detailEl.innerHTML = data.detail || '';

            if (data.link) {
                linkEl.href = data.link;
                linkEl.style.display = 'inline-flex';
            } else {
                linkEl.style.display = 'none';
            }

            popup.classList.add('is-open');
            document.body.classList.add('cgr-awards-open');
        }

        function closeAward() {
            popup.classList.remove('is-open');
            document.body.classList.remove('cgr-awards-open');
        }

        grid.querySelectorAll('.cgr-award-card').forEach(function (card) {
            card.addEventListener('click', function () {
                var payload = card.getAttribute('data-award-meta');
                if (!payload) {
                    return;
                }

                try {
                    var data = JSON.parse(payload);
                    openAward(data);
                } catch (error) {
                    console.error('Invalid award payload', error);
                }
            });
        });

        closeBtn.addEventListener('click', closeAward);

        popup.addEventListener('click', function (event) {
            if (event.target === popup || event.target.classList.contains('cgr-awards-popup__backdrop')) {
                closeAward();
            }
        });

        document.addEventListener('keyup', function (event) {
            if (event.key === 'Escape') {
                closeAward();
            }
        });
    });
})();
