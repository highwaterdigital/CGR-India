jQuery(document).ready(function($) {
    var frame;
    var imagesContainer = $('#cgr-gallery-images-preview');
    var inputField = $('#cgr_gallery_assets');

    // Add Images
    $('#cgr-add-gallery-images').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Gallery Images',
            button: {
                text: 'Add to Gallery'
            },
            multiple: true
        });

        frame.on('select', function() {
            var selection = frame.state().get('selection');
            var ids = inputField.val() ? inputField.val().split(',') : [];

            selection.map(function(attachment) {
                attachment = attachment.toJSON();
                
                if (ids.indexOf(attachment.id.toString()) === -1) {
                    ids.push(attachment.id);
                    
                    // Append preview
                    var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    var html = '<div class="cgr-gallery-image" data-id="' + attachment.id + '">';
                    html += '<img src="' + thumb + '" />';
                    html += '<span class="remove-image">×</span>';
                    html += '</div>';
                    imagesContainer.append(html);
                }
            });

            inputField.val(ids.join(','));
        });

        frame.open();
    });

    // Remove Image
    imagesContainer.on('click', '.remove-image', function() {
        var parent = $(this).parent();
        var id = parent.data('id').toString();
        var ids = inputField.val().split(',');
        
        var index = ids.indexOf(id);
        if (index > -1) {
            ids.splice(index, 1);
        }
        
        inputField.val(ids.join(','));
        parent.remove();
    });

    // Clear All
    $('#cgr-clear-gallery-images').on('click', function() {
        if (confirm('Are you sure you want to remove all images from this gallery?')) {
            inputField.val('');
            imagesContainer.empty();
        }
    });

    // Sortable (Optional - requires jQuery UI Sortable)
    if ($.fn.sortable) {
        imagesContainer.sortable({
            update: function(event, ui) {
                var ids = [];
                imagesContainer.find('.cgr-gallery-image').each(function() {
                    ids.push($(this).data('id'));
                });
                inputField.val(ids.join(','));
            }
        });
    }
});
