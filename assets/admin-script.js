/**
 * Bare Bones SEO Admin JavaScript v1.0.4
 *
 * Handles:
 * 1. Section accordion toggles (Snippet Builder, Indexing, Schema)
 * 2. Indexing warning display
 * 3. Snippet preview (desktop + mobile) on button click
 * 4. Bulk manager row expand/collapse
 * 5. Bulk manager AJAX save with collapsed row update
 * 6. Schema JSON validity indicator
 *
 * All event listeners use event delegation on document so they work
 * for both the meta box and dynamically rendered bulk manager rows.
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {

        /**
         * 1. SECTION ACCORDION TOGGLES
         *
         * Delegated click on .bb-section-toggle buttons.
         * Toggles the section body identified by data-target attribute.
         * Updates aria-expanded and the +/− icon.
         */
        document.addEventListener('click', function(e) {
            var toggle = e.target.closest('.bb-section-toggle');
            if (!toggle) return;

            e.preventDefault();
            e.stopPropagation();

            var targetId = toggle.getAttribute('data-target');
            var target   = document.getElementById(targetId);
            var icon     = toggle.querySelector('.bb-toggle-icon');

            if (!target) return;

            var expanded = toggle.getAttribute('aria-expanded') === 'true';

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
         * 2. INDEXING WARNING
         *
         * Show/hide the warning message when radio buttons change.
         * Warning appears only when Noindex is selected (removed-from-sitemap
         * stays indexed, so it isn't "hidden from Google").
         */
        document.addEventListener('change', function(e) {
            var radio = e.target;
            if (!radio.classList || !radio.classList.contains('bb-index-radio')) return;

            var uid     = radio.getAttribute('data-uid');
            var warning = document.getElementById(uid + '-index-warning');

            if (!warning) return;

            warning.style.display = (radio.value === 'no') ? 'block' : 'none';
        });

        /**
         * 3. SNIPPET PREVIEW
         *
         * On button click, read current title + description,
         * build preview title (custom title or post title + site name),
         * update desktop (600px) and mobile (920px) preview panels.
         *
         * Description area maintains min-height even when blank so
         * the preview box doesn't collapse.
         */
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.bb-trigger-preview');
            if (!btn) return;

            var uid    = btn.getAttribute('data-uid');
            var postId = uid.replace('bb-', '');
            var metaEl = document.getElementById(uid + '-meta-data');

            if (!metaEl) return;

            var metaData  = JSON.parse(metaEl.textContent);
            var siteName  = metaData.site_name;
            var postTitle = metaData.post_title;

            // Find inputs within this post's snippet section using ID-suffixed selectors
            var snippet    = document.getElementById(uid + '-snippet');
            if (!snippet) return;

            var titleInput = snippet.querySelector('[name="bb_seo_title_' + postId + '"]') || snippet.querySelector('.bb-title-input');
            var descInput  = snippet.querySelector('[name="bb_seo_desc_' + postId + '"]') || snippet.querySelector('.bb-desc-input');

            var rawTitle  = (titleInput && titleInput.value.trim()) ? titleInput.value.trim() : postTitle;
            var fullTitle = rawTitle + ' \u2014 ' + siteName;
            var rawDesc   = (descInput && descInput.value.trim()) ? descInput.value.trim() : '';

            // Update desktop preview (600px max)
            var dt = document.getElementById(uid + '-preview-desktop-title');
            var dd = document.getElementById(uid + '-preview-desktop-desc');
            if (dt) dt.textContent = fullTitle;
            if (dd) {
                dd.textContent = rawDesc;
                dd.style.minHeight = rawDesc ? 'auto' : '2.5em';
            }

            // Update mobile preview (920px max)
            var mt = document.getElementById(uid + '-preview-mobile-title');
            var md = document.getElementById(uid + '-preview-mobile-desc');
            if (mt) mt.textContent = fullTitle;
            if (md) {
                md.textContent = rawDesc;
                md.style.minHeight = rawDesc ? 'auto' : '2.5em';
            }
        });

        /**
         * 4. BULK MANAGER AJAX SAVE
         *
         * Collects all four fields from the expanded row,
         * sends via AJAX, updates the collapsed row display values,
         * then collapses the row on success.
         */
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.bb-bulk-save');
            if (!btn) return;

            var postId      = btn.getAttribute('data-post-id');
            var uid         = btn.getAttribute('data-uid');
            var nonceField  = document.getElementById('bb_bulk_nonce_field');

            if (!postId || !nonceField) return;

            var expandedRow = document.getElementById(uid + '-expanded');
            if (!expandedRow) return;

            // Collect all four field values using post-specific ID naming architecture
            var titleInput  = expandedRow.querySelector('[name="bb_seo_title_' + postId + '"]') || expandedRow.querySelector('[name="bb_seo_title"]');
            var descInput   = expandedRow.querySelector('[name="bb_seo_desc_' + postId + '"]') || expandedRow.querySelector('[name="bb_seo_desc"]');
            var schemaInput = expandedRow.querySelector('[name="bb_seo_schema_' + postId + '"]') || expandedRow.querySelector('[name="bb_seo_schema"]');
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
                action:              'bb_seo_bulk_save',
                security:            nonceField.value,
                post_id:             postId,
                bb_seo_title:        title,
                bb_seo_desc:         desc,
                bb_seo_schema:       schema,
                bb_seo_should_index: shouldIndex,
            })
            .done(function(response) {
                btn.disabled = false;

                if (response.success) {
                    // Update collapsed row preview values
                    var descPreview   = document.getElementById(uid + '-desc-preview');
                    var schemaPreview = document.getElementById(uid + '-schema-preview');
                    var badgeCell     = document.getElementById(uid + '-badge-cell');

                    if (descPreview)   descPreview.textContent   = desc;
                    if (schemaPreview) schemaPreview.textContent = schema;

                    // Update badge: red ✗ only when actively hiding
                    if (badgeCell) {
                        var showX = ['no', 'complicated_noindex', 'complicated_sitemap'].indexOf(shouldIndex) !== -1;
                        badgeCell.innerHTML = showX
                            ? '<span style="color:#dc3232; font-size:16px; font-weight:700;">✗</span>'
                            : '';
                    }

                    btn.textContent = 'Saved ✓';
                    setTimeout(function() {
                        btn.textContent = originalText;
                        bbToggleRow(postId);
                    }, 800);

                } else {
                    btn.textContent = 'Error — try again';
                    setTimeout(function() { btn.textContent = originalText; }, 3000);
                }
            })
            .fail(function() {
                btn.disabled    = false;
                btn.textContent = 'Network error';
                setTimeout(function() { btn.textContent = originalText; }, 3000);
            });
        });

        /**
         * 6. SCHEMA JSON VALIDITY
         *
         * Live "accepted or not" feedback for the schema box. Validates pure
         * JSON — matches the front-end engine, which adds the <script> wrapper
         * itself. Empty = no message; invalid = simply won't be output.
         */
        function bbValidateSchema(textarea) {
            var uid    = textarea.getAttribute('data-uid');
            var status = uid ? document.getElementById(uid + '-schema-status') : null;
            if (!status) return;

            var val = textarea.value.trim();
            if (val === '') {
                status.textContent = '';
                return;
            }

            var valid = true;
            try { JSON.parse(val); } catch (err) { valid = false; }

            if (valid) {
                status.textContent = '\u2713 Valid JSON \u2014 will be output';
                status.style.color = '#118a00';
            } else {
                status.textContent = '\u2717 Not valid JSON \u2014 won\'t be output until fixed';
                status.style.color = '#dc3232';
            }
        }

        document.addEventListener('input', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('bb-schema-input')) {
                bbValidateSchema(e.target);
            }
        });

        var bbSchemaInputs = document.querySelectorAll('.bb-schema-input');
        for (var bbI = 0; bbI < bbSchemaInputs.length; bbI++) {
            bbValidateSchema(bbSchemaInputs[bbI]);
        }

    });

})();

