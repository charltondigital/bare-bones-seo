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

    // --- 5. Bulk Manager: expand / collapse a row ---
    function bbCloseAllRows(exceptUid) {
        $('tr[id^="bb-"][id$="-expanded"]:visible').each(function() {
            var uid = this.id.replace(/-expanded$/, '');
            if (uid === exceptUid) { return; }
            $(this).hide();
            $('#' + uid + '-chevron').css('transform', 'rotate(0deg)');
        });
    }

    // Tracking tables are pulled in per row on first open rather than rendered
    // for every row up front. Failures leave the row unflagged so the next
    // open retries.
    function bbLoadTracking(postId) {
        if (typeof bbSeoData === 'undefined') { return; }

        var $lazy = $('#bb-' + postId + '-expanded')
            .find('.bb-tracking-lazy')
            .not('.bb-loaded, .bb-loading');

        if (!$lazy.length) { return; }

        $lazy.addClass('bb-loading');

        $.post(ajaxurl, {
            action:   bbSeoData.trackingAction,
            security: bbSeoData.nonce,
            post_id:  postId
        })
            .done(function(response) {
                if (response && response.success) {
                    $lazy.html(response.data.html).removeClass('bb-loading').addClass('bb-loaded');
                } else {
                    bbTrackingError($lazy);
                }
            })
            .fail(function() {
                bbTrackingError($lazy);
            });
    }

    function bbTrackingError($lazy) {
        $lazy.removeClass('bb-loading').html(
            '<p style="margin:0; color:#b32d2e;">Could not load tracking scripts. ' +
            'Close and reopen this row to try again.</p>'
        );
    }

    function bbToggleRow(postId) {
        var uid = 'bb-' + postId;
        var $expanded = $('#' + uid + '-expanded');
        var $chevron  = $('#' + uid + '-chevron');

        if ($expanded.is(':visible')) {
            bbCloseAllRows();
            return;
        }

        // Only one editor open at a time — stacked open rows are unreadable.
        bbCloseAllRows(uid);
        $expanded.show();
        $chevron.css('transform', 'rotate(90deg)');
        bbLoadTracking(postId);
    }

    // Delegated so it works regardless of script load order.
    $(document).on('click', '.bb-row-toggle', function(e) {
        // The View / Edit links live inside the clickable row.
        if ($(e.target).closest('.bb-row-actions').length) { return; }
        e.preventDefault();
        bbToggleRow($(this).data('post-id'));
    });

    // --- 6. Bulk Manager: save a row over AJAX ---
    $(document).on('click', '.bb-bulk-save', function(e) {
        e.preventDefault();

        if (typeof bbSeoData === 'undefined') { return; }

        var $btn   = $(this);
        var postId = $btn.data('post-id');
        var uid    = $btn.data('uid');
        var $row   = $('#' + uid + '-expanded');

        // serialize() preserves the post-ID-suffixed field names the server expects.
        var payload = $row.find('input, textarea, select').serialize()
            + '&action=' + encodeURIComponent(bbSeoData.ajaxAction)
            + '&security=' + encodeURIComponent(bbSeoData.nonce)
            + '&post_id=' + encodeURIComponent(postId);

        $btn.prop('disabled', true).text('Saving…');

        $.post(ajaxurl, payload)
            .done(function(response) {
                if (!response || !response.success) {
                    $btn.prop('disabled', false).text('Error — retry');
                    return;
                }
                $('#' + uid + '-desc-preview').text(response.data.desc || '');
                $('#' + uid + '-schema-preview').text(response.data.schema || '');
                $('#' + uid + '-badge-cell').html(
                    response.data.noindexed ? '<span class="bb-index-flag">\u2717</span>' : ''
                );
                $btn.text('Saved');
                setTimeout(function() {
                    $btn.prop('disabled', false).text('Update');
                    bbToggleRow(postId);
                }, 600);
            })
            .fail(function() {
                $btn.prop('disabled', false).text('Error — retry');
            });
    });
});
