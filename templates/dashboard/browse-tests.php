<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Translation Mapping
$ui_strings = array(
    'en' => array(
        'hub_title'     => "Test Repositories",
        'all'           => "All",
        'series'        => "Series",
        'single'        => "Single",
        'multiple'      => "Multiple Subject",
        'combined'      => "Combined",
        'self_test'     => "Self Testing",
        'random'        => "Create Your Own",
        'search_placeholder' => "Filter by subject or asset name...",
        'all_cats'      => "All Domains",
        'series_badge'  => "SERIES",
        'mock_badge'    => "EXAM",
        'single_badge'    => "SINGLE",
        'multiple_badge'  => "MULTIPLE",
        'combined_badge'  => "COMBINED",
        'self_test_badge' => "SELF TEST",
        'tests'         => "Tests",
        'ques'          => "Ques",
        'mins'          => "Mins",
        'marks'         => "Marks",
        'free'          => "FREE",
        'view_series'   => "View",
        'start_now'     => "Launch",
        'unlock_now'    => "Unlock",
        'no_exams'      => "No Assets Found",
        'no_exams_desc' => "Our high-fidelity test repository is currently being updated. Please check back shortly.",
        'return_dash'   => "Back to Hub"
    ),
    'hi' => array(
        'hub_title'     => "टेस्ट रिपॉजिटरी",
        'all'           => "सभी",
        'series'        => "सीरीज",
        'single'        => "एकल विषय",
        'multiple'      => "बहु-विषय",
        'combined'      => "संयुक्त",
        'self_test'     => "स्व-परीक्षण",
        'random'        => "स्वयं का टेस्ट बनाएँ",
        'search_placeholder' => "विषय या नाम से फ़िल्टर करें...",
        'all_cats'      => "सभी डोमेन",
        'series_badge'  => "सीरीज",
        'mock_badge'    => "परीक्षा",
        'single_badge'    => "एकल विषय",
        'multiple_badge'  => "बहु-विषय",
        'combined_badge'  => "संयुक्त",
        'self_test_badge' => "स्व-परीक्षण",
        'tests'         => "टेस्ट",
        'ques'          => "प्रश्न",
        'mins'          => "मिनट",
        'marks'         => "अंक",
        'free'          => "मुफ्त",
        'view_series'   => "देखें",
        'start_now'     => "शुरू करें",
        'unlock_now'    => "अनलॉक",
        'no_exams'      => "कोई एसेट नहीं मिला",
        'no_exams_desc' => "हमारे टेस्ट रिपोजिटरी को वर्तमान में अपडेट किया जा रहा है।",
        'return_dash'   => "हब पर लौटें"
    )
);

$_gep_lang = (isset($_SESSION['gep_lang']) ? $_SESSION['gep_lang'] : (get_user_meta(get_current_user_id(), 'gep_preferred_lang', true) ?: 'en'));
$strings = isset($ui_strings[$_gep_lang]) ? $ui_strings[$_gep_lang] : $ui_strings['en'];
?>

