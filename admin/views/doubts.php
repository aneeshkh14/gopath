<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'gep_doubts';

// Handle Reply
if ( isset( $_POST['gep_action'] ) && $_POST['gep_action'] === 'reply_doubt' && isset( $_POST['doubt_id'] ) ) {
    check_admin_referer( 'gep_doubt_reply' );
    $doubt_id = absint( $_POST['doubt_id'] );
    $reply = sanitize_textarea_field( wp_unslash( $_POST['reply'] ) );

    if ( ! empty( $reply ) ) {
        $wpdb->update(
            $table,
            array(
                'answer' => $reply,
                'status' => 'resolved',
                'updated_at' => current_time( 'mysql' )
            ),
            array( 'id' => $doubt_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );
        echo '<div class="notice notice-success is-dismissible"><p>Reply sent successfully. Doubt marked as resolved.</p></div>';
    }
}

// Handle Delete
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['doubt'] ) ) {
    check_admin_referer( 'delete_doubt_' . $_GET['doubt'] );
    $wpdb->delete( $table, array( 'id' => absint( $_GET['doubt'] ) ), array( '%d' ) );
    echo '<div class="notice notice-success is-dismissible"><p>Doubt deleted.</p></div>';
}

$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'unresolved';

$where_clause = $tab === 'resolved' ? "status = 'resolved'" : "status = 'unresolved'";

$query = "SELECT d.*, u.display_name, u.user_email, c.title as course_title, l.title as lesson_title 
          FROM $table d
          LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
          LEFT JOIN {$wpdb->prefix}gep_courses c ON d.course_id = c.id
          LEFT JOIN {$wpdb->prefix}gep_lessons l ON d.lesson_id = l.id
          WHERE $where_clause
          ORDER BY d.created_at DESC";

$doubts = $wpdb->get_results( $query );
?>

<div class="wrap gep-admin-wrap">
    <h1 class="wp-heading-inline">Doubt Resolution Command Center</h1>
    <p>Manage and resolve student questions from video lessons.</p>

    <h2 class="nav-tab-wrapper">
        <a href="?page=gep-doubts&tab=unresolved" class="nav-tab <?php echo $tab === 'unresolved' ? 'nav-tab-active' : ''; ?>">Unresolved Doubts</a>
        <a href="?page=gep-doubts&tab=resolved" class="nav-tab <?php echo $tab === 'resolved' ? 'nav-tab-active' : ''; ?>">Resolved Doubts</a>
    </h2>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 20%;">Student</th>
                <th style="width: 20%;">Location</th>
                <th style="width: 30%;">Question</th>
                <th style="width: 20%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $doubts ) ) : ?>
                <tr><td colspan="4">No doubts found in this tab. Great job!</td></tr>
            <?php else : ?>
                <?php foreach ( $doubts as $d ) : ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html( $d->display_name ); ?></strong><br>
                        <a href="mailto:<?php echo esc_attr( $d->user_email ); ?>"><?php echo esc_html( $d->user_email ); ?></a><br>
                        <small style="color: #64748b;"><?php echo esc_html( date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $d->created_at ) ) ); ?></small>
                    </td>
                    <td>
                        <strong>Course:</strong> <?php echo esc_html( $d->course_title ); ?><br>
                        <strong>Lesson:</strong> <?php echo esc_html( $d->lesson_title ); ?>
                    </td>
                    <td>
                        <div style="background: #f8fafc; padding: 10px; border-left: 3px solid #3b82f6; margin-bottom: 10px;">
                            <?php echo esc_html( $d->question ); ?>
                        </div>
                        <?php if ( $d->answer ) : ?>
                        <div style="background: #f0fdf4; padding: 10px; border-left: 3px solid #10b981;">
                            <strong>Your Reply:</strong><br>
                            <?php echo esc_html( $d->answer ); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( $d->status === 'unresolved' ) : ?>
                            <button type="button" class="button button-primary" onclick="jQuery('#reply-doubt-<?php echo $d->id; ?>').toggle();">Reply to Student</button>
                        <?php endif; ?>
                        <a href="<?php echo wp_nonce_url( '?page=gep-doubts&action=delete&doubt=' . $d->id, 'delete_doubt_' . $d->id ); ?>" class="button button-link-delete" onclick="return confirm('Are you sure you want to delete this doubt?');">Delete</a>

                        <?php if ( $d->status === 'unresolved' ) : ?>
                        <div id="reply-doubt-<?php echo $d->id; ?>" style="display: none; margin-top: 10px; background: #fff; padding: 15px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                            <form method="post" action="">
                                <?php wp_nonce_field( 'gep_doubt_reply' ); ?>
                                <input type="hidden" name="gep_action" value="reply_doubt">
                                <input type="hidden" name="doubt_id" value="<?php echo esc_attr( $d->id ); ?>">
                                <textarea name="reply" rows="4" style="width: 100%; margin-bottom: 10px;" placeholder="Type your expert reply here..."></textarea>
                                <button type="submit" class="button button-primary">Send Reply & Resolve</button>
                                <button type="button" class="button" onclick="jQuery('#reply-doubt-<?php echo $d->id; ?>').hide();">Cancel</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
