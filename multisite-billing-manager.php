<?php 
/*
Plugin Name: Multisite Billing Manager
Plugin URI: https://www.littlebizzy.com/plugins/multisite-billing-manager
Description: 
Version: 0.0.0
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
