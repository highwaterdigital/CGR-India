/**
 * CGR Gallery Dashboard JavaScript
 * Handles media uploader and AJAX operations
 */

jQuery(document).ready(function($) {
    'use strict';

    let currentGalleryId = null;
    let selectedMediaIds = [];
    let bulkMediaItems = [];
    const bulkSelect = $('#cgr-bulk-gallery-select');
    const bulkPreview = $('#cgr-bulk-preview');
    const bulkAddBtn = $('#cgr-bulk-add-media');
    const bulkAttachBtn = $('#cgr-bulk-attach-media');
    const bulkShareInput = $('#cgr-bulk-share-link');
    const bulkCopyBtn = $('#cgr-bulk-copy-link');

    // Quick Edit button click
    $(document).on('click', '.cgr-quick-edit', function(e) {
        e.preventDefault();
        currentGalleryId = $(this).data('gallery-id');
        openQuickEditModal(currentGalleryId);
    });

    // Close modal
    $('.cgr-modal-close, #cgr-cancel-edit').on('click', function() {
        closeModal();
    });

    // Click outside modal to close
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('cgr-modal')) {
            closeModal();
        }
    });

    // Add Media button
    $('#cgr-add-media').on('click', function(e) {
        e.preventDefault();
        
        // Create media frame
        const mediaFrame = wp.media({
            title: 'Select or Upload Gallery Media',
            button: {
                text: 'Add to Gallery'
            },
            multiple: true
        });

        // When media is selected
        mediaFrame.on('select', function() {
            const selection = mediaFrame.state().get('selection');
            
            selection.each(function(attachment) {
                const attachmentData = attachment.toJSON();
                addMediaItem(attachmentData.id, attachmentData.url, attachmentData.title);
            });
        });

        // Open media frame
        mediaFrame.open();
    });

    // Bulk media selector
    bulkAddBtn.on('click', function(e) {
        e.preventDefault();
        openBulkMediaFrame();
    });

    bulkAttachBtn.on('click', function(e) {
        e.preventDefault();
        saveBulkMedia();
    });

    bulkSelect.on('change', function() {
        updateBulkShareLink();
    });

    bulkCopyBtn.on('click', function(e) {
        e.preventDefault();
        const link = bulkShareInput.val();
        if (!link) {
            showNotification('Select a gallery to copy its share link.', 'error');
            return;
        }
        copyToClipboard(link);
    });

    // Save media changes
    $('#cgr-save-media').on('click', function() {
        saveGalleryMedia();
    });

    // Function to open quick edit modal
    function openQuickEditModal(galleryId) {
        selectedMediaIds = [];
        $('#cgr-selected-media').empty();
        $('#cgr-manual-urls').val('');

        // Load existing media
        $.ajax({
            url: cgrGalleryDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cgr_get_gallery_media',
                nonce: cgrGalleryDashboard.nonce,
                gallery_id: galleryId
            },
            success: function(response) {
                if (response.success) {
                    const mediaItems = response.data.media_items;
                    
                    mediaItems.forEach(function(item) {
                        if (item.thumbnail) {
                            addMediaItem(item.id, item.thumbnail, item.title, false);
                        }
                    });

                    $('#cgr-manual-urls').val(response.data.raw_data);
                }
            },
            error: function() {
                alert('Error loading gallery media. Please try again.');
            }
        });

        $('#cgr-quick-edit-modal').fadeIn(300);
    }

    // Function to close modal
    function closeModal() {
        $('#cgr-quick-edit-modal').fadeOut(300);
        currentGalleryId = null;
        selectedMediaIds = [];
    }

    // Function to add media item to preview
    function addMediaItem(id, url, title, addToArray = true) {
        if (addToArray && selectedMediaIds.indexOf(id) === -1) {
            selectedMediaIds.push(id);
        } else if (!addToArray && selectedMediaIds.indexOf(id) === -1) {
            selectedMediaIds.push(id);
        }

        const mediaHtml = `
            <div class="cgr-media-item" data-media-id="${id}">
                <img src="${url}" alt="${title}" title="${title}">
                <button type="button" class="remove-media" data-media-id="${id}">&times;</button>
            </div>
        `;

        $('#cgr-selected-media').append(mediaHtml);
        updateManualUrlsField();
    }

    // Remove media item
    $(document).on('click', '.remove-media', function() {
        const mediaId = $(this).data('media-id').toString();
        const index = selectedMediaIds.indexOf(mediaId);
        
        if (index > -1) {
            selectedMediaIds.splice(index, 1);
        }

        $(this).closest('.cgr-media-item').fadeOut(200, function() {
            $(this).remove();
            updateManualUrlsField();
        });
    });

    // Update manual URLs field
    function updateManualUrlsField() {
        $('#cgr-manual-urls').val(selectedMediaIds.join(', '));
    }

    // Manual URLs field change
    $('#cgr-manual-urls').on('change', function() {
        const value = $(this).val();
        const tokens = value.split(',').map(t => t.trim()).filter(t => t.length > 0);
        
        selectedMediaIds = [];
        $('#cgr-selected-media').empty();

        tokens.forEach(function(token) {
            if (/^\d+$/.test(token)) {
                // It's an attachment ID - fetch thumbnail
                fetchAttachmentThumbnail(token);
            } else if (isValidUrl(token)) {
                // It's a URL - use directly
                addMediaItem(token, token, 'External Image', false);
            }
        });
    });

    // Fetch attachment thumbnail
    function fetchAttachmentThumbnail(attachmentId) {
        $.ajax({
            url: cgrGalleryDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_ajax_get_attachment',
                id: attachmentId
            },
            success: function(response) {
                if (response.success) {
                    const attachment = response.data;
                    addMediaItem(attachmentId, attachment.sizes.thumbnail.url, attachment.title, false);
                }
            }
        });
    }

    // Validate URL
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    // Bulk preview removal
    $(document).on('click', '.cgr-bulk-remove-media', function() {
        const mediaId = $(this).data('media-id');
        bulkMediaItems = bulkMediaItems.filter(item => (item.id && item.id.toString() !== mediaId) && (item.url !== mediaId));
        renderBulkPreview();
    });

    function renderBulkPreview() {
        bulkPreview.empty();

        if (!bulkMediaItems.length) {
            bulkPreview.html('<p>No media selected yet.</p>');
            return;
        }

        bulkMediaItems.forEach(function(item) {
            const identifier = item.id ? item.id.toString() : item.url;
            const title = item.title ? `<p>${item.title}</p>` : '';
            bulkPreview.append(`
                <div class="cgr-bulk-item" data-id="${identifier}">
                    <img src="${item.thumb || item.url}" alt="${item.title}">
                    <button type="button" class="cgr-bulk-remove-media" data-media-id="${identifier}">&times;</button>
                    ${title}
                </div>
            `);
        });
    }

    function openBulkMediaFrame() {
        const mediaFrame = wp.media({
            title: 'Select bulk gallery media',
            button: {
                text: 'Add selected images'
            },
            multiple: true
        });

        mediaFrame.on('select', function() {
            const selection = mediaFrame.state().get('selection');
            selection.each(function(attachment) {
                const data = attachment.toJSON();
                const item = {
                    id: data.id,
                    url: data.url,
                    thumb: data.sizes && data.sizes.medium ? data.sizes.medium.url : data.url,
                    title: data.title
                };
                bulkMediaItems = cgrGalleryAddUnique(bulkMediaItems, item);
            });
            renderBulkPreview();
        });

        mediaFrame.open();
    }

    function cgrGalleryAddUnique(list, item) {
        if (!item.url) {
            return list;
        }

        const exists = list.some(existing => existing.url === item.url);
        if (exists) {
            return list;
        }
        return list.concat(item);
    }

    function saveBulkMedia() {
        const galleryId = parseInt(bulkSelect.val(), 10);
        if (!galleryId) {
            showNotification('Choose a gallery before uploading.', 'error');
            return;
        }

        if (!bulkMediaItems.length) {
            showNotification('Select at least one image.', 'error');
            return;
        }

        bulkAttachBtn.prop('disabled', true).text('Attaching...');

        $.ajax({
            url: cgrGalleryDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cgr_get_gallery_media',
                nonce: cgrGalleryDashboard.nonce,
                gallery_id: galleryId
            },
            success: function(response) {
                if (response.success) {
                    const existing = response.data.raw_data ? response.data.raw_data.split(',').map(t => t.trim()).filter(Boolean) : [];
                    const additions = bulkMediaItems.map(item => item.id ? item.id.toString() : item.url).filter(Boolean);
                    const combined = Array.from(new Set(existing.concat(additions)));
                    updateBulkGallery(galleryId, combined.join(', '));
                } else {
                    showNotification(response.data.message || 'Unable to fetch gallery assets.', 'error');
                    bulkAttachBtn.prop('disabled', false).text('Attach to gallery');
                }
            },
            error: function() {
                showNotification('Request failed. Please try again.', 'error');
                bulkAttachBtn.prop('disabled', false).text('Attach to gallery');
            }
        });
    }

    function updateBulkGallery(galleryId, rawData) {
        $.ajax({
            url: cgrGalleryDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cgr_update_gallery_media',
                nonce: cgrGalleryDashboard.nonce,
                gallery_id: galleryId,
                media_data: rawData
            },
            success: function(response) {
                if (response.success) {
                    bulkMediaItems = [];
                    renderBulkPreview();
                    showNotification('Gallery populated with new assets!', 'success');
                    bulkAttachBtn.prop('disabled', false).text('Attach to gallery');
                } else {
                    showNotification(response.data.message || 'Unable to attach media.', 'error');
                    bulkAttachBtn.prop('disabled', false).text('Attach to gallery');
                }
            },
            error: function() {
                showNotification('Server error. Please retry.', 'error');
                bulkAttachBtn.prop('disabled', false).text('Attach to gallery');
            }
        });
    }

    function updateBulkShareLink() {
        const permalink = bulkSelect.find('option:selected').data('permalink') || '';
        bulkShareInput.val(permalink);
    }

    function copyToClipboard(value) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value).then(function() {
                showNotification('Link copied to clipboard.', 'success');
            }).catch(function() {
                fallbackCopy(value);
            });
        } else {
            fallbackCopy(value);
        }
    }

    function fallbackCopy(value) {
        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
            showNotification('Link copied to clipboard.', 'success');
        } catch (err) {
            showNotification('Copy failed, please copy manually.', 'error');
        }
        document.body.removeChild(textarea);
    }

    // Save gallery media
    function saveGalleryMedia() {
        const mediaData = $('#cgr-manual-urls').val();

        if (!currentGalleryId) {
            alert('Error: No gallery selected.');
            return;
        }

        // Show loading state
        $('#cgr-save-media').prop('disabled', true).text('Saving...');

        $.ajax({
            url: cgrGalleryDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cgr_update_gallery_media',
                nonce: cgrGalleryDashboard.nonce,
                gallery_id: currentGalleryId,
                media_data: mediaData
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotification('Gallery media updated successfully!', 'success');
                    
                    // Update the gallery card thumbnail
                    updateGalleryCardThumbnail(currentGalleryId);
                    
                    // Close modal after short delay
                    setTimeout(function() {
                        closeModal();
                    }, 1000);
                } else {
                    showNotification(response.data.message || 'Error updating gallery media.', 'error');
                }
            },
            error: function() {
                showNotification('Ajax error. Please try again.', 'error');
            },
            complete: function() {
                $('#cgr-save-media').prop('disabled', false).text('Save Changes');
            }
        });
    }

    // Update gallery card thumbnail after save
    function updateGalleryCardThumbnail(galleryId) {
        const $card = $('.cgr-gallery-card[data-gallery-id="' + galleryId + '"]');
        const firstMediaItem = $('#cgr-selected-media .cgr-media-item:first-child img');
        
        if (firstMediaItem.length) {
            $card.find('.cgr-gallery-card__thumbnail img').attr('src', firstMediaItem.attr('src'));
        }

        // Update asset count
        const assetCount = selectedMediaIds.length;
        $card.find('.cgr-asset-count').text(assetCount + ' items');
    }

    // Show notification
    function showNotification(message, type) {
        const $notification = $('<div class="cgr-notification cgr-notification-' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);

        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }

    // Add notification styles dynamically
    $('<style>')
        .text(`
            .cgr-notification {
                position: fixed;
                top: 32px;
                right: 20px;
                background: #fff;
                padding: 15px 20px;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 100001;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                max-width: 350px;
                font-size: 14px;
            }
            .cgr-notification.show {
                transform: translateX(0);
            }
            .cgr-notification-success {
                border-left: 4px solid #1f4f2e;
                color: #1f4f2e;
            }
            .cgr-notification-error {
                border-left: 4px solid #dc2626;
                color: #dc2626;
            }
        `)
        .appendTo('head');

    renderBulkPreview();
    updateBulkShareLink();

    // Settings autosave (optional enhancement)
    $('#default-animation, #default-layout, #default-accent').on('change', function() {
        const settingName = $(this).attr('name');
        const settingValue = $(this).val();
        
        // You can add AJAX call here to save default settings to options table
        console.log('Setting changed:', settingName, settingValue);
    });
});
