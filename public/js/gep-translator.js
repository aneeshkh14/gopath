jQuery(document).ready(function($) {
    if (typeof GEP_Exam === 'undefined') return;

    const langBtn = $('#gep-lang-toggle');
    // Current exam templates render translations directly and have no legacy controls.
    if (!langBtn.length && !$('.gep-lang-icon').length) return;
    // Init from PHP session or sessionStorage
    let currentLang = GEP_Exam.lang || 'en';
    try { currentLang = sessionStorage.getItem('gep_current_lang') || currentLang; } catch (e) {}
    
    // Set initial state
    if (currentLang === 'hi') {
        langBtn.addClass('active').text('Switch to English');
    }

    // Global toggle (Header)
    langBtn.on('click', function() {
        currentLang = (currentLang === 'en') ? 'hi' : 'en';
        try { sessionStorage.setItem('gep_current_lang', currentLang); } catch (e) {}
        
        applyLanguageToAll();
        
        if (currentLang === 'hi') {
            $(this).addClass('active').text('Switch to English');
        } else {
            $(this).removeClass('active').text('Switch to Hindi');
        }
    });

    // Per-question toggle (Comment 1)
    $(document).on('click', '.gep-lang-icon', function(e) {
        e.stopPropagation();
        const block = $(this).closest('.gep-question-block');
        const id = block.data('id');
        const blockLang = block.data('current-lang') || currentLang;
        const newLang = (blockLang === 'en') ? 'hi' : 'en';
        
        block.data('current-lang', newLang);
        renderBlockLang(block, id, newLang);
    });

    // Apply language when switching questions
    $(document).on('gep_question_changed', function(e, data) {
        const block = $(`.gep-question-block[data-id="${data.questionId}"]`);
        const targetLang = block.data('current-lang') || currentLang;
        renderBlockLang(block, data.questionId, targetLang);
    });

    function applyLanguageToAll() {
        $('.gep-question-block').each(function() {
            const block = $(this);
            const id = block.data('id');
            block.data('current-lang', currentLang);
            renderBlockLang(block, id, currentLang);
        });
    }

    function renderBlockLang(block, id, lang) {
        if (lang === 'hi') {
            translateQuestion(id, block);
        } else {
            resetToEnglish(block);
        }
    }

    function translateQuestion(id, block) {
        // If already has Hindi data, just switch
        if (block.data('hi-title')) {
            renderLang(block, 'hi');
            return;
        }

        block.addClass('gep-loading');

        $.ajax({
            url: GEP_Exam.ajaxurl,
            type: 'POST',
            timeout: 20000,
            complete: function() { block.removeClass('gep-loading'); },
            data: {
                action: 'gep_get_translation',
                nonce: GEP_Exam.nonce,
                question_id: id
            },
            success: function(response) {
                block.removeClass('gep-loading');
                if (response.success) {
                    const data = response.data;
                    // Cache the translation in data attributes
                    block.data('en-title', block.find('.gep-q-text').html());
                    block.data('hi-title', data.title);
                    
                    // Options
                    ['a', 'b', 'c', 'd'].forEach(opt => {
                        const optText = block.find(`.gep-option-item input[value="${opt.toUpperCase()}"]`).closest('.gep-option-item').find('.gep-opt-text');
                        block.data(`en-option-${opt}`, optText.html());
                        block.data(`hi-option-${opt}`, data['option_' + opt]);
                    });

                    // Explanation (Comment 1)
                    const expText = block.find('.gep-explanation .exp-text');
                    block.data('en-explanation', expText.html());
                    block.data('hi-explanation', data.explanation);
                    
                    renderLang(block, 'hi');
                }
            }
        });
    }

    function resetToEnglish(block) {
        if (block.data('en-title')) {
            renderLang(block, 'en');
        }
    }

    function renderLang(block, lang) {
        const title = block.data(lang + '-title');
        if (title) block.find('.gep-q-text').html(title);

        ['a', 'b', 'c', 'd'].forEach(opt => {
            const content = block.data(`${lang}-option-${opt}`);
            if (content) {
                block.find(`.gep-option-item input[value="${opt.toUpperCase()}"]`).closest('.gep-option-item').find('.gep-opt-text').html(content);
            }
        });

        const explanation = block.data(lang + '-explanation');
        if (explanation) {
            block.find('.gep-explanation .exp-text').html(explanation);
            block.find('.gep-explanation').show();
        }
    }
});
