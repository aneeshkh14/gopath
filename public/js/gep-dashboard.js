jQuery(document).ready(function($) {
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

    // Real-time Search Filter
    $('#gep-header-search-input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        
        // Filter Course Cards
        $('.gep-course-card').filter(function() {
            $(this).toggle($(this).find('h4').text().toLowerCase().indexOf(value) > -1);
        });

        // Filter Test Cards
        $('.gep-test-card').filter(function() {
            $(this).toggle($(this).find('h4').text().toLowerCase().indexOf(value) > -1);
        });
    });

    // ── Mobile Sidebar Toggle ────────────────────────────────────────────────
    // FIX 1: Always start with sidebar CLOSED on every page load (clears stale state)
    var $sidebar = $('.gep-dashboard-sidebar');
    $sidebar.removeClass('active');
    $('body').css('overflow', '');

    // FIX 2: Inject backdrop and ensure it starts hidden
    if ( ! $('#gep-sidebar-backdrop').length ) {
        $('body').append('<div id="gep-sidebar-backdrop" class="gep-sidebar-backdrop"></div>');
    }
    var $backdrop = $('#gep-sidebar-backdrop');
    $backdrop.removeClass('active');

    function openSidebar() {
        $sidebar.addClass('active');
        $backdrop.addClass('active');
        $('body').css('overflow', 'hidden');
    }
    function closeSidebar() {
        $sidebar.removeClass('active');
        $backdrop.removeClass('active');
        $('body').css('overflow', '');
    }

    // FIX 3: Use document-level delegation for reliable mobile touch (catches button regardless of DOM timing)
    $(document).on('click touchend', '#gep-menu-toggle, .gep-mobile-toggle, .gep-sidebar-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if ( $sidebar.hasClass('active') ) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    // Close when backdrop clicked/touched
    $(document).on('click touchend', '#gep-sidebar-backdrop', function(e) {
        e.preventDefault();
        closeSidebar();
    });

    // Close when a nav link is tapped (mobile UX)
    $sidebar.find('a').on('click', function() {
        if ( window.innerWidth <= 1024 ) {
            closeSidebar();
        }
    });



    if (window.location.hash) {
        var hash = window.location.hash.replace('#', '');
        $('.gep-dashboard-nav a[data-tab="' + hash + '"]').click();
    }

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
    $(document).on('click', '#gep-mark-all-read', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Marking...');
        
        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gep_mark_all_notifs_read',
                nonce: gep_ajax.nonce
            },
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
        if (!id) return;
        
        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gep_mark_notif_read',
                id: id,
                nonce: gep_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $item.removeClass('unread');
                    
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
        var $notif = $('<div class="gep-alert gep-alert-' + type + '" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: none;">' + message + '</div>');
        $('body').append($notif);
        $notif.fadeIn().delay(3000).fadeOut(function() { $(this).remove(); });
    }

    // Hero Slideshow Carousel Logic
    (function() {
        var $slides = $('.gep-carousel-slide');
        var $dots = $('.gep-carousel-dot');
        var currentSlide = 0;
        var slideInterval;

        function showSlide(index) {
            if ($slides.length === 0) return;
            $slides.removeClass('active');
            $dots.removeClass('active');

            currentSlide = (index + $slides.length) % $slides.length;
            $slides.eq(currentSlide).addClass('active');
            $dots.eq(currentSlide).addClass('active');
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function startAutoplay() {
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        }

        if ($slides.length > 0) {
            $dots.on('click', function() {
                var index = parseInt($(this).data('index'), 10);
                showSlide(index);
                startAutoplay();
            });

            // Touch Swipe Gesture Support with Passive Listeners
            var startX = 0;
            var endX = 0;
            var isDragging = false;

            var carouselEl = document.querySelector('.gep-dashboard-carousel');
            if (carouselEl) {
                carouselEl.addEventListener('touchstart', function(e) {
                    startX = e.touches[0].clientX;
                    endX = 0;
                }, { passive: true });

                carouselEl.addEventListener('touchmove', function(e) {
                    endX = e.touches[0].clientX;
                }, { passive: true });

                carouselEl.addEventListener('touchend', function() {
                    var diff = startX - endX;
                    if (Math.abs(diff) > 50 && endX !== 0) {
                        if (diff > 0) {
                            nextSlide();
                        } else {
                            showSlide(currentSlide - 1);
                        }
                        startAutoplay();
                    }
                    startX = 0;
                    endX = 0;
                }, { passive: true });
            }

            // Mouse Drag Gesture Support (Bind document-level listeners ONLY when dragging is active)
            $('.gep-dashboard-carousel').on('mousedown', function(e) {
                startX = e.clientX;
                endX = 0;
                isDragging = true;

                $(document).on('mousemove.gep_carousel', function(ev) {
                    if (isDragging) {
                        endX = ev.clientX;
                    }
                });

                $(document).on('mouseup.gep_carousel', function() {
                    if (isDragging) {
                        isDragging = false;
                        var diff = startX - endX;
                        if (Math.abs(diff) > 75 && endX !== 0) {
                            if (diff > 0) {
                                nextSlide();
                            } else {
                                showSlide(currentSlide - 1);
                            }
                            startAutoplay();
                        }
                        startX = 0;
                        endX = 0;
                    }
                    // Unbind namespace listeners instantly to free CPU cycles
                    $(document).off('.gep_carousel');
                });
            });

            startAutoplay();
        }
    })();
});
