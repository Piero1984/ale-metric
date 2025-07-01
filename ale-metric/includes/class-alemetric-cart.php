<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Cart {
    
    public function __construct() {
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'calculate_cart_item_price'], 10, 1);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_item_data'], 10, 4);
    }
    
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (!get_post_meta($product_id, '_ale_metric_enabled', true)) {
            return $cart_item_data;
        }
        
        $cart_item_data['ale_metric'] = [];
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'ale_') === 0 && $key !== 'ale_metric_calculated_price') {
                $cart_item_data['ale_metric'][$key] = sanitize_text_field($value);
            }
        }
        
        if (isset($_POST['ale_metric_calculated_price']) && !empty($_POST['ale_metric_calculated_price'])) {
            $cart_item_data['ale_metric_price'] = floatval($_POST['ale_metric_calculated_price']);
        }
        
        return $cart_item_data;
    }
    
    public function display_cart_item_data($item_data, $cart_item) {
        if (!isset($cart_item['ale_metric'])) {
            return $item_data;
        }
        
        $product_id = $cart_item['product_id'];
        $numeric_attributes = get_post_meta($product_id, '_ale_metric_numeric_attributes', true) ?: [];
        $text_attributes = get_post_meta($product_id, '_ale_metric_text_attributes', true) ?: [];
        $all_attributes = array_merge($numeric_attributes, $text_attributes);
        
        foreach ($cart_item['ale_metric'] as $key => $value) {
            // Salta le chiavi relative agli accessori
            if (strpos($key, 'ale_accessory_') === 0) {
                continue; // Gli accessori sono gestiti da AleMetric_Accessories_Cart
            }
            
            // Gestisci attributi normali
            $clean_key = str_replace('ale_', '', $key);
            
            foreach ($all_attributes as $attr) {
                if (sanitize_title($attr['label']) == $clean_key) {
                    $unit = $attr['unit'] ?? '';
                    $item_data[] = [
                        'key' => $attr['label'],
                        'value' => $value . ($unit ? ' ' . $unit : '')
                    ];
                    break;
                }
            }
        }
        
        return $item_data;
    }
    
    public function calculate_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['ale_metric_price'])) {
                $cart_item['data']->set_price($cart_item['ale_metric_price']);
            }
        }
    }
    
    public function save_order_item_data($item, $cart_item_key, $values, $order) {
        if (isset($values['ale_metric'])) {
            foreach ($values['ale_metric'] as $key => $value) {
                // Salta le chiavi relative agli accessori
                if (strpos($key, 'ale_accessory_') === 0) {
                    continue; // Gli accessori sono gestiti da AleMetric_Accessories_Cart
                }
                
                // Gestisci attributi normali
                $label = str_replace('ale_', '', $key);
                $item->add_meta_data($label, $value);
            }
        }
    }
}