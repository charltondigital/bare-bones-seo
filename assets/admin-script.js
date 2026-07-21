jQuery(document).ready(function($) {

    // --- 1. Snappy Tab Switching (No Page Reloads) ---
    $('.nav-tab-wrapper a.nav-tab').on('click', function(e) {
        // Only trigger on the Bare Bones SEO dashboard
        if (window.location.search.indexOf('page=bare-bones-seo') === -1) return;
        
        e.preventDefault();

        // Update URL without reloading (allows bookmarking specific tabs)
        var url = new URL(window.location.href);
        var tabId = $(this).attr('href').split('tab=')[1] || 'overview';
        url.searchParams.set('tab', tabId);
        window.history.pushState(null, null, url.toString());

        // Toggle Visual Active State
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Show/Hide Content
        $('.bbseo-tab-content').hide();
        $('#bbseo-tab-' + tabId).show();
    });

    // --- 2. Tracking Scripts: Add Row ---
    $(document).on('click', '.bb-add-script-row', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var inputName = $button.data('input-name');
        
        // Find the specific container and template
        var $wrapper = $button.closest('.bbs-tracking-manager-wrapper');
        var $tbody = $wrapper.find('.bb-tracking-rows');
        var $template = $('#tpl-' + inputName);

        if (!$template.length) {
            console.error('BBS SEO: Template #tpl-' + inputName + ' not found.');
            return;
        }

        // Use timestamp for unique index to avoid row overwriting
        var index = Date.now();
        var newRow = $template.html().replace(/{{INDEX}}/g, index);
        
        $tbody.append(newRow);
    });

    // --- 3. Tracking Scripts: Remove Row ---
    $(document).on('click', '.bb-remove-row', function(e) {
        e.preventDefault();
        if (confirm('Permanently remove this script?')) {
            $(this).closest('tr').remove();
        }
    });

    // --- 4. Section Toggles (Meta Box Accordions) ---
    $(document).on('click', '.bb-section-toggle', function() {
        var targetId = $(this).data('target');
        var $target = $('#' + targetId);
        var $icon = $(this).find('.bb-toggle-icon');
        
        if ($target.is(':visible')) {
            $target.slideUp(150);
            $icon.text('+');
        } else {
            $target.slideDown(150);
            $icon.text('−');
        }
    });
});
