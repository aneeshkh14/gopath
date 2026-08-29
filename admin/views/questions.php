<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
global $wpdb;
$admin_questions = new GEP_Admin_Questions();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

$current_page_url = admin_url('admin.php?page=' . sanitize_text_field($_GET['page']));
if (isset($_GET['tab'])) {
    $current_page_url = add_query_arg('tab', sanitize_text_field($_GET['tab']), $current_page_url);
}
?>
<style>
    .tab-content.inactive {
        position: absolute !important;
        left: -99999px !important;
        top: -99999px !important;
        height: 0 !important;
        overflow: hidden !important;
        visibility: hidden !important;
    }
</style>

<div class="wrap gep-admin-wrap">
    <?php 
    if ( isset($_GET['message']) && $_GET['message'] === 'imported' ) {
        $import_result = get_transient( 'gep_import_last_result' );
        if ( is_array($import_result) ) {
            delete_transient( 'gep_import_last_result' );
            ?>
            <div class="gep-import-feedback" style="background: #fff; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #eef2f6;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 18px; color: var(--admin-text);">Intelligence Ingestion Report</h3>
                    <span style="font-size: 12px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase;">Real-time Metrics</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div style="background: #f0fdf4; padding: 20px; border-radius: 16px; border: 1px solid #bbf7d0; text-align: center;">
                        <span style="display: block; font-size: 32px; font-weight: 900; color: #166534;"><?php echo (int) $import_result['inserted']; ?></span>
                        <span style="font-size: 13px; font-weight: 700; color: #166534; text-transform: uppercase;">Assets Inserted</span>
                    </div>
                    <div style="background: #fffbeb; padding: 20px; border-radius: 16px; border: 1px solid #fef3c7; text-align: center;">
                        <span style="display: block; font-size: 32px; font-weight: 900; color: #92400e;"><?php echo (int) $import_result['skipped']; ?></span>
                        <span style="font-size: 13px; font-weight: 700; color: #92400e; text-transform: uppercase;">Assets Skipped</span>
                    </div>
                    <div style="background: #fef2f2; padding: 20px; border-radius: 16px; border: 1px solid #fecaca; text-align: center;">
                        <span style="display: block; font-size: 32px; font-weight: 900; color: #991b1b;"><?php echo count((array) $import_result['errors']); ?></span>
                        <span style="font-size: 13px; font-weight: 700; color: #991b1b; text-transform: uppercase;">Failures Detected</span>
                    </div>
                </div>
                <?php if ( ! empty($import_result['errors']) ) : ?>
                    <div style="margin-top: 20px; padding: 15px; background: #fafafa; border-radius: 12px; border: 1px solid #eee;">
                        <h4 style="margin: 0 0 10px; font-size: 14px; color: #991b1b;">Failure Logs:</h4>
                        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #666;">
                            <?php foreach((array) $import_result['errors'] as $err) echo '<li>'.esc_html($err).'</li>'; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    ?>
    <?php if ( $action === 'import' ) : ?>
        <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 style="margin: 0; font-size: 24px;">Bulk Question Import</h1>
                <p style="color: var(--admin-muted); font-weight: 600; font-size: 13px;">Inject massive datasets into your question bank with AI detection.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="<?php echo admin_url('admin-ajax.php?action=gep_download_import_sample'); ?>" class="button button-primary" style="border-radius: 12px; font-weight: 700; height: 40px; line-height: 40px; padding: 0 20px; background: #10b981; border-color: #10b981;">📥 Download Sample Excel</a>
                <a href="<?php echo esc_url($current_page_url); ?>" class="button" style="border-radius: 12px; font-weight: 700; height: 40px; line-height: 40px; padding: 0 20px;">← Back</a>
            </div>
        </div>

        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('gep_bulk_import', 'gep_import_nonce'); ?>
            
            <div class="gep-admin-console">
                <div class="gep-admin-console-header">
                    <h2 style="margin: 0; font-size: 18px;">1. Ingestion Method</h2>
                    <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">Choose your preferred way to upload questions.</p>
                </div>
                <div class="gep-admin-console-body">
                    <div class="gep-import-tabs" style="margin-bottom: 25px; display: flex; gap: 12px;">
                        <button type="button" class="button button-primary import-tab-btn" data-tab="file-upload" style="height: 45px; border-radius: 12px; flex: 1; font-weight: 800;">📂 Upload Excel/CSV</button>
                        <button type="button" class="button import-tab-btn" data-tab="manual-ingest" style="height: 45px; border-radius: 12px; flex: 1; font-weight: 800;">⌨️ PDF Copy-Paste</button>
                        <button type="button" class="button import-tab-btn" data-tab="pdf-ai-parser" style="height: 45px; border-radius: 12px; flex: 1; font-weight: 800;">📄 AI PDF Ingestor</button>
                    </div>

                    <div id="file-upload" class="import-tab-content">
                        <div style="border: 3px dashed var(--admin-border); padding: 50px; border-radius: 20px; text-align: center; background: #fcfdff; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--admin-primary)'" onmouseout="this.style.borderColor='var(--admin-border)'">
                            <input type="file" name="import_file" id="import_file" style="display: none;" accept=".csv,.xlsx,.xls,.pdf">
                            <label for="import_file" style="cursor: pointer; display: block;">
                                <span style="font-size: 50px; display: block; margin-bottom: 15px;">📊</span>
                                <strong style="font-size: 18px; display: block; color: var(--admin-text);">Click to Upload Question Asset</strong>
                                <span style="color: var(--admin-muted); font-size: 14px; font-weight: 600;">Maximum file size: 32MB • .xlsx, .csv, or .pdf supported</span>
                            </label>
                            <div id="file-name-display" style="margin-top: 20px; font-weight: 800; color: var(--admin-success); display: none;"></div>
                        </div>
                        <div id="gep-file-validation-report" style="display: none; margin-top: 20px; padding: 20px; border-radius: 16px; background: #fafafa; border: 1px solid #e2e8f0; text-align: left;"></div>
                    </div>

                    <div id="manual-ingest" class="import-tab-content" style="display:none;">
                        <label style="display: block; font-weight: 800; font-size: 12px; color: var(--admin-muted); margin-bottom: 12px; text-transform: uppercase;">Direct Text Input</label>
                        <textarea name="manual_text" rows="10" placeholder="1. What is the capital of India?
(A) Mumbai
(B) New Delhi
(C) Kolkata
(D) Chennai
Ans: B" style="width: 100%; font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 14px; line-height: 1.6;"></textarea>
                        <button type="button" id="gep-parse-manual-btn" class="button" style="margin-top: 15px; height: 42px; border-radius: 10px; font-weight: 800; background: var(--admin-primary); color: white; border: none; padding: 0 20px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                            <span>🔍</span> Verify & Preview Questions
                        </button>
                        <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 15px 20px; border-radius: 14px; margin-top: 20px; display: flex; gap: 15px; align-items: center;">
                            <span style="font-size: 24px;">✨</span>
                            <p style="font-size: 14px; color: #92400e; margin: 0; font-weight: 600;"><strong>Smart Detection Active:</strong> Our parser will automatically separate questions, options, and solutions. No complex formatting required.</p>
                        </div>
                    </div>

                    <div id="pdf-ai-parser" class="import-tab-content" style="display:none; position: relative;">
                        <?php if ( ! get_option('gep_ai_unlocked', true) ) : ?>
                            <div class="gep-ai-paywall" style="position: absolute; inset: 0; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(8px); z-index: 1000; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 20px; text-align: center; padding: 40px; border: 2px dashed #cbd5e1;">
                                <div style="font-size: 60px; margin-bottom: 20px;">🔒</div>
                                <h3 style="font-size: 22px; font-weight: 900; color: #1e293b; margin: 0 0 10px;">Premium Sanskrit AI PDF OCR Engine</h3>
                                <p style="color: #64748b; max-width: 420px; font-size: 14px; line-height: 1.6; margin: 0 0 25px; font-weight: 600;">
                                    Automate your workflow! Upload any Sanskrit or bilingual PDF exam sheet, run client-side AI OCR to scan questions and options, and batch-import them directly.
                                </p>
                                <a href="mailto:help@gopath.in?subject=Unlock%20GoPath%20Sanskrit%20AI%20OCR%20License" class="button button-primary" style="height: 48px; line-height: 48px; padding: 0 35px; border-radius: 12px; font-weight: 900; font-size: 15px; background: linear-gradient(135deg, #6366f1, #4f46e5); border: none; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);">
                                    🔑 Unlock AI OCR License
                                </a>
                            </div>
                        <?php endif; ?>
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4.0.2/dist/tesseract.min.js"></script>
                        <div style="border: 3px dashed var(--admin-border); padding: 50px; border-radius: 20px; text-align: center; background: #fcfdff; transition: all 0.3s; position: relative; cursor: pointer;" id="gep-pdf-dropzone" onmouseover="this.style.borderColor='var(--admin-primary)'" onmouseout="this.style.borderColor='var(--admin-border)'">
                            <input type="file" id="gep_pdf_uploader" style="display: none;" accept=".pdf">
                            <label for="gep_pdf_uploader" style="cursor: pointer; display: block;">
                                <span style="font-size: 50px; display: block; margin-bottom: 15px;">📄</span>
                                <strong style="font-size: 18px; display: block; color: var(--admin-text);">Drag & Drop Sanskrit / Bilingual PDF here</strong>
                                <span style="color: var(--admin-muted); font-size: 14px; font-weight: 600; display: block; margin-top: 5px;">Or click to browse from device • Supports all standard exam layouts</span>
                            </label>
                            
                            <div id="gep-pdf-progress-panel" style="display: none; margin-top: 25px;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 10px;">
                                    <div class="gep-pdf-spinner" style="width: 20px; height: 20px; border: 3px solid #eef2f6; border-top-color: var(--admin-primary); border-radius: 50%; animation: gep-spin 1s linear infinite;"></div>
                                    <span style="font-weight: 800; color: var(--admin-text); font-size: 14px;" id="gep-pdf-status-text">Analyzing documents...</span>
                                </div>
                                <div style="background: #eef2f6; height: 8px; border-radius: 10px; overflow: hidden; max-width: 300px; margin: 0 auto;">
                                    <div id="gep-pdf-progress-bar" style="background: var(--admin-primary); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; background: #f8fafc; border: 1px solid #eef2f6; padding: 20px; border-radius: 16px; display: flex; flex-direction: column; gap: 15px; text-align: left; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <strong style="font-size: 14px; color: #1e293b; display: block;">🪄 Enable browser-side AI OCR Engine (Highly Recommended)</strong>
                                    <span style="font-size: 12px; color: #64748b; font-weight: 500; display: block; margin-top: 2px;">Required for image-based PDFs (like NTA Sanskrit response sheets) where text is not directly selectable. Processes entirely in your browser with zero server overhead!</span>
                                </div>
                                <label class="gep-switch" style="position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0;">
                                    <input type="checkbox" id="gep_pdf_use_ocr" checked style="opacity: 0; width: 0; height: 0;">
                                    <span class="slider round" style="position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px;"></span>
                                </label>
                            </div>
                            <div id="gep_ocr_lang_wrapper" style="display: flex; border-top: 1px solid #eef2f6; padding-top: 15px; justify-content: space-between; align-items: center; gap: 15px;">
                                <div>
                                    <strong style="font-size: 13px; color: #1e293b; display: block;">OCR Recognition Language</strong>
                                    <span style="font-size: 11px; color: #64748b; font-weight: 500; display: block; margin-top: 2px;">The appropriate language models will load dynamically from CDN.</span>
                                </div>
                                <select id="gep_pdf_ocr_lang" style="width: 240px; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 700; padding: 0 10px; font-size: 13px; cursor: pointer;">
                                    <option value="hin+eng" selected>Hindi + English (Devanagari)</option>
                                    <option value="san+eng">Sanskrit + English</option>
                                    <option value="eng">English Only</option>
                                </select>
                            </div>
                        </div>

                        <style>
                            @keyframes gep-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                            .gep-switch input:checked + .slider { background-color: var(--admin-primary) !important; }
                            .gep-switch .slider:before {
                                content: "";
                                position: absolute;
                                height: 18px;
                                width: 18px;
                                left: 3px;
                                bottom: 3px;
                                background-color: white;
                                transition: .4s;
                                border-radius: 50%;
                            }
                            .gep-switch input:checked + .slider:before { transform: translateX(20px); }
                        </style>
                    </div>
                </div>
            </div>

            <div class="gep-admin-console">
                <div class="gep-admin-console-header">
                    <h2 style="margin: 0; font-size: 18px;">2. Intelligence Metadata</h2>
                    <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">Categorize these questions for the exam engine.</p>
                </div>
                <div class="gep-admin-console-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Target Subject</label>
                        <select name="category_id" style="width: 100%;" required>
                            <option value="">-- Select Deployment Subject --</option>
                            <?php
                            $category_logic = new GEP_Category();
                            $categories = $category_logic->get_categories(0);
                            $categories = array_filter( $categories, function( $sub ) {
                                return strcasecmp($sub->name, 'ved') !== 0 && strcasecmp($sub->slug, 'ved') !== 0;
                            } );
                            foreach($categories as $cat) {
                                echo '<option value="'.$cat->id.'">'.esc_html($cat->name).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Target Topic (Optional)</label>
                        <input type="text" name="subcategory_name" placeholder="e.g. Algebra, History" style="width: 100%;">
                    </div>
                    <div style="background: #f0fdf4; padding: 20px; border-radius: 16px; border: 1px solid #bbf7d0;">
                        <h4 style="margin: 0 0 5px; color: #166534; font-size: 15px;">💡 Pro Tip</h4>
                        <p style="margin: 0; font-size: 13px; color: #166534; font-weight: 600;">Use our <a href="<?php echo admin_url('admin-ajax.php?action=gep_download_import_sample'); ?>" style="color: #10b981; text-decoration: underline;">Standard Template</a> for 100% success rate in bulk uploads.</p>
                    </div>
                </div>
                <div class="gep-admin-console-footer">
                    <button type="submit" name="submit" class="button button-primary" style="height: 48px; padding: 0 35px; border-radius: 12px; font-weight: 900; font-size: 15px;">🚀 Execute Bulk Import</button>
                </div>
            </div>
        </form>

        <!-- AI Refining Terminal Console -->
        <div id="gep-refining-terminal" style="display: none; margin-top: 35px; background: #fff; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: 0 15px 40px rgba(0,0,0,0.05); overflow: hidden; transition: all 0.4s ease; margin-bottom: 30px;">
            <div style="background: #1e293b; color: #fff; padding: 25px 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0; font-size: 18px; color: #fff;">Intelligence Refining Terminal</h3>
                    <p style="margin: 4px 0 0; color: #94a3b8; font-size: 13px; font-weight: 500;">Directly review, translate, and verify parsed questions before committing them to the database.</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span style="font-size: 13px; font-weight: 800; color: #38bdf8;" id="gep-terminal-count">0 assets parsed</span>
                </div>
            </div>
            
            <div style="padding: 30px; background: #fafbfc; max-height: 600px; overflow-y: auto;" id="gep-terminal-grid">
                <!-- Dynamic question cards injected here -->
            </div>
            
            <div style="background: #f8fafc; padding: 20px 30px; border-top: 1px solid #eef2f6; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 13px; font-weight: 700; color: var(--admin-muted);">
                    Check/uncheck boxes to select which questions to import. Duplicate checks are active.
                </div>
                <button type="button" id="gep-terminal-inject-btn" class="button button-primary" style="height: 48px; padding: 0 35px; border-radius: 12px; font-weight: 900; font-size: 15px; background: #10b981; border-color: #10b981; box-shadow: 0 4px 14px rgba(16,185,129,0.3);">
                    🚀 Commit Verified Assets to Bank
                </button>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.import-tab-btn').click(function() {
                var tab = $(this).data('tab');
                $('.import-tab-content').hide();
                $('#' + tab).show();
                $('.import-tab-btn').removeClass('button-primary');
                $(this).addClass('button-primary');
            });

            $('#gep-parse-manual-btn').click(function() {
                var text = $('textarea[name="manual_text"]').val().trim();
                if (!text) {
                    alert('Please paste some question text first.');
                    return;
                }
                parseExtractedPdfText(text);
            });

            $('#gep_pdf_use_ocr').change(function() {
                $('#gep_ocr_lang_wrapper').css('display', this.checked ? 'flex' : 'none');
            });

            $('#import_file').change(function(e) {
                if (e.target.files.length > 0) {
                    var file = e.target.files[0];
                    var fileName = file.name;
                    $('#file-name-display').text('📄 Ready to Process: ' + fileName).show();

                    var ext = fileName.split('.').pop().toLowerCase();
                    if (['csv', 'xlsx', 'xls'].indexOf(ext) !== -1) {
                        var reportDiv = $('#gep-file-validation-report');
                        reportDiv.html(`
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="gep-pdf-spinner" style="width: 16px; height: 16px; border: 2px solid #eef2f6; border-top-color: var(--admin-primary); border-radius: 50%; animation: gep-spin 1s linear infinite;"></div>
                                <span style="font-weight: 700; color: var(--admin-muted); font-size: 13px;">Analyzing file column alignment...</span>
                            </div>
                        `).show();

                        var formData = new FormData();
                        formData.append('action', 'gep_validate_import_file');
                        formData.append('import_file', file);

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    var data = response.data;
                                    var missingHtml = '';
                                    if (data.missing_required.length > 0) {
                                        missingHtml = `
                                            <div style="background: #fff5f5; border: 1px solid #fed7d7; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px;">
                                                <strong style="color: #c53030; font-size: 13px; display: block; margin-bottom: 4px;">⚠️ Missing Required Columns:</strong>
                                                <span style="color: #9b2c2c; font-size: 12px; font-weight: 500;">
                                                    ${data.missing_required.join(', ')}. Please make sure these are present in your sheet or download the template.
                                                </span>
                                            </div>
                                        `;
                                    } else {
                                        missingHtml = `
                                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                                                <span style="font-size: 16px;">🟢</span>
                                                <strong style="color: #15803d; font-size: 13px;">File alignment 100% correct! All required fields successfully mapped.</strong>
                                            </div>
                                        `;
                                    }

                                    var mappedHtml = '';
                                    Object.keys(data.mapped).forEach(function(key) {
                                        var field = data.mapped[key];
                                        mappedHtml += `
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; margin: 3px;">
                                                ✓ ${field.label} &rarr; <span style="color: #0f172a;">${field.column}</span>
                                            </span>
                                        `;
                                    });

                                    reportDiv.html(`
                                        <h4 style="margin: 0 0 10px; font-size: 15px; color: var(--admin-text); display: flex; justify-content: space-between; align-items: center;">
                                            <span>📊 Spreadsheet Alignment Report</span>
                                            <span style="font-size: 12px; font-weight: 800; background: var(--admin-primary); color: white; padding: 2px 8px; border-radius: 6px;">${data.row_count} Questions Found</span>
                                        </h4>
                                        ${missingHtml}
                                        <div style="margin-top: 10px;">
                                            <strong style="display: block; font-size: 12px; color: var(--admin-muted); margin-bottom: 6px; text-transform: uppercase;">Mapped Fields Checklist:</strong>
                                            <div style="display: flex; flex-wrap: wrap; gap: 2px;">
                                                ${mappedHtml || '<em style="font-size: 12px; color: var(--admin-muted);">No columns mapped.</em>'}
                                            </div>
                                        </div>
                                    `);
                                } else {
                                    reportDiv.html(`
                                        <div style="color: #c53030; font-size: 13px; font-weight: 700;">
                                            ❌ Analysis Failed: ${response.data ? response.data.message : 'Unknown file parsing error.'}
                                        </div>
                                    `);
                                }
                            },
                            error: function() {
                                reportDiv.html(`
                                    <div style="color: #c53030; font-size: 13px; font-weight: 700;">
                                        ❌ Network or server error while validating file structure.
                                    </div>
                                `);
                            }
                        });
                    }
                }
            });

            // PDF.js Integration & Real-time Client-Side Ingestion
            var parsedQuestionsList = [];
            var dropzone = $('#gep-pdf-dropzone');
            var fileInput = $('#gep_pdf_uploader');

            dropzone.on('dragover', function(e) {
                e.preventDefault();
                $(this).css('borderColor', 'var(--admin-primary)');
                $(this).css('background', '#f1f7ff');
            });

            dropzone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).css('borderColor', 'var(--admin-border)');
                $(this).css('background', '#fcfdff');
            });

            dropzone.on('drop', function(e) {
                e.preventDefault();
                $(this).css('borderColor', 'var(--admin-border)');
                $(this).css('background', '#fcfdff');
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0 && files[0].type === 'application/pdf') {
                    processPdfFile(files[0]);
                } else {
                    alert('Please drop a valid PDF file.');
                }
            });

            fileInput.on('change', function(e) {
                if (e.target.files.length > 0) {
                    processPdfFile(e.target.files[0]);
                }
            });

            // Allow dropping anywhere on page if tab is active
            $(document).on('dragover', function(e) {
                e.preventDefault();
            });

            function processPdfFile(file) {
                $('#gep-pdf-progress-panel').fadeIn();
                $('#gep-pdf-status-text').text('Loading PDF Engine...');
                $('#gep-pdf-progress-bar').css('width', '5%');

                var useOcr = $('#gep_pdf_use_ocr').is(':checked');
                var ocrLang = $('#gep_pdf_ocr_lang').val() || 'hin+eng';

                var reader = new FileReader();
                reader.onload = function() {
                    var typedarray = new Uint8Array(this.result);
                    var pdfjsLib = window['pdfjs-dist/build/pdf'];
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

                    pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                        var totalPages = pdf.numPages;
                        var extractedText = '';
                        var pagesProcessed = 0;

                        if (useOcr) {
                            if (typeof Tesseract === 'undefined') {
                                alert('AI OCR Engine (Tesseract.js) is still loading from CDN. Please wait a few seconds and try again.');
                                $('#gep-pdf-progress-panel').fadeOut();
                                return;
                            }
                            $('#gep-pdf-status-text').text('Initializing AI OCR Model (' + ocrLang + ')...');
                            $('#gep-pdf-progress-bar').css('width', '10%');
                        }

                        function loadPage(pageNum) {
                            var progressVal = Math.round((pageNum / totalPages) * 85) + 10;
                            $('#gep-pdf-progress-bar').css('width', progressVal + '%');

                            pdf.getPage(pageNum).then(function(page) {
                                if (useOcr) {
                                    $('#gep-pdf-status-text').text('Rendering page ' + pageNum + ' to high-res canvas...');
                                    
                                    // 2.0x scale for crisp OCR lines
                                    var scale = 2.0;
                                    var viewport = page.getViewport({ scale: scale });
                                    var canvas = document.createElement('canvas');
                                    var context = canvas.getContext('2d');
                                    canvas.height = viewport.height;
                                    canvas.width = viewport.width;

                                    var renderContext = {
                                        canvasContext: context,
                                        viewport: viewport
                                    };

                                    page.render(renderContext).promise.then(function() {
                                        $('#gep-pdf-status-text').text('Running AI OCR on Page ' + pageNum + ' of ' + totalPages + '...');
                                        
                                        Tesseract.recognize(canvas, ocrLang, {
                                            logger: function(m) {
                                                if (m.status === 'recognizing text') {
                                                    var pagePct = Math.round(m.progress * 100);
                                                    $('#gep-pdf-status-text').text('OCR Page ' + pageNum + '/' + totalPages + ': ' + pagePct + '% complete');
                                                }
                                            }
                                        }).then(function(result) {
                                            extractedText += result.data.text + '\n';
                                            pagesProcessed++;
                                            if (pagesProcessed < totalPages) {
                                                loadPage(pagesProcessed + 1);
                                            } else {
                                                finalizeExtraction();
                                            }
                                        }).catch(function(err) {
                                            console.error('OCR Error on Page ' + pageNum + ':', err);
                                            // Fallback to text node extraction for this page
                                            fallbackExtractText(page, pageNum);
                                        });
                                    });
                                } else {
                                    $('#gep-pdf-status-text').text('Extracting Text: Page ' + pageNum + ' of ' + totalPages + '...');
                                    fallbackExtractText(page, pageNum);
                                }
                            });
                        }

                        function fallbackExtractText(page, pageNum) {
                            page.getTextContent().then(function(textContent) {
                                var lastY = -1;
                                var pageText = '';
                                
                                for (var i = 0; i < textContent.items.length; i++) {
                                    var item = textContent.items[i];
                                    if (lastY !== -1 && Math.abs(item.transform[5] - lastY) > 5) {
                                        pageText += '\n';
                                    }
                                    pageText += item.str + ' ';
                                    lastY = item.transform[5];
                                }

                                extractedText += pageText + '\n';
                                pagesProcessed++;

                                if (pagesProcessed < totalPages) {
                                    loadPage(pagesProcessed + 1);
                                } else {
                                    finalizeExtraction();
                                }
                            });
                        }

                        function finalizeExtraction() {
                            $('#gep-pdf-status-text').text('Analyzing patterns & splitting questions...');
                            $('#gep-pdf-progress-bar').css('width', '100%');
                            
                            setTimeout(function() {
                                $('#gep-pdf-progress-panel').fadeOut();
                                parseExtractedPdfText(extractedText);
                            }, 800);
                        }

                        loadPage(1);
                    }).catch(function(err) {
                        alert('Failed to parse PDF document: ' + err.message);
                        $('#gep-pdf-progress-panel').fadeOut();
                    });
                };
                reader.readAsArrayBuffer(file);
            }

            function parseExtractedPdfText(text) {
                text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                text = text.replace(/[ \t]+/g, ' ');

                // Question Splitter Pattern
                var questionBlocks = text.split(/(?=\n\s*(?:Question|Q|प्रश्न|Prashna|)\s*(?:\d+|[०-९]+)[\.\)\-\u0964\s]+)/i);
                
                parsedQuestionsList = [];

                for (var i = 0; i < questionBlocks.length; i++) {
                    var block = questionBlocks[i].trim();
                    if (!block) continue;
                    
                    var parsed = parseSingleBlock(block);
                    if (parsed) {
                        parsedQuestionsList.push(parsed);
                    }
                }

                $('#gep-pdf-progress-bar').css('width', '100%');
                $('#gep-pdf-status-text').text('Extraction success!');
                
                setTimeout(function() {
                    $('#gep-pdf-progress-panel').fadeOut();
                    renderRefiningTerminal();
                }, 1000);
            }

            function parseSingleBlock(block) {
                var title = block;
                var option_a = '';
                var option_b = '';
                var option_c = '';
                var option_d = '';
                var option_e = '';
                var correct_answer = 'A';
                var explanation = '';
                var question_hi = '';
                var option_a_hi = '';
                var option_b_hi = '';
                var option_c_hi = '';
                var option_d_hi = '';
                var option_e_hi = '';
                var explanation_hi = '';
                var translation_enabled = 0;

                // 1. Correct Answer Key (supports standard Ans/Key, Devanagari, and candidate response sheet Chosen Option)
                var ansMatch = block.match(/(?:Ans|Answer|Correct|Key|उत्तर|उत्तरम्|Chosen\s+Option)[\s\:\.\-ऊ]*(A|B|C|D|E|1|2|3|4|5|अ|ब|स|द|इ|क|ख|ग|घ|a|b|c|d|e)/i);
                if (ansMatch) {
                    var rawAns = ansMatch[1].toUpperCase();
                    if (rawAns === '1' || rawAns === 'अ' || rawAns === 'क' || rawAns === 'A') correct_answer = 'A';
                    else if (rawAns === '2' || rawAns === 'ब' || rawAns === 'ख' || rawAns === 'B') correct_answer = 'B';
                    else if (rawAns === '3' || rawAns === 'स' || rawAns === 'ग' || rawAns === 'C') correct_answer = 'C';
                    else if (rawAns === '4' || rawAns === 'द' || rawAns === 'घ' || rawAns === 'D') correct_answer = 'D';
                    else if (rawAns === '5' || rawAns === 'इ' || rawAns === 'ङ' || rawAns === 'E') correct_answer = 'E';
                    title = title.replace(ansMatch[0], '');
                }

                // 2. Explanation
                var expMatch = block.match(/(?:Expl|Explanation|व्याख्या)[\s\:\.\-]*(.*)/is);
                if (expMatch) {
                    explanation = expMatch[1].trim();
                    title = title.replace(expMatch[0], '');
                }

                // 3. Options Extraction
                var optA = title.match(/(?:^|[\s\n])[\(\[]?(?:A|1|अ|क|a)[\)\]\.\-\s]+(.*?)(?=(?:\n|[\s　])[\(\[]?(?:B|2|ब|ख|b)[\)\]\.\-\s]|$)/is);
                var optB = title.match(/(?:^|[\s\n])[\(\[]?(?:B|2|ब|ख|b)[\)\]\.\-\s]+(.*?)(?=(?:\n|[\s　])[\(\[]?(?:C|3|स|ग|c)[\)\]\.\-\s]|$)/is);
                var optC = title.match(/(?:^|[\s\n])[\(\[]?(?:C|3|स|ग|c)[\)\]\.\-\s]+(.*?)(?=(?:\n|[\s　])[\(\[]?(?:D|4|द|घ|d)[\)\]\.\-\s]|$)/is);
                var optD = title.match(/(?:^|[\s\n])[\(\[]?(?:D|4|द|घ|d)[\)\]\.\-\s]+(.*?)(?=(?:\n|[\s　])[\(\[]?(?:E|5|इ|ङ|e)[\)\]\.\-\s]|$)/is);
                var optE = title.match(/(?:^|[\s\n])[\(\[]?(?:E|5|इ|ङ|e)[\)\]\.\-\s]+(.*?)(?=(?:\n|[\s　])(?:Ans|Answer|Correct|उत्तर|व्याख्या|Expl|Chosen)|$)/is);

                if (optA) { option_a = optA[1].trim(); title = title.replace(optA[0], ''); }
                if (optB) { option_b = optB[1].trim(); title = title.replace(optB[0], ''); }
                if (optC) { option_c = optC[1].trim(); title = title.replace(optC[0], ''); }
                if (optD) { option_d = optD[1].trim(); title = title.replace(optD[0], ''); }
                if (optE) { option_e = optE[1].trim(); title = title.replace(optE[0], ''); }

                // Clean title from leading numbers and NTA prefixes (like Q.51, Question 51, etc.)
                title = title.replace(/^\s*(?:Question|Q|प्रश्न|Prashna|)\s*[\.\-\s]*(?:\d+|[०-९]+)[\.\)\-\u0964\s]*/i, '').trim();

                // 4. Sanskrit / Bilingual Parsing
                var hasDevanagari = /[\u0900-\u097F]/.test(block);
                if (hasDevanagari) {
                    translation_enabled = 1;
                    
                    var titleParts = title.split(/\s*[\/|।]\s*/);
                    if (titleParts.length > 1) {
                        title = titleParts[0].trim();
                        question_hi = titleParts[1].trim();
                    } else {
                        question_hi = title;
                    }

                    var optAParts = option_a.split(/\s*[\/|]\s*/);
                    if (optAParts.length > 1) { option_a = optAParts[0].trim(); option_a_hi = optAParts[1].trim(); }
                    else { option_a_hi = option_a; }

                    var optBParts = option_b.split(/\s*[\/|]\s*/);
                    if (optBParts.length > 1) { option_b = optBParts[0].trim(); option_b_hi = optBParts[1].trim(); }
                    else { option_b_hi = option_b; }

                    var optCParts = option_c.split(/\s*[\/|]\s*/);
                    if (optCParts.length > 1) { option_c = optCParts[0].trim(); option_c_hi = optCParts[1].trim(); }
                    else { option_c_hi = option_c; }

                    var optDParts = option_d.split(/\s*[\/|]\s*/);
                    if (optDParts.length > 1) { option_d = optDParts[0].trim(); option_d_hi = optDParts[1].trim(); }
                    else { option_d_hi = option_d; }

                    var optEParts = option_e.split(/\s*[\/|]\s*/);
                    if (optEParts.length > 1) { option_e = optEParts[0].trim(); option_e_hi = optEParts[1].trim(); }
                    else { option_e_hi = option_e; }

                    if (explanation) {
                        var expParts = explanation.split(/\s*[\/|।]\s*/);
                        if (expParts.length > 1) {
                            explanation = expParts[0].trim();
                            explanation_hi = expParts[1].trim();
                        } else {
                            explanation_hi = explanation;
                        }
                    }
                }

                if (!title) return null;

                return {
                    title: title,
                    question_type: (option_a || option_b) ? 'mcq' : 'short_answer',
                    option_a: option_a,
                    option_b: option_b,
                    option_c: option_c,
                    option_d: option_d,
                    option_e: option_e,
                    correct_answer: correct_answer,
                    explanation: explanation,
                    translation_enabled: translation_enabled,
                    question_hi: question_hi,
                    option_a_hi: option_a_hi,
                    option_b_hi: option_b_hi,
                    option_c_hi: option_c_hi,
                    option_d_hi: option_d_hi,
                    option_e_hi: option_e_hi,
                    explanation_hi: explanation_hi
                };
            }

            function renderRefiningTerminal() {
                var grid = $('#gep-terminal-grid');
                grid.empty();
                $('#gep-terminal-count').text(parsedQuestionsList.length + ' intelligence assets parsed');

                parsedQuestionsList.forEach(function(q, idx) {
                    var card = $(`
                        <div class="gep-terminal-card" style="background: #fff; border-radius: 16px; border: 1px solid #eef2f6; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: all 0.2s;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" class="gep-terminal-select" checked style="width: 18px; height: 18px; cursor: pointer; margin: 0;">
                                    <strong style="color: var(--admin-primary); font-size: 14px;">Question #${idx + 1}</strong>
                                </div>
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <label style="font-size: 11px; font-weight: 800; color: var(--admin-muted); display: flex; align-items: center; gap: 6px; text-transform: uppercase; margin: 0; cursor: pointer;">
                                        Translation
                                        <input type="checkbox" class="gep-terminal-hi-toggle" ${q.translation_enabled ? 'checked' : ''} style="margin: 0; cursor: pointer;">
                                    </label>
                                    <button type="button" class="gep-terminal-delete" style="background: none; border: none; font-size: 16px; cursor: pointer; padding: 0;" title="Remove Question">🗑️</button>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 6px; text-transform: uppercase;">Question Text (English)</label>
                                <textarea class="gep-terminal-title" rows="2" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 700; padding: 10px 12px; font-size: 14px; line-height: 1.5; color: #1e293b;">${q.title}</textarea>
                            </div>
                            
                            <div class="gep-terminal-hi-section" style="margin-bottom: 15px; display: ${q.translation_enabled ? 'block' : 'none'};">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: #7c3aed; margin-bottom: 6px; text-transform: uppercase;">Question Text (Hindi/Sanskrit)</label>
                                <textarea class="gep-terminal-title-hi" rows="2" style="width: 100%; border-radius: 8px; border: 1px solid #ddd6fe; font-weight: 700; padding: 10px 12px; background: #faf5ff; font-size: 14px; line-height: 1.5; color: #1e293b;">${q.question_hi}</textarea>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 10px; color: var(--admin-muted); margin-bottom: 4px;">OPTION A (EN)</label>
                                    <input type="text" class="gep-terminal-opt-a" value="${q.option_a}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <div class="gep-terminal-hi-section" style="display: ${q.translation_enabled ? 'block' : 'none'}; margin-top: 6px;">
                                        <input type="text" class="gep-terminal-opt-a-hi" value="${q.option_a_hi}" style="width: 100%; background: #faf5ff; border-color: #ddd6fe; padding: 8px 12px; border-radius: 6px; border-style: solid; border-width: 1px;" placeholder="Option A (Hindi)">
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 10px; color: var(--admin-muted); margin-bottom: 4px;">OPTION B (EN)</label>
                                    <input type="text" class="gep-terminal-opt-b" value="${q.option_b}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <div class="gep-terminal-hi-section" style="display: ${q.translation_enabled ? 'block' : 'none'}; margin-top: 6px;">
                                        <input type="text" class="gep-terminal-opt-b-hi" value="${q.option_b_hi}" style="width: 100%; background: #faf5ff; border-color: #ddd6fe; padding: 8px 12px; border-radius: 6px; border-style: solid; border-width: 1px;" placeholder="Option B (Hindi)">
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 10px; color: var(--admin-muted); margin-bottom: 4px;">OPTION C (EN)</label>
                                    <input type="text" class="gep-terminal-opt-c" value="${q.option_c}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <div class="gep-terminal-hi-section" style="display: ${q.translation_enabled ? 'block' : 'none'}; margin-top: 6px;">
                                        <input type="text" class="gep-terminal-opt-c-hi" value="${q.option_c_hi}" style="width: 100%; background: #faf5ff; border-color: #ddd6fe; padding: 8px 12px; border-radius: 6px; border-style: solid; border-width: 1px;" placeholder="Option C (Hindi)">
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 10px; color: var(--admin-muted); margin-bottom: 4px;">OPTION D (EN)</label>
                                    <input type="text" class="gep-terminal-opt-d" value="${q.option_d}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <div class="gep-terminal-hi-section" style="display: ${q.translation_enabled ? 'block' : 'none'}; margin-top: 6px;">
                                        <input type="text" class="gep-terminal-opt-d-hi" value="${q.option_d_hi}" style="width: 100%; background: #faf5ff; border-color: #ddd6fe; padding: 8px 12px; border-radius: 6px; border-style: solid; border-width: 1px;" placeholder="Option D (Hindi)">
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 10px; color: var(--admin-muted); margin-bottom: 4px;">OPTION E (EN - OPTIONAL)</label>
                                    <input type="text" class="gep-terminal-opt-e" value="${q.option_e || ''}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <div class="gep-terminal-hi-section" style="display: ${q.translation_enabled ? 'block' : 'none'}; margin-top: 6px;">
                                        <input type="text" class="gep-terminal-opt-e-hi" value="${q.option_e_hi || ''}" style="width: 100%; background: #faf5ff; border-color: #ddd6fe; padding: 8px 12px; border-radius: 6px; border-style: solid; border-width: 1px;" placeholder="Option E (Hindi)">
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 10px; color: var(--admin-muted); margin-bottom: 4px;">CORRECT KEY</label>
                                    <select class="gep-terminal-correct" style="width: 100%; font-weight: 800; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; height: 38px;">
                                        <option value="A" ${q.correct_answer === 'A' ? 'selected' : ''}>Option A</option>
                                        <option value="B" ${q.correct_answer === 'B' ? 'selected' : ''}>Option B</option>
                                        <option value="C" ${q.correct_answer === 'C' ? 'selected' : ''}>Option C</option>
                                        <option value="D" ${q.correct_answer === 'D' ? 'selected' : ''}>Option D</option>
                                        <option value="E" ${q.correct_answer === 'E' ? 'selected' : ''}>Option E</option>
                                    </select>
                                </div>
                                <div style="display:none;">
                                    <label style="display: block; font-weight: 800; font-size: 10px; color: var(--admin-muted); margin-bottom: 4px;">MARKS</label>
                                    <input type="number" class="gep-terminal-marks" value="1.0" step="0.5" style="width: 100%; font-weight: 800; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; height: 38px;">
                                </div>
                                <div style="display:none;">
                                    <label style="display: block; font-weight: 800; font-size: 10px; color: var(--admin-muted); margin-bottom: 4px;">NEGATIVE PENALTY</label>
                                    <input type="number" class="gep-terminal-negative" value="0.00" step="0.05" style="width: 100%; font-weight: 800; color: var(--admin-danger); padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; height: 38px;">
                                </div>
                            </div>
                        </div>
                    `);

                    card.find('.gep-terminal-select').change(function() {
                        card.css('opacity', this.checked ? '1' : '0.5');
                    });

                    card.find('.gep-terminal-hi-toggle').change(function() {
                        card.find('.gep-terminal-hi-section').toggle(this.checked);
                    });

                    card.find('.gep-terminal-delete').click(function() {
                        if (confirm('Are you sure you want to discard this question?')) {
                            card.slideUp(200, function() {
                                card.remove();
                                updateTerminalHeaderCount();
                            });
                        }
                    });

                    grid.append(card);
                });

                $('#gep-refining-terminal').slideDown(400);
            }

            function updateTerminalHeaderCount() {
                var activeCount = $('#gep-terminal-grid .gep-terminal-card').length;
                $('#gep-terminal-count').text(activeCount + ' intelligence assets parsed');
                if (activeCount === 0) {
                    $('#gep-refining-terminal').slideUp(200);
                }
            }

            // Commit Verified Questions via Batch AJAX Ingestion
            $('#gep-terminal-inject-btn').click(function() {
                var btn = $(this);
                var questionsToSave = [];
                var selectedCat = $('select[name="category_id"]').val();
                var selectedSubCat = $('select[name="subcategory_id"]').val();

                if (!selectedCat) {
                    alert('Please select a Target Deployment Subject in Section 2 first!');
                    return;
                }

                $('#gep-terminal-grid .gep-terminal-card').each(function() {
                    var card = $(this);
                    var isSelected = card.find('.gep-terminal-select').prop('checked');
                    
                    if (isSelected) {
                        var questionData = {
                            title: card.find('.gep-terminal-title').val().trim(),
                            question_hi: card.find('.gep-terminal-title-hi').val().trim(),
                            option_a: card.find('.gep-terminal-opt-a').val().trim(),
                            option_a_hi: card.find('.gep-terminal-opt-a-hi').val().trim(),
                            option_b: card.find('.gep-terminal-opt-b').val().trim(),
                            option_b_hi: card.find('.gep-terminal-opt-b-hi').val().trim(),
                            option_c: card.find('.gep-terminal-opt-c').val().trim(),
                            option_c_hi: card.find('.gep-terminal-opt-c-hi').val().trim(),
                            option_d: card.find('.gep-terminal-opt-d').val().trim(),
                            option_d_hi: card.find('.gep-terminal-opt-d-hi').val().trim(),
                            option_e: card.find('.gep-terminal-opt-e').length ? card.find('.gep-terminal-opt-e').val().trim() : '',
                            option_e_hi: card.find('.gep-terminal-opt-e-hi').length ? card.find('.gep-terminal-opt-e-hi').val().trim() : '',
                            correct_answer: card.find('.gep-terminal-correct').val(),
                            marks: parseFloat(card.find('.gep-terminal-marks').val()) || 1.0,
                            negative_marks: parseFloat(card.find('.gep-terminal-negative').val()) || 0.0,
                            translation_enabled: card.find('.gep-terminal-hi-toggle').prop('checked') ? 1 : 0,
                            category_id: selectedCat,
                            subcategory_id: selectedSubCat || 0
                        };
                        questionsToSave.push(questionData);
                    }
                });

                if (questionsToSave.length === 0) {
                    alert('Please select at least one question card to import!');
                    return;
                }

                btn.prop('disabled', true).text('Ingesting Verified Assets...');

                $.post(ajaxurl, {
                    action: 'gep_bulk_save_parsed_questions',
                    nonce: $('input[name="gep_import_nonce"]').val(),
                    questions: JSON.stringify(questionsToSave)
                }, function(response) {
                    if (response.success) {
                        alert('SUCCESS: ' + response.data.inserted + ' questions injected successfully into the database! (' + response.data.skipped + ' duplicates skipped)');
                        window.location.href = window.location.href.replace('&action=import', '');
                    } else {
                        alert('Ingestion error: ' + (response.data || 'Unknown server error'));
                        btn.prop('disabled', false).text('Commit Verified Assets to Bank');
                    }
                }).fail(function() {
                    alert('Connection timeout or server error during bulk injection.');
                    btn.prop('disabled', false).text('Commit Verified Assets to Bank');
                });
            });

            // Dynamic Answer Controller (Import Fallback)
            $('#gep_question_type').change(function() {
                var type = $(this).val();
                var wrapper = $('#gep_ans_wrapper');
                if (type === 'short_answer') {
                    wrapper.html('<input type="text" name="correct_answer" placeholder="Enter verified answer..." style="width: 100%; font-weight: 900;">');
                } else {
                    wrapper.html('<select name="correct_answer" style="width: 100%;"><option value="A">Asset A (Correct)</option><option value="B">Asset B (Correct)</option><option value="C">Asset C (Correct)</option><option value="D">Asset D (Correct)</option></select>');
                }
            });
        });
        </script>

    <?php elseif ( $action === 'add' || $action === 'edit' ) : 
        global $wpdb;
        $q = null;
        $passage_text = '';
        $passage_text_hi = '';
        if ( $action === 'edit' && isset($_GET['id']) ) {
            $table = $wpdb->prefix . 'gep_questions';
            $q = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", absint($_GET['id']) ) );
            $translated = gep_safe_json_decode($q->translated_data, true);
            if ( $q && $q->passage_id > 0 ) {
                $parent_passage = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $q->passage_id ) );
                if ( $parent_passage ) {
                    $passage_text = $parent_passage->title;
                    if ( ! empty( $parent_passage->translated_data ) ) {
                        $parent_trans = gep_safe_json_decode( $parent_passage->translated_data, true );
                        if ( isset( $parent_trans['title'] ) ) {
                            $passage_text_hi = $parent_trans['title'];
                        }
                    }
                }
            } elseif ( $q && $q->question_type === 'passage' ) {
                $passage_text = $q->title;
                if ( ! empty( $q->translated_data ) ) {
                    $parent_trans = gep_safe_json_decode( $q->translated_data, true );
                    if ( isset( $parent_trans['title'] ) ) {
                        $passage_text_hi = $parent_trans['title'];
                    }
                }
                $q->title = '';
                if ( isset( $translated['title'] ) ) {
                    $translated['title'] = '';
                }
            }
        }
    ?>
        <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0;"><?php echo $action === 'add' ? 'Manual Question Authoring' : 'Refine Intelligence Asset'; ?></h1>
                <p style="color: var(--admin-muted); font-weight: 600;">Author multi-lingual questions with rich text and instant preview.</p>
            </div>
            <a href="<?php echo esc_url($current_page_url); ?>" class="button" style="border-radius: 12px; font-weight: 700; height: 45px; line-height: 45px; padding: 0 25px;">← Back to Bank</a>
        </div>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field('gep_question_save', 'gep_question_nonce'); ?>
            <input type="hidden" name="action" value="gep_save_question">
            <?php if ($q) : ?><input type="hidden" name="question_id" value="<?php echo $q->id; ?>"><?php endif; ?>


            <?php if ( isset( $_GET['passage_mode'] ) && $_GET['passage_mode'] == '1' ) : ?>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 16px; padding: 20px; margin-bottom: 25px; color: #1e3a8a;">
                    <h3 style="margin: 0 0 10px; font-weight: 850; font-size: 15px; display: flex; align-items: center; gap: 8px;">📖 Passage Authoring Mode</h3>
                    <p style="margin: 0; font-weight: 600; font-size: 13px; line-height: 1.6;">
                        1. Paste or type the entire passage text in the editor below (English in Main tab, Hindi/Sanskrit translation in Translation tab).<br>
                        2. Make sure you select <strong>Passage (Comprehension)</strong> as the Question Type in the settings panel below.<br>
                        3. Once saved, this passage will appear in the "Passage (Comprehension)" selector dropdown when you add normal questions (MCQs), allowing you to link questions to it.
                    </p>
                </div>
            <?php endif; ?>

            <div class="gep-admin-console">
                <div class="gep-admin-console-header" style="padding-bottom: 0;">
                    <div class="gep-tabs" style="display: flex; gap: 5px;">
                        <button type="button" class="button button-primary tab-btn active" data-tab="english" style="height: 45px; border-bottom-left-radius: 0; border-bottom-right-radius: 0; padding: 0 30px; font-weight: 800;">MAIN QUESTION</button>
                        <button type="button" class="button tab-btn" data-tab="hindi" style="height: 45px; border-bottom-left-radius: 0; border-bottom-right-radius: 0; padding: 0 30px; font-weight: 800;">ADD TRANSLATION (EN/HI)</button>
                    </div>
                </div>
                <div class="gep-admin-console-body">
                    <div id="english" class="tab-content">
                        <!-- Passage Editor English -->
                        <div id="gep-passage-text-wrap-en" style="margin-bottom: 30px; display: <?php echo ($q && ($q->passage_id > 0 || $q->question_type === 'passage')) ? 'block' : 'none'; ?>;">
                            <label style="display: block; font-weight: 800; font-size: 12px; color: var(--admin-primary); margin-bottom: 8px; text-transform: uppercase;">Passage Text (English)</label>
                            <span style="display: block; font-size: 11px; color: var(--admin-muted); font-weight: 600; margin-bottom: 8px;">Paste or write the comprehension passage text here.</span>
                            <textarea name="passage_text" id="gep_passage_text_en" rows="6" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px; font-size: 14px; font-family: inherit;"><?php echo esc_textarea($passage_text); ?></textarea>
                        </div>

                        <div style="margin-bottom: 30px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <label style="font-weight: 800; font-size: 12px; color: var(--admin-muted); text-transform: uppercase;">Core Question Content</label>
                                    <span style="font-size: 11px; color: var(--admin-muted); font-weight: 600;">The "Intellectual Brain" of the asset. Define the core query and extraction logic.</span>
                                </div>
                                <button type="button" id="gep-smart-parse" class="gep-magic-btn">
                                    <span>✨</span> AUTO-EXTRACT OPTIONS
                                </button>
                            </div>
                            <?php wp_editor($q ? $q->title : '', 'question_title', array(
                                'textarea_rows' => 8,
                                'quicktags'     => true,
                                'tinymce'       => array(
                                    'block_formats'     => 'Paragraph=p;Heading 2=h2;Heading 3=h3;Preformatted=pre',
                                    'toolbar1'          => 'formatselect bold italic underline strikethrough superscript subscript bullist numlist blockquote hr alignleft aligncenter alignright link unlink image charmap undo redo',
                                    'toolbar2'          => '',
                                    'body_class'        => 'gep-editor-body',
                                    'content_style'     => 'body { font-family: Georgia, serif; font-size: 14px; line-height: 1.6; }',
                                ),
                            )); ?>
                            <div id="smart-parse-feedback" style="margin-top: 10px; display: none;"></div>
                        </div>
                        
                        <div id="gep-options-grid-en" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                            <div>
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Option A Asset</label>
                                <div class="gep-option-input-wrapper">
                                    <input type="text" name="option_a" value="<?php echo $q ? esc_attr($q->option_a) : ''; ?>" placeholder="Enter text or URL">
                                    <span class="gep-option-icon">A</span>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Option B Asset</label>
                                <div class="gep-option-input-wrapper">
                                    <input type="text" name="option_b" value="<?php echo $q ? esc_attr($q->option_b) : ''; ?>" placeholder="Enter text or URL">
                                    <span class="gep-option-icon">B</span>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Option C Asset</label>
                                <div class="gep-option-input-wrapper">
                                    <input type="text" name="option_c" value="<?php echo $q ? esc_attr($q->option_c) : ''; ?>" placeholder="Enter text or URL">
                                    <span class="gep-option-icon">C</span>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Option D Asset</label>
                                <div class="gep-option-input-wrapper">
                                    <input type="text" name="option_d" value="<?php echo $q ? esc_attr($q->option_d) : ''; ?>" placeholder="Enter text or URL">
                                    <span class="gep-option-icon">D</span>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Option E Asset (Optional)</label>
                                <div class="gep-option-input-wrapper">
                                    <input type="text" name="option_e" value="<?php echo $q ? esc_attr($q->option_e) : ''; ?>" placeholder="Enter text or URL">
                                    <span class="gep-option-icon">E</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="hindi" class="tab-content inactive">
                        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid var(--admin-border); margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <h3 style="margin: 0; font-size: 16px;">Multi-lingual Support</h3>
                                <p style="margin: 5px 0 0; color: var(--admin-muted); font-size: 13px; font-weight: 600;">Enable this to show the question in Hindi to students.</p>
                            </div>
                            <label class="gep-switch">
                                <input type="checkbox" name="translation_enabled" value="1" <?php if($q && $q->translation_enabled) echo 'checked'; ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div id="gep-translation-fields-wrapper" style="<?php echo ($q && $q->translation_enabled) ? '' : 'display: none;'; ?>">
                            <!-- Passage Editor Hindi -->
                            <div id="gep-passage-text-wrap-hi" style="margin-bottom: 25px; display: <?php echo ($q && ($q->passage_id > 0 || $q->question_type === 'passage')) ? 'block' : 'none'; ?>;">
                                <label style="display: block; font-weight: 800; font-size: 12px; color: #7c3aed; margin-bottom: 8px; text-transform: uppercase;">Passage Text (Translation)</label>
                                <textarea name="passage_text_hi" id="gep_passage_text_hi" rows="6" style="width: 100%; border-radius: 8px; border: 1px solid #ddd6fe; padding: 10px; font-size: 14px; font-family: inherit;"><?php echo esc_textarea($passage_text_hi); ?></textarea>
                            </div>
                            <?php wp_editor($q && isset($translated['title']) ? $translated['title'] : '', 'question_hi', array(
                                'textarea_rows' => 10,
                                'quicktags'     => true,
                                'tinymce'       => array(
                                    'block_formats' => 'Paragraph=p;Heading 2=h2;Heading 3=h3;Preformatted=pre',
                                    'toolbar1'      => 'formatselect bold italic underline superscript subscript bullist numlist hr alignleft aligncenter alignright link charmap undo redo',
                                    'toolbar2'      => '',
                                    'content_style' => 'body { font-family: Georgia, serif; font-size: 14px; line-height: 1.6; }',
                                ),
                            )); ?>
                            
                            <div id="gep-options-grid-hi" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 25px;">
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: #7c3aed; margin-bottom: 10px; text-transform: uppercase;">Option A (Translation)</label>
                                    <div class="gep-option-input-wrapper" style="border-color: #ddd6fe;">
                                        <input type="text" name="option_a_hi" value="<?php echo $q && isset($translated['option_a']) ? esc_attr($translated['option_a']) : ''; ?>" placeholder="Enter translated text">
                                        <span class="gep-option-icon" style="background: #7c3aed;">A</span>
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: #7c3aed; margin-bottom: 10px; text-transform: uppercase;">Option B (Translation)</label>
                                    <div class="gep-option-input-wrapper" style="border-color: #ddd6fe;">
                                        <input type="text" name="option_b_hi" value="<?php echo $q && isset($translated['option_b']) ? esc_attr($translated['option_b']) : ''; ?>" placeholder="Enter translated text">
                                        <span class="gep-option-icon" style="background: #7c3aed;">B</span>
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: #7c3aed; margin-bottom: 10px; text-transform: uppercase;">Option C (Translation)</label>
                                    <div class="gep-option-input-wrapper" style="border-color: #ddd6fe;">
                                        <input type="text" name="option_c_hi" value="<?php echo $q && isset($translated['option_c']) ? esc_attr($translated['option_c']) : ''; ?>" placeholder="Enter translated text">
                                        <span class="gep-option-icon" style="background: #7c3aed;">C</span>
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: #7c3aed; margin-bottom: 10px; text-transform: uppercase;">Option D (Translation)</label>
                                    <div class="gep-option-input-wrapper" style="border-color: #ddd6fe;">
                                        <input type="text" name="option_d_hi" value="<?php echo $q && isset($translated['option_d']) ? esc_attr($translated['option_d']) : ''; ?>" placeholder="Enter translated text">
                                        <span class="gep-option-icon" style="background: #7c3aed;">D</span>
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: #7c3aed; margin-bottom: 10px; text-transform: uppercase;">Option E (Translation - Optional)</label>
                                    <div class="gep-option-input-wrapper" style="border-color: #ddd6fe;">
                                        <input type="text" name="option_e_hi" value="<?php echo $q && isset($translated['option_e']) ? esc_attr($translated['option_e']) : ''; ?>" placeholder="Enter translated text">
                                        <span class="gep-option-icon" style="background: #7c3aed;">E</span>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 25px;">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: #7c3aed; margin-bottom: 12px; text-transform: uppercase;">Solution Explanation (Hindi)</label>
                                <?php wp_editor($q && isset($translated['explanation']) ? $translated['explanation'] : '', 'explanation_hi', array(
                                    'textarea_rows' => 5,
                                    'tinymce'       => array(
                                        'block_formats' => 'Paragraph=p;Heading 2=h2;Preformatted=pre',
                                        'toolbar1'      => 'formatselect bold italic underline superscript subscript bullist numlist hr link charmap undo redo',
                                        'toolbar2'      => '',
                                        'content_style' => 'body { font-family: Georgia, serif; font-size: 14px; line-height: 1.6; }',
                                    ),
                                )); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gep-admin-console">
                <div class="gep-admin-console-header">
                    <h2 style="margin: 0; font-size: 18px;">Logic & Scoring Engine</h2>
                    <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">The "Evaluation Engine". Configure precise scoring, penalties, and subject classification.</p>
                </div>
                <div class="gep-admin-console-body" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Question Type</label>
                        <select name="question_type" id="gep_question_type" style="width: 100%;">
                            <option value="mcq" <?php if(($q && $q->question_type == 'mcq') || (! $q && ! isset($_GET['passage_mode']))) echo 'selected'; ?>>MCQ — Single Choice (A-D)</option>
                            <option value="multi_select" <?php if($q && in_array($q->question_type, ['multi_select','msq'])) echo 'selected'; ?>>Multi-Select (MSQ)</option>
                            <option value="numerical" <?php if($q && $q->question_type == 'numerical') echo 'selected'; ?>>Numerical — NTA Style</option>
                            <option value="true_false" <?php if($q && $q->question_type == 'true_false') echo 'selected'; ?>>True / False</option>
                            <option value="assertion_reason" <?php if($q && $q->question_type == 'assertion_reason') echo 'selected'; ?>>Assertion-Reason</option>
                            <option value="matching" <?php if($q && $q->question_type == 'matching') echo 'selected'; ?>>Match the Following</option>
                            <option value="short_answer" <?php if($q && $q->question_type == 'short_answer') echo 'selected'; ?>>Short Answer / Fill-In</option>
                            <option value="passage" <?php if(($q && ($q->question_type == 'passage' || $q->passage_id > 0)) || (isset($_GET['passage_mode']) && $_GET['passage_mode'] == '1')) echo 'selected'; ?>>Passage (Comprehension)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Sub Topic</label>
                        <input type="text" name="difficulty" value="<?php echo $q ? esc_attr($q->difficulty) : ''; ?>" placeholder="e.g. Rigveda, Grammar" style="width: 100%; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-weight: 700; font-size: 13px;">
                    </div>
                    <div class="gep-passage-link-wrap">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-primary); margin-bottom: 8px; text-transform: uppercase;">Link to Passage (Optional)</label>
                        <select name="passage_id" style="width: 100%;">
                            <option value="0">-- No Passage --</option>
                            <?php
                            // Fetch all passages
                            $passages = $wpdb->get_results("SELECT id, LEFT(title, 50) as title_snippet FROM {$wpdb->prefix}gep_questions WHERE question_type = 'passage' ORDER BY id DESC LIMIT 100");
                            foreach($passages as $p) {
                                $selected = ($q && isset($q->passage_id) && $q->passage_id == $p->id) ? 'selected' : '';
                                echo '<option value="'.esc_attr($p->id).'" '.$selected.'>#'.esc_html($p->id).' - '.esc_html(strip_tags($p->title_snippet)).'...</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div id="gep-correct-answer-wrap">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Correct Answer</label>
                        <div id="gep_ans_wrapper">
                            <?php 
                            $qt = $q ? $q->question_type : 'mcq';
                            if ($qt === 'short_answer' || $qt === 'numerical') : ?>
                                <input type="text" name="correct_answer" value="<?php echo esc_attr($q ? $q->correct_answer : ''); ?>" placeholder="<?php echo $qt === 'numerical' ? 'Enter exact numerical value e.g. 3.14' : 'Enter text answer'; ?>" style="width: 100%; font-weight: 900;">
                            <?php elseif ($qt === 'multi_select' || $qt === 'msq') : ?>
                                <input type="text" name="correct_answer" value="<?php echo esc_attr($q ? $q->correct_answer : ''); ?>" placeholder="e.g. A,C or B,D" style="width: 100%; font-weight: 900;" title="Comma-separated correct options">
                                <small style="color:#6366f1;font-weight:600;">Comma-separated e.g. A,C</small>
                            <?php elseif ($qt === 'true_false') : ?>
                                <select name="correct_answer" style="width: 100%;">
                                    <option value="True" <?php selected($q ? $q->correct_answer : '', 'True'); ?>>True</option>
                                    <option value="False" <?php selected($q ? $q->correct_answer : '', 'False'); ?>>False</option>
                                </select>
                            <?php else : ?>
                                <select name="correct_answer" style="width: 100%;">
                                    <option value="A" <?php if($q && $q->correct_answer == 'A') echo 'selected'; ?>>Option A (Correct)</option>
                                    <option value="B" <?php if($q && $q->correct_answer == 'B') echo 'selected'; ?>>Option B (Correct)</option>
                                    <option value="C" <?php if($q && $q->correct_answer == 'C') echo 'selected'; ?>>Option C (Correct)</option>
                                    <option value="D" <?php if($q && $q->correct_answer == 'D') echo 'selected'; ?>>Option D (Correct)</option>
                                    <option value="E" <?php if($q && $q->correct_answer == 'E') echo 'selected'; ?>>Option E (Correct)</option>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Numerical Tolerance field (shown only for numerical type) -->
                    <div id="gep_numerical_tolerance_wrap" style="<?php echo ($q && $q->question_type === 'numerical') ? '' : 'display:none;'; ?>">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: #b45309; margin-bottom: 8px; text-transform: uppercase;">🔢 Numerical Tolerance (±)</label>
                        <input type="number" step="0.001" name="numerical_tolerance" value="<?php echo isset($q->numerical_tolerance) ? esc_attr($q->numerical_tolerance) : '0.01'; ?>" placeholder="e.g. 0.01" style="width: 100%; font-weight: 900; border-color: #f59e0b;">
                        <small style="color:#94a3b8;font-weight:600;">Acceptable error margin for evaluation</small>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Subject / Main Category</label>
                        <select name="category_id" style="width: 100%;">
                            <option value="0">General Intelligence</option>
                            <?php
                            $category_logic = new GEP_Category();
                            $categories = $category_logic->get_categories(0);
                            $categories = array_filter( $categories, function( $sub ) {
                                return strcasecmp($sub->name, 'ved') !== 0 && strcasecmp($sub->slug, 'ved') !== 0;
                            } );
                            foreach($categories as $cat) {
                                $selected = ($q && $q->category_id == $cat->id) ? 'selected' : '';
                                echo '<option value="'.$cat->id.'" '.$selected.'>'.esc_html($cat->name).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Topic / Subcategory</label>
                        <?php
                        $sub_name = '';
                        $all_subcats = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gep_categories WHERE parent_id > 0 ORDER BY name ASC" );
                        if ($q && $q->subcategory_id > 0) {
                            $subcat_obj = $category_logic->get_category_by_id($q->subcategory_id);
                            if ($subcat_obj) {
                                $sub_name = $subcat_obj->name;
                            }
                        }
                        ?>
                        <select name="subcategory_id" id="gep_subcategory_id" style="width: 100%; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-weight: 700; font-size: 13px; margin-bottom: 8px; cursor: pointer;">
                            <option value="0" data-parent="0">-- Select Topic / Subcategory --</option>
                            <?php foreach ( $all_subcats as $sub ) : ?>
                                <?php 
                                $selected = ($q && $q->subcategory_id == $sub->id) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $sub->id; ?>" data-parent="<?php echo $sub->parent_id; ?>" <?php echo $selected; ?>>
                                    <?php echo esc_html( $sub->name ); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="new">➕ Add New Topic...</option>
                        </select>
                        <input type="text" name="subcategory_name" id="gep_subcategory_name" placeholder="Type new subcategory/topic name..." value="<?php echo esc_attr($sub_name); ?>" style="width: 100%; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-weight: 700; font-size: 13px; display: none;">
                        
                        <script>
                        jQuery(document).ready(function($) {
                            function toggleNewSubcat() {
                                if ($('#gep_subcategory_id').val() === 'new') {
                                    $('#gep_subcategory_name').show().prop('required', true);
                                } else {
                                    $('#gep_subcategory_name').hide().prop('required', false);
                                }
                            }
                            $('#gep_subcategory_id').on('change', toggleNewSubcat);
                            toggleNewSubcat();
                            
                            $('select[name="category_id"]').on('change', function() {
                                $('#gep_subcategory_id').val('0');
                                toggleNewSubcat();
                            });
                        });
                        </script>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Link to Exam / Test</label>
                        <?php
                        $all_tests = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}gep_tests WHERE type = 'single' ORDER BY title ASC" );
                        ?>
                        <select name="linked_test_ids[]" multiple style="width: 100%; height: 90px; padding: 6px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 13px; cursor: pointer;">
                            <?php foreach ( $all_tests as $t ) : ?>
                                <?php 
                                $is_linked = false;
                                if ( $q ) {
                                    $is_linked = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}gep_test_questions WHERE test_id = %d AND question_id = %d", $t->id, $q->id ) );
                                }
                                ?>
                                <option value="<?php echo $t->id; ?>" <?php echo $is_linked ? 'selected' : ''; ?>>
                                    <?php echo esc_html( $t->title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #94a3b8; font-weight: 600; display: block; margin-top: 4px;">Hold Ctrl / Cmd to select multiple exams to link this question to.</small>
                    </div>
                    <div style="display:none;">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Weight (Marks)</label>
                        <input type="number" step="0.1" name="marks" value="<?php echo $q ? $q->marks : '1.0'; ?>" style="font-weight: 900; width: 100%;">
                    </div>
                    <div style="display:none;">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Negative Penalty</label>
                        <input type="number" step="0.01" name="negative_marks" value="<?php echo $q ? $q->negative_marks : '0.00'; ?>" style="font-weight: 900; width: 100%; color: var(--admin-danger);">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">PYQ Exam Tag</label>
                        <input type="text" name="pyqs" value="<?php echo $q ? esc_attr($q->pyqs) : ''; ?>" placeholder="e.g. UGC NET, RPSC" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Exam Year / Session</label>
                        <input type="text" name="year" value="<?php echo $q ? esc_attr($q->year) : ''; ?>" placeholder="e.g. Dec 2025, June 2024" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Search Tags</label>
                        <input type="text" name="tags" value="<?php echo $q ? esc_attr($q->tags) : ''; ?>" placeholder="e.g. Sanskrit, RPSC, 2024" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Sources</label>
                        <input type="text" name="source" value="<?php echo $q ? esc_attr($q->source) : ''; ?>" placeholder="e.g. UGC NET 2023, RPSC 2024" style="width: 100%;">
                    </div>
                </div>
                <div class="gep-admin-console-body" style="padding-top: 0;">
                    <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 12px; text-transform: uppercase;">Solution Explanation (Optional)</label>
                    <?php wp_editor($q ? $q->explanation : '', 'question_explanation', array(
                        'textarea_rows' => 5,
                        'tinymce'       => array(
                            'block_formats' => 'Paragraph=p;Heading 2=h2;Preformatted=pre',
                            'toolbar1'      => 'formatselect bold italic underline superscript subscript bullist numlist hr alignleft aligncenter link charmap undo redo',
                            'toolbar2'      => '',
                            'content_style' => 'body { font-family: Georgia, serif; font-size: 14px; line-height: 1.6; }',
                        ),
                    )); ?>
                </div>
                <div class="gep-admin-console-footer">
                    <button type="submit" name="submit" class="button button-primary" style="height: 48px; padding: 0 35px; border-radius: 12px; font-weight: 900; font-size: 15px;">💾 Save Asset to Bank</button>
                </div>
            </div>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('.tab-btn').click(function() {
                var tab = $(this).data('tab');
                $('.tab-content').addClass('inactive');
                $('#' + tab).removeClass('inactive');
                $('.tab-btn').removeClass('button-primary');
                $(this).addClass('button-primary');
            });

            // Toggle translation fields visibility based on translation_enabled checkbox
            $('input[name="translation_enabled"]').change(function() {
                if ($(this).is(':checked')) {
                    $('#gep-translation-fields-wrapper').slideDown(300);
                } else {
                    $('#gep-translation-fields-wrapper').slideUp(300);
                }
            });

            // Ensure tinyMCE syncs editor content to their textareas on form submit
            $('form').on('submit', function() {
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                } else if (typeof tinymce !== 'undefined') {
                    tinymce.triggerSave();
                }
            });

            // AI Smart Parse Logic
            $('#gep-smart-parse').click(function() {
                var content = '';
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('question_title')) {
                    content = tinyMCE.get('question_title').getContent({format: 'text'});
                } else {
                    content = $('#question_title').val();
                }

                if (!content) {
                    alert('Please paste the question and options into the box first.');
                    return;
                }

                // Detect if multiple questions are present (Improved Regex)
                // Looks for numbers at the start of a line or after a newline
                var questionCount = (content.match(/(?:^|\n)\s*\d+[\.\)]/g) || []).length;
                if (questionCount > 1) {
                    $('#smart-parse-feedback').html(
                        '<div class="gep-detection-alert">' +
                        '<span>⚠️</span> <div><strong>Intelligence Overflow Detected:</strong> I noticed ' + questionCount + ' questions in this block. For mass ingestion, please use the <a href="<?php echo admin_url("admin.php?page=gep-questions&action=import"); ?>" style="color: #d97706; text-decoration: underline; font-weight: 800;">Bulk Import Tool</a>.</div>' +
                        '</div>'
                    ).fadeIn();
                    return;
                }

                // Enhanced Multi-Stage Parser for Sanskrit/Devanagari
                // Stage 1: Locate the first legitimate option to isolate the question title
                var firstOptionMatch = content.match(/[\s\n][\(\[]?(?:1|A|a)[\)\]\.]/);
                var questionTitle = "";
                var optionsBlock = content;

                if (firstOptionMatch) {
                    questionTitle = content.substring(0, firstOptionMatch.index).trim();
                    optionsBlock = content.substring(firstOptionMatch.index);
                } else {
                    // Fallback: If no (1) or (A) found, assume the whole block is question or use old split
                    questionTitle = content;
                }

                // Stage 2: Clean the question title (remove leading "13. " etc)
                questionTitle = questionTitle.replace(/^\d+[\.\)]\s*/, '').trim();

                // Split question title by slash / if bilingual translations exist
                var enTitle = questionTitle;
                var hiTitle = "";
                if (questionTitle.includes('/')) {
                    var titleParts = questionTitle.split('/');
                    enTitle = titleParts[0].trim();
                    hiTitle = titleParts[1].trim();
                } else if (/[\u0900-\u097F]/.test(questionTitle)) {
                    // If pure Devanagari text, fill the Hindi tab editor too
                    hiTitle = questionTitle;
                }

                // Stage 3: Extract exactly 5 options from the options block
                var options = [];
                var optionRegex = /(?:^|[\s\n])[\(\[]?(1|2|3|4|5|A|B|C|D|E|a|b|c|d|e)[\)\]\.]\s*([\s\S]+?)(?=(?:\n|[\s　])[\(\[]?(?:1|2|3|4|5|A|B|C|D|E|a|b|c|d|e)[\)\]\.]|$)/g;
                var match;
                while ((match = optionRegex.exec(optionsBlock)) !== null) {
                    var optText = match[2].trim();
                    if (optText.length > 0) {
                        options.push(optText);
                    }
                    if (options.length >= 5) break; // We support up to 5 options
                }

                if (options.length >= 2) {
                    // Split English & Hindi options if slash / exists
                    var enOptions = [];
                    var hiOptions = [];
                    for (var i = 0; i < options.length; i++) {
                        var optVal = options[i];
                        if (optVal.includes('/')) {
                            var parts = optVal.split('/');
                            enOptions.push(parts[0].trim());
                            hiOptions.push(parts[1].trim());
                        } else {
                            enOptions.push(optVal);
                            hiOptions.push('');
                        }
                    }

                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('question_title')) {
                        tinyMCE.get('question_title').setContent(enTitle);
                    } else {
                        $('#question_title').val(enTitle);
                    }

                    if (hiTitle) {
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('question_hi')) {
                            tinyMCE.get('question_hi').setContent(hiTitle);
                        } else {
                            $('#question_hi').val(hiTitle);
                        }
                    }

                    // Populate option fields with English intelligence
                    $('input[name="option_a"]').val(enOptions[0] || '').trigger('change');
                    $('input[name="option_b"]').val(enOptions[1] || '').trigger('change');
                    $('input[name="option_c"]').val(enOptions[2] || '').trigger('change');
                    $('input[name="option_d"]').val(enOptions[3] || '').trigger('change');
                    $('input[name="option_e"]').val(enOptions[4] || '').trigger('change');

                    // Populate translation fields with Hindi/Sanskrit intelligence
                    $('input[name="option_a_hi"]').val(hiOptions[0] || '').trigger('change');
                    $('input[name="option_b_hi"]').val(hiOptions[1] || '').trigger('change');
                    $('input[name="option_c_hi"]').val(hiOptions[2] || '').trigger('change');
                    $('input[name="option_d_hi"]').val(hiOptions[3] || '').trigger('change');
                    $('input[name="option_e_hi"]').val(hiOptions[4] || '').trigger('change');

                    $('#smart-parse-feedback').html(
                        '<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px 20px; border-radius: 18px; font-size: 13px; color: #166534; font-weight: 600; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); animation: slideInDown 0.4s ease;">' +
                        '<span style="font-size: 20px;">✅</span> <div><strong>Intelligence Asset Extracted!</strong> The bilingual question and ' + options.length + ' options have been mapped perfectly across English &amp; Hindi tabs.</div>' +
                        '</div>'
                    ).fadeIn().delay(5000).fadeOut();
                } else {
                    alert('Could not detect options. Ensure they are formatted like (1), (2) or (A), (B).');
                }
            });

            // Dynamic Answer Controller for Manual Authoring Form
            var initialLoad = true;
            $('#gep_question_type').change(function() {
                var type = $(this).val();
                var wrapper = $('#gep_ans_wrapper');
                $('#gep_numerical_tolerance_wrap').hide();
                if(type === 'numerical'){
                    $('#gep_numerical_tolerance_wrap').show();
                }

                if (type === 'passage') {
                    $('.gep-passage-link-wrap').hide();
                    $('#gep-passage-text-wrap-en').show();
                    if ($('input[name="translation_enabled"]').is(':checked')) {
                        $('#gep-passage-text-wrap-hi').show();
                    } else {
                        $('#gep-passage-text-wrap-hi').hide();
                    }
                    $('#gep-correct-answer-wrap').show();
                    $('#gep-options-grid-en').show();
                    if ($('input[name="translation_enabled"]').is(':checked')) {
                        $('#gep-options-grid-hi').show();
                    } else {
                        $('#gep-options-grid-hi').hide();
                    }
                } else {
                    $('.gep-passage-link-wrap').show();
                    $('#gep-passage-text-wrap-en, #gep-passage-text-wrap-hi').hide();
                    $('#gep-correct-answer-wrap').show();
                    
                    // Show options grids only if not numerical or short_answer
                    var hideOpts = (type === 'numerical' || type === 'short_answer');
                    $('#gep-options-grid-en').toggle(!hideOpts);
                    if ($('input[name="translation_enabled"]').is(':checked')) {
                        $('#gep-options-grid-hi').toggle(!hideOpts);
                    } else {
                        $('#gep-options-grid-hi').hide();
                    }
                }

                if (!initialLoad) {
                    if (type === 'passage') {
                        wrapper.html('<select name="correct_answer" style="width: 100%;"><option value="A">Option A (Correct)</option><option value="B">Option B (Correct)</option><option value="C">Option C (Correct)</option><option value="D">Option D (Correct)</option><option value="E">Option E (Correct)</option></select>');
                    } else if (type === 'short_answer') {
                        wrapper.html('<input type="text" name="correct_answer" placeholder="Enter text answer..." style="width: 100%; font-weight: 900;">');
                    } else if (type === 'numerical') {
                        wrapper.html('<input type="text" name="correct_answer" placeholder="Enter exact value e.g. 3.14" style="width: 100%; font-weight: 900; border-color:#f59e0b;">');
                    } else if (type === 'multi_select') {
                        wrapper.html('<input type="text" name="correct_answer" placeholder="Comma-separated correct options e.g. A,C" style="width: 100%; font-weight: 900;"><small style="color:#6366f1;font-weight:600;">Comma-separated e.g. A,C</small>');
                    } else if (type === 'true_false') {
                        wrapper.html('<select name="correct_answer" style="width:100%;"><option value="True">True</option><option value="False">False</option></select>');
                    } else {
                        // MCQ, matching, assertion_reason — all use A/B/C/D/E
                        wrapper.html('<select name="correct_answer" style="width: 100%;"><option value="A">Option A (Correct)</option><option value="B">Option B (Correct)</option><option value="C">Option C (Correct)</option><option value="D">Option D (Correct)</option><option value="E">Option E (Correct)</option></select>');
                    }
                }
            });
            $('#gep_question_type').trigger('change');
            initialLoad = false;

            // Category/Subcategory filtering
            function filterSubcategories() {
                var parentId = $('select[name="category_id"]').val();
                $('#gep_subcategory_id option').each(function() {
                    var optionParent = $(this).data('parent');
                    if (!optionParent || optionParent == parentId || $(this).val() == '0') {
                        $(this).show();
                    } else {
                        $(this).hide();
                        if ($(this).is(':selected')) {
                            $('#gep_subcategory_id').val('0');
                        }
                    }
                });

                // Also filter the new subcategory_id in bulk import form
                $('select[name="subcategory_id"] option').each(function() {
                    var optionParent = $(this).data('parent');
                    if ($(this).val() == "0" || optionParent == parentId) {
                        $(this).show();
                    } else {
                        $(this).hide();
                        if ($(this).is(':selected')) {
                            $(this).parent().val('0');
                        }
                    }
                });
            }
            $('#gep_category_id, select[name="category_id"]').change(filterSubcategories);
            filterSubcategories();
        });
        </script>
    
    <?php else :
        $category_logic = new GEP_Category();
        $subjects = $category_logic->get_categories(0); // parent_id = 0
        
        // Filter out VED/ved
        $subjects = array_filter( $subjects, function( $sub ) {
            return strcasecmp($sub->name, 'ved') !== 0 && strcasecmp($sub->slug, 'ved') !== 0;
        } );
        $subjects = array_values($subjects); // Re-index

        $selected_category_id = 'all';
        if ( isset($_GET['category_id']) && $_GET['category_id'] !== '' ) {
            $selected_category_id = ($_GET['category_id'] === 'all') ? 'all' : (($_GET['category_id'] === '0' || $_GET['category_id'] === 0) ? 0 : absint($_GET['category_id']));
        }

        $per_page = isset($_GET['per_page']) ? sanitize_text_field(wp_unslash($_GET['per_page'])) : '50';
        $per_page_num = 50;
        if ( $per_page === 'all' ) {
            $per_page_num = 999999;
        } else {
            $per_page_num = max( 1, absint( $per_page ) );
        }

        $filter_args = array(
            'q_id'           => isset($_GET['q_id'])           ? sanitize_text_field(wp_unslash($_GET['q_id']))           : '',
            'category_id'    => $selected_category_id,
            'subcategory_id' => isset($_GET['subcategory_id']) ? absint($_GET['subcategory_id'])                          : 0,
            'question_type'  => isset($_GET['question_type'])  ? sanitize_text_field(wp_unslash($_GET['question_type']))  : '',
            'difficulty'     => isset($_GET['difficulty'])     ? sanitize_text_field(wp_unslash($_GET['difficulty']))     : '',
            'pyqs'           => isset($_GET['pyqs'])           ? sanitize_text_field(wp_unslash($_GET['pyqs']))           : '',
            'year'           => isset($_GET['year'])           ? sanitize_text_field(wp_unslash($_GET['year']))           : '',
            's'              => isset($_GET['s'])              ? sanitize_text_field(wp_unslash($_GET['s']))              : '',
            'paged'          => isset($_GET['paged'])          ? absint($_GET['paged'])                                   : 1,
            'per_page'       => $per_page_num,
        );
        // FIX: Unpack paginated result
        $q_result    = $admin_questions->list_questions( $filter_args );
        $questions   = $q_result['items'];
        $total_q     = $q_result['total'];
        $total_pages = (int) ceil( $total_q / $q_result['per_page'] );
        $cur_page    = $q_result['paged'];

        // Fetch unique PYQs and Years for filtering
        $all_pyqs  = $wpdb->get_col( "SELECT DISTINCT pyqs FROM {$wpdb->prefix}gep_questions WHERE pyqs IS NOT NULL AND pyqs != '' ORDER BY pyqs ASC" );
        $all_years = $wpdb->get_col( "SELECT DISTINCT year FROM {$wpdb->prefix}gep_questions WHERE year IS NOT NULL AND year != '' ORDER BY year DESC" );
        
        $subject_letters = array();
        $letter_code = ord('A');
        foreach ( $subjects as $sub ) {
            $subject_letters[ $sub->id ] = chr($letter_code);
            $letter_code++;
        }

        // Map child category IDs to their parent/subject IDs for quick lookup
        $category_parent_map = array();
        $all_cats = $wpdb->get_results( "SELECT id, parent_id FROM {$wpdb->prefix}gep_categories" );
        foreach ( $all_cats as $c ) {
            $curr_id = (int) $c->id;
            $parent_id = (int) $c->parent_id;
            while ( $parent_id > 0 ) {
                $p_cat = null;
                foreach ( $all_cats as $ac ) {
                    if ( $ac->id == $parent_id ) {
                        $p_cat = $ac;
                        break;
                    }
                }
                if ( $p_cat ) {
                    if ( $p_cat->parent_id == 0 ) {
                        break;
                    } else {
                        $parent_id = (int) $p_cat->parent_id;
                    }
                } else {
                    break;
                }
            }
            $category_parent_map[$curr_id] = ($parent_id > 0) ? $parent_id : $curr_id;
        }

        // Serial numbers are generated dynamically based on the current paginated view
    ?>
        <?php if ( isset($_GET['message']) && $_GET['message'] === 'saved' ) : ?>
        <div class="notice notice-success is-dismissible" style="border-left-color: #10b981; margin: 0 0 16px 0; padding: 10px 14px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 18px;">✅</span>
            <strong style="font-size: 14px; color: #065f46;">Question saved successfully!</strong>
        </div>
        <?php endif; ?>
        <div class="gep-sticky-header-container">
            <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding: 0 15px;">
                <div style="display: flex; align-items: baseline; gap: 12px;">
                    <h1 style="margin: 0; font-size: 20px; font-weight: 850; letter-spacing: -0.5px; line-height: 1;">Question Bank</h1>
                    <span style="color: var(--admin-muted); font-size: 12px; font-weight: 700;">
                        (<?php echo number_format( $total_q ); ?> total questions &middot; Page <?php echo $cur_page; ?> of <?php echo max(1, $total_pages); ?>)
                    </span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="<?php echo add_query_arg(array('action' => 'add', 'passage_mode' => '1'), $current_page_url); ?>" class="button" style="height: 32px; line-height: 30px; border-radius: 8px; font-size: 12px; font-weight: 700; padding: 0 15px; background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">📖 + Add Passage</a>
                    <a href="<?php echo add_query_arg('action', 'import', $current_page_url); ?>" class="button" style="height: 32px; line-height: 30px; border-radius: 8px; font-size: 12px; font-weight: 700; padding: 0 12px;">📂 Bulk Import</a>
                    <a href="<?php echo add_query_arg('action', 'add', $current_page_url); ?>" class="button button-primary" style="height: 32px; line-height: 30px; border-radius: 8px; font-size: 12px; font-weight: 700; padding: 0 15px;">+ Add Question</a>
                </div>
            </div>

            <!-- Subject Tabs -->
            <div class="gep-subject-quick-tabs" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; padding: 0 15px; align-items: center;">
                <?php 
                $is_all_active = ($selected_category_id === 'all');
                $all_tab_url = add_query_arg( array('category_id' => 'all', 'subcategory_id' => 0, 'paged' => 1), $current_page_url );
                ?>
                <a href="<?php echo esc_url( $all_tab_url ); ?>" 
                   class="button <?php echo $is_all_active ? 'button-primary' : ''; ?>" 
                   style="border-radius: 6px; font-weight: 800; padding: 4px 12px; height: 28px; line-height: 26px; font-size: 11px; text-transform: uppercase;">
                   All Subjects
                </a>

                <?php 
                $is_general_active = ($selected_category_id === 0 || $selected_category_id === '0');
                $general_tab_url = add_query_arg( array('category_id' => 0, 'subcategory_id' => 0, 'paged' => 1), $current_page_url );
                ?>
                <a href="<?php echo esc_url( $general_tab_url ); ?>" 
                   class="button <?php echo $is_general_active ? 'button-primary' : ''; ?>" 
                   style="border-radius: 6px; font-weight: 800; padding: 4px 12px; height: 28px; line-height: 26px; font-size: 11px; text-transform: uppercase;">
                   General Intelligence
                </a>

                <?php 
                foreach ( $subjects as $sub ) : 
                    $letter = isset($subject_letters[$sub->id]) ? $subject_letters[$sub->id] : '';
                    $is_active = ($selected_category_id == $sub->id && $selected_category_id !== 'all' && $selected_category_id !== '0' && $selected_category_id !== 0);
                    $tab_url = add_query_arg( array('category_id' => $sub->id, 'subcategory_id' => 0, 'paged' => 1), $current_page_url );
                ?>
                    <a href="<?php echo esc_url( $tab_url ); ?>" 
                       class="button <?php echo $is_active ? 'button-primary' : ''; ?>" 
                       style="border-radius: 6px; font-weight: 800; padding: 4px 12px; height: 28px; line-height: 26px; font-size: 11px; text-transform: uppercase;">
                       <?php echo esc_html( $letter . '. ' . $sub->name ); ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?php echo esc_url( admin_url('admin.php?page=gep-categories') ); ?>" 
                   class="button" 
                   style="border-radius: 6px; font-weight: 800; padding: 0 10px; height: 28px; line-height: 26px; font-size: 16px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;" 
                   title="Add More Subjects">
                   +
                </a>
            </div>

            <!-- Compact Advanced Filtering System -->
            <form method="get" action="" class="gep-filter-form-compact" style="margin: 0 15px;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                <?php if (isset($_GET['tab'])) : ?>
                    <input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab']); ?>">
                <?php endif; ?>
                
                <div class="gep-filter-grid" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end;">
                    <div>
                        <label>Question ID</label>
                        <input type="text" name="q_id" value="<?php echo esc_attr($filter_args['q_id']); ?>" placeholder="e.g. 101" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label>Search</label>
                        <input type="text" name="s" value="<?php echo esc_attr($filter_args['s']); ?>" placeholder="Keywords..." style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label>Subject</label>
                        <select name="category_id" id="filter_category_id" style="width: 100%; box-sizing: border-box;">
                            <option value="all" <?php selected($selected_category_id, 'all'); ?>>All Subjects</option>
                            <option value="0" <?php selected($selected_category_id, '0'); ?>>General Intelligence</option>
                            <?php
                            foreach($subjects as $cat) {
                                $selected = ($selected_category_id == $cat->id && $selected_category_id !== 'all' && $selected_category_id !== '0' && $selected_category_id !== 0) ? 'selected' : '';
                                echo '<option value="'.$cat->id.'" '.$selected.'>'.esc_html($cat->name).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Topic</label>
                        <select name="subcategory_id" id="filter_subcategory_id" style="width: 100%; box-sizing: border-box;">
                            <option value="">All Topics</option>
                            <?php
                            $subcategories = $category_logic->get_all_subcategories();
                            foreach($subcategories as $subcat) {
                                $selected = ($filter_args['subcategory_id'] == $subcat->id) ? 'selected' : '';
                                echo '<option value="'.$subcat->id.'" data-parent="'.$subcat->parent_id.'" '.$selected.'>'.esc_html($subcat->name).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Type</label>
                        <select name="question_type" style="width: 100%; box-sizing: border-box;">
                            <option value="">All Types</option>
                            <option value="mcq" <?php selected($filter_args['question_type'], 'mcq'); ?>>MCQ — Single Choice</option>
                            <option value="numerical" <?php selected($filter_args['question_type'], 'numerical'); ?>>Numerical — NTA Style</option>
                            <option value="true_false" <?php selected($filter_args['question_type'], 'true_false'); ?>>True / False</option>
                            <option value="assertion_reason" <?php selected($filter_args['question_type'], 'assertion_reason'); ?>>Assertion-Reason</option>
                            <option value="matching" <?php selected($filter_args['question_type'], 'matching'); ?>>Match the Following</option>
                            <option value="short_answer" <?php selected($filter_args['question_type'], 'short_answer'); ?>>Short Answer</option>
                            <option value="passage" <?php selected($filter_args['question_type'], 'passage'); ?>>Passage (Comprehension)</option>
                        </select>
                    </div>
                    <div>
                        <label>Sub Topic</label>
                        <input type="text" name="difficulty" value="<?php echo esc_attr(isset($filter_args['difficulty']) ? $filter_args['difficulty'] : ''); ?>" placeholder="Sub topic..." style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label>PYQ Exam</label>
                        <select name="pyqs" style="width: 100%; box-sizing: border-box;">
                            <option value="">All Exams</option>
                            <?php foreach($all_pyqs as $pyq) : ?>
                                <option value="<?php echo esc_attr($pyq); ?>" <?php selected($filter_args['pyqs'], $pyq); ?>><?php echo esc_html($pyq); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Year</label>
                        <select name="year" style="width: 100%; box-sizing: border-box;">
                            <option value="">All Years</option>
                            <?php foreach($all_years as $year) : ?>
                                <option value="<?php echo esc_attr($year); ?>" <?php selected($filter_args['year'], $year); ?>><?php echo esc_html($year); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center; height: 32px;">
                        <button type="submit" class="button button-primary" style="height: 32px; line-height: 30px; padding: 0 15px; border-radius: 8px; font-weight: 700; font-size: 12px; box-sizing: border-box;">🔍 Filter</button>
                        <?php if ( ! empty($filter_args['category_id']) || ! empty($filter_args['subcategory_id']) || ! empty($filter_args['pyqs']) || ! empty($filter_args['year']) || ! empty($filter_args['s']) || ! empty($filter_args['q_id']) || ! empty($filter_args['question_type']) ) : ?>
                            <a href="<?php echo esc_url($current_page_url); ?>" style="color: var(--admin-danger); font-size: 11px; font-weight: 800; text-decoration: none;">Reset</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="gep-admin-table-container">
            <table class="gep-admin-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID <span class="gep-info-tip" title="Serial No. (Large) is sequential for your view. Database ID (Small) is the unique system code used to link this question in tests. Gaps in Database IDs occur naturally when questions are deleted or passages are saved.">ℹ️</span></th>
                        <th style="width: 40%;">Question Details</th>
                        <th style="width: 10%;">Subject</th>
                        <th style="width: 10%;">Topic</th>
                        <th style="width: 10%;">Sub Topic</th>
                        <th style="width: 10%;">PYQ Exam</th>
                        <th style="width: 8%;">Year</th>
                        <th style="width: 10%;">Language</th>
                        <th style="width: 90px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $questions ) ) :
                        $offset = ( $cur_page - 1 ) * $q_result['per_page'];
                        $row_counter = 0;
                        foreach ( $questions as $q ) : ?>
                            <?php 
                            $q_cat_id = isset($category_parent_map[(int)$q->category_id]) ? $category_parent_map[(int)$q->category_id] : 0;
                            $subject_letter = isset($subject_letters[$q_cat_id]) ? $subject_letters[$q_cat_id] : 'U';
                            $serial_no = $total_q - $offset - $row_counter;
                            $row_counter++;
                            ?>
                            <tr>
                                <td style="padding: 18px 25px; vertical-align: middle;">
                                    <div style="font-weight: 900; color: var(--admin-primary); font-size: 15px;" title="Serial Number (Sequential)"><?php echo $serial_no; ?></div>
                                    <div style="font-size: 10px; color: var(--admin-muted); font-weight: 700; margin-top: 3px;" title="Unique Database ID (Use this to link in tests)">DB ID: #<?php echo $q->id; ?></div>
                                </td>
                                <td>
                                    <a href="<?php echo add_query_arg(array('action' => 'edit', 'id' => $q->id), $current_page_url); ?>" class="row-title">
                                        <?php echo wp_trim_words(strip_tags(wp_unslash($q->title)), 12); ?>
                                    </a>
                                    <div class="row-actions">
                                        <a href="<?php echo add_query_arg(array('action' => 'edit', 'id' => $q->id), $current_page_url); ?>">Edit</a>
                                        <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'delete', 'id' => $q->id), $current_page_url), 'gep_question_delete_'.$q->id); ?>" class="delete">Delete</a>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $cat_name = '—';
                                    // BUGFIX: category_id=0 is valid (General Intelligence) but 0 is falsy in PHP.
                                    // Must use !== null check, not simple if($q->category_id).
                                    if ( ! is_null( $q->category_id ) ) {
                                        if ( (int) $q->category_id === 0 ) {
                                            $cat_name = 'GENERAL INTELLIGENCE';
                                        } else {
                                            $category = ( new GEP_Category() )->get_category_by_id( $q->category_id );
                                            $cat_name = $category ? strtoupper( $category->name ) : '—';
                                        }
                                    }
                                    ?>
                                    <?php if ( $cat_name !== '—' ) : ?>
                                    <span class="status-badge" style="background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; font-weight: 800;">
                                        <?php echo esc_html($cat_name); ?>
                                    </span>
                                    <?php else : ?>
                                    <span style="color:#94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $subcat_name = '—';
                                    if ( $q->subcategory_id ) {
                                        $subcategory = ( new GEP_Category() )->get_category_by_id( $q->subcategory_id );
                                        $subcat_name = $subcategory ? strtoupper( $subcategory->name ) : '—';
                                    }
                                    ?>
                                    <?php if ( $subcat_name !== '—' ) : ?>
                                    <span class="status-badge" style="background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; font-weight: 800;">
                                        <?php echo esc_html($subcat_name); ?>
                                    </span>
                                    <?php else : ?>
                                    <span style="color:#94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php 
                                    $sub_topic = isset($q->difficulty) ? $q->difficulty : '';
                                    echo esc_html($sub_topic);
                                    ?>
                                </td>
                                <td>
                                    <?php if ( ! empty($q->pyqs) ) : ?>
                                        <span class="status-badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 800;">
                                            <?php echo esc_html(strtoupper($q->pyqs)); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="color: var(--admin-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( ! empty($q->year) ) : ?>
                                        <span class="status-badge" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-weight: 800;">
                                            <?php echo esc_html($q->year); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="color: var(--admin-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($q->translation_enabled) {
                                        echo '<span class="status-badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">BILINGUAL (EN/HI)</span>';
                                    } else {
                                        $has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $q->title);
                                        if ($has_devanagari) {
                                            echo '<span class="status-badge" style="background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;">SANSKRIT/HINDI</span>';
                                        } else {
                                            echo '<span class="status-badge" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">ENGLISH ONLY</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <a href="<?php echo add_query_arg(array('action' => 'edit', 'id' => $q->id), $current_page_url); ?>" class="button" style="padding: 0 10px; border-radius: 8px; min-width: 35px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;" title="Edit Asset">
                                            ✏️
                                        </a>
                                        <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'delete', 'id' => $q->id), $current_page_url), 'gep_question_delete_'.$q->id); ?>" class="button delete-question-btn" style="padding: 0 10px; border-radius: 8px; min-width: 35px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;" title="Destroy Asset">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div style="font-size: 40px; margin-bottom: 15px;">🔍</div>
                                <h3>No questions found in bank</h3>
                                <p>Start by adding a single question or use the Bulk Import tool.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- FIX: Pagination & View Count controls below table -->
        <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin: 24px 0; flex-wrap: wrap;">
            <?php if ( $total_pages > 1 && $cur_page > 1 ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $cur_page - 1, $_SERVER['REQUEST_URI'] ) ); ?>" class="button" style="border-radius: 10px; font-weight: 700; height: 38px; line-height: 38px; padding: 0 18px;">&larr; Prev</a>
            <?php endif; ?>
            
            <span style="font-size: 13px; font-weight: 800; color: var(--admin-muted); background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 20px; border-radius: 10px; display: inline-flex; align-items: center; gap: 10px;">
                <?php if ( $total_pages > 1 ) : ?>
                    <span>Page <?php echo $cur_page; ?> of <?php echo $total_pages; ?></span>
                    <span>&nbsp;&middot;&nbsp;</span>
                <?php endif; ?>
                <span><?php echo number_format($total_q); ?> questions</span>
                <span>&nbsp;&middot;&nbsp;</span>
                <span style="display: flex; align-items: center; gap: 6px;">
                    <label for="gep_per_page_select" style="font-size: 11px; color: var(--admin-muted); font-weight: 800; text-transform: uppercase;">Show:</label>
                    <select id="gep_per_page_select" style="height: 24px; padding: 0 5px; font-size: 12px; font-weight: 800; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; line-height: 1;">
                        <option value="50" <?php selected($per_page, '50'); ?>>50</option>
                        <option value="100" <?php selected($per_page, '100'); ?>>100</option>
                        <option value="250" <?php selected($per_page, '250'); ?>>250</option>
                        <option value="all" <?php selected($per_page, 'all'); ?>>All</option>
                    </select>
                </span>
            </span>

            <?php if ( $total_pages > 1 && $cur_page < $total_pages ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $cur_page + 1, $_SERVER['REQUEST_URI'] ) ); ?>" class="button" style="border-radius: 10px; font-weight: 700; height: 38px; line-height: 38px; padding: 0 18px;">Next &rarr;</a>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.delete-question-btn').click(function(e) {
                if (!confirm('CRITICAL ACTION: Are you sure you want to permanently destroy this intelligence asset? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });

            // Filter form subcategory filtering
            function filterFormSubcategories() {
                var parentId = $('#filter_category_id').val();
                $('#filter_subcategory_id option').each(function() {
                    var optionParent = $(this).data('parent');
                    if (!parentId || !optionParent || optionParent == parentId || $(this).val() == '') {
                        $(this).show();
                    } else {
                        $(this).hide();
                        if ($(this).is(':selected')) {
                            $('#filter_subcategory_id').val('');
                        }
                    }
                });
            }
            $('#filter_category_id').change(filterFormSubcategories);
            filterFormSubcategories();

            $('#gep_per_page_select').change(function() {
                var val = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('per_page', val);
                url.searchParams.set('paged', '1');
                window.location.href = url.toString();
            });
        });
        </script>
    <?php endif; ?>
</div>
