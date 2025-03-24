<?php 
/*
Plugin Name: Multisite Billing Manager
Plugin URI: https://www.littlebizzy.com/plugins/multisite-billing-manager
Description: Billing for Multisite networks
Version: 1.2.3
Requires PHP: 7.0
Tested up to: 6.7
Author: LittleBizzy
Author URI: https://www.littlebizzy.com
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Update URI: false
GitHub Plugin URI: littlebizzy/multisite-billing-manager
Primary Branch: master
Text Domain: multisite-billing-manager
*/

// prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// override wordpress.org with git updater
add_filter( 'gu_override_dot_org', function( $overrides ) {
    $overrides[] = 'multisite-billing-manager/multisite-billing-manager.php';
    return $overrides;
}, 999 );

// add billing tab to site edit screen in network admin
add_filter( 'network_edit_site_nav_links', 'multisite_billing_manager_tab' );
function multisite_billing_manager_tab( $tabs ) {
    $site_id = 0;
    if ( isset( $_GET['id'] ) ) {
        $site_id = intval( $_GET['id'] );
    }

    $tabs['billing'] = array(
        'label' => esc_html__( 'Billing', 'multisite-billing-manager' ),
        'url'   => 'sites.php?page=billing&id=' . $site_id,
        'cap'   => 'manage_sites'
    );

    return $tabs;
}

// add billing submenu under sites in network admin
add_action( 'network_admin_menu', 'multisite_billing_manager_page' );
function multisite_billing_manager_page() {
    add_submenu_page(
        'sites.php',
        esc_html__( 'Edit Website', 'multisite-billing-manager' ),
        esc_html__( 'Edit Website', 'multisite-billing-manager' ),
        'manage_network_options',
        'billing',
        'multisite_billing_manager_page_generate'
    );
}

// hide billing submenu link in network admin
add_action( 'admin_head', 'multisite_billing_manager_css_trick' );
function multisite_billing_manager_css_trick() {
    echo '<style>
        body.network-admin #menu-site .wp-submenu a[href*="page=billing"] {
            display: none;
        }
    </style>';
}

// display billing page in network admin
function multisite_billing_manager_page_generate() {
    if ( ! isset( $_GET['id'] ) || ! intval( $_GET['id'] ) ) {
        wp_die( esc_html__( 'Invalid site ID.', 'multisite-billing-manager' ) );
    }

    $id = intval( $_GET['id'] );
    $billplan = get_blog_option( $id, 'billing_plan' );

    $title = esc_html__( 'Edit site: ', 'multisite-billing-manager' );

    echo '<div class="wrap">
        <h1 id="edit-site">' . $title . '</h1>
        <p class="edit-site-actions">
            <a href="' . esc_url( get_home_url( $id, '/' ) ) . '">' . esc_html__( 'Visit', 'multisite-billing-manager' ) . '</a> | 
            <a href="' . esc_url( get_admin_url( $id ) ) . '">' . esc_html__( 'Dashboard', 'multisite-billing-manager' ) . '</a>
        </p>';

    // show navigation tabs
    network_edit_site_nav( array(
        'blog_id'  => $id,
        'selected' => 'billing'
    ) );

    // show billing form
    echo '<form method="post" action="' . esc_url( network_admin_url( 'edit.php?action=billingupdate' ) ) . '">';
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

    submit_button();
    echo '</form></div>';
}

// handle billing form submission in network admin
add_action( 'network_admin_edit_billingupdate', 'multisite_billing_manager_save_options' );
function multisite_billing_manager_save_options() {
    if ( ! isset( $_POST['id'] ) || ! intval( $_POST['id'] ) ) {
        wp_die( esc_html__( 'Invalid site ID.', 'multisite-billing-manager' ) );
    }

    $blog_id = intval( $_POST['id'] );

    // verify nonce
    check_admin_referer( 'billing-check' . $blog_id );

    // save billing plan if set
    if ( isset( $_POST['billing_plan'] ) ) {
        $billing_plan = sanitize_text_field( $_POST['billing_plan'] );
        update_blog_option( $blog_id, 'billing_plan', $billing_plan );
    }

    // redirect with success flag
    wp_safe_redirect( add_query_arg( array(
        'page'    => 'billing',
        'id'      => $blog_id,
        'updated' => 'true'
    ), network_admin_url( 'sites.php' ) ) );

    exit;
}

// show success notice after saving billing plan
add_action( 'network_admin_notices', 'multisite_billing_manager_notice_success' );
function multisite_billing_manager_notice_success() {
    if ( isset( $_GET['updated'], $_GET['page'] ) && $_GET['page'] === 'billing' ) {
        echo '<div id="message" class="updated notice is-dismissible">
            <p>' . esc_html__( 'Settings saved successfully!', 'multisite-billing-manager' ) . '</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'multisite-billing-manager' ) . '</span>
            </button>
        </div>';
    }
}

// validate site id before showing billing page
add_action( 'current_screen', 'multisite_billing_manager_redirects' );
function multisite_billing_manager_redirects() {
    $screen = get_current_screen();

    if ( $screen->id !== 'sites_page_billing-network' ) {
        return;
    }

    if ( ! isset( $_GET['id'] ) || ! intval( $_GET['id'] ) ) {
        wp_die( esc_html__( 'Invalid site ID.', 'multisite-billing-manager' ) );
    }

    $id = intval( $_GET['id'] );
    $details = get_site( $id );

    if ( ! $details ) {
        wp_die( esc_html__( 'The requested site does not exist.', 'multisite-billing-manager' ) );
    }
}

// add billing page under dashboard for child sites
add_action( 'admin_menu', 'multisite_billing_manager_child_site_menu' );
function multisite_billing_manager_child_site_menu() {
    add_submenu_page(
        'index.php',
        esc_html__( 'Billing', 'multisite-billing-manager' ),
        esc_html__( 'Billing', 'multisite-billing-manager' ),
        'read',
        'billing',
        'multisite_billing_manager_child_site_page'
    );
}

// show billing level on child site billing page
function multisite_billing_manager_child_site_page() {
    $blog_id = get_current_blog_id();
    $billplan = get_blog_option( $blog_id, 'billing_plan', 'free' );

    // define plan labels
    $plan_labels = array(
        'free'    => 'Free',
        'basic'   => 'Basic',
        'premium' => 'Premium',
        'vip'     => 'VIP'
    );

    // get label for current plan
    $plan_label = 'Free';
    if ( isset( $plan_labels[$billplan] ) ) {
        $plan_label = $plan_labels[$billplan];
    }

    echo '<div class="wrap">
        <h1>' . esc_html__( 'Billing Plan', 'multisite-billing-manager' ) . '</h1>
        <p>' . esc_html__( 'Your site is currently on the ', 'multisite-billing-manager' ) . '<strong>' . esc_html( $plan_label ) . '</strong> ' . esc_html__( 'plan and includes associated features.', 'multisite-billing-manager' ) . '</p>
    </div>';
}

// Ref: ChatGPT
// Ref: https://rudrastyh.com/wordpress-multisite/custom-tabs-with-options.html
// Ref: https://stackoverflow.com/questions/44015298/wordpress-checked-checked-syntax
