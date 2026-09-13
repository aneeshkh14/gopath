document.addEventListener('DOMContentLoaded', function() {
    if (typeof GEP_Exam === 'undefined') return;

    const examData = GEP_Exam;
    let fullscreenExited = false;
    let isLocalDev = (location.hostname === 'localhost' || location.hostname.includes('.local') || location.hostname === '127.0.0.1');
    let isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    let lastViolationTime = {};
    const COOLDOWN_MS = 2500;

    // ─── Reuse the modal/toast from gep-exam.js (always loaded first) ────────
    // We define our own minimal inline toast for security violations
    function showSecurityToast(message, duration = 5000) {
        const existing = document.getElementById('gep-security-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'gep-security-toast';
        toast.style.cssText = `
            position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:100001;
            background:#1e293b;border:2px solid #ef4444;
            color:#fca5a5;padding:14px 28px;border-radius:14px;
            font-size:14px;font-weight:700;box-shadow:0 8px 40px rgba(239,68,68,0.3);
            animation:gepFadeIn 0.2s ease;white-space:nowrap;display:flex;align-items:center;gap:10px;
        `;
        toast.innerHTML = `<span style="font-size:20px;">🛡️</span><span>${message}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'opacity 0.4s';
            toast.style.opacity = '0';
        }, duration);
        setTimeout(() => { if (toast.parentNode) toast.remove(); }, duration + 400);
    }
    function showSecurityModal({ title, message, onClose }) {
        const existing = document.getElementById('gep-security-modal');
        if (existing) return; // Don't stack modals

        const overlay = document.createElement('div');
        overlay.id = 'gep-security-modal';
        overlay.style.cssText = `
            position:fixed;inset:0;z-index:100002;display:flex;align-items:center;justify-content:center;
            background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);
        `;

        const modal = document.createElement('div');
        const opener = document.activeElement;
        modal.setAttribute('role', 'dialog'); modal.setAttribute('aria-modal', 'true'); modal.setAttribute('aria-label', title);
        modal.style.cssText = `
            background:#1e293b;border:2px solid #ef4444;border-radius:24px;
            padding:40px 48px;max-width:440px;width:90%;text-align:center;
            box-shadow:0 32px 80px rgba(239,68,68,0.3);
        `;
        
        const dashboardUrl = (typeof examData !== 'undefined' && examData.dashboard_url) ? examData.dashboard_url : '/';
        
        modal.innerHTML = `
            <div style="font-size:48px;margin-bottom:16px;">🛡️</div>
            <h3 style="font-size:20px;font-weight:800;color:#fca5a5;margin:0 0 10px;">${title}</h3>
            <p style="color:#94a3b8;font-size:14px;line-height:1.7;margin:0 0 28px;">${message}</p>
            <div style="display:flex;justify-content:center;gap:15px;">
                <button id="gep-sec-modal-ok" style="
                    padding:12px 30px;background:linear-gradient(135deg,#ef4444,#dc2626);
                    color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;
                ">I Understand</button>
                <button id="gep-sec-modal-exit" style="
                    padding:12px 30px;background:transparent;
                    color:#94a3b8;border:1px solid #475569;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;transition:all 0.2s;
                " onmouseover="this.style.color='#fca5a5';this.style.borderColor='#ef4444';" onmouseout="this.style.color='#94a3b8';this.style.borderColor='#475569';">Exit Exam</button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        document.getElementById('gep-sec-modal-ok').focus();
        modal.addEventListener('keydown', e => {
            if (e.key !== 'Tab') return;
            const first = document.getElementById('gep-sec-modal-ok'), last = document.getElementById('gep-sec-modal-exit');
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
        document.getElementById('gep-sec-modal-ok').addEventListener('click', () => {
            overlay.remove();
            if (opener && opener.isConnected) opener.focus();
            if (onClose) onClose();
        });
        
        document.getElementById('gep-sec-modal-exit').addEventListener('click', () => {
            overlay.remove();
            document.dispatchEvent(new Event('gep:request-exam-exit'));
        });
    }


    // ─── 1. Fullscreen Logic ─────────────────────────────────────────────────
    function enterFullscreen() {
        const el = document.documentElement;
        if (el.requestFullscreen) el.requestFullscreen().catch(() => {});
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
        else if (el.msRequestFullscreen) el.msRequestFullscreen();
    }

    const enterSecureBtn = document.getElementById('gep-enter-secure-btn');
    const secureOverlay  = document.getElementById('gep-secure-init-overlay');

    if (isMobile) {
        if (secureOverlay) secureOverlay.style.display = 'none';
    }

    if (secureOverlay) {
        // Prevent theme/plugin click-outside listeners from hiding the modal
        secureOverlay.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Also stop propagation on the card itself just in case
        const secureCard = secureOverlay.querySelector('.gep-secure-init-card');
        if (secureCard) {
            secureCard.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    }

    if (enterSecureBtn && secureOverlay) {
        enterSecureBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!isMobile) {
                enterFullscreen(); // Desktop only — mobile fullscreen breaks layout
            }
            if (isMobile) {
                secureOverlay.style.display = 'none';
                const modal = document.getElementById('gep-security-modal');
                if (modal) modal.remove();
            }
        });
    }

    const getFullscreenElement = () => {
        return document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement;
    };

    // Cross-browser fullscreen change listener
    const fsEvents = ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'];
    fsEvents.forEach(evtName => {
        document.addEventListener(evtName, () => {
            if (isMobile) return;
            const fsElement = getFullscreenElement();
            if (fsElement) {
                fullscreenExited = false;
                if (secureOverlay) secureOverlay.style.display = 'none';
                
                // Remove any security modal if user successfully returns to fullscreen
                const modal = document.getElementById('gep-security-modal');
                if (modal) modal.remove();
            } else {
                if (!fullscreenExited) {
                    fullscreenExited = true;
                    logViolation('fullscreen_exit');

                    if (isLocalDev) {
                        // Dev mode: just show a non-blocking toast, don't force overlay
                        showSecurityToast('⚠️ Fullscreen exited — violation recorded (dev mode)');
                        // Reset flag so next exit is also logged
                        setTimeout(() => { fullscreenExited = false; }, 3000);
                    } else {
                        // Production: show modal and restore secure overlay
                        showSecurityModal({
                            title: 'Security Violation',
                            message: 'You have exited full-screen mode. This has been recorded as a violation. Please return to full-screen to continue.',
                            onClose: () => {
                                // Re-enter fullscreen. The event listener will hide overlay when active.
                                enterFullscreen();
                            }
                        });
                        if (secureOverlay) secureOverlay.style.display = 'flex';
                    }
                }
            }
        });
    });

    // ─── 2. Tab Switch / Visibility & Focus Loss Detection ───────────────────
    let isFocusReturnPending = false;

    function handleFocusLoss() {
        logViolation('tab_switch');
        
        if (!isFocusReturnPending) {
            isFocusReturnPending = true;
            function onFocusReturn() {
                showSecurityToast('Tab switch / focus loss detected & recorded as a violation.');
                window.removeEventListener('focus', onFocusReturn);
                isFocusReturnPending = false;
            }
            window.addEventListener('focus', onFocusReturn);
        }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            handleFocusLoss();
        }
    });

    window.addEventListener('blur', () => {
        if (isMobile) return;
        handleFocusLoss();
    });

    // ─── 3. Keyboard Restrictions ────────────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        const key = e.key.toLowerCase();

        // Block Ctrl+C, Ctrl+V, Ctrl+U, Ctrl+S, Ctrl+P
        if (e.ctrlKey && ['c', 'v', 'u', 's', 'p'].includes(key)) {
            e.preventDefault();
            logViolation('prohibited_key_combo');
            showSecurityToast(`Ctrl+${e.key.toUpperCase()} is disabled during the exam.`);
        }

        // Block F12 and DevTools
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && key === 'i')) {
            e.preventDefault();
            logViolation('dev_tools_attempt');
        }

        // PrintScreen: FIX - replace alert() with toast
        if (e.key === 'PrintScreen') {
            e.preventDefault();
            logViolation('screenshot_attempt');
            showSecurityToast('Screenshots are disabled during the exam.');
        }
    });

    // ─── 4. Context Menu & Copy Restrictions ─────────────────────────────────
    document.addEventListener('contextmenu', e => e.preventDefault());
    document.addEventListener('copy',        e => e.preventDefault());
    document.addEventListener('cut',         e => e.preventDefault());

    // ─── 4b. DevTools Detection (window size method) ─────────────────────────
    let devToolsOpen = false;
    if (!isMobile) {
        const devToolsCheck = setInterval(() => {
            const threshold = 160; // DevTools panel width/height
            const widthDiff  = window.outerWidth  - window.innerWidth;
            const heightDiff = window.outerHeight - window.innerHeight;
            const isOpen = widthDiff > threshold || heightDiff > threshold;
            if (isOpen && !devToolsOpen) {
                devToolsOpen = true;
                logViolation('dev_tools_detected');
                showSecurityToast('⚠️ DevTools detected — recorded as a security violation.');
            } else if (!isOpen) {
                devToolsOpen = false;
            }
        }, 2000);
    }

    // ─── 5. Back Button Protection ───────────────────────────────────────────
    history.pushState(null, null, location.href);
    window.addEventListener('popstate', () => {
        history.pushState(null, null, location.href);
        // FIX: Replace alert() with toast
        showSecurityToast('Back navigation is disabled during the exam.');
        logViolation('back_button_attempt');
    });

    // ─── 6. Leave/Refresh Warning ─────────────────────────────────────────────
    const previousUnloadWarning = window.onbeforeunload;
    window.onbeforeunload = function(e) {
        if (previousUnloadWarning) previousUnloadWarning(e);
        e.preventDefault(); e.returnValue = ''; return '';
    };

    // ─── Violation Logger ────────────────────────────────────────────────────
    function logViolation(type) {
        const now = Date.now();
        
        // Cooldown check for similar/related violations to prevent duplicate logging
        if (type === 'tab_switch' || type === 'fullscreen_exit') {
            const lastTabSwitch = lastViolationTime['tab_switch'] || 0;
            const lastFullscreenExit = lastViolationTime['fullscreen_exit'] || 0;
            if (now - lastTabSwitch < COOLDOWN_MS || now - lastFullscreenExit < COOLDOWN_MS) {
                console.log(`[Security] Cooldown active. Suppressed duplicate violation: ${type}`);
                return;
            }
        } else {
            const lastTime = lastViolationTime[type] || 0;
            if (now - lastTime < COOLDOWN_MS) {
                console.log(`[Security] Cooldown active. Suppressed duplicate violation: ${type}`);
                return;
            }
        }
        lastViolationTime[type] = now;

        const formData = new FormData();
        formData.append('action',         'gep_log_violation');
        formData.append('nonce',          examData.nonce);
        formData.append('attempt_id',     examData.attempt_id);
        formData.append('violation_type', type);

        fetch(examData.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                // Auto-submit if too many violations
                if (data.data && data.data.status === 'auto_submitted') {
                    window.onbeforeunload = null;
                    showSecurityModal({
                        title: 'Exam Auto-Submitted',
                        message: 'Too many security violations detected. Your exam has been automatically submitted.',
                        onClose: () => { window.location.reload(); }
                    });
                }
            }).catch(() => {
                // Silently fail — violation logging is best-effort
            });
    }
});
