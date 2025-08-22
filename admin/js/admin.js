/**
 * Feed Favorites Admin JavaScript
 * Handles AJAX operations and user interactions
 */

jQuery(document).ready(function($) {

    // Initialize setup tab functionality
    initSetupTab();

    // Handle file input for import
    handleFileInput();

    // Initialize shortcode copy functionality (lightweight)
    initShortcodeCopy();

    /**
     * Initialize setup tab functionality
     */
    function initSetupTab() {
        // Test feed connection button
        $('#test-feed').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.html();
            var $results = $('#test-results');
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Testing...');
            $results.hide();
            
            // Make AJAX request to test feed
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'feed_favorites_test_feed',
                    nonce: feedFavoritesAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $results.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>').show();
                    } else {
                        $results.html('<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>').show();
                    }
                },
                error: function() {
                    $results.html('<div class="notice notice-error"><p>An error occurred while testing the feed connection.</p></div>').show();
                },
                complete: function() {
                    // Restore button state
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Manual sync button
        $('#manual-sync').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.html();
            var $results = $('#test-results');
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Syncing...');
            $results.hide();
            
            // Make AJAX request for manual sync
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'feed_favorites_manual_sync',
                    nonce: feedFavoritesAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $results.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>').show();
                        
                        // Reload page after a short delay to reflect changes
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $results.html('<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>').show();
                    }
                },
                error: function() {
                    $results.html('<div class="notice notice-error"><p>An error occurred during manual synchronization.</p></div>').show();
                },
                complete: function() {
                    // Restore button state
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });
    }
    
    /**
     * Handle file input for import form
     */
    function handleFileInput() {
        var $fileInput = $('#rss_json_file'); // Changed from feedbin_json_file
        var $importBtn = $('#rss-import-btn'); // Changed from feedbin-import-btn
        
        if ($fileInput.length && $importBtn.length) {
            $fileInput.on('change', function() {
                var fileName = this.files[0] ? this.files[0].name : '';
                
                if (fileName) {
                    // Enable import button
                    $importBtn.prop('disabled', false);
                    
                    // Show file name
                    var $status = $('#import-status');
                    $status.html('<span class="description">Selected file: ' + fileName + '</span>');
                } else {
                    // Disable import button
                    $importBtn.prop('disabled', true);
                    
                    // Clear status
                    $('#import-status').html('');
                }
            });
        }
        
        // Handle new import form in setup tab
        var $newFileInput = $('input[name="rss_export"]'); // Changed from feedbin_export
        var $newImportBtn = $('input[name="import_json"]');
        
        if ($newFileInput.length && $newImportBtn.length) {
            $newFileInput.on('change', function() {
                var fileName = this.files[0] ? this.files[0].name : '';
                
                if (fileName) {
                    // Enable import button
                    $newImportBtn.prop('disabled', false);
                    
                    // Show file name
                    var $status = $(this).siblings('.description');
                    if ($status.length) {
                        $status.html('Selected file: ' + fileName);
                    }
                } else {
                    // Disable import button
                    $newImportBtn.prop('disabled', true);
                    
                    // Clear status
                    var $status = $(this).siblings('.description');
                    if ($status.length) {
                        $status.html('Select your RSS export file (.json)');
                    }
                }
            });
        }
    }
    
    /**
     * Handle reset buttons
     */
    $('[data-reset-action]').on('click', function() {
        var action = $(this).data('reset-action');
        var $button = $(this);
        var originalText = $button.html();
        
        // Confirm action
        if (action === 'all') {
            if (!confirm('Are you sure you want to reset everything? This will clear all logs, statistics, and reset the system notice.')) {
                return;
            }
        } else if (action === 'logs') {
            if (!confirm('Are you sure you want to clear all logs?')) {
                return;
            }
        } else if (action === 'stats') {
            if (!confirm('Are you sure you want to reset all statistics?')) {
                return;
            }
        } else if (action === 'system_notice') {
            if (!confirm('Reset system notice? It will show again on next page load.')) {
                return;
            }
        }
        
        // Show loading state
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Processing...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'feed_favorites_reset_stats',
                reset_type: action,
                nonce: feedFavoritesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#reset-status').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Reload page after a short delay to reflect changes
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#reset-status').html('<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $('#reset-status').html('<div class="notice notice-error"><p>An error occurred while processing the request.</p></div>');
            },
            complete: function() {
                // Restore button state
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    /**
     * Handle sync button
     */
    $('#feed-favorites-sync-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.html();
        
        // Show loading state
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Syncing...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'feed_favorites_sync',
                nonce: feedFavoritesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#feed-favorites-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Reload page after a short delay to reflect changes
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#feed-favorites-messages').html('<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $('#feed-favorites-messages').html('<div class="notice notice-error"><p>An error occurred during synchronization.</p></div>');
            },
            complete: function() {
                // Restore button state
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    /**
     * Initialize shortcode copy functionality (reduced)
     */
    function initShortcodeCopy() {
        $('.copy-shortcode-btn').on('click', function() {
            var $button = $(this);
            var explicit = $button.data('clipboard-text');
            var targetSelector = $button.data('clipboard-target');
            var textFromTarget = targetSelector ? $(targetSelector).text() : '';
            var text = explicit || textFromTarget || '[feed_favorites]';

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    feedback($button, 'success');
                }).catch(function() {
                    feedback($button, 'error');
                });
            } else {
                // Minimal fallback
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                try { document.execCommand('copy'); feedback($button, 'success'); }
                catch(e) { feedback($button, 'error'); }
                document.body.removeChild(textarea);
            }
        });
    }

    function feedback($button, type) {
        var $icon = $button.find('.dashicons');
        var original = $icon.attr('class');
        if (type === 'success') {
            $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes-alt');
            $button.attr('title', 'Shortcode copied!');
        } else {
            $icon.removeClass('dashicons-clipboard').addClass('dashicons-warning');
            $button.attr('title', 'Failed to copy shortcode');
        }
        setTimeout(function(){
            $icon.attr('class', original);
            $button.attr('title', 'Copy shortcode to clipboard');
        }, 1500);
    }
    
    // Removed debug-only functions to reduce JS surface
    
}); 