/**
 * 5. BULK MANAGER ROW TOGGLE
 *
 * Global function called by onclick attributes in PHP.
 * Swaps collapsed/expanded rows and rotates the chevron.
 * Multiple rows can be open simultaneously.
 *
 * @param {number|string} postId
 */
function bbToggleRow(postId) {
    var uid      = 'bb-' + postId;
    var collapsed = document.getElementById(uid + '-collapsed');
    var expanded  = document.getElementById(uid + '-expanded');
    var chevron   = document.getElementById(uid + '-chevron');

    if (!collapsed || !expanded) return;

    var isExpanded = (expanded.style.display !== 'none');

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

document.addEventListener('DOMContentLoaded', function() {
    // Handle Adding Rows
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-bbs-row')) {
            const btn = e.target.closest('.add-bbs-row');
            const container = document.getElementById(btn.dataset.target);
            const tbody = container.querySelector('.bbs-tracking-rows');
            const template = container.querySelector('.bbs-row-template').innerHTML;
            
            // Generate a unique index based on current row count
            const index = tbody.children.length;
            const newRowHtml = template.replace(/{{INDEX}}/g, index);
            
            tbody.insertAdjacentHTML('beforeend', newRowHtml);
        }
    });

    // Handle Removing Rows
    document.addEventListener('click', function(e) {
        if (e.target.closest('.bbs-remove-row')) {
            const row = e.target.closest('tr');
            if (confirm('Are you sure you want to remove this script?')) {
                row.remove();
            }
        }
    });
});
