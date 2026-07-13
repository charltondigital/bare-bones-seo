/**
 * Bare Bones SEO Admin JavaScript
 * 
 * Handles:
 * - Live preview updates in the post editor
 * - Radio button toggle logic for advanced options
 * - AJAX bulk save with error handling
 */

(function() {
    'use strict';

    /**
     * ========================================================================
     * POST EDITOR META BOX: Live Preview
     * ========================================================================
     * 
     * Updates the search result preview (desktop & mobile) as the user
     * types in the title/description fields.
     */

    document.addEventListener('DOMContentLoaded', function() {
        var triggerButton = document.getElementById('bb-trigger-preview');

        // Only run on pages that have the preview button (post editor)
        if (!triggerButton) {
            return;
        }

        triggerButton.addEventListener('click', function() {
            updatePreview();
        });

        /**
         * Update the live preview display
         * 
         * Reads current values from the title/description inputs
         * and updates both desktop (600px) and mobile (360px) preview cards.
         * Falls back to post title if no custom title is entered.
         */
        function updatePreview() {
            var rawTitle = document.getElementById('bb_seo_title').value;
            var rawDesc = document.getElementById('bb_seo_desc').value;

            // Fallback: Use native post title if no custom title provided
            if (!rawTitle) {
                // This is escaped when rendered by PHP, safe to use
                var pageTitle = document.querySelector('[data-page-title]');
                if (pageTitle) {
                    rawTitle = pageTitle.getAttribute('data-page-title');
                } else {
                    rawTitle = 'Your page title will appear here...';
                }
            }

            // Fallback: Use placeholder text if no description provided
            if (!rawDesc) {
                rawDesc = 'Please enter a description value to accurately populate search snippets...';
            }

            // Update desktop preview (600px)
            var desktopTitle = document.getElementById('bb-preview-desktop-title');
            var desktopDesc = document.getElementById('bb-preview-desktop-desc');
            if (desktopTitle) desktopTitle.innerText = rawTitle;
            if (desktopDesc) desktopDesc.innerText = rawDesc;

            // Update mobile preview (360px)
            var mobileTitle = document.getElementById('bb-preview-mobile-title');
            var mobileDesc = document.getElementById('bb-preview-mobile-desc');
            if (mobileTitle) mobileTitle.innerText = rawTitle;
            if (mobileDesc) mobileDesc.innerText = rawDesc;
        }
    });

    /**
     * ========================================================================
     * GLOBAL INDEXING MAP: Radio Button Toggle
     * ========================================================================
     * 
     * When a radio button changes from/to "advanced", show/hide
     * the advanced options row for that post type.
     * 
     * Uses data-* attributes instead of regex for robustness.
     */

    document.addEventListener('DOMContentLoaded', function() {
        var radioButtons = document.querySelectorAll('.bb-radio-toggle');

        // Only run on pages that have radio toggles (global map)
        if (radioButtons.length === 0) {
            return;
        }

        radioButtons.forEach(function(radio) {
            radio.addEventListener('change', function() {
                // Get the post type from the data attribute (safe, no regex)
                var postType = this.getAttribute('data-post-type');

                if (!postType) {
                    console.error('bb-radio-toggle missing data-post-type attribute');
                    return;
                }

                // Find the corresponding advanced options row
                var advancedRow = document.querySelector(
                    '.bb-advanced-row[data-post-type="' + postType + '"]'
                );

                if (!advancedRow) {
                    return;
                }

                // Show/hide based on selected value
                if (this.value === 'advanced') {
                    advancedRow.style.display = '';
                } else {
                    advancedRow.style.display = 'none';
                }
            });
        });
    });

    /**
     * ========================================================================
     * BULK PAGE MANAGER: AJAX Save with Error Handling
     * ========================================================================
     * 
     * Handles the "Quick Save" button functionality. Sends the edited
     * title and indexing status via AJAX, with:
     * - Loading state (button text change)
     * - Success feedback (status badge + button color change)
     * - Error feedback (button shows error message)
     * - Network error handling
     */

    document.addEventListener('DOMContentLoaded', function() {
        var saveButtons = document.querySelectorAll('.bb-bulk-save-trigger');

        // Only run on pages that have save buttons (bulk manager)
        if (saveButtons.length === 0) {
            return;
        }

        saveButtons.forEach(function(button) {
            button.addEventListener('click', handleBulkSave);
        });

        /**
         * Handle bulk save for a single post
         * 
         * @param {Event} event The click event
         */
        function handleBulkSave(event) {
            event.preventDefault();

            var button = this;
            var postId = button.getAttribute('data-id');

            if (!postId) {
                console.error('Save button missing data-id attribute');
                return;
            }

            // Get current form values
            var seoTitle = document.getElementById('bb-bulk-title-' + postId);
            var indexCheckbox = document.getElementById('bb-bulk-index-' + postId);
            var statusBadge = document.querySelector('.bb-status-badge-' + postId);

            if (!seoTitle || !indexCheckbox) {
                console.error('Could not find form fields for post ' + postId);
                return;
            }

            // Change button to loading state
            var originalText = button.textContent;
            button.textContent = 'Saving...';
            button.disabled = true;

            // Get nonce from form or data attribute
            var nonce = button.getAttribute('data-nonce');
            if (!nonce) {
                // Try to find nonce field in DOM
                var nonceField = document.getElementById('bb_bulk_nonce_field');
                if (nonceField) {
                    nonce = nonceField.value;
                }
            }

            // Prepare AJAX request data
            var data = {
                action: 'bb_seo_bulk_save', // AJAX action (from PHP constant)
                security: nonce, // CSRF token
                post_id: postId,
                seo_title: seoTitle.value,
                should_index: indexCheckbox.checked ? 'true' : 'false',
            };

            // Send AJAX request
            jQuery.post(ajaxurl, data, function(response) {
                // Reset button to interactive state
                button.disabled = false;

                // Check if AJAX call was successful
                if (response.success) {
                    // Update status badge based on new value
                    if (indexCheckbox.checked) {
                        statusBadge.textContent = 'INDEXED';
                        statusBadge.style.color = '#46b450';
                    } else {
                        statusBadge.textContent = '🚨 HIDDEN';
                        statusBadge.style.color = '#dc3232';
                    }

                    // Visual feedback: change button color to indicate success
                    button.classList.remove('button-secondary');
                    button.classList.add('button-primary');
                    button.textContent = 'Saved ✓';

                    // Revert button after 2 seconds
                    setTimeout(function() {
                        button.classList.remove('button-primary');
                        button.classList.add('button-secondary');
                        button.textContent = originalText;
                    }, 2000);
                } else {
                    // Handle AJAX error response
                    var errorMessage = response.data || 'Save failed';
                    button.textContent = 'Error: ' + errorMessage;
                    button.classList.add('button-primary');

                    // Revert button after 3 seconds
                    setTimeout(function() {
                        button.textContent = originalText;
                        button.classList.remove('button-primary');
                    }, 3000);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                // Handle network errors (connection timeout, etc.)
                button.disabled = false;

                // Display network error
                button.textContent = 'Network error';
                button.classList.add('button-primary');

                // Log the error for debugging
                console.error('AJAX Error:', textStatus, errorThrown);

                // Revert button after 3 seconds
                setTimeout(function() {
                    button.textContent = originalText;
                    button.classList.remove('button-primary');
                }, 3000);
            });
        }
    });

})();
/**
 * GLOBAL MAP: Live Sitemap Preview
 * 
 * As user changes radio buttons, the preview updates in real-time
 * to show what sections will be in the sitemap.
 */

document.addEventListener('DOMContentLoaded', function() {
    var radioButtons = document.querySelectorAll('.bb-radio-toggle[data-section]');
    var sitemapPreview = document.getElementById('bb-sitemap-preview');
    var sectionDataEl = document.getElementById('bb-section-data');

    // Only run on global map page
    if (!sitemapPreview || !sectionDataEl) {
        return;
    }

    // Parse section data
    var sectionData = JSON.parse(sectionDataEl.textContent).sections;

    /**
     * Update the sitemap preview based on current radio selections
     */
    function updateSitemapPreview() {
        var html = '<div style="color: #2c3e50; font-weight: bold; margin-bottom: 10px;">/sitemap.xml</div>';

        // Build sitemap based on current selections
        for (var sectionName in sectionData) {
            var section = sectionData[sectionName];
            var radio = document.querySelector('input[name="section_index[' + sectionName + '"]:checked');
            
            if (!radio) continue;

            var isIndexed = radio.value === 'yes';
            var icon = isIndexed ? '✓' : '✗';
            var color = isIndexed ? '#46b450' : '#dc3232';

            html += '<div style="margin-left: 20px; color: ' + color + '; margin-bottom: 6px;">';
            html += '<span style="font-weight: bold;">' + icon + '</span> ';
            html += '/sitemap-' + sectionName + '-1.xml';
            html += '</div>';
        }

        sitemapPreview.innerHTML = html;
    }

    // Listen for radio button changes
    radioButtons.forEach(function(radio) {
        radio.addEventListener('change', updateSitemapPreview);
    });

    // Initial render
    updateSitemapPreview();
});
