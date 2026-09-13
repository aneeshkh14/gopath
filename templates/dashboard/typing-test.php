<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();
global $wpdb;
$table_name = $wpdb->prefix . 'gep_typing_attempts';

// Self-healing database table creation for typing logs
if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        wpm double NOT NULL,
        accuracy double NOT NULL,
        errors int(11) NOT NULL,
        duration int(11) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Fetch user's historical typing attempts
$attempts = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d ORDER BY id DESC LIMIT 10",
    $user_id
) );
?>

<div class="gep-main-inner" style="font-family: 'Inter', system-ui, sans-serif; padding-bottom: 80px;">
    <!-- Typing Hero Header -->
    <div style="background: linear-gradient(135deg, #0f172a, #1e293b); border-radius: 24px; padding: 40px; color: #fff; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
        <div>
            <span style="font-size: 11px; font-weight: 800; background: rgba(99,102,241,0.2); border: 1px solid rgba(99,102,241,0.3); color: #818cf8; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Skill Academy Simulator', 'gopath-exam-portal' ); ?></span>
            <h2 style="font-size: 32px; font-weight: 900; margin: 8px 0 10px; letter-spacing: -1px; color: #fff;"><?php _e( 'Interactive Typing Console', 'gopath-exam-portal' ); ?></h2>
            <p style="margin: 0; color: #94a3b8; font-size: 14px; font-weight: 600;"><?php _e( 'Develop speed and accuracy for SSC, Bank, and Typing Clerk exams.', 'gopath-exam-portal' ); ?></p>
        </div>
        <div style="font-size: 55px; opacity: 0.2; transform: rotate(-10deg);">⌨️</div>
    </div>

