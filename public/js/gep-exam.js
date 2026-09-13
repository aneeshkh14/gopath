document.addEventListener('DOMContentLoaded', function() {
    if (typeof GEP_Exam === 'undefined') return;

    const examData = GEP_Exam;
    const questions = document.querySelectorAll('.gep-question-block');
    let currentQuestionIndex = 0;
    let timerInterval;
    let submitting = false;
    let submissionRequested = false;
    let navigationReady = false;
    let draftSaveTimer;
    const pendingAnswers = new Map();
    let savePromise = null;
    const storagePrefix = `gep_pending_${examData.attempt_id}_`;
    function storePending(id, data) {
        try {
            if (data) localStorage.setItem(storagePrefix + id, JSON.stringify(data));
            else localStorage.removeItem(storagePrefix + id);
        } catch (e) { /* Saving to the server must work when device storage is unavailable. */ }
    }
    async function postExam(formData) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch(examData.ajaxurl, {method: 'POST', body: formData, signal: controller.signal});
            if (!response.ok) throw new Error('Connection failed.');
            return await response.json();
        } finally { clearTimeout(timeout); }
    }
    function showSaveStatus(text, failed) {
        let status = document.getElementById('gep-save-status');
        if (!status) {
            status = document.createElement('div');
            status.id = 'gep-save-status'; status.setAttribute('role', 'status');
            document.body.appendChild(status);
        }
        status.textContent = text;
        status.classList.toggle('is-pending', !!failed);
    }
    window.onbeforeunload = function(e) {
        if (pendingAnswers.size || submitting || submissionRequested) { e.preventDefault(); e.returnValue = ''; return ''; }
    };
    let remainingSeconds = examData.remaining_seconds;
    let questionStartTime = Date.now(); // NTA-style per-Q time tracking
    let currentFontSize = 16; // Text zoom support — must match --gep-zoom-font-size in gep-exam.css
    
    // Anchor the clock to elapsed wall time so background tabs and device sleep
    // cannot pause an exam. Heartbeats update this anchor, not a sectional timer.
    const totalDuration = Math.max(0, Number(examData.remaining_seconds) || 0) + Math.max(0, Number(examData.elapsed_seconds) || 0);
    let elapsedAtSync = Math.max(0, Number(examData.elapsed_seconds) || 0);
    let clockSyncedAt = Date.now();
    let timerExpired = false, heartbeatBusy = false;
    const sectionalTimings = (examData.sections_data || []).map((sec, idx) => ({
        id: 'sec_' + idx, limit_seconds: Math.max(0, Number(sec.time_limit) || 0) * 60,
        name: sec.name || 'Section ' + (idx + 1)
    }));
    const hasSectionalTiming = sectionalTimings.some(sec => sec.limit_seconds > 0);
    function clockState() {
        const elapsed = elapsedAtSync + Math.max(0, (Date.now() - clockSyncedAt) / 1000);
        const totalLeft = Math.max(0, totalDuration - elapsed);
        if (!hasSectionalTiming) return {index: 0, remaining: Math.ceil(totalLeft), expired: totalLeft <= 0};
        let sectionStart = 0;
        for (let index = 0; index < sectionalTimings.length; index++) {
            const limit = sectionalTimings[index].limit_seconds;
            // An untimed section consumes the remaining total exam time.
            if (!limit) return {index, remaining: Math.ceil(totalLeft), expired: totalLeft <= 0};
            const sectionLeft = sectionStart + limit - elapsed;
            if (sectionLeft > 0) return {index, remaining: Math.ceil(Math.min(totalLeft, sectionLeft)), expired: totalLeft <= 0};
            sectionStart += limit;
        }
        return {index: Math.max(0, sectionalTimings.length - 1), remaining: 0, expired: true};
    }
    let currentSectionIdx = clockState().index;
    remainingSeconds = clockState().remaining;

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

        const opener = document.activeElement;
        const overlay = document.createElement('div');
        overlay.id = 'gep-modal-overlay';
        overlay.style.cssText = `
            position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;
            background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);
            animation:gepFadeIn 0.2s ease;
        `;

        const modal = document.createElement('div');
        modal.setAttribute('role', 'dialog'); modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'gep-dialog-title'); modal.tabIndex = -1;
        modal.style.cssText = `
            background:#ffffff;border:1px solid ${c.border};border-radius:24px;
            padding:clamp(20px,5vw,40px);max-width:460px;width:90%;text-align:center;box-sizing:border-box;max-height:90dvh;overflow:auto;
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
            <h3 id="gep-dialog-title" style="font-size:20px;font-weight:800;color:#1e293b;margin:0 0 10px;letter-spacing:-0.5px;">${title}</h3>
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
                if (opener && opener.isConnected) opener.focus();
                const handler = buttons.find(b => b.action === action);
                if (handler && handler.onClick) handler.onClick();
                if (onClose) onClose(action);
            });
        });

        const focusable = Array.from(modal.querySelectorAll('button'));
        (focusable[0] || modal).focus();
        overlay.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const cancel = modal.querySelector('[data-action="cancel"]');
                if (cancel) { e.preventDefault(); cancel.click(); }
            }
            if (e.key === 'Tab') {
                if (!focusable.length) { e.preventDefault(); return; }
                const first = focusable[0], last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
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
        toast.setAttribute('role', 'status');
        toast.style.cssText = `
            position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:100000;
            background:#ffffff;border:1px solid ${colors[type]||colors.info};
            color:#1e293b;padding:12px 24px;border-radius:12px;
            font-size:14px;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,0.1);
            animation:gepFadeIn 0.2s ease;max-width:calc(100vw - 32px);box-sizing:border-box;text-align:center;overflow-wrap:anywhere;
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
    let currentLang = examData.lang || 'en';
    const langBtns = document.querySelectorAll('.gep-lang-btn');
    const ntaLangSelect = document.querySelector('.gep-nta-lang-select');
    
    function switchLanguage(lang) {
        lang = lang.toLowerCase();
        if (lang !== 'en' && lang !== 'hi') {
            lang = 'en';
        }
        currentLang = lang;
        try { sessionStorage.setItem('gep_current_lang', lang); } catch (e) {}

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
        try { sessionStorage.removeItem('gep_current_lang'); } catch (e) {}
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

        let savedLang = examData.lang || 'en';
        try { savedLang = sessionStorage.getItem('gep_current_lang') || savedLang; } catch (e) {}
        switchLanguage(savedLang);
    }

    // ─── Exit Exam confirmation dialog ─────────────────────────────────────
    const exitBtn = document.getElementById('gep-exit-btn');
    if (exitBtn) {
        exitBtn.addEventListener('click', function() {
            if (submitting || submissionRequested || timerExpired) return;
            const destUrl = this.dataset.url;
            showModal({
                title: 'Exit Exam?',
                message: 'Leave this exam? The timer keeps running while you are away. We will confirm that your answers are saved before leaving.',
                type: 'warning',
                buttons: [
                    { label: 'Cancel', action: 'cancel', primary: false },
                    { label: 'Exit Exam', action: 'exit', primary: true, onClick: async () => {
                        await captureCurrentAnswer();
                        if (pendingAnswers.size) { showToast('Some answers are not synced. Reconnect and try again.', 'warning', 6000); return; }
                        window.onbeforeunload = null;
                        window.location.href = destUrl;
                    }}
                ]
            });
        });
        document.addEventListener('gep:request-exam-exit', () => exitBtn.click());
    }

    // ─── Timer ──────────────────────────────────────────────────────────────
    function captureCurrentAnswer() {
        const block = questions[currentQuestionIndex];
        if (!block) return flushAnswers();
        const pal = document.querySelector(`.gep-palette-btn[data-id="${block.dataset.id}"]`);
        return saveAnswer(block.dataset.id, getAnswerFromBlock(block), !!pal && (pal.classList.contains('flagged') || pal.classList.contains('answered-flagged')));
    }
    function updateClock() {
        if (submitting || submissionRequested || timerExpired) return;
        const state = clockState();
        const previousRemaining = remainingSeconds;
        remainingSeconds = state.remaining;
        updateTimerDisplay();
        if (state.expired) {
            timerExpired = true;
            clearInterval(timerInterval);
            submitExam();
            return;
        }
        if (hasSectionalTiming && state.index > currentSectionIdx) {
            currentSectionIdx = state.index;
            switchSection(sectionalTimings[currentSectionIdx].id);
            showToast('Section time ended. Continuing to ' + sectionalTimings[currentSectionIdx].name + '.', 'info', 5000);
        } else if (previousRemaining > 300 && remainingSeconds <= 300) {
            showToast('5 minutes remaining!', 'warning', 5000);
        }
    }
    function startTimer() {
        updateClock();
        if (!timerExpired) timerInterval = setInterval(updateClock, 1000);
        // One heartbeat for the entire attempt, including all section transitions.
        setInterval(async () => {
            if (heartbeatBusy || timerExpired || submitting || submissionRequested) return;
            heartbeatBusy = true;
            const formData = new FormData();
            formData.append('action', 'gep_exam_heartbeat');
            formData.append('nonce', examData.nonce);
            formData.append('attempt_id', examData.attempt_id);
            try {
                const data = await postExam(formData);
                if (data && data.success && data.data && Number.isFinite(Number(data.data.remaining_seconds))) {
                    // A delayed response must never rewind elapsed time or reopen a section.
                    elapsedAtSync = Math.max(
                        elapsedAtSync + Math.max(0, (Date.now() - clockSyncedAt) / 1000),
                        totalDuration - Math.max(0, Math.min(totalDuration, Number(data.data.remaining_seconds)))
                    );
                    clockSyncedAt = Date.now();
                    updateClock();
                }
            } catch (e) { /* Wall clock continues while the network is unavailable. */ }
            finally { heartbeatBusy = false; }
        }, 30000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) updateClock(); });
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

    // ─── Answer Saving ───────────────────────────────────────────────────────
    // Serialize saves across questions: the server updates one attempt answer map.
    function saveAnswer(questionId, answer, flagged = false) {
        if (submissionRequested) return Promise.resolve(false);
        queueAnswer(questionId, answer, flagged);
        return flushAnswers();
    }
    function queueAnswer(questionId, answer, flagged) {
        const data = {answer, flagged, timestamp: Date.now(), time_ms: Date.now() - questionStartTime};
        pendingAnswers.set(String(questionId), data);
        storePending(questionId, data);
        updatePaletteStatus(questionId, answer, flagged);
    }
    function flushAnswers() {
        if (savePromise) return savePromise;
        savePromise = (async function() {
            while (pendingAnswers.size) {
                const [id, data] = pendingAnswers.entries().next().value;
                const btn = document.querySelector(`.gep-palette-btn[data-id="${id}"]`);
                if (btn) btn.classList.add('saving');
                showSaveStatus('Saving answers…', false);
                const formData = new FormData();
                Object.entries({action: 'gep_save_answer', nonce: examData.nonce, attempt_id: examData.attempt_id, question_id: id, answer: data.answer, flagged: data.flagged, time_ms: data.time_ms || 0})
                    .forEach(([key, value]) => formData.append(key, value));
                try {
                    const response = await postExam(formData);
                    if (!response || !response.success) throw new Error('Save not confirmed.');
                    if (pendingAnswers.get(id) === data) { pendingAnswers.delete(id); storePending(id, null); }
                } catch (e) {
                    showSaveStatus('Answers not synced. Keep this page open; reconnect to retry.', true);
                    return false;
                } finally { if (btn) btn.classList.remove('saving'); }
            }
            showSaveStatus('All answers saved', false);
            return true;
        })().finally(() => { savePromise = null; });
        return savePromise;
    }
    window.addEventListener('online', () => { flushAnswers(); });

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

    function loadQuestion(index, capturePrevious = true) {
        const questions = document.querySelectorAll('.gep-question-block');
        if (!questions[index] || submitting || submissionRequested || timerExpired) return;
        if (hasSectionalTiming && questions[index].dataset.catId !== sectionalTimings[currentSectionIdx].id) return;
        if (navigationReady && capturePrevious && currentQuestionIndex !== index) captureCurrentAnswer();

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
        const examScroller = document.querySelector('.gep-exam-fullscreen-container');
        if (examScroller) examScroller.scrollTop = 0;
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
        if (prevBtn) prevBtn.disabled = (index === 0 || (hasSectionalTiming && questions[index - 1].dataset.catId !== sectionalTimings[currentSectionIdx].id));
        if (nextBtn) nextBtn.textContent = (index === questions.length - 1) ? 'Save & Finish' : (hasSectionalTiming && questions[index + 1].dataset.catId !== sectionalTimings[currentSectionIdx].id) ? 'Save Answer' : 'Save & Next →';
        
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
        const activeLang = currentLang;
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
            if (submitting || submissionRequested || timerExpired) return;
            const currentQuestion = document.querySelectorAll('.gep-question-block')[currentQuestionIndex];
            const questionId = currentQuestion.dataset.id;
            const answer = getAnswerFromBlock(currentQuestion);

            saveAnswer(questionId, answer, false);

            if (currentQuestionIndex < document.querySelectorAll('.gep-question-block').length - 1) {
                loadQuestion(currentQuestionIndex + 1, false);
            } else {
                triggerSubmitModal();
            }
        });
    }

    if (reviewBtn) {
        reviewBtn.addEventListener('click', () => {
            if (submitting || submissionRequested || timerExpired) return;
            const currentQuestion = document.querySelectorAll('.gep-question-block')[currentQuestionIndex];
            const questionId = currentQuestion.dataset.id;
            const answer = getAnswerFromBlock(currentQuestion);
            saveAnswer(questionId, answer, true);
            if (currentQuestionIndex < document.querySelectorAll('.gep-question-block').length - 1) {
                loadQuestion(currentQuestionIndex + 1, false);
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
        setPaletteOpen(false);
        if (submitting || submissionRequested || timerExpired) return;
        // Include the current unblurred value in the confirmation summary.
        const block = questions[currentQuestionIndex];
        if (block) {
            const pal = document.querySelector(`.gep-palette-btn[data-id="${block.dataset.id}"]`);
            updatePaletteStatus(block.dataset.id, getAnswerFromBlock(block), !!pal && (pal.classList.contains('flagged') || pal.classList.contains('answered-flagged')));
        }
        // Build summary stats for the modal
        const total = document.querySelectorAll('.gep-question-block').length;
        const answered = document.querySelectorAll('.gep-palette-btn.answered, .gep-palette-btn.answered-flagged').length;
        const unanswered = total - answered;

        showModal({
            title: 'Submit Exam?',
            message: `You have answered <strong style="color:#10b981">${answered}</strong> out of <strong style="color:inherit">${total}</strong> questions.<br>
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
    
    // The Grid button opens the same full-screen overview on every device.
    // The desktop edge handle remains a separate compact-sidebar control.
    const paletteClose = document.getElementById('gep-palette-close');
    const paletteBackground = Array.from(document.querySelectorAll('.gep-exam-header, .gep-exam-main, .gep-exam-footer'));
    let paletteBackgroundState = [];
    function setPaletteOpen(open, restoreFocus = true) {
        if (!examSidebar) return;
        const wasOpen = examSidebar.classList.contains('active');
        if (open === wasOpen) return;
        examSidebar.classList.toggle('active', open);
        examSidebar.setAttribute('role', open ? 'dialog' : 'complementary');
        if (open) {
            examSidebar.setAttribute('aria-modal', 'true');
            paletteBackgroundState = paletteBackground.map(el => [el, el.inert]);
            paletteBackground.forEach(el => { el.inert = true; });
        } else {
            examSidebar.removeAttribute('aria-modal');
            paletteBackgroundState.forEach(([el, value]) => { el.inert = value; });
            paletteBackgroundState = [];
        }
        if (paletteToggle) paletteToggle.setAttribute('aria-expanded', String(open));
        syncPaletteVisibility();
        if (open && paletteClose) paletteClose.focus();
        else if (restoreFocus && paletteToggle) paletteToggle.focus();
    }
    function syncPaletteVisibility() {
        if (!examSidebar) return;
        const hidden = !examSidebar.classList.contains('active') && window.innerWidth <= 992;
        examSidebar.inert = hidden;
        examSidebar.setAttribute('aria-hidden', String(hidden));
    }
    if (paletteToggle && examSidebar) {
        paletteToggle.addEventListener('click', () => setPaletteOpen(!examSidebar.classList.contains('active')));
        if (paletteClose) paletteClose.addEventListener('click', () => setPaletteOpen(false));
        examSidebar.addEventListener('keydown', e => {
            if (!examSidebar.classList.contains('active')) return;
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); setPaletteOpen(false); }
            if (e.key !== 'Tab') return;
            const items = Array.from(examSidebar.querySelectorAll('button:not(:disabled), select, a[href], [tabindex="0"]')).filter(el => {
                for (let node = el; node && node !== examSidebar; node = node.parentElement) {
                    if (node.hidden || getComputedStyle(node).display === 'none') return false;
                }
                return true;
            });
            const first = items[0], last = items[items.length - 1];
            if (!first) { e.preventDefault(); return; }
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
        // Each action closes the palette in its own click handler. Hiding or
        // making the clicked sidebar inert during capture can interrupt a tap.
        window.addEventListener('resize', syncPaletteVisibility);
        syncPaletteVisibility();
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

    // ─── Collapsible Sidebar Edge Toggle ───────────────────────────
    const sidebarCollapseToggle = document.getElementById('gep-sidebar-collapse-toggle');

    
    if (sidebarCollapseToggle && examSidebar) {
        sidebarCollapseToggle.addEventListener('click', function() {
            examSidebar.classList.toggle('collapsed');
            if (examLayout) {
                examLayout.classList.toggle('sidebar-collapsed');
            }
            syncPaletteVisibility();
            this.setAttribute('aria-expanded', String(!examSidebar.classList.contains('collapsed')));
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
            setPaletteOpen(false);
        });
    });

    // ─── Section Tabs Filtering ────────────────────────────────────
    const sectionTabs = document.querySelectorAll('.gep-section-tab');
    let activeCatId = null;
    let activeTab = null;

    if (sectionTabs.length > 0) {
        activeCatId = hasSectionalTiming ? sectionalTimings[currentSectionIdx].id : sectionTabs[0].dataset.catId;
        activeTab = activeCatId;
        
        sectionTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const catId = this.dataset.catId;
                // The copies of this strip that live inside the palette only
                // re-point the palette at another section's numbers: the student
                // is mid-way through choosing a question, so jumping them to the
                // first question of the section and shutting the palette would
                // undo the very thing they opened it to do.
                const keepOpen = this.dataset.keepPaletteOpen === '1';
                switchSection(catId, !keepOpen);
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
        
        // 1. Update tab active state — on both strips, the one in the main area
        //    and its copy inside the palette, so they never disagree.
        sectionTabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.catId === catId);
            if (tab.dataset.catId === catId) {
                const activeLabel = document.getElementById('gep-palette-section-name');
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
        btnInst.addEventListener('click', () => {
            setPaletteOpen(false);
            modalInst.style.display = 'flex';
        });
        if (closeInst) closeInst.addEventListener('click', () => { modalInst.style.display = 'none'; });
        modalInst.addEventListener('click', (e) => { if (e.target === modalInst) modalInst.style.display = 'none'; });
    }

    const btnQpaper = document.getElementById('gep-btn-question-paper');
    const modalQpaper = document.getElementById('gep-qpaper-modal');
    const closeQpaper = document.getElementById('gep-qpaper-close');

    if (btnQpaper && modalQpaper) {
        btnQpaper.addEventListener('click', () => {
            setPaletteOpen(false);
            modalQpaper.style.display = 'flex';
        });
        if (closeQpaper) closeQpaper.addEventListener('click', () => { modalQpaper.style.display = 'none'; });
        modalQpaper.addEventListener('click', (e) => { if (e.target === modalQpaper) modalQpaper.style.display = 'none'; });
    }

    // ─── Submit Exam ────────────────────────────────────────────────────────
    async function submitExam() {
        if (submitting) return;
        submitting = true;
        clearTimeout(draftSaveTimer);
        questions.forEach(block => { block.inert = true; });
        showModal({title: 'Submitting…', message: 'Saving your answers and confirming submission. Keep this page open.', buttons: []});
        try {
            // Once submission is sent, retry that idempotent endpoint only: a lost
            // response may mean the attempt is already closed to answer writes.
            if (!submissionRequested) {
                // Include text currently being edited, even if its change event has not fired.
                const block = questions[currentQuestionIndex];
                if (block) {
                    const pal = document.querySelector(`.gep-palette-btn[data-id="${block.dataset.id}"]`);
                    await saveAnswer(block.dataset.id, getAnswerFromBlock(block), !!pal && (pal.classList.contains('flagged') || pal.classList.contains('answered-flagged')));
                } else await flushAnswers();
                if (pendingAnswers.size) throw new Error('Some answers have not reached the server. Reconnect and retry submission.');
            }
            const formData = new FormData();
            formData.append('action', 'gep_submit_exam'); formData.append('nonce', examData.nonce);
            formData.append('attempt_id', examData.attempt_id); formData.append('lang', currentLang);
            submissionRequested = true;
            const data = await postExam(formData);
            if (!data || !data.success || !data.data || !data.data.redirect_url) throw new Error('The server has not confirmed submission. Please retry.');
            window.onbeforeunload = null;
            window.location.href = data.data.redirect_url;
        } catch (e) {
            showModal({
                title: 'Submission not confirmed', message: e.message || 'Could not connect. Please retry submission.', type: 'warning',
                buttons: [
                    ...(!submissionRequested && !timerExpired ? [{label: 'Back to exam', action: 'cancel'}] : []),
                    {label: 'Retry submission', action: 'retry', primary: true, onClick: () => submitExam()}
                ]
            });
        } finally {
            submitting = false;
            questions.forEach(block => { block.inert = submissionRequested || timerExpired; });
        }
    }

    // ─── Init ────────────────────────────────────────────────────────────────
    examData.saved_answers = examData.saved_answers || {};
    questions.forEach(block => {
        try {
            const raw = localStorage.getItem(storagePrefix + block.dataset.id);
            if (!raw) return;
            const data = JSON.parse(raw);
            if (!data || typeof data.answer !== 'string' || typeof data.flagged !== 'boolean') return;
            pendingAnswers.set(String(block.dataset.id), data);
            examData.saved_answers[block.dataset.id] = data;
        } catch (e) {}
    });
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
                    const radio = Array.from(block.querySelectorAll('input[type="radio"]')).find(input => input.value === data.answer);
                    if (radio) {
                        radio.checked = true;
                        const card = radio.closest('.gep-option-card');
                        if (card) card.classList.add('selected');
                    }
                    const textInput = block.querySelector('.gep-text-ans, .gep-numerical-ans');
                    if (textInput) textInput.value = data.answer;
                }
            }
            updatePaletteStatus(qId, data.answer || '', data.flagged || false);
        });
    }

    const firstAvailable = hasSectionalTiming ? Array.from(questions).findIndex(q => q.dataset.catId === sectionalTimings[currentSectionIdx].id) : 0;
    loadQuestion(Math.max(0, firstAvailable));
    navigationReady = true;
    updateSidebarCounters();
    if (pendingAnswers.size) flushAnswers();
    // Start after hydration/navigation are ready: an expired reload may submit now.
    startTimer();

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
        // Native change handles keyboard radio navigation as well as clicks.
        input.addEventListener('change', function() {
            if (submitting || submissionRequested || timerExpired) return;
            const block = this.closest('.gep-question-block');
            block.querySelectorAll('.gep-option-card').forEach(card => {
                card.classList.toggle('selected', !!card.querySelector('input:checked'));
            });
            const pal = document.querySelector(`.gep-palette-btn[data-id="${block.dataset.id}"]`);
            saveAnswer(block.dataset.id, getAnswerFromBlock(block), !!pal && (pal.classList.contains('flagged') || pal.classList.contains('answered-flagged')));
        });
    });

    document.querySelectorAll('.gep-text-ans, .gep-numerical-ans').forEach(input => {
        input.addEventListener('input', function() {
            if (submitting || submissionRequested || timerExpired) return;
            const block = this.closest('.gep-question-block');
            const pal = document.querySelector(`.gep-palette-btn[data-id="${block.dataset.id}"]`);
            queueAnswer(block.dataset.id, getAnswerFromBlock(block), !!pal && (pal.classList.contains('flagged') || pal.classList.contains('answered-flagged')));
            showSaveStatus('Answer edited. Waiting to sync…', true);
            clearTimeout(draftSaveTimer);
            draftSaveTimer = setTimeout(flushAnswers, 500);
        });
        input.addEventListener('change', function() {
            if (submitting || submissionRequested || timerExpired) return;
            clearTimeout(draftSaveTimer);
            const block = this.closest('.gep-question-block');
            const pal = document.querySelector(`.gep-palette-btn[data-id="${block.dataset.id}"]`);
            saveAnswer(block.dataset.id, getAnswerFromBlock(block), !!pal && (pal.classList.contains('flagged') || pal.classList.contains('answered-flagged')));
        });
    });

    // ─── Clear Response ──────────────────────────────────────────────────────
    const clearBtn = document.getElementById('gep-clear-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (submitting || submissionRequested || timerExpired) return;
            const block = document.querySelectorAll('.gep-question-block')[currentQuestionIndex];
            if (!block) return;

            const questionId = block.dataset.id;
            const container = block.querySelector('.gep-options-container');
            const qtype = container ? container.dataset.qtype : 'mcq';

            if (qtype === 'short_answer') {
                const textInput = block.querySelector('.gep-text-ans, .gep-numerical-ans');
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
    const calcApply = document.getElementById('gep-calc-apply');
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
            if (calcApply) calcApply.hidden = !questions[currentQuestionIndex]?.querySelector('.gep-numerical-ans');
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
                        if (!Number.isFinite(result)) throw new Error('Invalid result');
                        calcExpression = String(parseFloat(result.toFixed(8)));
                        if (calcDisplay) calcDisplay.value = calcExpression;
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

    if (calcApply) calcApply.addEventListener('click', () => {
        const value = calcDisplay ? calcDisplay.value : '';
        if (!value.trim() || !Number.isFinite(Number(value))) { showToast('Calculate a valid number first.', 'warning'); return; }
        const input = questions[currentQuestionIndex]?.querySelector('.gep-numerical-ans');
        if (!input) return;
        input.value = value; input.dispatchEvent(new Event('change')); closeCalc();
    });
    // All auxiliary exam dialogs share Escape, focus trapping and focus return.
    [modalInst, modalQpaper, calcModal].filter(Boolean).forEach(overlay => {
        const panel = overlay.firstElementChild;
        if (!panel) return;
        panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-modal', 'true'); panel.tabIndex = -1;
        const heading = panel.querySelector('h2, h3');
        if (heading) { heading.id = overlay.id + '-title'; panel.setAttribute('aria-labelledby', heading.id); }
        let opened = false, opener = null, previousLayoutInert;
        // Reference dialogs are siblings of the grid. The calculator is
        // inside it, so do not make the calculator's own ancestor inert.
        const blocksLayout = examLayout && !examLayout.contains(overlay);
        const controls = () => Array.from(panel.querySelectorAll('button, a[href], input, select, textarea, [tabindex="0"]')).filter(el => !el.disabled && !el.hidden && getComputedStyle(el).display !== 'none');
        new MutationObserver(() => {
            const visible = getComputedStyle(overlay).display !== 'none';
            if (visible === opened) return;
            opened = visible;
            if (opened) {
                opener = document.activeElement;
                if (blocksLayout) { previousLayoutInert = examLayout.inert; examLayout.inert = true; }
                (controls()[0] || panel).focus();
            } else {
                if (blocksLayout) examLayout.inert = previousLayoutInert;
                if (opener && opener.isConnected) opener.focus();
            }
        }).observe(overlay, {attributes: true, attributeFilter: ['style']});
        overlay.addEventListener('keydown', e => {
            if (e.key === 'Escape') { e.preventDefault(); overlay.style.display = 'none'; }
            if (e.key !== 'Tab') return;
            const items = controls(), first = items[0], last = items[items.length - 1];
            if (!first) e.preventDefault();
            else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
    });

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
        return document.querySelector('.gep-exam-fullscreen-container') || document.scrollingElement || document.documentElement;
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
        const examScroller = document.querySelector('.gep-exam-fullscreen-container');
        if (examScroller) examScroller.addEventListener('scroll', revealScrollButtons, { passive: true });

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
