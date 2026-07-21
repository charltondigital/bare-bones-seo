jQuery(document).ready(function($) {

    // --- 1. Dashboard Tab Switching (No Reload) ---
    $('.nav-tab-wrapper a.nav-tab').on('click', function(e) {
        if (window.location.search.indexOf('page=bare-bones-seo') === -1) return;
        e.preventDefault();
        
        var tabId = $(this).attr('href').split('tab=')[1] || 'overview';
        window.history.pushState(null, null, $(this).attr('href'));

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.bbseo-tab-content').hide();
        $('#bbseo-tab-' + tabId).show();
    });

    // --- 2. Add Tracking Row ---
    $(document).on('click', '.bb-add-script-row', function(e) {
        e.preventDefault();
        var inputName = $(this).data('input-name');
        var $wrapper = $(this).closest('.bbs-tracking-manager');
        var $tbody = $wrapper.find('.bb-tracking-rows');
        var $template = $('#tpl-' + inputName);

        if ($template.length) {
            var index = Date.now(); // Unique index
            var html = $template.html().replace(/{{INDEX}}/g, index);
            $tbody.append(html);
        }
    });

    // --- 3. Remove Tracking Row ---
    $(document).on('click', '.bb-remove-row', function(e) {
        e.preventDefault();
        if (confirm('Delete this script?')) {
            $(this).closest('tr').remove();
        }
    });

    // --- 4. Section Toggles ---
    $(document).on('click', '.bb-section-toggle', function() {
        var targetId = $(this).data('target');
        var $target = (targetId.startsWith('bb-')) ? $('#' + targetId) : $('[id$="' + targetId + '"]');
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
