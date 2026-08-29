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
