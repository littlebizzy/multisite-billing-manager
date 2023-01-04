<?php 
/*
Plugin Name: Multisite Billing Manager
Plugin URI: https://www.littlebizzy.com/plugins/multisite-billing-manager
Description: Billing for Multisite networks
Version: 1.0.0
Author: LittleBizzy
Author URI: https://www.littlebizzy.com
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
GitHub Plugin URI: littlebizzy/multisite-billing-manager
Primary Branch: main
Forked from: https://rudrastyh.com/wordpress-multisite/custom-tabs-with-options.html
*/

add_filter( 'network_edit_site_nav_links', 'multisite_billing_manager_tab' );

function multisite_billing_manager_tab( $tabs ){

        $tabs['billing'] = array(
                'label' => 'Billing',
                'url' => 'sites.php?page=billing',
                'cap' => 'manage_sites'
        );
        return $tabs;

}

add_action( 'network_admin_menu', 'multisite_billing_manager_page' );

function multisite_billing_manager_page(){
        add_submenu_page(
                'sites.php',
                'Edit website', // will be displayed in <title>
                'Edit website', // doesn't matter
                'manage_network_options', // capabilities
                'billing',
                'multisite_billing_manager_page_generate' // the name of the function which displays the page
        );
}

/*
 * Some CSS tricks to hide the link to our custom submenu page
 */
add_action('admin_head','misha_trick');
function misha_trick(){
	
	echo '<style>
	#menu-site .wp-submenu li:last-child{
		display:none;
	}
	</style>';
	
}

function multisite_billing_manager_page_generate(){

        // do not worry about that, we will check it too
        $id = $_REQUEST['id'];

        // you can use $details = get_site( $id ) to add website specific detailes to the title
        $title = 'Edit site: ';
    
        // must be blog (child site) specific
        $billplan = get_blog_option( $id, 'billing_plan' );

        
        echo '<div class="wrap"><h1 id="edit-site">' . $title . '</h1>
	<p class="edit-site-actions"><a href="' . esc_url( get_home_url( $id, '/' ) ) . '">Visit</a> | <a href="' . esc_url( get_admin_url( $id ) ) . '">Dashboard</a></p>';

                // navigation tabs
                network_edit_site_nav( array(
                        'blog_id'  => $id,
                        'selected' => 'billing' // current tab
                ) );

                // more CSS tricks :)
                echo '
                <style>
                #menu-site .wp-submenu li.wp-first-item{
                        font-weight:600;
                }
                #menu-site .wp-submenu li.wp-first-item a{
                        color:#fff;
                }
                </style>
                <form method="post" action="edit.php?action=billingupdate">';
                        wp_nonce_field( 'billing-check' . $id );

                        echo '<input type="hidden" name="id" value="' . $id . '" />
                        <table class="form-table">
                            <tr>
                                        <th scope="row">Billing Plan</th>

<td>
<fieldset>
<legend class="screen-reader-text">Set billing plan</legend>
<label><input type="radio" name="billing_plan" value="free"' . checked( 'free', $billplan, false ) . ' />Free</label><br />
<label><input type="radio" name="billing_plan" value="basic"' . checked( 'basic', $billplan, false ) . ' />Basic</label><br />
<label><input type="radio" name="billing_plan" value="premium"' . checked( 'premium', $billplan, false ) . ' />Premium</label><br />
<fieldset>
</td>
                    </tr>
                        </table>';
                        submit_button();
                echo '</form></div>';

}

add_action('network_admin_edit_billingupdate',  'multisite_billing_manager_save_options');
function multisite_billing_manager_save_options() {

        $blog_id = $_POST['id'];

        check_admin_referer('billing-check'.$blog_id); // security check

        update_blog_option( $blog_id, 'billing_plan', $_POST['billing_plan'] );

        wp_redirect( add_query_arg( array(
                'page' => 'billing',
                'id' => $blog_id,
                'updated' => 'true'), network_admin_url('sites.php')
        ));
        // redirect to /wp-admin/sites.php?page=mishapage&blog_id=ID&updated=true

        exit;

}

add_action( 'network_admin_notices', 'multisite_billing_manager_notice_success' );
function multisite_billing_manager_notice_success() {

        if( isset( $_GET['updated'] ) && isset( $_GET['page'] ) && $_GET['page'] == 'mishapage' ) {

		echo '<div id="message" class="updated notice is-dismissible">
			<p>Congratulations!</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
		</div>';

	}

}

add_action( 'current_screen', 'misha_redirects' );
function misha_redirects(){

	// do nothing if we are on another page
	$screen = get_current_screen();
	if( $screen->id !== 'sites_page_mishapage-network' ) {
		return;
	}

	// $id is a blog ID
	$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;

	if ( ! $id ) {
		wp_die( __('Invalid site ID.') );
	}

	$details = get_site( $id );
	if ( ! $details ) {
		wp_die( __( 'The requested site does not exist.' ) );
	}

	//if ( ! can_edit_network( $details->site_id ) ) {
	//	wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );
	//}

}

// https://stackoverflow.com/questions/44015298/wordpress-checked-checked-syntax