<style>
.gep-typing-grid {
    display: grid;
    grid-template-columns: 1.8fr 1.2fr;
    gap: 30px;
    margin-bottom: 40px;
}
@media (max-width: 1024px) {
    .gep-typing-grid {
        grid-template-columns: 1fr;
    }
}
</style>

    <!-- Main Workspace Container -->
    <div class="gep-typing-grid">
        
        <!-- Practice Area -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 35px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);">
            <!-- Settings Config Block -->
            <div id="gep-typing-setup" style="display: block;">
                <h3 style="margin: 0 0 20px; font-size: 18px; font-weight: 900; color: #0f172a;"><?php _e( 'Configure Typing Session', 'gopath-exam-portal' ); ?></h3>
                
                <div style="margin-bottom: 20px;">
                    <label for="gep-typing-paragraph" style="display: block; font-size: 13px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;"><?php _e( 'Select Exercise Text', 'gopath-exam-portal' ); ?></label>
                    <select id="gep-typing-paragraph" style="width: 100%; height: 48px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 15px; font-weight: 700; color: #334155; outline: none; background: #fff;" aria-label="<?php esc_attr_e( 'Select Exercise Text', 'gopath-exam-portal' ); ?>">
                        <option value="para1"><?php _e( 'SSC CHSL Practice (Easy) - Technology Overview', 'gopath-exam-portal' ); ?></option>
                        <option value="para2"><?php _e( 'Constitutional History (Medium) - Indian Constitution', 'gopath-exam-portal' ); ?></option>
                        <option value="para3"><?php _e( 'Scientific Innovation (Hard) - Artificial Intelligence', 'gopath-exam-portal' ); ?></option>
                    </select>
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-size: 13px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;"><?php _e( 'Select Test Duration', 'gopath-exam-portal' ); ?></label>
                    <div style="display: flex; gap: 15px;">
                        <label class="gep-dur-label" style="flex: 1; text-align: center; border: 2px solid #e2e8f0; border-radius: 14px; padding: 12px; cursor: pointer; font-weight: 800; color: #475569; transition: all 0.25s;">
                            <input type="radio" name="gep_typing_dur" value="60" checked class="gep-visually-hidden" aria-label="<?php esc_attr_e( '1 Minute', 'gopath-exam-portal' ); ?>"> <?php _e( '1 Minute', 'gopath-exam-portal' ); ?>
                        </label>
                        <label class="gep-dur-label" style="flex: 1; text-align: center; border: 2px solid #e2e8f0; border-radius: 14px; padding: 12px; cursor: pointer; font-weight: 800; color: #475569; transition: all 0.25s;">
                            <input type="radio" name="gep_typing_dur" value="120" class="gep-visually-hidden" aria-label="<?php esc_attr_e( '2 Minutes', 'gopath-exam-portal' ); ?>"> <?php _e( '2 Minutes', 'gopath-exam-portal' ); ?>
                        </label>
                        <label class="gep-dur-label" style="flex: 1; text-align: center; border: 2px solid #e2e8f0; border-radius: 14px; padding: 12px; cursor: pointer; font-weight: 800; color: #475569; transition: all 0.25s;">
                            <input type="radio" name="gep_typing_dur" value="300" class="gep-visually-hidden" aria-label="<?php esc_attr_e( '5 Minutes', 'gopath-exam-portal' ); ?>"> <?php _e( '5 Minutes', 'gopath-exam-portal' ); ?>
                        </label>
                    </div>
                </div>

                <button type="button" id="gep-start-typing-btn" style="width: 100%; background: #6366f1; border: none; color: #fff; padding: 15px; border-radius: 14px; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(99,102,241,0.2);">
                    <?php _e( 'Start Typing Test', 'gopath-exam-portal' ); ?>
                </button>
            </div>

            <!-- Active Typing Console -->
            <div id="gep-typing-active" style="display: none;">
                <!-- Real-time Stats Overlay -->
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px;"><?php _e( 'Time Remaining', 'gopath-exam-portal' ); ?></span>
                        <span id="gep-timer-label" style="font-size: 20px; font-weight: 900; color: #0f172a;" aria-live="polite">01:00</span>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px;"><?php _e( 'Speed (WPM)', 'gopath-exam-portal' ); ?></span>
                        <span id="gep-wpm-label" style="font-size: 20px; font-weight: 900; color: #6366f1;" aria-live="polite">0</span>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px;"><?php _e( 'Accuracy', 'gopath-exam-portal' ); ?></span>
                        <span id="gep-acc-label" style="font-size: 20px; font-weight: 900; color: #10b981;" aria-live="polite">100%</span>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px;"><?php _e( 'Mistakes', 'gopath-exam-portal' ); ?></span>
                        <span id="gep-errors-label" style="font-size: 20px; font-weight: 900; color: #ef4444;" aria-live="polite">0</span>
                    </div>
                </div>

                <!-- Scrolling Target Text Overlay -->
                <div id="gep-typing-text-wrapper" style="border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; background: #f8fafc; font-size: 18px; font-weight: 600; line-height: 1.7; color: #64748b; margin-bottom: 20px; max-height: 160px; overflow-y: auto; user-select: none; letter-spacing: 0.3px;" tabindex="0" aria-label="<?php esc_attr_e( 'Target text to type', 'gopath-exam-portal' ); ?>">
                    <!-- Characters generated dynamically -->
                </div>

                <!-- Input Textarea -->
                <textarea id="gep-typing-input" placeholder="<?php esc_attr_e( 'Start typing here to activate test...', 'gopath-exam-portal' ); ?>" style="width: 100%; height: 120px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 15px; font-size: 16px; font-weight: 600; line-height: 1.6; outline: none; background: #fff; resize: none; transition: border-color 0.2s;" disabled aria-label="<?php esc_attr_e( 'Typing input area', 'gopath-exam-portal' ); ?>"></textarea>

                <div style="display: flex; gap: 15px; margin-top: 15px;">
                    <button type="button" id="gep-restart-test" style="flex: 1; background: #f1f5f9; border: none; color: #475569; padding: 12px; border-radius: 12px; font-weight: 800; cursor: pointer;">
                        <?php _e( 'Restart', 'gopath-exam-portal' ); ?>
                    </button>
                    <button type="button" id="gep-submit-early" style="flex: 1; background: #ef4444; border: none; color: #fff; padding: 12px; border-radius: 12px; font-weight: 800; cursor: pointer;">
                        <?php _e( 'End Early', 'gopath-exam-portal' ); ?>
                    </button>
                </div>
            </div>

            <!-- Complete Results Badge -->
            <div id="gep-typing-completed" role="status" style="display: none; text-align: center; padding: 20px 0;" role="status">
                <div style="font-size: 55px; margin-bottom: 15px;">🏆</div>
                <h3 style="margin: 0 0 5px; font-size: 22px; font-weight: 900; color: #0f172a;"><?php _e( 'Session Completed!', 'gopath-exam-portal' ); ?></h3>
                <p style="margin: 0 0 25px; color: #64748b; font-size: 14px; font-weight: 600;"><?php _e( 'Your stats have been computed and saved to your skill academy profile.', 'gopath-exam-portal' ); ?></p>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 16px;">
                        <span style="font-size: 24px; font-weight: 950; color: #6366f1;" id="final-wpm">0</span>
                        <span style="display: block; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-top: 2px;"><?php _e( 'Net WPM', 'gopath-exam-portal' ); ?></span>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 16px;">
                        <span style="font-size: 24px; font-weight: 950; color: #10b981;" id="final-acc">0%</span>
                        <span style="display: block; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-top: 2px;"><?php _e( 'Accuracy', 'gopath-exam-portal' ); ?></span>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 16px;">
                        <span style="font-size: 24px; font-weight: 950; color: #ef4444;" id="final-errors">0</span>
                        <span style="display: block; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-top: 2px;"><?php _e( 'Total Errors', 'gopath-exam-portal' ); ?></span>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 16px;">
                        <span style="font-size: 24px; font-weight: 950; color: #475569;" id="final-duration">0s</span>
                        <span style="display: block; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-top: 2px;"><?php _e( 'Duration', 'gopath-exam-portal' ); ?></span>
                    </div>
                </div>

                <p id="gep-typing-save-status" role="status"></p>
                <button type="button" id="gep-new-test-btn" style="background: #6366f1; border: none; color: #fff; padding: 12px 30px; border-radius: 12px; font-weight: 800; cursor: pointer;">
                    <?php _e( 'Start New Session', 'gopath-exam-portal' ); ?>
                </button>
            </div>
        </div>

        <!-- History & Leaderboard -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 35px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); display: flex; flex-direction: column;">
            <h3 style="margin: 0 0 20px; font-size: 18px; font-weight: 900; color: #0f172a;"><?php _e( 'Recent Typing Log', 'gopath-exam-portal' ); ?></h3>
            
            <div style="flex: 1; overflow-y: auto; max-height: 380px;">
                <?php if ( $attempts ) : ?>
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; font-weight: 700; color: #475569;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9; color: #94a3b8; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                                <th style="padding: 10px 0;"><?php _e( 'Date', 'gopath-exam-portal' ); ?></th>
                                <th><?php _e( 'Speed', 'gopath-exam-portal' ); ?></th>
                                <th><?php _e( 'Accuracy', 'gopath-exam-portal' ); ?></th>
                                <th><?php _e( 'Errors', 'gopath-exam-portal' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $attempts as $a ) : ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 12px 0; color: #94a3b8;"><?php echo date('M j, Y', strtotime($a->created_at)); ?></td>
                                    <td style="color: #6366f1; font-weight: 900;"><?php echo round($a->wpm); ?> <?php _e( 'WPM', 'gopath-exam-portal' ); ?></td>
                                    <td style="color: #10b981;"><?php echo round($a->accuracy); ?>%</td>
                                    <td style="color: #ef4444;"><?php echo $a->errors; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div style="text-align: center; padding: 50px 20px; color: #64748b;">
                        <div style="font-size: 32px; margin-bottom: 10px;">📉</div>
                        <p style="margin: 0; font-size: 13px; font-weight: 600;"><?php _e( 'No sessions completed yet.', 'gopath-exam-portal' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const paragraphs = {
        para1: "The rapid growth of technology in the modern world has transformed the way we communicate and conduct business. Today, computers are integrated into almost every aspect of daily life, from online banking and education to manufacturing and entertainment. As a result, typing speed and accuracy have become essential skills in the digital job market. Employers highly value candidates who can input data quickly and accurately without looking at the keyboard. Regular typing practice helps build muscle memory, reduce fatigue, and significantly improve overall productivity. Investing a few minutes daily in systematic drills can double your typing speed over a few weeks.",
        para2: "The Constitution of India is the supreme law of the country. It lays down the framework defining fundamental political principles, establishes the structure, procedures, powers and duties of government institutions, and sets out fundamental rights, directive principles and the duties of citizens. It is the longest written constitution of any sovereign country in the world. The drafting committee, led by Dr. B. R. Ambedkar, painstakingly researched various global democratic models to tailor a resilient constitution for India's diverse population. It was formally adopted by the Constituent Assembly on November 26, 1949, and came into effect on January 26, 1950, celebrating Republic Day.",
        para3: "Artificial intelligence and machine learning are redefining the limits of human capability. From healthcare diagnosis to autonomous navigation, intelligent agents are analyzing massive datasets at speeds impossible for manual human agents. The core of these technologies lies in deep neural networks trained on high-performance compute nodes. By adjusting millions of parameters through gradient descent, the algorithms learn to classify images, compose texts, and predict future sequences. As neural architectures mature, ensuring ethical alignment, transparency, and safety has become the paramount objective for researchers worldwide."
    };

    let targetText = '';
    let duration = 60; // seconds
    let timeRemaining = 60;
    let timerInterval = null;
    let started = false, finished = false, sessionRevision = 0, startedAt = 0;
    let totalKeystrokes = 0;
    let errorCount = 0;

    // Toggle settings duration highlight
    $('input[name="gep_typing_dur"]').on('change', function() {
        $('input[name="gep_typing_dur"]').parent().css({
            borderColor: '#e2e8f0',
            background: '#fff',
            color: '#475569'
        });
        $(this).parent().css({
            borderColor: '#6366f1',
            background: '#f0f3ff',
            color: '#6366f1'
        });
    });

    $('#gep-start-typing-btn').on('click', function() {
        sessionRevision++; finished = false; startedAt = 0; clearInterval(timerInterval);
        $('#gep-typing-save-status').text('');
        targetText = paragraphs[$('#gep-typing-paragraph').val()];
        duration = parseInt($('input[name="gep_typing_dur"]:checked').val());
        timeRemaining = duration;

        $('#gep-typing-setup').hide();
        $('#gep-typing-completed').hide();
        $('#gep-typing-active').show();

        // Render target text with individual span elements per character
        let textHTML = '';
        for (let i = 0; i < targetText.length; i++) {
            let char = targetText[i];
            textHTML += `<span id="gep-char-${i}">${char}</span>`;
        }
        $('#gep-typing-text-wrapper').html(textHTML);

        // Reset tracking vars
        started = false;
        totalKeystrokes = 0;
        errorCount = 0;
        $('#gep-char-0').css('background-color', '#c7d2fe'); // Highlight first char
        
        // Reset timers & labels
        updateTimerLabel();
        $('#gep-wpm-label').text('0');
        $('#gep-acc-label').text('100%');
        $('#gep-errors-label').text('0');

        // Enable and focus textarea
        $('#gep-typing-input').prop('disabled', false).attr('maxlength', targetText.length).val('').focus();
    });

    function updateTimerLabel() {
        let mins = Math.floor(timeRemaining / 60);
        let secs = timeRemaining % 60;
        $('#gep-timer-label').text(
            (mins < 10 ? '0' + mins : mins) + ':' + (secs < 10 ? '0' + secs : secs)
        );
    }

    // Interactive keystroke handler
    $('#gep-typing-input').on('input', function(e) {
        if (finished) return;
        if (!started) {
            startTimer();
            started = true;
        }

        let currentVal = $(this).val().slice(0, targetText.length);
        $(this).val(currentVal);
        totalKeystrokes = currentVal.length;

        errorCount = 0;
        for (let i = 0; i < targetText.length; i++) {
            let targetChar = targetText[i];
            let typedChar = currentVal[i];
            let span = $(`#gep-char-${i}`);

            if (typedChar === undefined) {
                // Not typed yet
                span.css({
                    color: '#64748b',
                    background: 'none'
                });
            } else if (typedChar === targetChar) {
                // Correct match
                span.css({
                    color: '#10b981',
                    background: 'none'
                });
            } else {
                // Typo / Error
                span.css({
                    color: '#ef4444',
                    background: '#fee2e2'
                });
                errorCount++;
            }
        }

        // Highlight the current character matching cursor location
        $('[id^="gep-char-"]').css('text-decoration', 'none');
        let cursorIndex = currentVal.length;
        if (cursorIndex < targetText.length) {
            $(`#gep-char-${cursorIndex}`).css({
                backgroundColor: '#c7d2fe',
                color: '#1e1b4b'
            });
        }

        // Auto-scroll the target text area as the user types
        let currentSpan = $(`#gep-char-${cursorIndex}`);
        if (currentSpan.length > 0) {
            let wrapper = $('#gep-typing-text-wrapper');
            let scrollPos = currentSpan.position().top - wrapper.position().top + wrapper.scrollTop();
            if (scrollPos > 100) {
                wrapper.scrollTop(scrollPos - 50);
            }
        }

        // Live calculation metrics
        let elapsedMins = (duration - timeRemaining) / 60;
        if (elapsedMins <= 0) elapsedMins = 0.01;
        let correctChars = currentVal.length - errorCount;
        let netWpm = Math.max(0, Math.round((correctChars / 5) / elapsedMins));
        let acc = Math.round(correctChars / (totalKeystrokes || 1) * 100);

        $('#gep-wpm-label').text(netWpm);
        $('#gep-acc-label').text(acc + '%');
        $('#gep-errors-label').text(errorCount);

        // Success boundary completion
        if (currentVal.length >= targetText.length) {
            endSession();
        }
    });

    function startTimer() {
        startedAt = Date.now();
        timerInterval = setInterval(function() {
            timeRemaining = Math.max(0, duration - Math.floor((Date.now() - startedAt) / 1000));
            updateTimerLabel();

            // Calculate current WPM
            let elapsedMins = (duration - timeRemaining) / 60;
            if (elapsedMins <= 0) elapsedMins = 0.01;
            let currentVal = $('#gep-typing-input').val();
            let correctChars = currentVal.length - errorCount;
            let netWpm = Math.max(0, Math.round((correctChars / 5) / elapsedMins));
            $('#gep-wpm-label').text(netWpm);

            if (timeRemaining <= 0) {
                endSession();
            }
        }, 1000);
    }

    function endSession() {
        if (finished) return;
        finished = true;
        const revision = sessionRevision;
        if (startedAt) timeRemaining = Math.max(0, duration - Math.min(duration, Math.max(1, Math.floor((Date.now() - startedAt) / 1000))));
        clearInterval(timerInterval);
        $('#gep-typing-input').prop('disabled', true);

        let finalVal = $('#gep-typing-input').val();
        let correctChars = finalVal.length - errorCount;
        let elapsedMins = (duration - timeRemaining) / 60;
        if (elapsedMins <= 0) elapsedMins = 0.01;

        let finalWpm = Math.max(0, Math.round((correctChars / 5) / elapsedMins));
        let finalAcc = Math.round(correctChars / (totalKeystrokes || 1) * 100);

        // Display results badge
        $('#final-wpm').text(finalWpm);
        $('#final-acc').text(finalAcc + '%');
        $('#final-errors').text(errorCount);
        $('#final-duration').text((duration - timeRemaining) + 's');

        $('#gep-typing-active').hide();
        $('#gep-typing-completed').show();

        // AJAX Save Result to log
        $.post(gep_ajax.ajax_url, {
            action: 'gep_save_typing_attempt',
            wpm: finalWpm,
            accuracy: finalAcc,
            errors: errorCount,
            duration: duration - timeRemaining,
            nonce: '<?php echo wp_create_nonce("gep_student_access"); ?>'
        }, function(response) {
            if (revision !== sessionRevision) return;
            if (!response.success) {
                $('#gep-typing-save-status').text('Your result is shown here, but it could not be saved to your history.');
            }
        }).fail(function() { if (revision !== sessionRevision) return; $('#gep-typing-save-status').text('Could not save your result to history. Check your connection.'); });
    }

    $('#gep-restart-test').on('click', function() {
        clearInterval(timerInterval);
        $('#gep-start-typing-btn').click();
    });

    $('#gep-submit-early').on('click', function() {
        endSession();
    });

    $('#gep-new-test-btn').on('click', function() {
        $('#gep-typing-completed').hide();
        $('#gep-typing-setup').show();
    });
});
</script>
