(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {

        /**
         * SECTION TOGGLES
         *
         * Clicking a section header opens/closes the section body.
         * Toggle icon changes between + and − to indicate state.
         */
        var toggleButtons = document.querySelectorAll('.bb-section-toggle');

        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var target   = document.getElementById(targetId);
                var icon     = this.querySelector('.bb-toggle-icon');
                var expanded = this.getAttribute('aria-expanded') === 'true';

                if (expanded) {
                    target.style.display = 'none';
                    this.setAttribute('aria-expanded', 'false');
                    icon.textContent = '+';
                } else {
                    target.style.display = 'block';
                    this.setAttribute('aria-expanded', 'true');
                    icon.textContent = '−';
                }
            });
        });

        /**
         * INDEXING WARNING
         *
         * Show warning inline when NO or It's Complicated is selected.
         */
        var indexRadios  = document.querySelectorAll('input[name="bb_seo_should_index"]');
        var indexWarning = document.getElementById('bb-index-warning');

        if (indexRadios.length && indexWarning) {
            indexRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.value === 'no' || this.value === 'advanced') {
                        indexWarning.style.display = 'block';
                    } else {
                        indexWarning.style.display = 'none';
                    }
                });
            });
        }

        /**
         * SNIPPET PREVIEW
         *
         * On button click, read current title and description values,
         * build the preview string (title + site name), and update
         * both desktop and mobile preview panels.
         *
         * Uses the same font and container widths as Google:
         * - Desktop title: max-width 600px, Arial 20px
         * - Mobile title:  max-width 380px, Arial 20px
         * - Description:   2-line clamp in both
         *
         * CSS overflow: hidden + text-overflow: ellipsis handles
         * pixel-accurate truncation since we match Google's font/size.
         */
        var previewButton   = document.getElementById('bb-trigger-preview');
        var metaDataEl      = document.getElementById('bb-meta-data');

        if (!previewButton || !metaDataEl) {
            return;
        }

        var metaData    = JSON.parse(metaDataEl.textContent);
        var siteName    = metaData.site_name;
        var postTitle   = metaData.post_title;

        previewButton.addEventListener('click', function() {
            var titleInput = document.getElementById('bb_seo_title');
            var descInput  = document.getElementById('bb_seo_desc');

            if (!titleInput || !descInput) {
                return;
            }

            // Use custom title if entered, otherwise fall back to post title
            var rawTitle = titleInput.value.trim() || postTitle;

            // Append site name like Google does
            var fullTitle = rawTitle + ' \u2014 ' + siteName;

            var rawDesc = descInput.value.trim();

            // Update desktop preview
            var desktopTitle = document.getElementById('bb-preview-desktop-title');
            var desktopDesc  = document.getElementById('bb-preview-desktop-desc');

            if (desktopTitle) desktopTitle.textContent = fullTitle;
            if (desktopDesc)  desktopDesc.textContent  = rawDesc;

            // Update mobile preview
            var mobileTitle = document.getElementById('bb-preview-mobile-title');
            var mobileDesc  = document.getElementById('bb-preview-mobile-desc');

            if (mobileTitle) mobileTitle.textContent = fullTitle;
            if (mobileDesc)  mobileDesc.textContent  = rawDesc;
        });

    });

})();
