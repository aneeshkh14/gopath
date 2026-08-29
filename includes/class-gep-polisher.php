<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * System Polisher and High-Fidelity Configurator.
 * Ensures all pages are published, correctly titled, and shortcode-linked.
 */
class GEP_Polisher {

    public static function polish() {
        self::ensure_pages();
        self::ensure_settings();
        self::cleanup();
    }

    private static function cleanup() {
        // 1. Remove Sample Page
        $sample = get_page_by_path('sample-page');
        if ( $sample ) {
            wp_delete_post( $sample->ID, true );
        }

        // 2. Remove "no title" GEP drafts (scoped to prevent data loss of other WP drafts)
        global $wpdb;
        $no_titles = $wpdb->get_results( "SELECT ID FROM {$wpdb->posts} WHERE post_title = '' AND post_type = 'page' AND post_content LIKE '%[gep_%'" );
        foreach ( $no_titles as $page ) {
            wp_delete_post( $page->ID, true );
        }

        // 3. Remove default WP Privacy Policy if it's a draft and we have our own
        $privacy_draft = get_page_by_path('privacy-policy-draft'); // Common slug for the draft
        if ( ! $privacy_draft ) {
             // Try searching by title if slug differs
             $privacy_draft = $wpdb->get_row( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '%Privacy Policy%' AND post_status = 'draft' AND post_type = 'page' LIMIT 1" );
        }
        
        if ( $privacy_draft ) {
            wp_delete_post( $privacy_draft->ID, true );
        }
    }

    private static function ensure_pages() {
        $pages = array(
            'home' => array(
                'title'   => 'GoPath Intelligence | Premier Exam Portal',
                'content' => '[gep_home]',
                'slug'    => 'front-page'
            ),
            'dashboard' => array(
                'title'   => 'GoPath | Student Dashboard',
                'content' => '[gep_dashboard]',
                'slug'    => 'dashboard'
            ),
            'exam' => array(
                'title'   => 'GoPath | Exam Engine',
                'content' => '[gep_exam]',
                'slug'    => 'exam'
            ),
            'result' => array(
                'title'   => 'GoPath | Performance Analysis',
                'content' => '[gep_result]',
                'slug'    => 'result'
            ),
            'login' => array(
                'title'   => 'Login | GoPath Intelligence',
                'content' => '[gep_login]',
                'slug'    => 'login'
            ),
            'register' => array(
                'title'   => 'Join | GoPath Academy',
                'content' => '[gep_register]',
                'slug'    => 'register'
            ),
            'checkout' => array(
                'title'   => 'Secure Checkout | GoPath',
                'content' => '[gep_checkout]',
                'slug'    => 'checkout'
            ),
            'terms' => array(
                'title'   => 'Terms of Service | GoPath',
                'content' => 'Terms and conditions for GoPath Exam Portal.',
                'slug'    => 'terms'
            ),
            'privacy' => array(
                'title'   => 'Privacy Policy | GoPath',
                'content' => 'Privacy policy for GoPath Exam Portal.',
                'slug'    => 'privacy'
            )
        );

        foreach ( $pages as $key => $data ) {
            $page_check = get_page_by_path( $data['slug'] );
            
            $page_data = array(
                'post_title'   => $data['title'],
                'post_content' => $data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => $data['slug']
            );

            if ( ! $page_check ) {
                $page_id = wp_insert_post( $page_data );
            } else {
                $page_data['ID'] = $page_check->ID;
                wp_update_post( $page_data );
                $page_id = $page_check->ID;
            }

            // Sync with plugin settings
            update_option( 'gep_page_' . $key, $page_id );
        }

        // Set Front Page
        $front_page = get_page_by_path( 'front-page' );
        if ( $front_page ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $front_page->ID );
        }
    }

    private static function ensure_settings() {
        // High-fidelity defaults
        if ( ! get_option( 'gep_violation_limit' ) ) {
            update_option( 'gep_violation_limit', 3 );
        }
        if ( ! get_option( 'gep_violation_action' ) ) {
            update_option( 'gep_violation_action', 'warn' );
        }

        // Razorpay Production Credentials (only set if not already present)
        if ( ! get_option( 'gep_razorpay_key_id' ) ) {
            update_option( 'gep_razorpay_key_id', 'rzp_live_SfIiIY8iOUwvj1' );
        }
        if ( ! get_option( 'gep_razorpay_key_secret' ) ) {
            update_option( 'gep_razorpay_key_secret', base64_encode( '3HTqkdHfQzZgAXCMfsjB7yL6' ) );
        }
    }
}