<div class="gep-browse-sovereign">
    <!-- Header Strategy -->
    <div class="gep-browse-header gep-glass">
        <div class="header-left">
            <h3><?php echo esc_html($strings['hub_title']); ?></h3>
            
            <!-- Category (Subject Domain) Select Dropdown -->
            <select id="gep-cat-select">
                <option value="0"><?php echo esc_html($strings['all_cats']); ?></option>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo $cat->id; ?>"><?php echo esc_html( $cat->name ); ?></option>
                <?php endforeach; ?>
            </select>

            <?php $_initial_type = isset( $initial_type_filter ) ? $initial_type_filter : 'all'; ?>
            <!-- Test Type (Single, Combined, Series, etc.) Select Dropdown -->
            <select id="gep-type-select">
                <option value="all"<?php selected( $_initial_type, 'all' ); ?>><?php echo esc_html($strings['all']); ?></option>
                <option value="series"<?php selected( $_initial_type, 'series' ); ?>><?php echo esc_html($strings['series']); ?></option>
                <option value="single"<?php selected( $_initial_type, 'single' ); ?>><?php echo esc_html($strings['single']); ?></option>
                <option value="multiple"<?php selected( $_initial_type, 'multiple' ); ?>><?php echo esc_html($strings['multiple']); ?></option>
                <option value="combined"<?php selected( $_initial_type, 'combined' ); ?>><?php echo esc_html($strings['combined']); ?></option>
                <option value="self_test"<?php selected( $_initial_type, 'self_test' ); ?>><?php echo esc_html($strings['self_test']); ?></option>
                <option value="random"<?php selected( $_initial_type, 'random' ); ?>><?php echo esc_html($strings['random']); ?></option>
            </select>
        </div>
        
        <div class="header-right">
            <div class="gep-search-micro">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="gep-test-search" placeholder="<?php echo esc_attr($strings['search_placeholder']); ?>">
            </div>
        </div>
    </div>

    <!-- Asset Grid with Category Grouping -->
    <div class="gep-category-groups" id="gep-ajax-test-grid" style="width: 100%;">
        <?php 
        $test_logic = new GEP_Test();

        // Batch the per-card counts up front — these used to be two queries per
        // rendered card (N+1) inside the loops below.
        $all_test_ids   = array();
        $all_series_ids = array();
        foreach ( $tests as $_t ) {
            $all_test_ids[] = $_t->id;
            if ( $_t->type === 'series' || $_t->type === 'bundle' ) {
                $all_series_ids[] = $_t->id;
            }
        }
        $q_count_map      = $test_logic->get_question_counts( $all_test_ids );
        $series_count_map = $test_logic->get_series_test_counts( $all_series_ids );

        // Group tests by parent category
        global $wpdb;
        $category_mapping_res = $wpdb->get_results( "SELECT id, parent_id FROM {$wpdb->prefix}gep_categories" );
        $cat_to_parent = array();
        foreach ( $category_mapping_res as $c_map ) {
            $cat_to_parent[ (int) $c_map->id ] = (int) $c_map->parent_id;
        }

        $grouped_tests = array();
        foreach ( $tests as $test ) {
            $cat_id = (int) $test->category_id;
            $sub_id = (int) (isset($test->subcategory_id) ? $test->subcategory_id : 0);
            
            $resolved_parent_id = 0;
            
            if ( $sub_id > 0 && isset($cat_to_parent[$sub_id]) ) {
                $resolved_parent_id = $cat_to_parent[$sub_id] > 0 ? $cat_to_parent[$sub_id] : $sub_id;
            }
            
            if ( $resolved_parent_id <= 0 && $cat_id > 0 ) {
                if ( isset($cat_to_parent[$cat_id]) && $cat_to_parent[$cat_id] > 0 ) {
                    $resolved_parent_id = $cat_to_parent[$cat_id];
                } else {
                    $resolved_parent_id = $cat_id;
                }
            }
            
            $test->resolved_parent_id = $resolved_parent_id;
            
            if ( ! isset( $grouped_tests[$resolved_parent_id] ) ) {
                $grouped_tests[$resolved_parent_id] = array();
            }
            $grouped_tests[$resolved_parent_id][] = $test;
        }

        if ( empty( $tests ) ) : ?>
            <div style="grid-column: 1 / -1; background: #18181b; border: 2px dashed rgba(255,255,255,0.1); border-radius: 24px; padding: 80px 40px; text-align: center; width: 100%;">
                <div style="font-size: 50px; margin-bottom: 20px;">🔭</div>
                <h3 style="margin: 0 0 15px; font-size: 22px; font-weight: 850; color: #fff;"><?php echo esc_html($strings['no_exams']); ?></h3>
                <p style="color: #a1a1aa; font-size: 15px; font-weight: 600; max-width: 500px; margin: 0 auto;"><?php echo esc_html($strings['no_exams_desc']); ?></p>
            </div>
        <?php else :
            // Loop through each category and render its section
            foreach ( $categories as $cat ) :
                if ( empty( $grouped_tests[$cat->id] ) ) continue;
                ?>
                <div class="gep-category-section" data-cat="<?php echo $cat->id; ?>" style="margin-bottom: 45px; width: 100%;">
                    <h3 class="gep-category-title" style="font-size: 20px; font-weight: 850; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <span style="width: 4px; height: 18px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 2px; display: inline-block;"></span>
                        <?php echo esc_html( $cat->name ); ?>
                    </h3>
                    <div class="gep-asset-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
                        <?php foreach ( $grouped_tests[$cat->id] as $test ) : 
                            $is_series = ($test->type === 'series' || $test->type === 'bundle');
                            $q_count = isset( $q_count_map[ $test->id ] ) ? $q_count_map[ $test->id ] : 0;
                            $series_test_count = $is_series && isset( $series_count_map[ $test->id ] ) ? $series_count_map[ $test->id ] : 0;
                            
                            $card_icon = '⚡';
                            $type_label = $strings['mock_badge'];
                            $accent_color = '#10b981';

                            if ( $is_series ) {
                                $card_icon = '📁';
                                $type_label = $strings['series_badge'];
                                $accent_color = '#6366f1';
                            } else {
                                if ( $test->type === 'single' ) {
                                    $card_icon = '📝';
                                    $type_label = $strings['single_badge'];
                                    $accent_color = '#10b981';
                                } elseif ( $test->type === 'multiple' ) {
                                    $card_icon = '📚';
                                    $type_label = $strings['multiple_badge'];
                                    $accent_color = '#3b82f6';
                                } elseif ( $test->type === 'combined' ) {
                                    $card_icon = '🧩';
                                    $type_label = $strings['combined_badge'];
                                    $accent_color = '#f59e0b';
                                } elseif ( $test->type === 'self_test' ) {
                                    $card_icon = '⚙️';
                                    $type_label = $strings['self_test_badge'];
                                    $accent_color = '#ec4899';
                                }
                            }
                        ?>
                            <div class="gep-asset-card" data-cat="<?php echo $test->resolved_parent_id; ?>" data-type="<?php echo esc_attr( $is_series ? 'series' : $test->type ); ?>">
                                <?php if ( ! empty( $test->thumbnail ) ) : ?>
                                    <div class="asset-thumbnail-wrap" style="height: 140px; overflow: hidden; border-radius: 12px; margin-bottom: 15px; border: 1px solid rgba(0,0,0,0.05);">
                                        <img src="<?php echo esc_url( $test->thumbnail ); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <div class="asset-top" style="<?php echo ! empty( $test->thumbnail ) ? 'margin-top: 0;' : ''; ?>">
                                    <?php if ( empty( $test->thumbnail ) ) : ?>
                                        <div class="asset-icon-wrap" style="background: <?php echo $accent_color; ?>10; color: <?php echo $accent_color; ?>;">
                                            <?php echo $card_icon; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="asset-badge-micro" style="border-color: <?php echo $accent_color; ?>40; color: <?php echo $accent_color; ?>; <?php echo ! empty( $test->thumbnail ) ? 'margin-left: 0;' : ''; ?>">
                                        <?php echo $type_label; ?>
                                    </div>
                                </div>

                                <div class="asset-main">
                                    <h4><?php echo esc_html( $test->title ); ?></h4>
                                    <div class="asset-telemetry">
                                        <?php if ($is_series) : ?>
                                            <span><?php echo $series_test_count; ?> <?php echo esc_html($strings['tests']); ?></span>
                                        <?php else : ?>
                                            <span><?php echo $q_count; ?> <?php echo esc_html($strings['ques']); ?></span>
                                        <?php endif; ?>
                                        <span class="dot">•</span>
                                        <span><?php echo $test->duration_minutes; ?> <?php echo esc_html($strings['mins']); ?></span>
                                    </div>
                                </div>

                                <div class="asset-action-row" style="display: flex; gap: 8px; align-items: center; justify-content: space-between; margin-top: 15px;">
                                    <div class="asset-valuation">
                                        <?php if($test->is_free): ?>
                                            <span class="free-text"><?php echo $strings['free']; ?></span>
                                        <?php else: ?>
                                            <span class="cur">₹</span><span class="val"><?php echo $test->price; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <button type="button" class="gep-btn-details gep-view-details-btn" 
                                            data-title="<?php echo esc_attr( $test->title ); ?>"
                                            data-thumbnail="<?php echo esc_url( $test->thumbnail ?: GEP_PLUGIN_URL . 'assets/images/placeholder-test.jpg' ); ?>"
                                            data-price="<?php echo $test->is_free ? 'FREE' : '₹' . $test->price; ?>"
                                            data-duration="<?php echo $test->duration_minutes; ?> Mins"
                                            data-qcount="<?php echo $q_count; ?>"
                                            data-refundable="<?php 
                                                $t_data = ! empty( $test->translated_data ) ? json_decode( $test->translated_data, true ) : array();
                                                echo esc_attr( isset($t_data['refundable']) ? $t_data['refundable'] : 'no' ); 
                                            ?>"
                                            data-topics="<?php echo esc_attr( isset($t_data['topics']) ? $t_data['topics'] : '' ); ?>"
                                            data-details="<?php echo esc_attr( isset($t_data['what_you_get']) ? $t_data['what_you_get'] : '' ); ?>"
                                            style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer; height: 35px; line-height: 21px;">
                                            Details
                                        </button>
                                        
                                        <?php if ( (isset($test->is_purchased) && $test->is_purchased) || $test->is_free ) : ?>
                                            <a href="<?php echo gep_get_url('exam'); ?>?id=<?php echo $test->id; ?>" class="gep-btn-asset" style="height: 35px; line-height: 35px; display: flex; align-items: center; justify-content: center; padding: 0 15px;">
                                                <?php echo $is_series ? $strings['view_series'] : $strings['start_now']; ?>
                                            </a>
                                        <?php else : ?>
                                            <button type="button" class="gep-btn-asset-primary gep-unlock-btn"
                                                data-id="<?php echo $test->id; ?>"
                                                data-type="test"
                                                data-title="<?php echo esc_attr($test->title); ?>"
                                                data-price="<?php echo esc_attr($test->price); ?>"
                                                style="height: 35px; line-height: 21px; display: flex; align-items: center; justify-content: center; padding: 0 15px;">
                                                <?php echo esc_html($strings['unlock_now']); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Uncategorized section -->
            <?php if ( ! empty( $grouped_tests[0] ) ) : ?>
                <div class="gep-category-section" data-cat="0" style="margin-bottom: 45px; width: 100%;">
                    <h3 class="gep-category-title" style="font-size: 20px; font-weight: 850; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <span style="width: 4px; height: 18px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 2px; display: inline-block;"></span>
                        Uncategorized Tests
                    </h3>
                    <div class="gep-asset-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
                        <?php foreach ( $grouped_tests[0] as $test ) : 
                            $is_series = ($test->type === 'series' || $test->type === 'bundle');
                            $q_count = isset( $q_count_map[ $test->id ] ) ? $q_count_map[ $test->id ] : 0;
                            $series_test_count = $is_series && isset( $series_count_map[ $test->id ] ) ? $series_count_map[ $test->id ] : 0;
                            
                            $card_icon = '⚡';
                            $type_label = $strings['mock_badge'];
                            $accent_color = '#10b981';

                            if ( $is_series ) {
                                $card_icon = '📁';
                                $type_label = $strings['series_badge'];
                                $accent_color = '#6366f1';
                            } else {
                                if ( $test->type === 'single' ) {
                                    $card_icon = '📝';
                                    $type_label = $strings['single_badge'];
                                    $accent_color = '#10b981';
                                } elseif ( $test->type === 'multiple' ) {
                                    $card_icon = '📚';
                                    $type_label = $strings['multiple_badge'];
                                    $accent_color = '#3b82f6';
                                } elseif ( $test->type === 'combined' ) {
                                    $card_icon = '🧩';
                                    $type_label = $strings['combined_badge'];
                                    $accent_color = '#f59e0b';
                                } elseif ( $test->type === 'self_test' ) {
                                    $card_icon = '⚙️';
                                    $type_label = $strings['self_test_badge'];
                                    $accent_color = '#ec4899';
                                }
                            }
                        ?>
                            <div class="gep-asset-card" data-cat="<?php echo $test->resolved_parent_id; ?>" data-type="<?php echo esc_attr( $is_series ? 'series' : $test->type ); ?>">
                                <?php if ( ! empty( $test->thumbnail ) ) : ?>
                                    <div class="asset-thumbnail-wrap" style="height: 140px; overflow: hidden; border-radius: 12px; margin-bottom: 15px; border: 1px solid rgba(0,0,0,0.05);">
                                        <img src="<?php echo esc_url( $test->thumbnail ); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <div class="asset-top" style="<?php echo ! empty( $test->thumbnail ) ? 'margin-top: 0;' : ''; ?>">
                                    <?php if ( empty( $test->thumbnail ) ) : ?>
                                        <div class="asset-icon-wrap" style="background: <?php echo $accent_color; ?>10; color: <?php echo $accent_color; ?>;">
                                            <?php echo $card_icon; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="asset-badge-micro" style="border-color: <?php echo $accent_color; ?>40; color: <?php echo $accent_color; ?>; <?php echo ! empty( $test->thumbnail ) ? 'margin-left: 0;' : ''; ?>">
                                        <?php echo $type_label; ?>
                                    </div>
                                </div>

                                <div class="asset-main">
                                    <h4><?php echo esc_html( $test->title ); ?></h4>
                                    <div class="asset-telemetry">
                                        <?php if ($is_series) : ?>
                                            <span><?php echo $series_test_count; ?> <?php echo esc_html($strings['tests']); ?></span>
                                        <?php else : ?>
                                            <span><?php echo $q_count; ?> <?php echo esc_html($strings['ques']); ?></span>
                                        <?php endif; ?>
                                        <span class="dot">•</span>
                                        <span><?php echo $test->duration_minutes; ?> <?php echo esc_html($strings['mins']); ?></span>
                                    </div>
                                </div>

                                <div class="asset-action-row" style="display: flex; gap: 8px; align-items: center; justify-content: space-between; margin-top: 15px;">
                                    <div class="asset-valuation">
                                        <?php if($test->is_free): ?>
                                            <span class="free-text"><?php echo $strings['free']; ?></span>
                                        <?php else: ?>
                                            <span class="cur">₹</span><span class="val"><?php echo $test->price; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <button type="button" class="gep-btn-details gep-view-details-btn" 
                                            data-title="<?php echo esc_attr( $test->title ); ?>"
                                            data-thumbnail="<?php echo esc_url( $test->thumbnail ?: GEP_PLUGIN_URL . 'assets/images/placeholder-test.jpg' ); ?>"
                                            data-price="<?php echo $test->is_free ? 'FREE' : '₹' . $test->price; ?>"
                                            data-duration="<?php echo $test->duration_minutes; ?> Mins"
                                            data-qcount="<?php echo $q_count; ?>"
                                            data-refundable="<?php 
                                                $t_data = ! empty( $test->translated_data ) ? json_decode( $test->translated_data, true ) : array();
                                                echo esc_attr( isset($t_data['refundable']) ? $t_data['refundable'] : 'no' ); 
                                            ?>"
                                            data-topics="<?php echo esc_attr( isset($t_data['topics']) ? $t_data['topics'] : '' ); ?>"
                                            data-details="<?php echo esc_attr( isset($t_data['what_you_get']) ? $t_data['what_you_get'] : '' ); ?>"
                                            style="padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer; height: 35px; line-height: 21px;">
                                            Details
                                        </button>
                                        
                                        <?php if ( (isset($test->is_purchased) && $test->is_purchased) || $test->is_free ) : ?>
                                            <a href="<?php echo gep_get_url('exam'); ?>?id=<?php echo $test->id; ?>" class="gep-btn-asset" style="height: 35px; line-height: 35px; display: flex; align-items: center; justify-content: center; padding: 0 15px;">
                                                <?php echo $is_series ? $strings['view_series'] : $strings['start_now']; ?>
                                            </a>
                                        <?php else : ?>
                                            <button type="button" class="gep-btn-asset-primary gep-unlock-btn"
                                                data-id="<?php echo $test->id; ?>"
                                                data-type="test"
                                                data-title="<?php echo esc_attr($test->title); ?>"
                                                data-price="<?php echo esc_attr($test->price); ?>"
                                                style="height: 35px; line-height: 21px; display: flex; align-items: center; justify-content: center; padding: 0 15px;">
                                                <?php echo esc_html($strings['unlock_now']); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
