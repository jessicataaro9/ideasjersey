<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !function_exists( 'clfwpao_fs' ) ) {
    // Create a helper function for easy SDK access.
    function clfwpao_fs()
    {
        global  $clfwpao_fs ;
        
        if ( !isset( $clfwpao_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $clfwpao_fs = fs_dynamic_init( array(
                'id'             => '11637',
                'slug'           => 'conditional-logic-for-woocommerce-product-add-ons',
                'type'           => 'plugin',
                'public_key'     => 'pk_bd6b56da7db37dd322941425b35e5',
                'is_premium'     => false,
                'premium_suffix' => 'Premium',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                'days'               => 7,
                'is_require_payment' => true,
            ),
                'menu'           => array(
                'first-path' => 'plugins.php',
                'contact'    => false,
                'support'    => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $clfwpao_fs;
    }
    
    // Init Freemius.
    clfwpao_fs();
    // Signal that SDK was initiated.
    do_action( 'clfwpao_fs_loaded' );
}

add_action( 'admin_menu', function () {
    // Account
    add_submenu_page(
        null,
        __( 'Freemius Account', 'conditional-logic-for-product-addons' ),
        __( 'Freemius Account', 'conditional-logic-for-product-addons' ),
        'manage_options',
        'clfwpao-account',
        function () {
        clfwpao_fs()->_account_page_load();
        clfwpao_fs()->_account_page_render();
    }
    );
    // Contact us
    add_submenu_page(
        null,
        __( 'Contact Us', 'conditional-logic-for-product-addons' ),
        __( 'Contact Us', 'conditional-logic-for-product-addons' ),
        'manage_options',
        'clfwpao-contact-us',
        function () {
        clfwpao_fs()->_contact_page_render();
    }
    );
} );