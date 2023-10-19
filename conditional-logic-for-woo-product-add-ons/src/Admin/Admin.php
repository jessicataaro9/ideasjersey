<?php

namespace MeowCrew\AddonsConditions\Admin;

use  MeowCrew\AddonsConditions\AddonsConditionsPlugin ;
use  MeowCrew\AddonsConditions\Core\ServiceContainerTrait ;
use  MeowCrew\AddonsConditions\SanitizeManager ;
use  MeowCrew\AddonsConditions\Schema ;
/**
 * Class Admin
 *
 * @package MeowCrew\AddonsConditions\Admin
 */
class Admin
{
    use  ServiceContainerTrait ;
    protected  $savedAddons = 0 ;
    public function __construct()
    {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ) );
        add_action(
            'woocommerce_product_addons_panel_before_options',
            array( $this, 'renderConditionalBlock' ),
            10,
            3
        );
        add_filter(
            'woocommerce_product_addons_save_data',
            array( $this, 'handleSaving' ),
            10,
            2
        );
    }
    
    /**
     * Render conditional block
     *
     * @param  WP_Post  $post
     * @param  array  $currentAddon
     * @param  int  $loop
     */
    public function renderConditionalBlock( $post, $currentAddon, $loop )
    {
        $newAddon = false;
        $isProduct = false;
        $postID = 0;
        
        if ( !$post ) {
            
            if ( isset( $_GET['page'] ) && 'addons' === $_GET['page'] && isset( $_GET['edit'] ) ) {
                $postID = (int) sanitize_text_field( $_GET['edit'] );
                $addons = array_filter( (array) get_post_meta( $postID, '_product_addons', true ) );
            } else {
                $addons = array();
                $newAddon = true;
            }
        
        } else {
            $postID = $post->ID;
            $addons = array_filter( (array) get_post_meta( $postID, '_product_addons', true ) );
            $isProduct = true;
        }
        
        foreach ( $addons as $_addonKey => $_addon ) {
            if ( empty($_addon['slug']) ) {
                $addons[$_addonKey]['slug'] = uniqid();
            }
        }
        if ( in_array( $currentAddon['type'], Schema::getSupportedAddonTypes() ) ) {
            $this->getContainer()->getFileManager()->includeTemplate( 'admin/conditions/conditions.php', array(
                'loop'          => $loop,
                'current_addon' => $currentAddon,
                'addons'        => $addons,
                'new_addon'     => $newAddon,
                'is_product'    => $isProduct,
                'post_id'       => $postID,
            ) );
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueueAdminScripts()
    {
        wp_register_script(
            'cfpa-admin.js',
            $this->getContainer()->getFileManager()->locateAsset( 'admin/admin.js' ),
            array( 'jquery' ),
            AddonsConditionsPlugin::VERSION
        );
        wp_localize_script( 'cfpa-admin.js', 'cfpaGLOBAL', array(
            'isPremium' => clfwpao_fs()->is_premium(),
        ) );
        wp_enqueue_script( 'cfpa-admin.js' );
        wp_enqueue_style(
            'cfpa-admin.css',
            $this->getContainer()->getFileManager()->locateAsset( 'admin/admin.css' ),
            array(),
            AddonsConditionsPlugin::VERSION
        );
    }
    
    /**
     * Handle saving a product addon
     *
     * @param  array  $data
     * @param  int  $loop
     *
     * @return array
     */
    public function handleSaving( $data, $loop )
    {
        $data['slug'] = SanitizeManager::getPOSTAddonSlug( $loop );
        $data['condition_enabled'] = SanitizeManager::getPOSTIsConditionsEnabled( $loop );
        if ( !clfwpao_fs()->is_premium() && $this->savedAddons > 1 ) {
            return $data;
        }
        $data['conditional_rules'] = SanitizeManager::getPOSTConditionalRules( $loop );
        $data['condition_action'] = SanitizeManager::getPOSTConditionAction( $loop );
        $data['condition_match_type'] = SanitizeManager::getPOSTConditionMatchType( $loop );
        if ( !empty($data['conditional_rules']) ) {
            $this->savedAddons++;
        }
        return $data;
    }

}