</div>

<style>
.gep-browse-sovereign {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Header Strategy */
.gep-browse-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    border-radius: 16px;
    margin-bottom: 24px;
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.05);
    flex-wrap: wrap;
    gap: 15px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    flex: 1;
}

.gep-browse-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    letter-spacing: -0.5px;
    color: #fff;
}

/* Dropdown styling */
.gep-browse-header select {
    height: 36px;
    padding: 0 30px 0 12px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.3) url("data:image/svg+xml;utf8,<svg fill='%23a1a1aa' height='20' viewBox='0 0 24 24' width='20' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>") no-repeat right 8px center;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    outline: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    min-width: 150px;
}

.gep-browse-header select option {
    background: #18181b;
    color: #fff;
}

.gep-search-micro {
    position: relative;
    width: 240px;
}

.gep-search-micro svg {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #a1a1aa;
}

.gep-search-micro input {
    width: 100%;
    height: 36px;
    padding: 0 12px 0 36px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    transition: border-color 0.3s;
}
.gep-search-micro input:focus {
    outline: none;
    border-color: rgba(99, 102, 241, 0.5);
}

/* Domain Scroller */
.gep-domain-scroller {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 10px;
    margin-bottom: 30px;
    scrollbar-width: none;
}

.gep-domain-scroller::-webkit-scrollbar { display: none; }

