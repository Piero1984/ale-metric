<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Accessories_Cart {
    
    public function __construct() {
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_accessories_to_cart'], 20, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_accessories_in_cart'], 20, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_accessories_to_order'], 20, 4);
    }
    
    public function add_accessories_to_cart($cart_item_data, $product_id, $variation_id) {
        if (!get_post_meta($product_id, '_ale_metric_enabled', true)) {
            return $cart_item_data;
        }
        
        $accessories = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'ale_accessory_') === 0 && $value == '1') {
                $index = str_replace('ale_accessory_', '', $key);
                $accessories[$index] = '1';
            }
        }
        
        if (!empty($accessories)) {
            $cart_item_data['ale_accessories'] = $accessories;
        }
        
        return $cart_item_data;
    }
    
    public function display_accessories_in_cart($item_data, $cart_item) {
        if (!isset($cart_item['ale_accessories'])) {
            return $item_data;
        }
        
        $product_id = $cart_item['product_id'];
        $accessories_config = get_post_meta($product_id, '_ale_metric_accessories', true) ?: [];
        
        // Array per tracciare accessori già aggiunti
        $added_accessories = [];
        
        foreach ($cart_item['ale_accessories'] as $index => $selected) {
            if ($selected && isset($accessories_config[$index])) {
                $accessory_title = $accessories_config[$index]['title'];
                
                // Evita duplicati
                if (!in_array($accessory_title, $added_accessories)) {
                    $item_data[] = [
                        'key' => 'Accessorio',
                        'value' => $accessory_title
                    ];
                    $added_accessories[] = $accessory_title;
                }
            }
        }
        
        return $item_data;
    }
    
    public function save_accessories_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['ale_accessories'])) {
            $product_id = $values['product_id'];
            $accessories_config = get_post_meta($product_id, '_ale_metric_accessories', true) ?: [];
            
            // Array per tracciare accessori selezionati
            $selected_titles = [];
            
            foreach ($values['ale_accessories'] as $index => $selected) {
                if ($selected && isset($accessories_config[$index])) {
                    $title = $accessories_config[$index]['title'];
                    if (!in_array($title, $selected_titles)) {
                        $selected_titles[] = $title;
                    }
                }
            }
            
            // Aggiungi un unico metadato con tutti gli accessori separati da virgole
            if (!empty($selected_titles)) {
                $item->add_meta_data('Accessori', implode(', ', $selected_titles));
            }
        }
    }
}