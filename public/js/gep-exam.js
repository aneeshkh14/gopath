document.addEventListener('DOMContentLoaded', function() {
    if (typeof GEP_Exam === 'undefined') return;

    const examData = GEP_Exam;
    let currentQuestionIndex = 0;
    let timerInterval;
    let remainingSeconds = examData.remaining_seconds;
    let questionStartTime = Date.now(); // NTA-style per-Q time tracking
    let currentFontSize = 16; // Text zoom support — must match --gep-zoom-font-size in gep-exam.css
    
    // --- Sectional Timings ---
    let sectionalTimings = [];
    let hasSectionalTiming = false;
    let currentSectionIdx = 0;
    
    if (examData.sections_data && examData.sections_data.length > 0) {
        examData.sections_data.forEach((sec, idx) => {
            let t = parseInt(sec.time_limit) || 0;
            if (t > 0) hasSectionalTiming = true;
            sectionalTimings.push({
                id: 'sec_' + idx,
                limit_seconds: t * 60,
                name: sec.name || 'Section ' + (idx + 1)
            });
        });
    }

    if (hasSectionalTiming) {
        // Enforce sequential sectional time limits
        let elapsed = parseInt(examData.elapsed_seconds) || 0;
        for (let i = 0; i < sectionalTimings.length; i++) {
            let sec = sectionalTimings[i];
            if (sec.limit_seconds > 0) {
                if (elapsed >= sec.limit_seconds) {
                    elapsed -= sec.limit_seconds; // Already spent this section's time
                } else {
                    currentSectionIdx = i;
                    remainingSeconds = sec.limit_seconds - elapsed;
                    break;
                }
            } else {
                currentSectionIdx = i;
                // If section has no time limit, it just takes up whatever is left of the total test duration.
                remainingSeconds = examData.remaining_seconds - parseInt(examData.elapsed_seconds || 0);
                break;
            }
        }
        
        // Safety bounds
        if (currentSectionIdx >= sectionalTimings.length) {
            currentSectionIdx = sectionalTimings.length - 1;
            remainingSeconds = 0;
        }
    }

    // ─── Modal System (replaces all alert/confirm dialogs) ───────────────────
    function showModal({ title, message, type = 'info', buttons = [], onClose = null }) {
        // Remove any existing modal
        const existing = document.getElementById('gep-modal-overlay');
        if (existing) existing.remove();

        const iconMap = { info: '💡', warning: '⚠️', success: '✅', danger: '🚫', time: '⏰', submit: '📋' };
        const colorMap = {
            info:    { bg: 'rgba(99,102,241,0.12)', border: '#6366f1', btn: '#6366f1' },
            warning: { bg: 'rgba(245,158,11,0.12)', border: '#f59e0b', btn: '#f59e0b' },
            success: { bg: 'rgba(16,185,129,0.12)',  border: '#10b981', btn: '#10b981' },
            danger:  { bg: 'rgba(239,68,68,0.12)',   border: '#ef4444', btn: '#ef4444' },
            time:    { bg: 'rgba(239,68,68,0.12)',   border: '#ef4444', btn: '#ef4444' },
            submit:  { bg: 'rgba(99,102,241,0.12)',  border: '#6366f1', btn: '#6366f1' },
        };
        const c = colorMap[type] || colorMap.info;

        const overlay = document.createElement('div');
        overlay.id = 'gep-modal-overlay';
        overlay.style.cssText = `
            position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;
            background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);
            animation:gepFadeIn 0.2s ease;
        `;

        const modal = document.createElement('div');
        modal.style.cssText = `
            background:#ffffff;border:1px solid ${c.border};border-radius:24px;
            padding:40px 48px;max-width:460px;width:90%;text-align:center;
            box-shadow:0 32px 80px rgba(0,0,0,0.15);
            animation:gepSlideUp 0.25s ease;
        `;

        let btnHTML = buttons.map(b => `
            <button data-action="${b.action}" style="
                padding:12px 28px;border:none;border-radius:12px;font-size:15px;font-weight:700;
                cursor:pointer;transition:all 0.2s;
                background:${b.primary ? `linear-gradient(135deg,${c.btn},${c.btn}cc)` : '#f1f5f9'};
                color:${b.primary ? '#fff' : '#475569'};
                border:${b.primary ? 'none' : '1px solid #cbd5e1'};
                margin:0 6px;
            ">${b.label}</button>
        `).join('');

        modal.innerHTML = `
            <div style="font-size:48px;margin-bottom:16px;">${iconMap[type]}</div>
            <h3 style="font-size:20px;font-weight:800;color:#1e293b;margin:0 0 10px;letter-spacing:-0.5px;">${title}</h3>
            <p style="color:#475569;font-size:14px;line-height:1.7;margin:0 0 28px;">${message}</p>
            <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">${btnHTML}</div>
        `;

        const style = document.createElement('style');
        style.textContent = `
            @keyframes gepFadeIn { from { opacity:0 } to { opacity:1 } }
            @keyframes gepSlideUp { from { transform:translateY(20px);opacity:0 } to { transform:translateY(0);opacity:1 } }
            #gep-modal-overlay button:hover { transform:translateY(-2px);filter:brightness(1.1); }
        `;
        overlay.appendChild(style);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Bind button actions
        modal.querySelectorAll('button[data-action]').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action;
                overlay.remove();
                const handler = buttons.find(b => b.action === action);
                if (handler && handler.onClick) handler.onClick();
                if (onClose) onClose(action);
            });
        });

        return overlay;
    }

    // ─── Toast Notification (non-blocking status messages) ─────────────────
    function showToast(message, type = 'info', duration = 3000) {
        const existing = document.getElementById('gep-toast');
        if (existing) existing.remove();

        const colors = { info:'#6366f1', success:'#10b981', warning:'#f59e0b', danger:'#ef4444' };
        const toast = document.createElement('div');
        toast.id = 'gep-toast';
        toast.style.cssText = `
            position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:100000;
            background:#ffffff;border:1px solid ${colors[type]||colors.info};
            color:#1e293b;padding:12px 24px;border-radius:12px;
            font-size:14px;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,0.1);
            animation:gepFadeIn 0.2s ease;white-space:nowrap;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => { if (toast.parentNode) toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; }, duration);
        setTimeout(() => { if (toast.parentNode) toast.remove(); }, duration + 300);
    }

    // ─── Elements ────────────────────────────────────────────────────────────
    const timerDisplay  = document.getElementById('gep-timer');
    const paletteButtons = document.querySelectorAll('.gep-palette-btn');
    const prevBtn       = document.getElementById('gep-prev-btn');
    const nextBtn       = document.getElementById('gep-next-btn');
    const reviewBtn     = document.getElementById('gep-review-btn');
    const submitBtn     = document.getElementById('gep-submit-btn');

    // ─── Language Switching ────────────────────────────────────────────────
    const langBtns = document.querySelectorAll('.gep-lang-btn');
    const ntaLangSelect = document.querySelector('.gep-nta-lang-select');
    
    function switchLanguage(lang) {
        lang = lang.toLowerCase();
        if (lang !== 'en' && lang !== 'hi') {
            lang = 'en';
        }
        sessionStorage.setItem('gep_current_lang', lang);

        langBtns.forEach(b => b.classList.toggle('active', b.dataset.lang === lang));
        
        if (ntaLangSelect) {
            ntaLangSelect.value = lang;
        }

        // Toggle text display languages globally
        document.querySelectorAll('.en-text').forEach(el => el.classList.toggle('active', lang === 'en'));
        document.querySelectorAll('.hi-text').forEach(el => el.classList.toggle('active', lang === 'hi'));

        // Update passage pane if populated
        const passagePane = document.querySelector('#gep-active-passage-pane');
        if (passagePane && passagePane.style.display !== 'none') {
            passagePane.querySelectorAll('.en-text').forEach(el => el.classList.toggle('active', lang === 'en'));
            passagePane.querySelectorAll('.hi-text').forEach(el => el.classList.toggle('active', lang === 'hi'));
        }
    }

    // A test that is single-language throughout: the selector is already absent from
    // the DOM server-side, but harden here too — never honor a stale sessionStorage
    // language, and don't wire up any switch controls that might still exist.
    // A test with a mix of sections is NOT locked here; each section decides for
    // itself in loadQuestion().
    if (examData.lang_locked) {
        sessionStorage.removeItem('gep_current_lang');
        switchLanguage(examData.lang || 'en');
    } else {
        langBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                switchLanguage(this.dataset.lang);
            });
        });

        if (ntaLangSelect) {
            ntaLangSelect.addEventListener('change', function() {
                switchLanguage(this.value);
            });
        }

        let savedLang = sessionStorage.getItem('gep_current_lang') || examData.lang || 'en';
        switchLanguage(savedLang);
    }

    // ─── Exit Exam confirmation dialog ─────────────────────────────────────
    const exitBtn = document.getElementById('gep-exit-btn');
    if (exitBtn) {
        exitBtn.addEventListener('click', function() {
            const destUrl = this.dataset.url;
            showModal({
                title: 'Exit Exam?',
                message: 'Are you sure you want to exit? Your progress will be saved, but your exam will not be submitted.',
                type: 'warning',
                buttons: [
                    { label: 'Cancel', action: 'cancel', primary: false },
                    { label: 'Exit Exam', action: 'exit', primary: true, onClick: () => {
                        window.onbeforeunload = null;
                        window.location.href = destUrl;
                    }}
                ]
            });
        });
    }

    // ─── Timer ──────────────────────────────────────────────────────────────
    function startTimer() {
        updateTimerDisplay();
        timerInterval = setInterval(() => {
            remainingSeconds--;
            updateTimerDisplay();
            if (remainingSeconds === 300) {
                showToast('⏰ 5 minutes remaining!', 'warning', 5000);
            }
            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                if (hasSectionalTiming) {
                    moveToNextSection();
                } else {
                    autoSubmitExam();
                }
            }
        }, 1000);

        // Server Time Sync Heartbeat (syncs clock every 30 seconds)
        setInterval(() => {
            if (remainingSeconds <= 0) return;
            const formData = new FormData();
            formData.append('action', 'gep_exam_heartbeat');
            formData.append('nonce', examData.nonce);
            formData.append('attempt_id', examData.attempt_id);
            fetch(examData.ajaxurl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data && typeof data.data.remaining_seconds !== 'undefined') {
                        remainingSeconds = data.data.remaining_seconds;
                        updateTimerDisplay();
                    }
                }).catch(err => console.error('Heartbeat sync failed:', err));
        }, 30000);
    }

    function moveToNextSection() {
        // Disable everything briefly
        document.getElementById('gep-exam-main-container').style.opacity = '0.5';
        document.getElementById('gep-exam-main-container').style.pointerEvents = 'none';

        showModal({
            title: 'Section Time Expired',
            message: 'Time for the current section has ended. Your answers are saved and you will now proceed to the next section.',
            type: 'time',
            buttons: [{ label: 'Continue', action: 'ok', primary: true, onClick: () => {
                currentSectionIdx++;
                if (currentSectionIdx < sectionalTimings.length) {
                    let sec = sectionalTimings[currentSectionIdx];
                    remainingSeconds = sec.limit_seconds > 0 ? sec.limit_seconds : Math.max(0, examData.remaining_seconds - parseInt(examData.elapsed_seconds || 0));
                    
                    document.getElementById('gep-exam-main-container').style.opacity = '1';
                    document.getElementById('gep-exam-main-container').style.pointerEvents = 'auto';

                    // Switch to the section tab
                    const nextTabBtn = document.querySelector(`.gep-tab-btn[data-cat-id="${sec.id}"]`);
                    if (nextTabBtn) nextTabBtn.click();
                    
                    startTimer();
                } else {
                    submitExam();
                }
            } }]
        });
    }

    function updateTimerDisplay() {
        if (!timerDisplay) return;
        const hrs  = Math.floor(remainingSeconds / 3600);
        const mins = Math.floor((remainingSeconds % 3600) / 60);
        const secs = remainingSeconds % 60;
        timerDisplay.textContent = `${hrs.toString().padStart(2,'0')}:${mins.toString().padStart(2,'0')}:${secs.toString().padStart(2,'0')}`;
        if (remainingSeconds < 300) {
            timerDisplay.parentElement.classList.add('timer-danger');
        } else {
            timerDisplay.parentElement.classList.remove('timer-danger');
        }
    }

    function autoSubmitExam() {
        // Replace alert() with custom modal
        showModal({
            title: 'Time\'s Up!',
            message: 'Your exam time has expired. Your answers are being submitted automatically.',
            type: 'time',
            buttons: [{ label: 'OK', action: 'ok', primary: true, onClick: () => submitExam() }]
        });
    }

    // ─── Answer Saving ───────────────────────────────────────────────────────
    function saveAnswer(questionId, answer, flagged = false) {
        const btn = document.querySelector(`.gep-palette-btn[data-id="${questionId}"]`);
        if (btn) btn.classList.add('saving');

        // Compute time spent on this question in ms
        const timeSpentMs = Date.now() - questionStartTime;

        const formData = new FormData();
        formData.append('action', 'gep_save_answer');
        formData.append('nonce', examData.nonce);
        formData.append('attempt_id', examData.attempt_id);
        formData.append('question_id', questionId);
        formData.append('answer', answer);
        formData.append('flagged', flagged);
        formData.append('time_ms', timeSpentMs); // per-question time tracking

        // Offline Persistence
        localStorage.setItem(`gep_pending_${examData.attempt_id}_${questionId}`, JSON.stringify({ answer, flagged, timestamp: Date.now() }));

        fetch(examData.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (btn) btn.classList.remove('saving');
                if (data.success) {
                    localStorage.removeItem(`gep_pending_${examData.attempt_id}_${questionId}`);
                    updatePaletteStatus(questionId, answer, flagged);
                    if (data.data && typeof data.data.remaining_seconds !== 'undefined') {
                        remainingSeconds = data.data.remaining_seconds;
                        updateTimerDisplay();
                    }
                    if (btn) { btn.classList.add('save-success'); setTimeout(() => btn.classList.remove('save-success'), 1000); }
                }
            }).catch(() => {
                if (btn) btn.classList.remove('saving');
                showToast('Answer saved locally (offline)', 'warning');
            });
    }

    function updatePaletteStatus(questionId, answer, flagged) {
        const btn = document.querySelector(`.gep-palette-btn[data-id="${questionId}"]`);
        if (!btn) return;
        btn.classList.remove('not-visited', 'answered', 'not-answered', 'flagged', 'answered-flagged');
        if (answer && flagged)   btn.classList.add('answered-flagged');
        else if (flagged)        btn.classList.add('flagged');
        else if (answer)         btn.classList.add('answered');
        else                     btn.classList.add('not-answered');
        updateSidebarCounters();
    }

    // Marks come from the DB as strings like "2.00" / "0.50"; show them the way a
    // question paper prints them.
    function trimNum(n) {
        return parseFloat(n.toFixed(2)).toString();
    }

    function loadQuestion(index) {
        const questions = document.querySelectorAll('.gep-question-block');

        // Reset per-question timer
        questionStartTime = Date.now();

        // Transition previous question's status if active
        if (typeof currentQuestionIndex !== 'undefined' && currentQuestionIndex !== index && currentQuestionIndex >= 0 && currentQuestionIndex < questions.length) {
            const prevBlock = questions[currentQuestionIndex];
            const prevId = prevBlock.dataset.id;
            const prevAns = getAnswerFromBlock(prevBlock);
            const palBtn = document.querySelector(`.gep-palette-btn[data-id="${prevId}"]`);
            if (palBtn) {
                const isFlagged = palBtn.classList.contains('flagged') || palBtn.classList.contains('answered-flagged');
                updatePaletteStatus(prevId, prevAns, isFlagged);
            }
        }

        questions.forEach((q, i) => { q.style.display = (i === index) ? 'block' : 'none'; });

        // Land on the top of the new question whichever element is doing the
        // scrolling: the inner pane on the desktop grid, the page on a phone.
        const examMain = document.getElementById('gep-exam-main-container');
        if (examMain) examMain.scrollTop = 0;
        const qDisp = document.getElementById('gep-question-display');
        if (qDisp) qDisp.scrollTop = 0;
        const pageScroller = document.scrollingElement || document.documentElement;
        if (pageScroller) pageScroller.scrollTop = 0;
        currentQuestionIndex = index;
        
        // Update header question number dynamically
        document.querySelectorAll('.gep-header-q-num').forEach(el => {
            el.textContent = (index + 1);
        });

        // Keep the pane header describing the question it sits above: its section
        // and what it is worth. These used to live in rows of their own inside the
        // question body; folding them into the header gives the question itself
        // most of a phone screen back.
        const headerBlock = document.querySelectorAll('.gep-question-block')[index];
        if (headerBlock) {
            const marksEl = document.getElementById('gep-q-pane-marks');
            if (marksEl) {
                const pos = parseFloat(headerBlock.dataset.marks);
                const neg = parseFloat(headerBlock.dataset.neg);
                let html = '';
                if (!isNaN(pos) && pos !== 0) {
                    html += '<span class="pos">+' + trimNum(pos) + '</span>';
                }
                if (!isNaN(neg) && neg > 0) {
                    html += '<span class="neg">-' + trimNum(neg) + '</span>';
                }
                marksEl.innerHTML = html;
            }
            const catName = headerBlock.dataset.catName || '';
            const sectionEl = document.getElementById('gep-q-pane-section');
            if (sectionEl) {
                sectionEl.textContent = catName;
                sectionEl.style.display = catName ? '' : 'none';
            }
            const paletteSectionEl = document.getElementById('gep-palette-section-name');
            if (paletteSectionEl) paletteSectionEl.textContent = catName;

            // Language control follows the section. One test can hold both kinds:
            // a bilingual general paper and a single-language one. Offering a
            // selector on a section with no translation would promise a switch
            // that changes nothing, so on such a section the control is simply
            // absent — the "🔒 Fixed" chip that used to take its place said
            // nothing the student could act on and cost header width on a phone.
            const sectionLocked = headerBlock.dataset.langLock === '1';
            const langPicker = document.getElementById('gep-q-pane-lang-select');
            if (langPicker) langPicker.hidden = sectionLocked;
        }

        paletteButtons.forEach((btn, i) => btn.classList.toggle('active', i === index));
        if (prevBtn) prevBtn.disabled = (index === 0);
        if (nextBtn) nextBtn.textContent = (index === questions.length - 1) ? 'Save & Finish' : 'Save & Next →';
        
        // Immediately mark the newly loaded question as not-answered if it is currently not-visited
        const activeBlock = questions[index];
        if (activeBlock) {
            const questionId = activeBlock.dataset.id;
            const palBtn = document.querySelector(`.gep-palette-btn[data-id="${questionId}"]`);
            if (palBtn && palBtn.classList.contains('not-visited')) {
                palBtn.classList.remove('not-visited');
                palBtn.classList.add('not-answered');
            }

            // Auto-switch section tab if the loaded question belongs to a different section
            const catId = activeBlock.dataset.catId;
            if (typeof activeCatId !== 'undefined' && activeCatId !== catId) {
                switchSection(catId, false); // Switch tab visually but don't force-reload first question
            }

            // Comprehension Passage Logic
            const passagePane = document.getElementById('gep-active-passage-pane');
            const passageBody = document.getElementById('gep-passage-body');
            const passageId = activeBlock.dataset.passageId;
            const splitWrapper = document.getElementById('gep-exam-split-wrapper');
            if (passagePane) {
                if (passageId && passageId !== '0') {
                    // This is a passage question, split the screen
                    const passageContent = document.querySelector(`.gep-passage-content[data-pid="${passageId}"]`);
                    if (passageContent) {
                        if (passageBody) {
                            passageBody.innerHTML = passageContent.innerHTML;
                        } else {
                            passagePane.innerHTML = passageContent.innerHTML;
                        }
                        passagePane.style.display = 'flex';
                        if (splitWrapper) splitWrapper.classList.add('has-passage');
                        
                        // Sync passage language to currently selected language
                        const currentLang = sessionStorage.getItem('gep_current_lang') || examData.lang || 'en';
                        const targetElement = passageBody || passagePane;
                        targetElement.querySelectorAll('.en-text').forEach(el => el.classList.toggle('active', currentLang === 'en'));
                        targetElement.querySelectorAll('.hi-text').forEach(el => el.classList.toggle('active', currentLang === 'hi'));
                    } else {
                        passagePane.style.display = 'none';
                        if (splitWrapper) splitWrapper.classList.remove('has-passage');
                    }
                } else {
                    // Normal question, hide passage pane
                    passagePane.style.display = 'none';
                    if (splitWrapper) splitWrapper.classList.remove('has-passage');
                }
            }
        }

        // Translation Toggle Logic
        const activeLang = sessionStorage.getItem('gep_current_lang') || examData.lang || 'en';
        const langSelector = document.querySelector('.gep-lang-selector');
        if (langSelector) {
            langSelector.querySelectorAll('.gep-lang-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = langSelector.querySelector(`[data-lang="${activeLang}"]`);
            if (activeBtn) activeBtn.classList.add('active');
        }
        if (activeBlock) {
            activeBlock.querySelectorAll('.en-text').forEach(el => el.classList.toggle('active', activeLang === 'en'));
            activeBlock.querySelectorAll('.hi-text').forEach(el => el.classList.toggle('active', activeLang === 'hi'));
        }
        updateSidebarCounters();
    }

    // ─── Navigation ─────────────────────────────────────────────────────────
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentQuestionIndex > 0) loadQuestion(currentQuestionIndex - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const currentQuestion = document.querySelectorAll('.gep-question-block')[currentQuestionIndex];
            const questionId = currentQuestion.dataset.id;
            const answer = getAnswerFromBlock(currentQuestion);

            if (answer !== '') saveAnswer(questionId, answer, false);
            else updatePaletteStatus(questionId, '', false);

            if (currentQuestionIndex < document.querySelectorAll('.gep-question-block').length - 1) {
                loadQuestion(currentQuestionIndex + 1);
            } else {
                triggerSubmitModal();
            }
        });
    }

    if (reviewBtn) {
        reviewBtn.addEventListener('click', () => {
            const currentQuestion = document.querySelectorAll('.gep-question-block')[currentQuestionIndex];
            const questionId = currentQuestion.dataset.id;
            const answer = getAnswerFromBlock(currentQuestion);
            saveAnswer(questionId, answer, true);
            if (currentQuestionIndex < document.querySelectorAll('.gep-question-block').length - 1) {
                loadQuestion(currentQuestionIndex + 1);
            }
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', () => {
            // FIX: Replace confirm() with custom styled modal
            triggerSubmitModal();
        });
    }

    function triggerSubmitModal() {
        // Build summary stats for the modal
        const total = document.querySelectorAll('.gep-question-block').length;
        const answered = document.querySelectorAll('.gep-palette-btn.answered, .gep-palette-btn.answered-flagged').length;
        const unanswered = total - answered;

        showModal({
            title: 'Submit Exam?',
            message: `You have answered <strong style="color:#10b981">${answered}</strong> out of <strong style="color:#f1f5f9">${total}</strong> questions.<br>
                      ${unanswered > 0 ? `<span style="color:#f59e0b">⚠️ ${unanswered} question${unanswered > 1 ? 's' : ''} unanswered.</span><br>` : ''}
                      <br>Once submitted, you cannot change your answers.`,
            type: 'submit',
            buttons: [
                { label: 'Cancel', action: 'cancel', primary: false },
                { label: '✓ Submit Exam', action: 'submit', primary: true, onClick: () => submitExam() }
            ]
        });
    }

    const paletteToggle = document.getElementById('gep-palette-toggle');
    const examSidebar   = document.querySelector('.gep-exam-sidebar');
    const examLayout    = document.querySelector('.gep-exam-layout');
    
    if (paletteToggle && examSidebar) {
        paletteToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 992) {
                // Mobile behavior
                examSidebar.classList.toggle('active');
            } else {
                // Desktop behavior
                examSidebar.classList.toggle('collapsed');
                if (examLayout) {
                    examLayout.classList.toggle('sidebar-collapsed');
                }
            }
        });
        
        document.addEventListener('click', function(e) {
            if (examSidebar.classList.contains('active') && !examSidebar.contains(e.target) && e.target !== paletteToggle) {
                examSidebar.classList.remove('active');
            }
        });
    }

    // ─── Passage: collapse once it has been read ───────────────────────────
    const passageToggle = document.getElementById('gep-passage-toggle');
    if (passageToggle) {
        passageToggle.addEventListener('click', function () {
            const pane = document.getElementById('gep-active-passage-pane');
            if (!pane) return;
            const collapsed = pane.classList.toggle('collapsed');
            this.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    }

    // ─── Closing the palette ───────────────────────────────────────────────
    // On a phone the palette covers the whole screen. Tapping outside it is not a
    // discoverable way out when there is no visible "outside", so give it an
    // explicit close button, and let Escape close it too.
    const paletteClose = document.getElementById('gep-palette-close');
    if (paletteClose && examSidebar) {
        paletteClose.addEventListener('click', function(e) {
            e.stopPropagation();
            examSidebar.classList.remove('active');
            if (window.innerWidth > 992) {
                examSidebar.classList.add('collapsed');
                if (examLayout) examLayout.classList.add('sidebar-collapsed');
            }
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && examSidebar && examSidebar.classList.contains('active')) {
            examSidebar.classList.remove('active');
        }
    });

    // ─── Collapsible Sidebar Edge Toggle ───────────────────────────
    const sidebarCollapseToggle = document.getElementById('gep-sidebar-collapse-toggle');

    
    if (sidebarCollapseToggle && examSidebar) {
        sidebarCollapseToggle.addEventListener('click', function() {
            examSidebar.classList.toggle('collapsed');
            if (examLayout) {
                examLayout.classList.toggle('sidebar-collapsed');
            }
            const icon = this.querySelector('.toggle-icon');
            if (icon) {
                if (examSidebar.classList.contains('collapsed')) {
                    icon.textContent = '❮'; // Points left to indicate expand
                } else {
                    icon.textContent = '❯'; // Points right to indicate collapse
                }
            }
        });
    }

    paletteButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            // FIX: Use data-index (absolute question index) instead of DOM array index.
            // When section filtering hides buttons, the DOM array index is wrong.
            const questionIndex = parseInt(btn.dataset.index, 10);
            if (!isNaN(questionIndex)) {
                loadQuestion(questionIndex);
            }
            if (examSidebar) {
                examSidebar.classList.remove('active');
            }
        });
    });

    // ─── Section Tabs Filtering ────────────────────────────────────
    const sectionTabs = document.querySelectorAll('.gep-section-tab');
    let activeCatId = null;
    let activeTab = null;

    if (sectionTabs.length > 0) {
        activeCatId = sectionTabs[0].dataset.catId;
        activeTab = activeCatId;
        
        sectionTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const catId = this.dataset.catId;
                switchSection(catId, true);
            });
        });
        
        // Trigger initial filtering
        switchSection(activeCatId, false);
    } else {
        // A single-section paper renders no tab strip (one dead chip is not
        // navigation), so seed the active section from the first question instead
        // of leaving it null until the first navigation.
        const firstBlock = document.querySelector('.gep-question-block');
        if (firstBlock) {
            activeCatId = firstBlock.dataset.catId;
            activeTab = activeCatId;
        }
    }

    function switchSection(catId, forceLoadFirst = true) {
        if (hasSectionalTiming && typeof sectionalTimings !== 'undefined') {
            let targetIdx = sectionalTimings.findIndex(s => s.id === catId);
            if (targetIdx !== -1 && targetIdx !== currentSectionIdx) {
                showToast('Navigation between sections is locked when Sectional Timing is active.', 'danger');
                return; // Block switching
            }
        }

        activeCatId = catId;
        activeTab = catId;
        
        // 1. Update tab active state
        sectionTabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.catId === catId);
            if (tab.dataset.catId === catId) {
                const activeLabel = document.getElementById('gep-sidebar-active-section-name');
                if (activeLabel) activeLabel.textContent = tab.textContent.trim();
            }
        });

        // 2. Filter palette section groups (Only show active section group)
        const sectionGroups = document.querySelectorAll('.gep-palette-section-group');
        sectionGroups.forEach(group => {
            if (group.dataset.catId === catId) {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        });

        let firstIndexInCat = -1;
        paletteButtons.forEach(btn => {
            const btnCat = btn.dataset.catId;
            const idx = parseInt(btn.dataset.index);
            if (btnCat === catId) {
                if (firstIndexInCat === -1) {
                    firstIndexInCat = idx;
                }
            }
        });

        // 3. Optional: Load the first question of this section
        if (forceLoadFirst && firstIndexInCat !== -1) {
            loadQuestion(firstIndexInCat);
        }
    }

    // ─── Sidebar Counters Updater ──────────────────────────────────
    function updateSidebarCounters() {
        let answered = 0;
        let flagged = 0;
        let notVisited = 0;
        let ansMarked = 0;
        let notAnswered = 0;

        paletteButtons.forEach(btn => {
            if (btn.classList.contains('answered')) answered++;
            else if (btn.classList.contains('flagged')) flagged++;
            else if (btn.classList.contains('not-visited')) notVisited++;
            else if (btn.classList.contains('answered-flagged')) ansMarked++;
            else if (btn.classList.contains('not-answered')) notAnswered++;
        });

        const elAns = document.getElementById('count-answered');
        const elFlagged = document.getElementById('count-flagged');
        const elNotVisited = document.getElementById('count-not-visited');
        const elAnsMarked = document.getElementById('count-ans-marked');
        const elNotAnswered = document.getElementById('count-not-answered');

        if (elAns) elAns.textContent = answered;
        if (elFlagged) elFlagged.textContent = flagged;
        if (elNotVisited) elNotVisited.textContent = notVisited;
        if (elAnsMarked) elAnsMarked.textContent = ansMarked;
        if (elNotAnswered) elNotAnswered.textContent = notAnswered;
    }

    // ─── Modal Popup Actions (Instructions & Question Paper) ───────
    const btnInst = document.getElementById('gep-btn-instructions');
    const modalInst = document.getElementById('gep-instructions-modal');
    const closeInst = document.getElementById('gep-inst-close');

    if (btnInst && modalInst) {
        btnInst.addEventListener('click', () => { modalInst.style.display = 'flex'; });
        if (closeInst) closeInst.addEventListener('click', () => { modalInst.style.display = 'none'; });
        modalInst.addEventListener('click', (e) => { if (e.target === modalInst) modalInst.style.display = 'none'; });
    }

    const btnQpaper = document.getElementById('gep-btn-question-paper');
    const modalQpaper = document.getElementById('gep-qpaper-modal');
    const closeQpaper = document.getElementById('gep-qpaper-close');

    if (btnQpaper && modalQpaper) {
        btnQpaper.addEventListener('click', () => { modalQpaper.style.display = 'flex'; });
        if (closeQpaper) closeQpaper.addEventListener('click', () => { modalQpaper.style.display = 'none'; });
        modalQpaper.addEventListener('click', (e) => { if (e.target === modalQpaper) modalQpaper.style.display = 'none'; });
    }

    // ─── Submit Exam ────────────────────────────────────────────────────────
    function submitExam() {
        window.onbeforeunload = null;

        // Show loading state
        showModal({
            title: 'Submitting...',
            message: 'Please wait while your exam is being submitted.',
            type: 'info',
            buttons: []
        });

        const formData = new FormData();
        formData.append('action', 'gep_submit_exam');
        formData.append('nonce', examData.nonce);
        formData.append('attempt_id', examData.attempt_id);
        formData.append('lang', sessionStorage.getItem('gep_current_lang') || 'en');

        fetch(examData.ajaxurl, { method: 'POST', body: formData })
            .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
            .then(data => {
                const modal = document.getElementById('gep-modal-overlay');
                if (modal) modal.remove();

                if (data.success) {
                    window.onbeforeunload = null;
                    window.location.href = data.data.redirect_url;
                } else {
                    showModal({
                        title: 'Submission Error',
                        message: data.data ? data.data.message : 'An error occurred. Redirecting to results...',
                        type: 'danger',
                        buttons: [{ label: 'Go to Results', action: 'ok', primary: true, onClick: () => {
                            window.onbeforeunload = null;
                            window.location.href = examData.ajaxurl.replace('wp-admin/admin-ajax.php', 'result/?id=' + examData.attempt_id);
                        }}]
                    });
                }
            }).catch(() => {
                const modal = document.getElementById('gep-modal-overlay');
                if (modal) modal.remove();
                window.onbeforeunload = null;
                window.location.href = examData.ajaxurl.replace('wp-admin/admin-ajax.php', 'result/?id=' + examData.attempt_id);
            });
    }

    // ─── Init ────────────────────────────────────────────────────────────────
    startTimer();

    // Hydrate saved answers (supports both MCQ radio and MSQ comma-separated)
    if (examData.saved_answers && typeof examData.saved_answers === 'object') {
        Object.keys(examData.saved_answers).forEach(qId => {
            const data = examData.saved_answers[qId];
            if (!data || typeof data !== 'object') return;
            const block = document.querySelector(`.gep-question-block[data-id="${qId}"]`);
            if (block && data.answer) {
                const container = block.querySelector('.gep-options-container');
                const qtype = container ? container.dataset.qtype : 'mcq';

                if (qtype === 'msq' || qtype === 'multi_select') {
                    // Restore multiple checked values (comma-separated like "A,C")
                    const selectedLetters = data.answer.toUpperCase().split(',').map(s => s.trim());
                    selectedLetters.forEach(letter => {
                        const cb = block.querySelector(`input[value="${letter}"]`);
                        if (cb) {
                            cb.checked = true;
                            const card = cb.closest('.gep-option-card');
                            if (card) card.classList.add('selected');
                        }
                    });
                } else {
                    // Radio: restore single selection
                    const radio = block.querySelector(`input[value="${data.answer}"]`);
                    if (radio) {
                        radio.checked = true;
                        const card = radio.closest('.gep-option-card');
                        if (card) card.classList.add('selected');
                    }
                    const textInput = block.querySelector('.gep-text-ans');
                    if (textInput) textInput.value = data.answer;
                }
            }
            updatePaletteStatus(qId, data.answer || '', data.flagged || false);
        });
    }

    loadQuestion(0);
    updateSidebarCounters();

    // ─── Unified Answer Extractor ────────────────────────────────────────────
    // Works for MCQ (radio), MSQ (checkbox), and short_answer (text)
    function getAnswerFromBlock(block) {
        const container = block.querySelector('.gep-options-container');
        const qtype = container ? container.dataset.qtype : 'mcq';

        if (qtype === 'short_answer') {
            const txt = block.querySelector('.gep-text-ans');
            return txt ? txt.value.trim() : '';
        }
        if (qtype === 'numerical') {
            const num = block.querySelector('.gep-numerical-ans');
            return num ? num.value.trim() : '';
        }
        if (qtype === 'msq' || qtype === 'multi_select') {
            // Collect all checked checkboxes, sort letters, join with comma
            const checked = [...block.querySelectorAll('input[type="checkbox"]:checked')];
            const letters = checked.map(cb => cb.value).sort();
            return letters.join(',');
        }
        // Default: radio (mcq)
        const radio = block.querySelector('input[type="radio"]:checked');
        return radio ? radio.value : '';
    }

    // ─── Option Selection ────────────────────────────────────────────────────
    document.querySelectorAll('.gep-option-card input').forEach(input => {
        input.addEventListener('click', function(e) {
            const card = this.closest('.gep-option-card');
            const block = this.closest('.gep-question-block');
            const container = block.querySelector('.gep-options-container');
            const qtype = container ? container.dataset.qtype : 'mcq';
            const questionId = block.dataset.id;

            if (qtype === 'msq' || qtype === 'multi_select') {
                // MSQ checkbox: matching card visual class to checked state
                card.classList.toggle('selected', this.checked);
            } else {
                // MCQ radio: checking previous selection state for deselect
                const wasSelected = card.classList.contains('selected');
                
                block.querySelectorAll('.gep-option-card').forEach(c => {
                    c.classList.remove('selected');
                    const cInput = c.querySelector('input');
                    if (cInput && cInput !== this) cInput.checked = false;
                });

                if (wasSelected) {
                    card.classList.remove('selected');
                    this.checked = false;
                } else {
                    card.classList.add('selected');
                    this.checked = true;
                }
            }

            const answer = getAnswerFromBlock(block);
            const palBtn = document.querySelector(`.gep-palette-btn[data-id="${questionId}"]`);
            const isFlagged = palBtn && (palBtn.classList.contains('flagged') || palBtn.classList.contains('answered-flagged'));
            saveAnswer(questionId, answer, isFlagged);
        });
    });

    document.querySelectorAll('.gep-text-ans').forEach(input => {
        input.addEventListener('change', function() {
            const block = this.closest('.gep-question-block');
            const questionId = block.dataset.id;
            const palBtn = document.querySelector(`.gep-palette-btn[data-id="${questionId}"]`);
            const isFlagged = palBtn && (palBtn.classList.contains('flagged') || palBtn.classList.contains('answered-flagged'));
            saveAnswer(questionId, this.value, isFlagged);
        });
    });

    // ─── Numerical Input Auto-Save ───────────────────────────────────────────
    document.querySelectorAll('.gep-numerical-ans').forEach(input => {
        input.addEventListener('change', function() {
            const block = this.closest('.gep-question-block');
            const questionId = block.dataset.id;
            const palBtn = document.querySelector(`.gep-palette-btn[data-id="${questionId}"]`);
            const isFlagged = palBtn && (palBtn.classList.contains('flagged') || palBtn.classList.contains('answered-flagged'));
            saveAnswer(questionId, this.value.trim(), isFlagged);
            if (this.value.trim()) showToast('✅ Numerical answer saved', 'success', 2000);
        });
        input.addEventListener('blur', function() {
            if (this.value.trim()) this.dispatchEvent(new Event('change'));
        });
    });

    // ─── Clear Response ──────────────────────────────────────────────────────
    const clearBtn = document.getElementById('gep-clear-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            const block = document.querySelectorAll('.gep-question-block')[currentQuestionIndex];
            if (!block) return;

            const questionId = block.dataset.id;
            const container = block.querySelector('.gep-options-container');
            const qtype = container ? container.dataset.qtype : 'mcq';

            if (qtype === 'short_answer') {
                const textInput = block.querySelector('.gep-text-ans');
                if (textInput) textInput.value = '';
            } else if (qtype === 'numerical') {
                const numInput = block.querySelector('.gep-numerical-ans');
                if (numInput) numInput.value = '';
            } else {
                block.querySelectorAll('.gep-option-card').forEach(card => {
                    card.classList.remove('selected');
                    const input = card.querySelector('input');
                    if (input) input.checked = false;
                });
            }

            const palBtn = document.querySelector(`.gep-palette-btn[data-id="${questionId}"]`);
            const isFlagged = palBtn && (palBtn.classList.contains('flagged') || palBtn.classList.contains('answered-flagged'));
            updatePaletteStatus(questionId, '', isFlagged);
            saveAnswer(questionId, '', isFlagged);
            showToast('Response cleared', 'info');
        });
    }

    // ─── NTA-Style Calculator ────────────────────────────────────────────────
    const calcModal = document.getElementById('gep-calculator-modal');
    const calcDisplay = document.getElementById('gep-calc-display');
    const calcClose = document.getElementById('gep-calc-close');
    let calcExpression = '';

    function evaluateSimpleExpression(str) {
        str = str.replace(/[^0-9+\-*/%.]/g, '');
        const tokens = [];
        let numberBuffer = "";
        for (let i = 0; i < str.length; i++) {
            const char = str[i];
            if (/[0-9.]/.test(char)) {
                numberBuffer += char;
            } else {
                if (numberBuffer) {
                    tokens.push(parseFloat(numberBuffer));
                    numberBuffer = "";
                }
                if (char === '-' && (tokens.length === 0 || ['+', '-', '*', '/', '%'].includes(tokens[tokens.length - 1]))) {
                    numberBuffer = "-";
                } else {
                    tokens.push(char);
                }
            }
        }
        if (numberBuffer) {
            tokens.push(parseFloat(numberBuffer));
        }
        const intermediateTokens = [];
        for (let i = 0; i < tokens.length; i++) {
            const token = tokens[i];
            if (token === '*' || token === '/' || token === '%') {
                const nextToken = tokens[i + 1];
                const prevVal = intermediateTokens.pop();
                if (typeof prevVal === 'number' && typeof nextToken === 'number') {
                    let res;
                    if (token === '*') res = prevVal * nextToken;
                    else if (token === '/') res = prevVal / nextToken;
                    else res = prevVal % nextToken;
                    intermediateTokens.push(res);
                    i++;
                } else {
                    throw new Error("Invalid expression");
                }
            } else {
                intermediateTokens.push(token);
            }
        }
        if (intermediateTokens.length === 0) return 0;
        let finalResult = intermediateTokens[0];
        if (typeof finalResult !== 'number') {
            throw new Error("Invalid expression");
        }
        for (let i = 1; i < intermediateTokens.length; i += 2) {
            const op = intermediateTokens[i];
            const val = intermediateTokens[i + 1];
            if (typeof val !== 'number') {
                throw new Error("Invalid expression");
            }
            if (op === '+') {
                finalResult += val;
            } else if (op === '-') {
                finalResult -= val;
            } else {
                throw new Error("Invalid expression");
            }
        }
        return finalResult;
    }

    function openCalc() {
        if (calcModal) {
            calcModal.style.display = 'flex';
            if (calcDisplay) calcDisplay.value = calcExpression || '0';
        }
    }
    function closeCalc() { if (calcModal) calcModal.style.display = 'none'; }

    document.querySelectorAll('.gep-calc-btn').forEach(btn => btn.addEventListener('click', openCalc));
    if (calcClose) calcClose.addEventListener('click', closeCalc);
    if (calcModal) calcModal.addEventListener('click', e => { if (e.target === calcModal) closeCalc(); });

    if (calcModal) {
        calcModal.querySelectorAll('.gep-calc-key').forEach(key => {
            key.addEventListener('click', function() {
                const k = this.dataset.key;
                if (k === 'C') {
                    calcExpression = '';
                    if (calcDisplay) calcDisplay.value = '0';
                } else if (k === '⌫') {
                    calcExpression = calcExpression.slice(0, -1);
                    if (calcDisplay) calcDisplay.value = calcExpression || '0';
                } else if (k === '=') {
                    try {
                        const expr = calcExpression.replace(/×/g,'*').replace(/÷/g,'/');
                        const result = evaluateSimpleExpression(expr);
                        calcExpression = String(parseFloat(result.toFixed(8)));
                        if (calcDisplay) calcDisplay.value = calcExpression;
                        const block = document.querySelectorAll('.gep-question-block')[currentQuestionIndex];
                        if (block) {
                            const numInput = block.querySelector('.gep-numerical-ans');
                            if (numInput) { numInput.value = calcExpression; numInput.dispatchEvent(new Event('change')); }
                        }
                    } catch(e) {
                        if (calcDisplay) calcDisplay.value = 'Error';
                        calcExpression = '';
                    }
                } else if (k === '±') {
                    calcExpression = calcExpression.startsWith('-') ? calcExpression.slice(1) : '-' + calcExpression;
                    if (calcDisplay) calcDisplay.value = calcExpression;
                } else if (k === '%') {
                    try { calcExpression = String(parseFloat(calcExpression) / 100); if (calcDisplay) calcDisplay.value = calcExpression; } catch(e) {}
                } else {
                    calcExpression += ({'×':'*','÷':'/'}[k] || k);
                    if (calcDisplay) calcDisplay.value = calcExpression.replace(/\*/g,'×').replace(/\//g,'÷');
                }
            });
        });
    }

    // ─── Text Zoom ───────────────────────────────────────────────────────────
    // A chosen text size should survive a reload — a proctoring lock or a dropped
    // connection mid-paper should not hand the student back a size they rejected.
    const ZOOM_MIN = 13, ZOOM_MAX = 27, ZOOM_KEY = 'gep_zoom_font_size';
    try {
        const savedZoom = parseInt(sessionStorage.getItem(ZOOM_KEY), 10);
        if (!isNaN(savedZoom) && savedZoom >= ZOOM_MIN && savedZoom <= ZOOM_MAX) {
            currentFontSize = savedZoom;
        }
    } catch (err) { /* storage blocked — the default size is fine */ }

    document.documentElement.style.setProperty('--gep-zoom-font-size', currentFontSize + 'px');

    const zoomBtns = document.querySelectorAll('.gep-zoom-btn');
    function applyZoom(clicked) {
        document.documentElement.style.setProperty('--gep-zoom-font-size', currentFontSize + 'px');
        try { sessionStorage.setItem(ZOOM_KEY, String(currentFontSize)); } catch (err) {}

        zoomBtns.forEach(b => {
            // A control that can no longer move is disabled rather than silently inert,
            // so "nothing happened" always has a visible reason.
            const atLimit = (b.dataset.zoom === 'in' && currentFontSize >= ZOOM_MAX) ||
                            (b.dataset.zoom === 'out' && currentFontSize <= ZOOM_MIN);
            b.disabled = atLimit;
            b.style.opacity = atLimit ? '0.4' : '';
            if (b !== clicked) {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            }
        });
        if (clicked) {
            clicked.classList.add('active');
            clicked.setAttribute('aria-pressed', 'true');
        }
    }

    // On a phone the controls sit in a 48px header and one 2px step is easy to
    // miss, so a tap that did change the size says so. Without this the honest
    // report is "A- / A+ don't work".
    let zoomToastTimer;
    function showZoomToast() {
        let toast = document.getElementById('gep-zoom-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'gep-zoom-toast';
            toast.className = 'gep-zoom-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }
        const steps = Math.round((ZOOM_MAX - ZOOM_MIN) / 2) + 1;
        const step  = Math.round((currentFontSize - ZOOM_MIN) / 2) + 1;
        toast.textContent = 'Text size ' + step + ' / ' + steps;
        toast.classList.add('visible');
        clearTimeout(zoomToastTimer);
        zoomToastTimer = setTimeout(function() { toast.classList.remove('visible'); }, 1100);
    }

    zoomBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.dataset.zoom;
            const before = currentFontSize;
            if (action === 'in'  && currentFontSize < ZOOM_MAX) currentFontSize += 2;
            if (action === 'out' && currentFontSize > ZOOM_MIN) currentFontSize -= 2;
            applyZoom(this);
            if (currentFontSize !== before) showZoomToast();
        });
    });
    applyZoom(null);

    // ─── Scrolling Logic for Question Options and Floating Up/Down Buttons ───
    // Which element actually scrolls depends on the layout: the desktop grid
    // scrolls #gep-question-display internally, while the phone layout drops the
    // grid and scrolls the page. Pointing these controls at the pane
    // unconditionally made every one of them a no-op on a phone.
    const scrollDisplayPane = document.getElementById('gep-question-display');
    const scrollButtonsWrapper = document.getElementById('gep-floating-scroll-btns');
    let scrollButtonTimeout;

    function questionScroller() {
        if (scrollDisplayPane && scrollDisplayPane.scrollHeight > scrollDisplayPane.clientHeight + 1) {
            return scrollDisplayPane;
        }
        return document.scrollingElement || document.documentElement;
    }

    function scrollQuestionTo(top) {
        const scroller = questionScroller();
        scroller.scrollTo({ top: top, behavior: 'smooth' });
    }

    if (scrollButtonsWrapper) {
        const revealScrollButtons = function() {
            scrollButtonsWrapper.classList.add('visible');
            clearTimeout(scrollButtonTimeout);
            scrollButtonTimeout = setTimeout(function() {
                scrollButtonsWrapper.classList.remove('visible');
            }, 1500);
        };
        if (scrollDisplayPane) {
            scrollDisplayPane.addEventListener('scroll', revealScrollButtons, { passive: true });
        }
        window.addEventListener('scroll', revealScrollButtons, { passive: true });

        const floatScrollUpBtn = document.getElementById('gep-float-scroll-up');
        if (floatScrollUpBtn) {
            floatScrollUpBtn.addEventListener('click', function() {
                scrollQuestionTo(0);
            });
        }

        const floatScrollDownBtn = document.getElementById('gep-float-scroll-down');
        if (floatScrollDownBtn) {
            floatScrollDownBtn.addEventListener('click', function() {
                scrollQuestionTo(questionScroller().scrollHeight);
            });
        }
    }

    const scrollToOptionsButton = document.getElementById('gep-scroll-to-options-btn');
    if (scrollToOptionsButton) {
        scrollToOptionsButton.addEventListener('click', function() {
            const activeQuestionBlock = document.querySelector('.gep-question-block[style*="display: block"]');
            if (!activeQuestionBlock) return;
            const optionsList = activeQuestionBlock.querySelector('.gep-options-container');
            if (!optionsList) return;

            const scroller = questionScroller();
            if (scroller === scrollDisplayPane) {
                scroller.scrollTo({ top: optionsList.offsetTop - 20, behavior: 'smooth' });
            } else {
                // Page scroll: offsetTop is relative to the card, not the document,
                // so measure against the viewport instead.
                const headerH = parseInt(
                    getComputedStyle(document.documentElement).getPropertyValue('--gep-header-h'), 10
                ) || 48;
                const top = optionsList.getBoundingClientRect().top + scroller.scrollTop - headerH - 12;
                scroller.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    }

    // ─── MOBILE FIXES ────────────────────────────────────────────────────────

    // 1. NOTE: MutationObserver removed — it caused infinite scroll loop.
    //    Scroll-to-top on question nav is now done directly inside loadQuestion().

    // 2. iOS Safari viewport height fix
    //    CSS 100vh on iOS includes the address bar. This JS sets the real height.
    function setMobileViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }
    setMobileViewportHeight();
    window.addEventListener('resize', setMobileViewportHeight);
    window.addEventListener('orientationchange', function() {
        setTimeout(setMobileViewportHeight, 300);
    });

    // 3. Double-tap zoom on option cards is suppressed by `touch-action:
    //    manipulation` in the stylesheet. The synthetic click that used to be
    //    fired from touchend here also fired when the finger had been dragged
    //    across the card to scroll, so scrolling from an option selected it.

    // 4. Wake Lock — keep screen on during exam (Chrome/Android)
    if ('wakeLock' in navigator) {
        let wakeLock = null;
        async function requestWakeLock() {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
            } catch(e) { /* silently ignore — not supported on all browsers */ }
        }
        requestWakeLock();
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                requestWakeLock();
            }
        });
    }

    // 5. Pull-to-refresh is blocked by `overscroll-behavior-y: contain` in the
    //    stylesheet, which the browser applies without touching normal scrolling.
    //    The JS guard that used to live here cancelled *every* touchmove whose
    //    target sat outside #gep-question-display while
    //    document.documentElement.scrollTop was 0 — and on a phone the exam
    //    scrolls the body, so that scrollTop is always 0 and clientY > 0 is true
    //    of any touch anywhere. The result was that the page, the passage pane
    //    and the palette could not be scrolled at all. Nothing replaces it.

});

