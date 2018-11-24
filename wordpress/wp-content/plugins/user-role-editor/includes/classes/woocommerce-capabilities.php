<?php
/**
 * Class to provide the list of WooCommerce plugin user capabilities
 *
 * @package    User-Role-Editor
 * @subpackage Admin
 * @author     Vladimir Garagulya <support@role-editor.com>
 * @copyright  Copyright (c) 2010 - 2016, Vladimir Garagulya
 **/
class URE_Woocommerce_Capabilities {

    public static $post_types = array('product', 'shop_order', 'shop_coupon', 'shop_webhook', 'product_variation', 'shop_order_refund');
    private static $capability_types = array('product', 'shop_order', 'shop_coupon', 'shop_webhook');    
    
    
    public static function add_group_to_caps(&$caps, $post_type, $group) {

        $post_types = $post_type .'s';
        $caps['edit_'. $post_types][] = $group;
        $caps['edit_others_'. $post_types][] = $group;
        $caps['publish_'. $post_types][] = $group;
        $caps['read_private_'. $post_types][] = $group;
        $caps['delete_'. $post_types][] = $group;
        $caps['delete_private_'. $post_types][] = $group;
        $caps['delete_published_'. $post_types][] = $group;
        $caps['delete_others_'. $post_types][] = $group;
        $caps['edit_private_'. $post_types][] = $group;
        $caps['edit_published_'. $post_types][] = $group;
        
    }
    // end of add_group_to_caps()
    

    private static function add_base_caps(&$caps, $group, $subgroup, $cap_type) {
        
        $cap_types = $cap_type .'s';
        $caps['edit_'. $cap_type] = array('custom', 'custom_post_types', $group, $subgroup, $cap_type);
        $caps['read_'. $cap_type] = array('custom', 'custom_post_types', $group, $subgroup, $cap_type);
        $caps['delete_'. $cap_type] = array('custom', $group, $subgroup, $cap_type);
        $caps['edit_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['edit_others_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['publish_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['read_private_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['delete_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['delete_private_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['delete_published_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['delete_others_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['edit_private_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        $caps['edit_published_'. $cap_types] = array('custom', $group, $subgroup, $cap_type);
        
    }
    // end of add_base_caps()
    
    
    /**
     * Returns full list of WooCommerce plugin user capabilities
     */
    public static function get_caps_groups() {
        
        $caps = array(
            'manage_woocommerce'=>array('custom', 'woocommerce', 'woocommerce_core'),
            'view_woocommerce_reports'=>array('custom', 'woocommerce', 'woocommerce_core'),
            'view_admin_dashboard'=>array('custom', 'woocommerce', 'woocommerce_core')
            );
        
        // code was built on the base of woocommerce/includes/class-wc-install.php method WC_Install::get_core_capabilities() 
        $group = 'woocommerce';
        foreach (self::$capability_types as $cap_type) {            
            $subgroup = $group .'_'. $cap_type;
            self::add_base_caps($caps, $group, $subgroup, $cap_type);            
            $caps['manage_'. $cap_type .'_terms'] = array('custom', $group, $subgroup, $cap_type);
            $caps['edit_'. $cap_type .'_terms'] = array('custom', $group, $subgroup, $cap_type);
            $caps['delete_'. $cap_type .'_terms'] = array('custom', $group, $subgroup, $cap_type);
            $caps['assign_'. $cap_type .'_terms'] = array('custom', $group, $subgroup, $cap_type);
        }
     
        $pto1 = get_post_type_object('product_variation');
        if (empty($pto1) || $pto1->capability_type === 'product') { // default, not redefined by some plugin
            // add capabilities group for the product_variation custom post type
            self::add_group_to_caps($caps, 'product', 'woocommerce_product_variation');
            self::add_group_to_caps($caps, 'product', 'product_variation');
        } else {
            $cap_type = 'product_variation';
            $subgroup = $group .'_'. $cap_type;
            self::add_base_caps($caps, $group, $subgroup, $cap_type);
        }
        $pto2 = get_post_type_object('shop_order_refund');
        if (empty($pto2) || $pto2->capability_type === 'shop_order') { // default, not redefined by some plugin
            // add capabilities group for the shop_order_refund custom post type
            self::add_group_to_caps($caps, 'shop_order', 'woocommerce_shop_order_refund');
            self::add_group_to_caps($caps, 'shop_order', 'shop_order_refund');
        } else {
            $cap_type = 'shop_order_variant';
            $subgroup = $group .'_'. $cap_type;
            self::add_base_caps($caps, $group, $subgroup, $cap_type);
        }

        return $caps;
    }
    // end of get()
    
    
    /**
     * This custom post types use capabilities from the other custom post types
     * So we should define capabilities set for them manually
     * @return array()
     */
    public static function get_post_types_without_caps() {
        
        $pt_without_caps = array();
        $pto1 = get_post_type_object('product_variation');
        if (!empty($pto1) && $pto1->capability_type === 'product') {
            $pt_without_caps[] = $pto1->name;
        }
        $pto2 = get_post_type_object('shop_order_refund');
        if (!empty($pto2) && $pto2->capability_type === 'shop_order') {
            $pt_without_caps[] = $pto2->name;
        }
        
        return $pt_without_caps;
    }
    // end of get_post_types_without_caps()

}
// end of URE_Woocommerce_Capabilities class