<?php
// BUG-13 FIX: Ensure PHP session is active before reading $_SESSION for language
if ( ! session_id() ) session_start();
if ( ! defined( 'ABSPATH' ) ) exit;
$_gep_lang = isset($_SESSION['gep_lang']) ? $_SESSION['gep_lang'] : 'en';

$test_logic = new GEP_Test();
?>
<?php
// Translation Mapping
$ui_strings = array(
    'en' => array(
        'title'      => 'General Instructions',
        'lang'       => 'Language:',
        'important'  => 'Important:',
        'notice'     => 'Please read all instructions carefully before starting the exam. Your timer will begin once you click the button below.',
        'agreement'  => 'I have read and understood the instructions. All computer hardware allotted to me is in proper working condition. I agree that I am not in possession of any prohibited material.',
        'begin'      => 'I am ready to begin'
    ),
    'hi' => array(
        'title'      => 'सामान्य निर्देश',
        'lang'       => 'भाषा:',
        'important'  => 'महत्वपूर्ण:',
        'notice'     => 'कृपया परीक्षा शुरू करने से पहले सभी निर्देशों को ध्यान से पढ़ें। आपके द्वारा नीचे दिए गए बटन पर क्लिक करने के बाद आपका समय शुरू हो जाएगा।',
        'agreement'  => 'मैंने निर्देशों को पढ़ और समझ लिया है। मुझे आवंटित सभी कंप्यूटर हार्डवेयर उचित कार्यशील स्थिति में हैं। मैं सहमत हूँ कि मेरे पास कोई भी प्रतिबंधित सामग्री नहीं है।',
        'begin'      => 'मैं शुरू करने के लिए तैयार हूँ'
    )
);
$strings = isset($ui_strings[$_gep_lang]) ? $ui_strings[$_gep_lang] : $ui_strings['en'];
?>
<div class="gep-instructions-scroll-wrapper" style="position: absolute; inset: 0; width: 100%; height: 100%; overflow-y: auto; padding: 40px 20px; box-sizing: border-box;">
<div class="gep-exam-instructions-container" style="max-width: 900px; margin: 0 auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; overflow: hidden; font-family: 'Inter', sans-serif;">
    <div class="gep-instructions-header" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 25px 40px; color: #fff; display: flex; justify-content: space-between; align-items: center;">
        <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #fff;"><?php echo esc_html( $strings['title'] ); ?></h1>
        <div class="gep-lang-status">
            <div style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.7);"><?php echo esc_html( $strings['lang'] ); ?></label>
                <select id="gep-inst-lang-select" style="height: 32px; padding: 0 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: #fff; font-weight: 700; cursor: pointer; outline: none;">
                    <option value="en"<?php selected($_gep_lang, 'en'); ?> style="color: #334155;">English</option>
                    <option value="hi"<?php selected($_gep_lang, 'hi'); ?> style="color: #334155;">Hindi</option>
                </select>
            </div>
        </div>
    </div>

    <div class="gep-instructions-content" style="padding: 40px; font-size: 15px; line-height: 1.7; color: #334155;">
        
        <?php
        $remaining = $test_logic->get_remaining_attempts( get_current_user_id(), $test->id );
        ?>
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px 20px; border-radius: 14px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; font-weight: 800; color: #166534; font-size: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span>🎟️</span>
                <span>Remaining Attempts: <?php echo $remaining; ?></span>
            </div>
            <?php if ( $test->type === 'random' ) : ?>
                <a href="<?php echo esc_url( gep_get_url('checkout') . '?id=' . $test->id ); ?>" style="color: #2563eb; text-decoration: none; font-size: 12px; font-weight: 800; border: 1.5px solid #2563eb; padding: 6px 12px; border-radius: 8px; background: #fff; transition: all 0.3s; box-shadow: 0 2px 4px rgba(37,99,235,0.06);">+ Buy More Attempts</a>
            <?php endif; ?>
        </div>

        <?php if ( $test->type === 'random' ) : ?>
            <div id="gep-random-selector-wrap" style="margin-bottom: 30px; background: #fff; padding: 25px; border-radius: 20px; border: 1.5px solid #e2e8f0;">
                <h3 style="margin-top: 0; font-size: 16px; font-weight: 900; color: #0f172a; margin-bottom: 8px;">Configure Questions for Attempt</h3>
                <p style="color: #64748b; font-size: 13px; font-weight: 600; margin: 0 0 20px;">Select Subject, check the topics you want, and input the question count for each.</p>
                
                <div id="gep-subject-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 15px; flex-wrap: wrap;">
                    <div style="color: #64748b; font-weight: 600; font-size: 13px;">Loading Subjects...</div>
                </div>

                <div id="gep-topics-container" style="display: grid; grid-template-columns: 1fr; gap: 12px; max-height: 280px; overflow-y: auto; padding-right: 5px;">
                    <!-- Loaded dynamically via JS -->
                </div>
                
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1.5px dashed #f1f5f9; display: flex; justify-content: space-between; align-items: center; font-weight: 800; color: #0f172a; font-size: 14px;">
                    <span>Total Selected Questions:</span>
                    <span id="gep-total-selected-q" style="font-size: 20px; font-weight: 900; color: #2563eb;">0</span>
                </div>
            </div>
        <?php else : ?>
            <div class="gep-instructions-inner" style="margin-bottom: 30px;">
                <?php 
                if ( ! empty( $instructions ) ) {
                    echo wp_kses_post( $instructions );
                } else {
                    $current_lang = $_gep_lang;
                    if ( $current_lang === 'hi' ) {
                        ?>
                        <div class="gep-default-instructions" style="color: #334155; font-size: 14.5px; line-height: 1.7;">
                            <h3 style="margin-top: 0; color: #0f172a; font-weight: 800; font-size: 17px; margin-bottom: 12px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">सामान्य दिशा-निर्देश (General Instructions):</h3>
                            <ul style="padding-left: 20px; margin-bottom: 25px;">
                                <li style="margin-bottom: 10px;">सर्वर पर घड़ी सेट की जाएगी। स्क्रीन के शीर्ष दाएं कोने में उलटी गिनती टाइमर परीक्षा पूरी करने के लिए आपके पास उपलब्ध शेष समय को प्रदर्शित करेगा।</li>
                                <li style="margin-bottom: 10px;">स्क्रीन के दाईं ओर प्रदर्शित प्रश्न पैलेट प्रत्येक प्रश्न की स्थिति को विभिन्न रंगों में दर्शाएगा।</li>
                                <li style="margin-bottom: 10px;">आप सीधे उस प्रश्न पर जाने के लिए प्रश्न पैलेट में किसी प्रश्न संख्या पर क्लिक कर सकते हैं।</li>
                            </ul>
                            <h3 style="color: #0f172a; font-weight: 800; font-size: 17px; margin-bottom: 12px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">प्रश्नों का उत्तर देना (Answering Questions):</h3>
                            <ul style="padding-left: 20px; margin-bottom: 20px;">
                                <li style="margin-bottom: 10px;">अपना उत्तर चुनने के लिए, किसी एक विकल्प के बटन पर क्लिक करें।</li>
                                <li style="margin-bottom: 10px;">अपने चुने हुए उत्तर को हटाने के लिए, <strong>Clear Response</strong> बटन पर क्लिक करें।</li>
                                <li style="margin-bottom: 10px;">अपना उत्तर सहेजने के लिए, आपको प्रत्येक प्रश्न के बाद <strong>Save & Next</strong> बटन पर क्लिक करना आवश्यक है।</li>
                                <li style="margin-bottom: 10px;">समीक्षा के लिए प्रश्न को चिह्नित करने के लिए, <strong>Mark for Review & Next</strong> बटन पर क्लिक करें।</li>
                            </ul>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="gep-default-instructions" style="color: #334155; font-size: 14.5px; line-height: 1.7;">
                            <h3 style="margin-top: 0; color: #0f172a; font-weight: 800; font-size: 17px; margin-bottom: 12px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">General Guidelines:</h3>
                            <ul style="padding-left: 20px; margin-bottom: 25px;">
                                <li style="margin-bottom: 10px;">The clock will be set at the server. The countdown timer in the top right corner of the screen will display the remaining time available for you to complete the examination.</li>
                                <li style="margin-bottom: 10px;">The Question Palette displayed on the right side of the screen will show the status of each question using color-coded status badges.</li>
                                <li style="margin-bottom: 10px;">You can click on a question number in the Question Palette to go to that question directly.</li>
                            </ul>
                            <h3 style="color: #0f172a; font-weight: 800; font-size: 17px; margin-bottom: 12px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">Answering Questions:</h3>
                            <ul style="padding-left: 20px; margin-bottom: 20px;">
                                <li style="margin-bottom: 10px;">To select your answer, click on the button of one of the options.</li>
                                <li style="margin-bottom: 10px;">To deselect your chosen answer, click on the <strong>Clear Response</strong> button.</li>
                                <li style="margin-bottom: 10px;">To save your answer, you MUST click on the <strong>Save & Next</strong> button.</li>
                                <li style="margin-bottom: 10px;">To mark the question for review, click on the <strong>Mark for Review & Next</strong> button.</li>
                            </ul>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px; padding: 20px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; font-size: 14px; font-weight: 700; color: #b45309; line-height: 1.5; display: flex; align-items: flex-start; gap: 10px;">
            <span>⚠️</span>
            <span><?php echo esc_html($strings['notice']); ?></span>
        </div>

    </div>

    <div class="gep-instructions-footer" style="padding: 30px 40px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 20px;">
        <div class="gep-agreement" style="font-size: 14px; font-weight: 600; color: #475569; line-height: 1.6;">
            <label class="gep-checkbox-container" style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                <input type="checkbox" id="agree-terms" style="margin-top: 3px; width: 18px; height: 18px; cursor: pointer; accent-color: #2563eb;">
                <span class="gep-checkmark"></span>
                <span class="gep-label-text" style="flex: 1;"><?php echo esc_html( $strings['agreement'] ); ?></span>
            </label>
        </div>
        <div class="gep-actions" style="display: flex; justify-content: flex-end;">
            <button type="button" id="start-exam-btn" class="gep-btn gep-btn-primary" disabled style="padding: 14px 36px; font-size: 15px; font-weight: 800; border-radius: 12px; background: #2563eb; color: #fff; border: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                <?php echo esc_html( $strings['begin'] ); ?>
            </button>
        </div>
    </div>
</div>
</div>
