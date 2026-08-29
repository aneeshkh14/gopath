<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
global $wpdb; 

$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
$editing_cat = null;
if ( $action === 'edit' && isset( $_GET['id'] ) ) {
    $editing_cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gep_categories WHERE id = %d", absint( $_GET['id'] ) ) );
}
?>
<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="margin-bottom: 30px;">
        <h1>Subject & Category Architecture</h1>
        <p style="color: var(--admin-muted); font-weight: 600;">Define the core structure of your exams and courses.</p>
    </div>
    
    <div style="display: grid; grid-template-columns: 380px 1fr; gap: 30px; align-items: flex-start;">
        <!-- Left: Form -->
        <div class="gep-admin-console">
            <div class="gep-admin-console-header">
                <h2 style="margin: 0; font-size: 18px; font-weight: 800;"><?php echo $editing_cat ? 'Edit Category' : 'Add New Category / Topic'; ?></h2>
            </div>
            <div class="gep-admin-console-body">
                <form id="addtag" method="post" action="">
                    <?php wp_nonce_field('gep_category_action', 'gep_category_nonce'); ?>
                    <?php if ( $editing_cat ) : ?>
                        <input type="hidden" name="category_id" value="<?php echo $editing_cat->id; ?>">
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Category / Topic Name</label>
                        <input name="name" type="text" placeholder="e.g. Mathematics" value="<?php echo $editing_cat ? esc_attr($editing_cat->name) : ''; ?>" required>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">URL Slug</label>
                        <input name="slug" type="text" placeholder="e.g. mathematics" value="<?php echo $editing_cat ? esc_attr($editing_cat->slug) : ''; ?>">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Parent Subject / Category (for Subtopics)</label>
                        <select name="parent_id">
                            <option value="0">Root Level (None)</option>
                            <?php
                            $category_logic = new GEP_Category();
                            $categories = $category_logic->get_categories(0);
                            foreach ( $categories as $cat ) {
                                // Skip selecting self as parent to prevent infinite loops
                                if ( $editing_cat && $editing_cat->id == $cat->id ) {
                                    continue;
                                }
                                $selected = ( $editing_cat && $editing_cat->parent_id == $cat->id ) ? 'selected' : '';
                                echo '<option value="' . $cat->id . '" ' . $selected . '>' . esc_html( $cat->name ) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Description</label>
                        <textarea name="description" rows="4"><?php echo $editing_cat ? esc_textarea($editing_cat->description) : ''; ?></textarea>
                    </div>

                    <?php if ( $editing_cat ) : ?>
                        <button type="submit" name="gep_edit_category" class="button button-primary" style="width: 100%; height: 55px; border-radius: 14px; font-weight: 900; font-size: 16px;">💾 Update Category</button>
                        <a href="<?php echo admin_url('admin.php?page=gep-categories'); ?>" class="button" style="display: block; text-align: center; width: 100%; height: 45px; line-height: 43px; border-radius: 12px; font-weight: 800; margin-top: 10px; background: #e2e8f0; color: #475569; border: none; text-decoration: none;">Cancel Edit</a>
                    <?php else : ?>
                        <button type="submit" name="gep_add_category" class="button button-primary" style="width: 100%; height: 55px; border-radius: 14px; font-weight: 900; font-size: 16px;">💾 Add Category / Topic</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Right: Table -->
        <div class="gep-admin-table-container">
            <table class="gep-admin-table">
                <thead>
                    <tr>
                        <th>Subject / Topic Name</th>
                        <th>Slug Path</th>
                        <th>Order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $all_cats = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gep_categories ORDER BY menu_order ASC" );
                    if ( $all_cats ) :
                        $cat_tree = array();
                        foreach ( $all_cats as $cat ) {
                            $cat_tree[$cat->parent_id][] = $cat;
                        }

                        $gep_print_category_tree = function( $parent_id, $cat_tree, $depth = 0, $visited = array() ) use ( &$gep_print_category_tree ) {
                            if ( ! isset( $cat_tree[$parent_id] ) ) return;
                            foreach ( $cat_tree[$parent_id] as $cat ) {
                                if ( in_array( $cat->id, $visited ) ) continue;
                                $visited[] = $cat->id;
                                ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px; padding-left: <?php echo $depth * 25; ?>px;">
                                            <?php if($depth > 0): ?>
                                                <span style="color: #94a3b8; font-weight: bold;">↳</span>
                                            <?php endif; ?>
                                            <strong style="font-size: 15px;"><?php echo esc_html($cat->name); ?></strong>
                                        </div>
                                        <div class="row-actions" style="padding-left: <?php echo $depth * 25; ?>px;">
                                            <a href="<?php echo admin_url( 'admin.php?page=gep-categories&action=edit&id=' . $cat->id ); ?>">Edit</a>
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gep-categories&action=delete&id=' . $cat->id ), 'gep_category_delete_' . $cat->id ); ?>" class="delete" onclick="return confirm('Are you sure you want to delete this category?');">Remove</a>
                                        </div>
                                    </td>
                                    <td>
                                        <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 12px; color: var(--admin-primary);">/<?php echo esc_html($cat->slug); ?></code>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: #f1f5f9; color: #64748b; font-weight: 800;">
                                            POS: <?php echo $cat->menu_order; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php
                                $gep_print_category_tree( $cat->id, $cat_tree, $depth + 1, $visited );
                            }
                        };
                        
                        $gep_print_category_tree( 0, $cat_tree );
                    else : ?>

                        <tr>
                            <td colspan="3" class="empty-state">
                                <div style="font-size: 40px; margin-bottom: 15px;">🌳</div>
                                <h3>The tree is empty</h3>
                                <p>Start by adding your first subject or category.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
