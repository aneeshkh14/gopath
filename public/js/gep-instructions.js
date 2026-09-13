jQuery(document).ready(function($) {
    if (typeof GEP_Instructions === 'undefined') return;

    const agreeCheck = $('#agree-terms');
    const startBtn = $('#start-exam-btn');
    const originalBtnText = startBtn.text();
    var starting = false;
    function showError(message) {
        if (!$('#gep-start-error').length) startBtn.before('<p id="gep-start-error" role="alert"></p>');
        $('#gep-start-error').text(message);
    }



    // ─── LANGUAGE SELECT DROPDOWN ──────────────────────────────────
    $('#gep-inst-lang-select').on('change', function() {
        const lang = $(this).val();
        const $select = $(this);
        
        $select.prop('disabled', true);

        $.ajax({
            url: GEP_Instructions.ajaxurl,
            type: 'POST',
            timeout: 20000,
            data: {
                action: 'gep_update_lang',
                lang: lang
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $select.prop('disabled', false);
                }
            },
            error: function() {
                $select.prop('disabled', false);
            }
        });
    });

    // ─── DYNAMIC RANDOM TEST SELECTOR ──────────────────────────────
    var subjectsConfig = [];
    var activeSubjectId = null;

    if ($('#gep-random-selector-wrap').length) {
        $.ajax({url: GEP_Instructions.ajaxurl, type: 'POST', timeout: 20000, data: {action: 'gep_get_random_test_config'}}).done(function(res) {
            if (res.success && res.data.length) {
                subjectsConfig = res.data;
                renderSubjectTabs();
            } else {
                $('#gep-subject-tabs').html('<div style="color: #ef4444; font-weight: 700; font-size: 13px;">No subjects/topics configured in the question bank.</div>');
            }
        }).fail(function() {
            $('#gep-subject-tabs').empty().append($('<button type="button">Retry loading subjects</button>').on('click', function() { location.reload(); }));
            showError('Could not load subjects. Check your connection and retry.');
        });
    }

    function renderSubjectTabs() {
        var html = '';
        subjectsConfig.forEach(function(sub, idx) {
            var activeClass = idx === 0 ? 'background: #2563eb; color: #fff;' : 'background: #f1f5f9; color: #475569;';
            html += '<button type="button" class="gep-sub-tab" data-id="' + sub.id + '" style="border: none; padding: 10px 20px; border-radius: 12px; font-weight: 800; font-size: 13px; cursor: pointer; transition: all 0.3s; ' + activeClass + '">' + $('<div>').text(sub.name).html() + '</button>';
        });
        $('#gep-subject-tabs').html(html);
        
        if (subjectsConfig.length) {
            selectSubject(subjectsConfig[0].id);
        }
    }

    $(document).on('click', '.gep-sub-tab', function() {
        var subId = $(this).data('id');
        $('.gep-sub-tab').css({'background': '#f1f5f9', 'color': '#475569'});
        $(this).css({'background': '#2563eb', 'color': '#fff'});
        selectSubject(subId);
    });

    function selectSubject(subId) {
        activeSubjectId = subId;
        var subject = subjectsConfig.find(function(s) { return String(s.id) === String(subId); });
        if (!subject) return;

        var html = '';
        subject.topics.forEach(function(topic) {
            if (Number(topic.question_count) < 1) return;
            html += '<div class="gep-topic-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; background: #f8fafc; border-radius: 12px; border: 1.5px solid #f1f5f9; margin-bottom: 8px;">' +
                '<label style="display: flex; align-items: center; gap: 10px; font-weight: 700; color: #1e293b; cursor: pointer; flex: 1; margin: 0;">' +
                    '<input type="checkbox" class="gep-topic-cb" value="' + topic.id + '" style="accent-color: #2563eb; width: 16px; height: 16px;">' +
                    '<span>' + $('<div>').text(topic.name).html() + ' <small style="color: #94a3b8; font-weight: 600;">(Available: ' + topic.question_count + ')</small></span>' +
                '</label>' +
                '<input type="number" class="gep-topic-count" aria-label="Number of questions" min="1" max="' + topic.question_count + '" value="5" data-max="' + topic.question_count + '" style="width: 75px; text-align: center; border-radius: 8px; border: 1.5px solid #cbd5e1; font-weight: 800; padding: 6px; display: none;">' +
            '</div>';
        });
        $('#gep-topics-container').html(html);
        updateTotalSelectedCount();
    }

    $(document).on('change', '.gep-topic-cb', function() {
        var $row = $(this).closest('.gep-topic-row');
        var $countInput = $row.find('.gep-topic-count');
        if ($(this).is(':checked')) {
            var maxVal = parseInt($countInput.data('max')) || 0;
            var defaultVal = Math.min(5, maxVal);
            $countInput.val(defaultVal).show();
            $row.css({'border-color': '#bfdbfe', 'background': '#f0f6ff'});
        } else {
            $countInput.hide().val(0);
            $row.css({'border-color': '#f1f5f9', 'background': '#f8fafc'});
        }
        updateTotalSelectedCount();
    });

    $(document).on('input change', '.gep-topic-count', function() {
        var maxVal = parseInt($(this).data('max')) || 0;
        var val = parseInt($(this).val()) || 0;
        if (val < 1) $(this).val(1);
        if (val > maxVal) $(this).val(maxVal);
        updateTotalSelectedCount();
    });

    function updateTotalSelectedCount() {
        var total = 0;
        $('.gep-topic-cb:checked').each(function() {
            var count = parseInt($(this).closest('.gep-topic-row').find('.gep-topic-count').val()) || 0;
            total += count;
        });
        $('#gep-total-selected-q').text(total);
        validateStartButton();
    }

    function validateStartButton() {
        var total = parseInt($('#gep-total-selected-q').text()) || 0;
        var isAgreed = $('#agree-terms').is(':checked');
        var isRandom = $('#gep-random-selector-wrap').length > 0;
        
        if (starting) { startBtn.prop('disabled', true); return; }
        if (isRandom) {
            $('#start-exam-btn').prop('disabled', !(isAgreed && total > 0));
        } else {
            $('#start-exam-btn').prop('disabled', !isAgreed);
        }
    }

    $(document).on('change', '#agree-terms', function() {
        validateStartButton();
    });

    // ─── START EXAM ACTION ─────────────────────────────────────────
    startBtn.on('click', function() {
        if (starting) return;
        starting = true;
        $('#gep-start-error').text('');
        const testId = GEP_Instructions.test_id;
        
        startBtn.prop('disabled', true).text('STARTING...');

        // Clear instructions step and current lang session storage once test starts
        try { sessionStorage.removeItem('gep_inst_step'); sessionStorage.removeItem('gep_current_lang'); } catch (e) {}

        // Collect custom selections for random tests
        var selected_topics = [];
        if ($('#gep-random-selector-wrap').length) {
            $('.gep-topic-cb:checked').each(function() {
                var id = $(this).val();
                var count = parseInt($(this).closest('.gep-topic-row').find('.gep-topic-count').val()) || 0;
                if (count > 0) {
                    selected_topics.push({
                        topic_id: id,
                        count: count
                    });
                }
            });
        }

        $.ajax({
            url: GEP_Instructions.ajaxurl,
            type: 'POST',
            timeout: 20000,
            data: {
                action: 'gep_start_exam',
                nonce: GEP_Instructions.nonce,
                test_id: testId,
                selected_topics: selected_topics.length ? JSON.stringify(selected_topics) : ''
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); 
                } else {
                    showError(response.data && response.data.message || 'Failed to start exam.');
                    starting = false; startBtn.text(originalBtnText); validateStartButton();
                }
            },
            error: function(xhr, status, error) {
                console.error('GEP_EXAM_START_ERROR:', {status, error, response: xhr.responseText});
                showError('Could not connect. Check your connection and try again.');
                starting = false; startBtn.text(originalBtnText); validateStartButton();
            }
        });
    });
});
