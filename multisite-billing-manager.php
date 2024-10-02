<?php 
/*
Plugin Name: Multisite Billing Manager
Plugin URI: https://www.littlebizzy.com/plugins/multisite-billing-manager
Description: Billing for Multisite networks
Version: 1.2.0
Author: LittleBizzy
Author URI: https://www.littlebizzy.com
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
GitHub Plugin URI: littlebizzy/multisite-billing-manager
Primary Branch: main
*/

// Exit if accessed directly for security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Disable WordPress.org updates for this plugin
add_filter( 'gu_override_dot_org', function( $overrides ) {
    $overrides[] = 'multisite-billing-manager/multisite-billing-manager.php';
    return $overrides;
}, 999 ); 

// Add custom "Billing" tab to the site edit screen in the Network Admin
add_filter( 'network_edit_site_nav_links', 'multisite_billing_manager_tab' );
function multisite_billing_manager_tab( $tabs ) {
    $tabs['billing'] = array(
        'label' => esc_html__( 'Billing', 'multisite-billing-manager' ),
        'url'   => esc_url( network_admin_url( 'sites.php?page=billing' ) ),
        'cap'   => 'manage_sites' // Only users with 'manage_sites' capability can see the tab
    );
    return $tabs;
}

// Add "Billing" submenu page under "Sites" in the Network Admin
add_action( 'network_admin_menu', 'multisite_billing_manager_page' );
function multisite_billing_manager_page() {
    add_submenu_page(
        'sites.php',
        esc_html__( 'Edit Website', 'multisite-billing-manager' ),
        esc_html__( 'Edit Website', 'multisite-billing-manager' ),
        'manage_network_options', // Required capability to access the page
        'billing',
        'multisite_billing_manager_page_generate'
    );
}

// Hide the submenu link in the Network Admin UI
add_action( 'admin_head', 'multisite_billing_manager_css_trick' );
function multisite_billing_manager_css_trick() {
    echo '<style>
        #menu-site .wp-submenu li:last-child {
            display: none;
        }
    </style>';
}

// Display the custom "Billing" page for the site in the Network Admin
function multisite_billing_manager_page_generate() {
    if ( ! isset( $_REQUEST['id'] ) || ! intval( $_REQUEST['id'] ) ) {
        wp_die( esc_html__( 'Invalid site ID.', 'multisite-billing-manager' ) );
    }

    $id = intval( $_REQUEST['id'] );
    $billplan = get_blog_option( $id, 'billing_plan' ); // Retrieve the current billing plan

    $title = esc_html__( 'Edit site: ', 'multisite-billing-manager' );
    echo '<div class="wrap">
        <h1 id="edit-site">' . $title . '</h1>
        <p class="edit-site-actions"><a href="' . esc_url( get_home_url( $id, '/' ) ) . '">' . esc_html__( 'Visit', 'multisite-billing-manager' ) . '</a> | 
        <a href="' . esc_url( get_admin_url( $id ) ) . '">' . esc_html__( 'Dashboard', 'multisite-billing-manager' ) . '</a></p>';

    // Display the tab navigation
    network_edit_site_nav( array(
        'blog_id'  => $id,
        'selected' => 'billing' // Highlight the "Billing" tab
    ) );

    // Display the form for editing the billing plan
    echo '<form method="post" action="' . esc_url( admin_url( 'edit.php?action=billingupdate' ) ) . '">';
    wp_nonce_field( 'billing-check' . $id );
    echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '" />
        <table class="form-table">
            <tr>
                <th scope="row">' . esc_html__( 'Billing Plan', 'multisite-billing-manager' ) . '</th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">' . esc_html__( 'Set billing plan', 'multisite-billing-manager' ) . '</legend>
                        <label><input type="radio" name="billing_plan" value="free" ' . checked( 'free', $billplan, false ) . ' /> ' . esc_html__( 'Free', 'multisite-billing-manager' ) . '</label><br />
                        <label><input type="radio" name="billing_plan" value="basic" ' . checked( 'basic', $billplan, false ) . ' /> ' . esc_html__( 'Basic', 'multisite-billing-manager' ) . '</label><br />
                        <label><input type="radio" name="billing_plan" value="premium" ' . checked( 'premium', $billplan, false ) . ' /> ' . esc_html__( 'Premium', 'multisite-billing-manager' ) . '</label><br />
                        <label><input type="radio" name="billing_plan" value="vip" ' . checked( 'vip', $billplan, false ) . ' /> ' . esc_html__( 'VIP', 'multisite-billing-manager' ) . '</label>
                    </fieldset>
                </td>
            </tr>
        </table>';
    submit_button(); // Standard WordPress submit button
    echo '</form></div>';
}

// Handle form submission and update the billing plan
add_action( 'network_admin_edit_billingupdate', 'multisite_billing_manager_save_options' );
function multisite_billing_manager_save_options() {
    if ( ! isset( $_POST['id'] ) || ! intval( $_POST['id'] ) ) {
        wp_die( esc_html__( 'Invalid site ID.', 'multisite-billing-manager' ) );
    }

    $blog_id = intval( $_POST['id'] );
    check_admin_referer( 'billing-check' . $blog_id ); // Security check

    if ( isset( $_POST['billing_plan'] ) ) {
        $billing_plan = sanitize_text_field( $_POST['billing_plan'] ); // Sanitize input
        update_blog_option( $blog_id, 'billing_plan', $billing_plan ); // Update billing plan
    }

    // Redirect back to the "Billing" page with a success message
    wp_safe_redirect( add_query_arg( array(
        'page'    => 'billing',
        'id'      => $blog_id,
        'updated' => 'true',
    ), network_admin_url( 'sites.php' ) ) );
    exit;
}

// Display success notice after saving the billing plan
add_action( 'network_admin_notices', 'multisite_billing_manager_notice_success' );
function multisite_billing_manager_notice_success() {
    if ( isset( $_GET['updated'], $_GET['page'] ) && $_GET['page'] === 'billing' ) {
        echo '<div id="message" class="updated notice is-dismissible">
            <p>' . esc_html__( 'Settings saved successfully!', 'multisite-billing-manager' ) . '</p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'multisite-billing-manager' ) . '</span></button>
        </div>';
    }
}

// Redirect to the "Billing" page with proper validation
add_action( 'current_screen', 'multisite_billing_manager_redirects' );
function multisite_billing_manager_redirects() {
    $screen = get_current_screen();
    if ( $screen->id !== 'sites_page_billing-network' ) {
        return;
    }

    if ( ! isset( $_REQUEST['id'] ) || ! intval( $_REQUEST['id'] ) ) {
        wp_die( esc_html__( 'Invalid site ID.', 'multisite-billing-manager' ) );
    }

    $id = intval( $_REQUEST['id'] );
    $details = get_site( $id );

    if ( ! $details ) {
        wp_die( esc_html__( 'The requested site does not exist.', 'multisite-billing-manager' ) );
    }
}

// Ref: ChatGPT
// Ref: https://rudrastyh.com/wordpress-multisite/custom-tabs-with-options.html
// Ref: https://stackoverflow.com/questions/44015298/wordpress-checked-checked-syntax
