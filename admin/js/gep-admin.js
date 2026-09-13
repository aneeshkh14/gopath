jQuery(document).ready(function($) {
    // Admin specific interactions
    console.log('GoPath Exam Portal Admin Loaded');

    // Professional Modal Interactivity: Click outside to close
    $(document).on('click', '.gep-modal-overlay', function(e) {
        // Only close if the actual overlay (the blank area) was clicked, not its children
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });

    // Ensure escape key also closes modals for premium accessibility
    $(document).keydown(function(e) {
        if (e.keyCode === 27) { // ESC key
            $('.gep-modal-overlay:visible').fadeOut(200);
        }
    });

    // AI Question Injector Engine
    $('#gep_btn_fetch_questions').on('click', function() {
        const $btn = $(this);
        const catId = $('#gep_fetch_cat').val();
        const count = $('#gep_fetch_count').val();

        if (!catId) {
            alert('Please select a subject domain first.');
            return;
        }

        $btn.prop('disabled', true).text('Injecting...');

        $.post(ajaxurl, {
            action: 'gep_fetch_question_ids',
            cat_id: catId,
            count: count,
            nonce: $('#gep_test_nonce').val()
        }, function(response) {
            if (response.success) {
                const existing = $('#gep_question_ids').val();
                const newIds = response.data.ids.join(',');
                
                if (existing && confirm('Merge with existing IDs? Cancel to overwrite.')) {
                    $('#gep_question_ids').val(existing + ',' + newIds);
                } else {
                    $('#gep_question_ids').val(newIds);
                }

                // Trigger input event to update the total count display
                $('#gep_question_ids').trigger('input');
                
                $btn.text('Injection Success!').addClass('button-primary');
                setTimeout(() => {
                    $btn.prop('disabled', false).text('Inject IDs').removeClass('button-primary');
                }, 2000);
            } else {
                alert('Intelligence Retrieval Failed: ' + (response.data || 'Unknown error'));
                $btn.prop('disabled', false).text('Inject IDs');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Inject IDs');
            alert('Could not load questions. Please check your connection and try again.');
        });
    });

    // Media Library Image/Banner Uploader Handler
    $(document).on('click', '.gep-upload-image-btn', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $input = $('#' + $button.data('target'));
        
        var uploader = wp.media({
            title: 'Select or Upload Banner Image',
            button: {
                text: 'Use this Image'
            },
            multiple: false
        }).on('select', function() {
            var attachment = uploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
        }).open();
    });
});

// Give existing admin dialogs a consistent keyboard lifecycle without changing
// their submit handlers or WordPress media dialogs.
jQuery(function($) {
    var active = null, opener = null, previousFocus = null;
    document.addEventListener('click', function(e) { if (!active) previousFocus = e.target.closest('button, a, input'); }, true);
    function syncDialogs() {
        var next = Array.from(document.querySelectorAll('.gep-modal-overlay')).find(el => getComputedStyle(el).display !== 'none');
        if (next === active) return;
        if (!next) {
            active = null;
            if (opener && opener.isConnected) opener.focus();
            opener = null;
            return;
        }
        active = next; opener = previousFocus || document.activeElement;
        var panel = active.querySelector('.gep-modal-content') || active;
        panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-modal', 'true'); panel.tabIndex = -1;
        var heading = panel.querySelector('h2, h3');
        if (heading) {
            if (!heading.id) heading.id = active.id + '-heading';
            panel.setAttribute('aria-labelledby', heading.id);
        }
        var first = panel.querySelector('button, input:not([type="hidden"]), select, textarea');
        (first || panel).focus();
    }
    document.querySelectorAll('.gep-modal-overlay').forEach(function(el) {
        new MutationObserver(syncDialogs).observe(el, {attributes: true, attributeFilter: ['style', 'class']});
    });
    document.addEventListener('keydown', function(e) {
        if (!active || e.key !== 'Tab') return;
        var items = $(active).find('button, a[href], input:not([type="hidden"]), select, textarea').filter(':enabled').filter(':visible').toArray();
        if (!items.length) { e.preventDefault(); return; }
        var first=items[0], last=items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
    // Associate simple adjacent labels used across legacy admin forms.
    $('.gep-admin-wrap label:not([for])').each(function(index) {
        if (this.querySelector('input, select, textarea')) return;
        var sibling=this.nextElementSibling;
        if (!sibling) return;
        var field=sibling.matches('input, select, textarea') ? sibling : null;
        if (!field || field.type === 'hidden') return;
        if (!field.id) field.id='gep-admin-field-' + index;
        this.htmlFor=field.id;
    });
    $('.gep-admin-table-container').attr({'tabindex':'0', 'role':'region', 'aria-label':'Scrollable records table'});
    syncDialogs();
});
