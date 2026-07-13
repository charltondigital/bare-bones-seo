(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {

        /**
         * SECTION TOGGLES
         *
         * Works for both meta box and bulk manager expanded rows.
         * Uses data-target to find the section body by ID.
         */
        document.addEventListener('click', function(e) {
            var toggle = e.target.closest('.bb-section-toggle');
            if (!toggle) return;

            var targetId = toggle.getAttribute('data-target');
            var target   = document.getElementById(targetId);
            var icon     = toggle.querySelector('.bb-toggle-icon');
            var expanded = toggle.getAttribute('aria-expanded') === 'true';

            if (!target) return;

            if (expanded) {
                target.style.display = 'none';
                toggle.setAttribute('aria-expanded', 'false');
                if (icon) icon.textContent = '+';
            } else {
                target.style.display = 'block';
                toggle.setAttribute('aria-expanded', 'true');
                if (icon) icon.textContent = '−';
            }
        });

        /**
         * INDEXING WARNING
         *
         * Show warning when anything other than YES is selected.
         * Works for any radio group with class bb-index-radio.
         */
        document.addEventListener('change', function(e) {
            var radio = e.target;
            if (!radio.classList.contains('bb-index-radio')) return;

            var uid     = radio.getAttribute('data-uid');
            var warning = document.getElementById(uid + '-index-warning');

            if (!warning) return;

            warning.style.display = (radio.value !== 'yes') ? 'block' : 'none';
        });

        /**
         * SNIPPET PREVIEW
         *
         * On button click, read title and description for this post's
         * fields (identified by uid), build preview string, update panels.
         *
         * Works for both meta box and bulk manager since both use the
         * same uid-prefixed IDs.
         */
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.bb-trigger-preview');
            if (!btn) return;

            var uid       = btn.getAttribute('data-uid');
            var metaEl    = document.getElementById(uid + '-meta-data');

            if (!metaEl) return;

            var metaData  = JSON.parse(metaEl.textContent);
            var siteName  = metaData.site_name;
            var postTitle = metaData.post_title;

            // Find inputs scoped to this uid's section
            var section   = document.getElementById(uid + '-snippet');
            if (!section) return;

            var titleInput = section.querySelector('.bb-title-input');
            var descInput  = section.querySelector('.bb-desc-input');

            var rawTitle  = (titleInput && titleInput.value.trim()) ? titleInput.value.trim() : postTitle;
            var fullTitle = rawTitle + ' \u2014 ' + siteName;
            var rawDesc   = (descInput && descInput.value.trim()) ? descInput.value.trim() : '';

            var desktopTitle = document.getElementById(uid + '-preview-desktop-title');
            var desktopDesc  = document.getElementById(uid + '-preview-desktop-desc');
            var mobileTitle  = document.getElementById(uid + '-preview-mobile-title');
            var mobileDesc   = document.getElementById(uid + '-preview-mobile-desc');

            if (desktopTitle) desktopTitle.textContent = fullTitle;
            if (desktopDesc)  desktopDesc.textContent  = rawDesc;
            if (mobileTitle)  mobileTitle.textContent  = fullTitle;
            if (mobileDesc)   mobileDesc.textContent   = rawDesc;
        });

        /**
         * BULK MANAGER: AJAX SAVE
         *
         * Collects all four fields from the expanded row and saves
         * via AJAX. Shows loading state then success/error feedback.
         */
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.bb-bulk-save');
            if (!btn) return;

            var postId  = btn.getAttribute('data-post-id');
            var uid     = btn.getAttribute('data-uid');
            var nonce   = document.getElementById('bb_bulk_nonce_field');

            if (!postId || !nonce) return;

            // Get expanded row container
            var expandedRow = document.getElementById(uid + '-expanded');
            if (!expandedRow) return;

            // Read all four fields
            var titleInput  = expandedRow.querySelector('[name="bb_seo_title"]');
            var descInput   = expandedRow.querySelector('[name="bb_seo_desc"]');
            var schemaInput = expandedRow.querySelector('[name="bb_seo_schema"]');
            var indexRadio  = expandedRow.querySelector('[name="bb_seo_should_index_' + postId + '"]:checked');

            var title       = titleInput  ? titleInput.value  : '';
            var desc        = descInput   ? descInput.value   : '';
            var schema      = schemaInput ? schemaInput.value : '';
            var shouldIndex = indexRadio  ? indexRadio.value  : 'yes';

            // Loading state
            var originalText = btn.textContent;
            btn.textContent  = 'Saving...';
            btn.disabled     = true;

            jQuery.post(ajaxurl, {
                action:          'bb_seo_bulk_save',
                security:        nonce.value,
                post_id:         postId,
                bb_seo_title:    title,
                bb_seo_desc:     desc,
                bb_seo_schema:   schema,
                bb_seo_should_index: shouldIndex,
            })
            .done(function(response) {
                btn.disabled = false;

                if (response.success) {
                    // Update collapsed row display values
                    var collapsedRow = document.getElementById(uid + '-collapsed');
                    if (collapsedRow) {
                        var cells = collapsedRow.querySelectorAll('td');

                        // Description cell (index 1)
                        if (cells[1]) {
                            cells[1].querySelector('span').textContent = desc;
                        }

                        // Indexation badge (index 2)
                        if (cells[2]) {
                            cells[2].innerHTML = shouldIndex === 'yes'
                                ? '<span style="color:#46b450; font-size:16px; font-weight:700;">✓</span>'
                                : '<span style="color:#dc3232; font-size:16px; font-weight:700;">✗</span>';
                        }

                        // Schema cell (index 3)
                        if (cells[3]) {
                            cells[3].querySelector('span').textContent = schema;
                        }
                    }

                    // Success feedback then collapse
                    btn.textContent = 'Saved ✓';
                    setTimeout(function() {
                        btn.textContent = originalText;
                        bbToggleRow(postId);
                    }, 1000);

                } else {
                    btn.textContent = 'Error — try again';
                    setTimeout(function() {
                        btn.textContent = originalText;
                    }, 3000);
                }
            })
            .fail(function() {
                btn.disabled    = false;
                btn.textContent = 'Network error';
                setTimeout(function() {
                    btn.textContent = originalText;
                }, 3000);
            });
        });

    });

})();

/**
 * BULK MANAGER ROW TOGGLE
 *
 * Global function so onclick attributes in PHP can call it.
 * Swaps collapsed/expanded rows and rotates the chevron.
 *
 * @param {number} postId
 */
function bbToggleRow(postId) {
    var uid      = 'bb-' + postId;
    var collapsed = document.getElementById(uid + '-collapsed');
    var expanded  = document.getElementById(uid + '-expanded');
    var chevron   = document.getElementById(uid + '-chevron');

    if (!collapsed || !expanded) return;

    var isExpanded = expanded.style.display !== 'none';

    if (isExpanded) {
        expanded.style.display  = 'none';
        collapsed.style.display = '';
        if (chevron) chevron.style.transform = '';
    } else {
        collapsed.style.display = 'none';
        expanded.style.display  = '';
        if (chevron) chevron.style.transform = 'rotate(90deg)';
    }
}