.domain-chip {
    padding: 10px 24px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 30px;
    font-size: 12px;
    font-weight: 800;
    color: #a1a1aa;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.domain-chip:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.domain-chip.active {
    background: #fff;
    color: #000;
    border-color: #fff;
    box-shadow: 0 10px 20px rgba(255,255,255,0.1);
}

/* Asset Grid */
.gep-asset-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
}

.gep-asset-card {
    background: #18181b;
    border-radius: 28px;
    padding: 25px;
    border: 1px solid rgba(255,255,255,0.05);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.gep-asset-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.3);
}

.asset-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.asset-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    background: rgba(255,255,255,0.05);
}

.asset-badge-micro {
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 0.5px;
    padding: 6px 12px;
    border: 1px solid;
    border-radius: 8px;
}

.asset-main h4 {
    margin: 0 0 12px;
    font-size: 18px;
    font-weight: 850;
    color: #fff;
    line-height: 1.4;
}

.asset-telemetry {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    font-weight: 700;
    color: #a1a1aa;
    margin-bottom: 25px;
}

.asset-action-row {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.asset-valuation .cur { font-size: 14px; color: #a1a1aa; font-weight: 800; }
.asset-valuation .val { font-size: 22px; color: #fff; font-weight: 900; }
.free-text { font-size: 14px; font-weight: 900; color: #10b981; }

.gep-btn-asset, .gep-btn-asset-primary {
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
    transition: all 0.3s;
}

.gep-btn-asset { background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); }
.gep-btn-asset-primary { background: #fff; color: #000; }

.gep-btn-asset:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
.gep-btn-asset-primary:hover { background: #f8fafc; box-shadow: 0 10px 20px rgba(255,255,255,0.1); }

@media (max-width: 768px) {
    .gep-browse-header { flex-direction: column; gap: 20px; align-items: flex-start; }
    .header-right { width: 100%; }
    .gep-search-micro { width: 100%; }
}
</style>

<script>
jQuery(document).ready(function($) {
    function filterAssets() {
        const catId = $('#gep-cat-select').val();
        const type = $('#gep-type-select').val();
        const search = $('#gep-test-search').val().toLowerCase();
        
        $('.gep-category-section').each(function() {
            let sectionHasVisible = false;
            $(this).find('.gep-asset-card').each(function() {
                const cardCat = $(this).data('cat');
                const cardType = $(this).data('type');
                const cardTitle = $(this).find('h4').text().toLowerCase();
                let show = true;
                if (catId != 0 && cardCat != catId) show = false;
                if (type !== 'all' && cardType !== type) show = false;
                if (search && cardTitle.indexOf(search) === -1) show = false;
                
                if (show) {
                    $(this).show();
                    sectionHasVisible = true;
                } else {
                    $(this).hide();
                }
            });
            
            if (sectionHasVisible) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    $('#gep-cat-select').on('change', filterAssets);
    $('#gep-type-select').on('change', filterAssets);
    $('#gep-test-search').on('input', filterAssets);

    // Apply any server-provided initial type filter (e.g. deep link from "Create Your Own Test")
    if ($('#gep-type-select').val() !== 'all') {
        filterAssets();
    }
});
</script>

<?php
$razorpay_key = get_option('gep_razorpay_key_id');
$current_user = wp_get_current_user();
?>

<!-- ─── Inline Payment Modal ─────────────────────────────────────── -->
<div id="gep-pay-modal-overlay" style="display:none;position:fixed;inset:0;z-index:99990;background:rgba(0,0,0,0.80);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);align-items:center;justify-content:center;">
  <div id="gep-pay-modal" style="background:#0f172a;border:1px solid rgba(255,255,255,0.1);border-radius:28px;padding:40px 36px;max-width:420px;width:92%;position:relative;box-shadow:0 32px 80px rgba(0,0,0,0.7);animation:gepPaySlide 0.25s ease;">
    <style>@keyframes gepPaySlide{from{transform:translateY(24px);opacity:0}to{transform:translateY(0);opacity:1}}</style>
    <button id="gep-pay-modal-close" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,0.07);border:none;color:#94a3b8;width:30px;height:30px;border-radius:50%;font-size:14px;cursor:pointer;line-height:30px;text-align:center;">✕</button>

    <div style="text-align:center;margin-bottom:24px;">
      <div style="width:52px;height:52px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 14px;">🔓</div>
      <h3 id="gep-pay-item-title" style="font-size:17px;font-weight:900;color:#f1f5f9;margin:0 0 4px;letter-spacing:-0.5px;"></h3>
      <p style="font-size:12px;color:#64748b;margin:0;">Unlock lifetime access to this test</p>
    </div>

    <div style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);border-radius:14px;padding:18px;text-align:center;margin-bottom:20px;">
      <div style="font-size:10px;font-weight:800;color:#6366f1;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Total Amount</div>
      <div style="font-size:34px;font-weight:900;color:#f1f5f9;letter-spacing:-1px;"><span style="font-size:16px;color:#94a3b8;">₹</span><span id="gep-pay-price"></span></div>
    </div>

    <div style="display:flex;gap:6px;justify-content:center;margin-bottom:20px;flex-wrap:wrap;">
      <span style="padding:5px 11px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:7px;font-size:11px;font-weight:700;color:#94a3b8;">💳 Cards</span>
      <span style="padding:5px 11px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:7px;font-size:11px;font-weight:700;color:#94a3b8;">📱 UPI</span>
      <span style="padding:5px 11px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:7px;font-size:11px;font-weight:700;color:#94a3b8;">🏦 NetBanking</span>
      <span style="padding:5px 11px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:7px;font-size:11px;font-weight:700;color:#94a3b8;">👛 Wallets</span>
    </div>

    <div style="display:flex;gap:8px;margin-bottom:6px;">
      <input type="text" id="gep-modal-coupon" placeholder="Coupon code (optional)" style="flex:1;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:11px;padding:10px 14px;color:#f1f5f9;font-size:13px;font-weight:600;outline:none;">
      <button id="gep-modal-apply-coupon" style="background:#1e293b;color:#6366f1;border:1px solid rgba(99,102,241,0.3);border-radius:11px;padding:10px 14px;font-size:12px;font-weight:800;cursor:pointer;white-space:nowrap;">Apply</button>
    </div>
    <div id="gep-modal-coupon-status" style="font-size:12px;font-weight:700;padding-left:4px;min-height:20px;margin-bottom:14px;"></div>

    <button id="gep-modal-pay-btn" style="width:100%;padding:15px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:14px;font-size:15px;font-weight:800;cursor:pointer;transition:all 0.2s;">🔐 Pay with Razorpay</button>
    <div id="gep-modal-pay-error" style="margin-top:10px;font-size:12px;color:#ef4444;text-align:center;font-weight:600;display:none;"></div>
    <div style="text-align:center;margin-top:14px;font-size:11px;color:#334155;font-weight:600;">🛡 Secure · PCI DSS Compliant · 256-bit SSL</div>
  </div>
</div>

<script>
(function() {
    var AJAXURL    = '<?php echo esc_js( admin_url("admin-ajax.php") ); ?>';
    var NONCE      = '<?php echo wp_create_nonce("gep_checkout_nonce"); ?>';
    var RZP_KEY    = '<?php echo esc_js( $razorpay_key ); ?>';
    var USER_NAME  = '<?php echo esc_js( $current_user->display_name ); ?>';
    var USER_EMAIL = '<?php echo esc_js( $current_user->user_email ); ?>';

    var itemId = 0, itemType = 'test', finalAmount = 0, appliedCoupon = '';

    // Ensure Razorpay SDK loaded
    function loadRazorpay(cb) {
        if (window.Razorpay) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://checkout.razorpay.com/v1/checkout.js';
        s.onload = cb;
        s.onerror = function() { showErr('Failed to load payment SDK. Check your internet connection.'); };
        document.head.appendChild(s);
    }

    // Open modal
    document.querySelectorAll('.gep-unlock-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            itemId        = this.dataset.id;
            itemType      = this.dataset.type || 'test';
            finalAmount   = parseFloat(this.dataset.price) || 0;
            appliedCoupon = '';
            document.getElementById('gep-pay-item-title').textContent = this.dataset.title;
            document.getElementById('gep-pay-price').textContent = finalAmount.toFixed(0);
            document.getElementById('gep-modal-coupon').value = '';
            document.getElementById('gep-modal-coupon-status').textContent = '';
            document.getElementById('gep-modal-pay-error').style.display = 'none';
            resetBtn();
            var ov = document.getElementById('gep-pay-modal-overlay');
            ov.style.display = 'flex';
        });
    });

    // Close
    function closeModal() { document.getElementById('gep-pay-modal-overlay').style.display = 'none'; }
    document.getElementById('gep-pay-modal-close').addEventListener('click', closeModal);
    document.getElementById('gep-pay-modal-overlay').addEventListener('click', function(e) { if (e.target === this) closeModal(); });

    // Apply coupon
    document.getElementById('gep-modal-apply-coupon').addEventListener('click', function() {
        var code = document.getElementById('gep-modal-coupon').value.trim();
        if (!code) return;
        var me = this; me.disabled = true; me.textContent = '...';
        var fd = new FormData();
        fd.append('action','gep_apply_coupon'); fd.append('nonce',NONCE);
        fd.append('coupon_code',code); fd.append('item_id',itemId); fd.append('item_type',itemType);
        fetch(AJAXURL,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res) {
            me.disabled = false; me.textContent = 'Apply';
            var el = document.getElementById('gep-modal-coupon-status');
            if (res.success) {
                appliedCoupon = code;
                finalAmount   = parseFloat(res.data.new_total);
                document.getElementById('gep-pay-price').textContent = finalAmount.toFixed(0);
                el.style.color = '#10b981';
                el.textContent = '✓ Coupon applied! Saved ₹' + parseFloat(res.data.discount).toFixed(0);
            } else {
                appliedCoupon = '';
                el.style.color = '#ef4444';
                el.textContent = '✗ ' + ((res.data && res.data.message) ? res.data.message : 'Invalid coupon');
            }
        }).catch(function() { me.disabled = false; me.textContent = 'Apply'; });
    });

    // Pay button
    document.getElementById('gep-modal-pay-btn').addEventListener('click', function() {
        if (this._processing) return;
        this._processing = true; this.disabled = true; this.textContent = '⏳ Initializing...';
        var fd = new FormData();
        fd.append('action','gep_create_payment_order'); fd.append('nonce',NONCE);
        fd.append('item_id',itemId); fd.append('item_type',itemType); fd.append('coupon_code',appliedCoupon);
        fetch(AJAXURL,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res) {
            if (!res.success) { showErr((res.data&&res.data.message)||'Failed to initialize. Please try again.'); resetBtn(); return; }
            if (res.data.status === 'free') { window.location.href = res.data.redirect; return; }
            loadRazorpay(function() {
                var opts = {
                    key: RZP_KEY, amount: res.data.amount, currency: 'INR',
                    name: 'GoPath Exam Portal',
                    description: document.getElementById('gep-pay-item-title').textContent,
                    order_id: res.data.id,
                    prefill: { name: USER_NAME, email: USER_EMAIL },
                    theme: { color: '#6366f1' },
                    modal: { ondismiss: function() { resetBtn(); } },
                    handler: function(response) {
                        document.getElementById('gep-modal-pay-btn').textContent = '✅ Verifying...';
                        verify(response);
                    }
                };
                try {
                    var rzp = new Razorpay(opts);
                    rzp.on('payment.failed', function(r) { showErr('Payment failed: ' + (r.error.description||'Unknown')); resetBtn(); });
                    rzp.open();
                    resetBtn();
                } catch(e) { showErr('Could not load payment gateway. Please refresh and try again.'); resetBtn(); }
            });
        }).catch(function() { showErr('Network error. Please check your connection.'); resetBtn(); });
    });

    function verify(payResp) {
        var fd = new FormData();
        fd.append('action','gep_verify_payment'); fd.append('nonce',NONCE);
        fd.append('razorpay_payment_id', payResp.razorpay_payment_id);
        fd.append('razorpay_order_id',   payResp.razorpay_order_id);
        fd.append('razorpay_signature',  payResp.razorpay_signature);
        fd.append('item_id',itemId); fd.append('item_type',itemType);
        fetch(AJAXURL,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res) {
            if (res.success) { window.location.href = res.data.redirect_url; }
            else { showErr('Verification issue. Your access will activate shortly. ID: ' + payResp.razorpay_payment_id); resetBtn(); }
        }).catch(function() { showErr('⚠️ Payment received. Access will activate within minutes. ID: ' + payResp.razorpay_payment_id); resetBtn(); });
    }

    function resetBtn() {
        var b = document.getElementById('gep-modal-pay-btn');
        b._processing = false; b.disabled = false; b.textContent = '🔐 Pay with Razorpay';
    }
    function showErr(m) {
        var el = document.getElementById('gep-modal-pay-error');
        el.textContent = m; el.style.display = 'block';
    }
})();
</script>

