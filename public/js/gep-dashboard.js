jQuery(document).ready(function($) {

    // ─── Light / dark theme ──────────────────────────────────────────────────
    // The stored choice is already applied by an inline script in <head> so the
    // page never paints in the wrong palette. This only handles the switch, and
    // only writes to storage when the student actually picks one — leaving the
    // key absent means "follow the device", which is the default we want.
    (function () {
        var root = document.documentElement;
        var toggle = document.getElementById('gep-theme-toggle');
        if (!toggle) return;

        function current() {
            var set = root.getAttribute('data-gep-theme');
            if (set === 'dark' || set === 'light') return set;
            return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
                ? 'dark' : 'light';
        }

        function label() {
            var next = current() === 'dark' ? 'light' : 'dark';
            toggle.setAttribute('title', 'Switch to ' + next + ' theme');
            toggle.setAttribute('aria-label', 'Switch to ' + next + ' theme');
        }
        label();

        toggle.addEventListener('click', function () {
            var next = current() === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-gep-theme', next);
            try { localStorage.setItem('gep-theme', next); } catch (e) {}
            label();
        });

        // Follow the device while the student has expressed no preference of
        // their own, so a phone flipping to dark at sunset takes the portal with
        // it mid-session.
        if (window.matchMedia) {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var onChange = function () {
                var stored = null;
                try { stored = localStorage.getItem('gep-theme'); } catch (e) {}
                if (stored !== 'dark' && stored !== 'light') {
                    root.removeAttribute('data-gep-theme');
                    label();
                }
            };
            if (mq.addEventListener) mq.addEventListener('change', onChange);
            else if (mq.addListener) mq.addListener(onChange);
        }
    })();

    $('.gep-dashboard-nav a[data-tab]').on('click', function(e) {
        e.preventDefault();
        
        var tab = $(this).data('tab');
        
        // Update Nav
        $('.gep-dashboard-nav a').removeClass('active');
        $(this).addClass('active');
        
        // Update Title
        $('#gep-tab-title').text($(this).text().replace(/[^\w\s]/gi, '').trim());
        
        // Switch View
        $('.gep-tab-view').hide().removeClass('active');
        $('#gep-view-' + tab).fadeIn().addClass('active');
        
        // Update URL hash
        window.location.hash = tab;
    });

    // Category Filter — BUG-24 FIX: preserve current tab hash when reloading with category param
    $('#gep-cat-filter').on('change', function() {
        var cat = $(this).val();
        var url = new URL(window.location.href);
        if (cat > 0) {
            url.searchParams.set('cat', cat);
        } else {
            url.searchParams.delete('cat');
        }
        // Preserve active tab hash so user lands on the same tab after reload
        if (window.location.hash) {
            url.hash = window.location.hash;
        }
        window.location.href = url.toString();
    });

    // Search the rendered cards; keep the test hub's category/type filters together.
    var $search = $('#gep-header-search-input');
    var $hubSearch = $('#gep-test-search');
    var $cards = $('.gep-main-inner').find('.gep-hcard, .gep-course-card, .gep-test-card, .gep-asset-card');
    if (!$hubSearch.length && !$cards.length) {
        $search.attr('placeholder', 'Search the test catalogue...').attr('aria-label', 'Search the test catalogue');
    }
    function filterPage() {
        var value = $.trim($search.val() || '').toLocaleLowerCase();
        if ($hubSearch.length) {
            $hubSearch.val(value).trigger('input');
            return;
        }
        if (!$cards.length) return;
        var matches = 0;
        $cards.each(function() {
            var show = $(this).text().toLocaleLowerCase().indexOf(value) !== -1;
            $(this).toggle(show);
            if (show) matches++;
        });
        if (!$('#gep-search-feedback').length) {
            $('.gep-main-inner').prepend('<div id="gep-search-feedback" class="gep-search-feedback" role="status"></div>');
        }
        $('#gep-search-feedback').text(value ? (matches ? matches + ' matching items on this page.' : 'No matches on this page. Try another term or browse Test Series.') : '').toggle(!!value);
    }
    $search.on('input', filterPage);
    $('#gep-header-search-form').on('submit', function(e) {
        if ($cards.length || $hubSearch.length) { e.preventDefault(); filterPage(); }
    });
    if ($search.val()) filterPage();

    // One owner for the mobile drawer. Native click also handles touch and keyboard.
    var $sidebar = $('.gep-dashboard-sidebar');
    var $toggle = $('#gep-menu-toggle');
    var $main = $('.gep-dashboard-content');
    if ($sidebar.length && !$('#gep-sidebar-backdrop').length) {
        $('body').append('<div id="gep-sidebar-backdrop" class="gep-sidebar-backdrop"></div>');
    }
    var $backdrop = $('#gep-sidebar-backdrop');
    function closeSidebar(restoreFocus) {
        $sidebar.removeClass('active').removeAttr('role aria-modal');
        $backdrop.removeClass('active');
        $('html').removeClass('gep-menu-open');
        $main.prop('inert', false);
        $toggle.attr({'aria-expanded': 'false', 'aria-label': 'Open menu'});
        $sidebar.prop('inert', window.innerWidth <= 1024);
        if (restoreFocus && $toggle.length) $toggle[0].focus();
    }
    function openSidebar() {
        $sidebar.prop('inert', false).addClass('active').attr({'role': 'dialog', 'aria-modal': 'true'});
        $backdrop.addClass('active');
        $('html').addClass('gep-menu-open');
        $toggle.attr({'aria-expanded': 'true', 'aria-label': 'Close menu'});
        $('#gep-menu-close').trigger('focus');
        $main.prop('inert', true);
    }
    closeSidebar(false);
    $toggle.on('click', function() { $sidebar.hasClass('active') ? closeSidebar(true) : openSidebar(); });
    $('#gep-menu-close, #gep-sidebar-backdrop').on('click', function() { closeSidebar(true); });
    $sidebar.find('a').on('click', function() { if (window.innerWidth <= 1024) closeSidebar(false); });
    $(window).on('resize', function() { closeSidebar(false); });
    $(document).on('keydown', function(e) {
        if (!$sidebar.hasClass('active')) return;
        if (e.key === 'Escape') { e.preventDefault(); closeSidebar(true); }
        if (e.key === 'Tab') {
            var $focusable = $sidebar.find('a, button, input, select').filter(':visible').filter(':enabled');
            var first = $focusable[0], last = $focusable[$focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });
    $('.gep-dashboard-nav a.active').attr('aria-current', 'page');

    var $bell = $('#gep-notif-trigger'), $panel = $('#gep-notif-panel');
    function closeNotifications(restoreFocus) {
        $panel.removeClass('active'); $bell.attr('aria-expanded', 'false');
        if (restoreFocus) $bell.trigger('focus');
    }
    $bell.on('click', function() {
        var open = !$panel.hasClass('active');
        $panel.toggleClass('active', open); $bell.attr('aria-expanded', String(open));
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.gep-header-notification-wrapper').length) closeNotifications(false);
    }).on('keydown', function(e) {
        if (e.key === 'Escape' && $panel.hasClass('active')) closeNotifications(true);
    });
    $(document).on('keydown', '.notif-item[role="button"], .gep-notification-item[role="button"], .gep-skill-card[role="button"]', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
    });

    // Avatar Upload Handler — BUG-21 FIX: Add error handler so button doesn't get stuck
    $('#gep-avatar-input').on('change', function() {
        var file_data = $(this).prop('files')[0];
        if (!file_data) return;

        var form_data = new FormData();
        form_data.append('avatar', file_data);
        form_data.append('action', 'gep_update_avatar');
        // BUG-22 FIX: Explicitly append nonce with the exact field name the server expects
        form_data.append('gep_profile_nonce', $('#gep-profile-nonce').val());

        var $avatarContainer = $('.gep-profile-avatar');
        $avatarContainer.css('opacity', '0.5');

        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            data: form_data,
            contentType: false,
            processData: false,
            success: function(response) {
                $avatarContainer.css('opacity', '1');
                if (response.success) {
                    $('.gep-profile-avatar img').attr('src', response.data.image_url);
                    // Also update header avatar if it's an image
                    $('.gep-header-avatar').css('background-image', 'url(' + response.data.image_url + ')').text('');
                    show_gep_notification('Avatar updated successfully!', 'success');
                } else {
                    show_gep_notification(response.data.message || 'Upload failed.', 'danger');
                }
            },
            error: function(xhr, status, err) {
                // BUG-21 FIX: Restore opacity and show error so button isn't stuck
                $avatarContainer.css('opacity', '1');
                show_gep_notification('Upload failed. Please check file size and try again.', 'danger');
            }
        });
    });

    // Profile Form Handler — BUG-20 FIX: Add error handler so submit button doesn't get stuck
    $('#gep-profile-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Updating...');

        // BUG-22 FIX: Explicitly append nonce with exact field name to guarantee it's sent correctly
        var nonce_val = $('#gep-profile-nonce').val();
        var form_data = $(this).serialize() + '&action=gep_update_profile&gep_profile_nonce=' + encodeURIComponent(nonce_val);

        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            data: form_data,
            success: function(response) {
                $btn.prop('disabled', false).text(originalText);
                if (response.success) {
                    show_gep_notification(response.data.message, 'success');
                } else {
                    show_gep_notification(response.data.message || 'Update failed.', 'danger');
                }
            },
            error: function(xhr, status, err) {
                // BUG-20 FIX: Restore button state on network/server error
                $btn.prop('disabled', false).text(originalText);
                show_gep_notification('Connection error. Please try again.', 'danger');
            }
        });
    });

    // Mark all notifications as read
    $(document).on('click', '#gep-mark-all-read, .gep-mark-all-read', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        if ($btn.data('pending')) return;
        var originalText = $btn.text();
        $btn.data('pending', true).attr('aria-disabled', 'true').text('Marking…');
        
        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gep_mark_all_notifs_read',
                nonce: gep_ajax.nonce
            },
            complete: function() { $btn.data('pending', false).removeAttr('aria-disabled'); },
            success: function(response) {
                if (response.success) {
                    $('.gep-notif-count').fadeOut(function() { $(this).remove(); });
                    $('.notif-item, .gep-notification-item').removeClass('unread');
                    $btn.text(originalText);
                    show_gep_notification('All notifications marked as read.', 'success');
                } else {
                    $btn.text(originalText);
                    show_gep_notification('Failed to mark notifications as read.', 'danger');
                }
            },
            error: function() {
                $btn.text(originalText);
                show_gep_notification('AJAX protocol error.', 'danger');
            }
        });
    });

    // Mark individual notification as read on click
    $(document).on('click', '.notif-item.unread, .gep-notification-item.unread', function(e) {
        var $item = $(this);
        var id = $item.data('id');
        if (!id || $item.data('pending')) return;
        $item.data('pending', true);
        
        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gep_mark_notif_read',
                id: id,
                nonce: gep_ajax.nonce
            },
            complete: function() { $item.data('pending', false); },
            error: function() { show_gep_notification('Could not mark this notification as read. Try again.', 'danger'); },
            success: function(response) {
                if (response.success) {
                    $('.notif-item, .gep-notification-item').filter(function() { return String($(this).data('id')) === String(id); }).removeClass('unread').find('.notif-pulse-dot').remove();
                    
                    // Update main student notification page icon/status if clicked there
                    if ($item.hasClass('gep-notification-item')) {
                        $item.find('.icon').text('✅');
                    }

                    // Decrement notification count in header
                    var $countBadge = $('.gep-notif-count');
                    if ($countBadge.length) {
                        var count = parseInt($countBadge.text(), 10);
                        if (count > 1) {
                            $countBadge.text(count - 1);
                        } else {
                            $countBadge.fadeOut(function() { $(this).remove(); });
                        }
                    }
                }
            }
        });
    });

    function show_gep_notification(message, type) {
        var $notif = $('<div class="gep-alert gep-alert-' + type + ' gep-toast" role="status"></div>').text(message).hide();
        $('body').append($notif);
        $notif.fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
    }

    // Deliberate carousel navigation: no moving target while reading or tabbing.
    (function() {
        var $slides = $('.gep-carousel-slide'), $dots = $('.gep-carousel-dot');
        if (!$slides.length) return;
        function showSlide(index) {
            $slides.each(function(i) {
                $(this).toggleClass('active', i === index).attr('aria-hidden', String(i !== index)).prop('inert', i !== index);
            });
            $dots.removeClass('active').attr('aria-pressed', 'false').eq(index).addClass('active').attr('aria-pressed', 'true');
        }
        $dots.on('click', function() { showSlide(Number($(this).data('index'))); });
        showSlide(0);
    })();
});
