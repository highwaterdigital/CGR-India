document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cgr-visitor-counter-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to update the visitor count to ' + countInput.value + '?')) {
                return;
            }

            const countInput = document.getElementById('cgr_visitor_counter_count');
            const noticeDiv = document.getElementById('cgr-visitor-counter-notice');
            const currentCountTd = document.getElementById('cgr-current-count');
            const submitButton = document.getElementById('cgr_visitor_counter_submit');

            submitButton.disabled = true;

            const data = new FormData();
            data.append('action', 'cgr_update_visitor_count');
            data.append('count', countInput.value);
            data.append('_ajax_nonce', cgr_visitor_counter.nonce);


            fetch(cgr_visitor_counter.ajax_url, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    noticeDiv.className = 'notice notice-success';
                    noticeDiv.textContent = 'Visitor count updated!';
                    currentCountTd.textContent = result.data.new_count;
                } else {
                    noticeDiv.className = 'notice notice-error';
                    noticeDiv.textContent = 'Error updating visitor count.';
                }
                submitButton.disabled = false;
            })
            .catch(error => {
                noticeDiv.className = 'notice notice-error';
                noticeDiv.textContent = 'Error updating visitor count.';
                submitButton.disabled = false;
            });
        });
    }
});