<!-- Details Modal -->
<div id="gep-details-modal" class="gep-details-modal-overlay" style="display: none;">
    <div class="gep-details-modal-content">
        <button type="button" class="gep-details-modal-close" id="gep-details-modal-close">&times;</button>
        <div class="gep-details-modal-header">
            <div class="gep-details-modal-thumb-container">
                <img id="gep-modal-thumb" src="" alt="Thumbnail">
            </div>
            <div class="gep-details-modal-title-info">
                <h3 id="gep-modal-title">Test Title</h3>
                <div class="gep-details-modal-stats">
                    <span id="gep-modal-duration">60 Mins</span>
                    <span class="dot">•</span>
                    <span id="gep-modal-qcount">100 Ques</span>
                </div>
            </div>
        </div>
        <div class="gep-details-modal-body">
            <div class="gep-details-info-section">
                <h4>📋 Topics Covered</h4>
                <p id="gep-modal-topics">Algebra, Calculus, etc.</p>
            </div>
            <div class="gep-details-info-section">
                <h4>🎁 What You Get</h4>
                <p id="gep-modal-details">Detailed Solutions, AI Doubts, etc.</p>
            </div>
            <div class="gep-details-info-section flex-row">
                <div>
                    <h4>💰 Refundable</h4>
                    <p id="gep-modal-refundable">Non-Refundable</p>
                </div>
                <div>
                    <h4>🏷️ Price</h4>
                    <p id="gep-modal-price" class="price-val">₹0</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show details modal
    $(document).on('click', '.gep-view-details-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        
        // Populate modal data
        $('#gep-modal-title').text($btn.data('title'));
        $('#gep-modal-thumb').attr('src', $btn.data('thumbnail'));
        $('#gep-modal-duration').text($btn.data('duration'));
        $('#gep-modal-qcount').text($btn.data('qcount') + ' Ques');
        
        var refundable = $btn.data('refundable');
        $('#gep-modal-refundable').text(refundable === 'yes' ? 'Refundable (100% money back)' : 'Non-Refundable');
        
        var topics = $btn.data('topics');
        $('#gep-modal-topics').text(topics ? topics : 'No specific topics listed.');
        
        var details = $btn.data('details');
        $('#gep-modal-details').text(details ? details : 'Full access to exam, timed simulator and instant results.');
        
        $('#gep-modal-price').text($btn.data('price'));
        
        // Open modal
        $('#gep-details-modal').fadeIn(200);
    });
    
    // Close modal
    $(document).on('click', '#gep-details-modal-close, .gep-details-modal-overlay', function(e) {
        if (e.target === this || $(e.target).hasClass('gep-details-modal-close')) {
            $('#gep-details-modal').fadeOut(200);
        }
    });
});
</script>

