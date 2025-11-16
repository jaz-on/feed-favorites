/**
 * Feed Favorites Meta Box JavaScript
 *
 * Handles validation and functionality for meta boxes.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		var $externalUrl = $('#feed_favorites_external_url');
		var $sourceSite = $('#feed_favorites_source_site');
		var $linkSummary = $('#feed_favorites_link_summary');
		var $previewButton = $('#feed-favorites-preview-url');
		var $publishButton = $('#publish, #save-post');

		// Auto-set post format to 'link' when meta box is visible.
		if ($('#post-format-link').length) {
			$('#post-format-link').prop('checked', true).trigger('change');
		}

		// Auto-populate source site from URL.
		$externalUrl.on('blur', function() {
			var url = $(this).val();
			if (url && !$sourceSite.val()) {
				try {
					var urlObj = new URL(url);
					var hostname = urlObj.hostname.replace('www.', '');
					$sourceSite.val(hostname);
				} catch (e) {
					// Invalid URL, ignore.
				}
			}
		});

		// Preview external link button.
		$previewButton.on('click', function(e) {
			e.preventDefault();
			var url = $externalUrl.val();
			if (url) {
				window.open(url, '_blank');
			} else {
				alert(feedFavoritesMetaBox.strings.urlRequired || 'Please enter a URL first.');
			}
		});

		// Character counter for summary field.
		var $charCount = $('#summary-char-count');
		var maxLength = 500; // Optional: make this configurable.

		function updateCharCount() {
			var length = $linkSummary.val().length;
			$charCount.text(' (' + length + ' / ' + maxLength + ' characters)');
			if (length > maxLength) {
				$charCount.css('color', '#dc3232');
			} else {
				$charCount.css('color', '#666');
			}
		}

		$linkSummary.on('input', updateCharCount);
		updateCharCount();

		// Validation before publish.
		$publishButton.on('click', function(e) {
			var isValid = true;
			var errors = [];

			// Check required fields.
			if (!$externalUrl.val()) {
				isValid = false;
				errors.push(feedFavoritesMetaBox.strings.urlRequired || 'External URL is required.');
			}

			// Validate URL format.
			if ($externalUrl.val() && !isValidUrl($externalUrl.val())) {
				isValid = false;
				errors.push(feedFavoritesMetaBox.strings.invalidUrl || 'Invalid URL format.');
			}

			// Check if summary is required.
			if (feedFavoritesMetaBox.linkSummaryRequired && !$linkSummary.val().trim()) {
				isValid = false;
				errors.push(feedFavoritesMetaBox.strings.summaryRequired || 'Link summary is required.');
			}

			// Check if commentary is required.
			if (feedFavoritesMetaBox.commentaryRequired) {
				var commentary = $('#feed_favorites_link_commentary').val();
				if (!commentary || !commentary.trim()) {
					isValid = false;
					errors.push(feedFavoritesMetaBox.strings.commentaryRequired || 'Commentary is required.');
				}
			}

			if (!isValid) {
				e.preventDefault();
				e.stopPropagation();
				alert(errors.join('\n'));
				return false;
			}
		});

		/**
		 * Validate URL format.
		 *
		 * @param {string} url The URL to validate.
		 * @return {boolean} True if valid.
		 */
		function isValidUrl(url) {
			try {
				new URL(url);
				return true;
			} catch (e) {
				return false;
			}
		}
	});
})(jQuery);

