<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_ale_calculate_price', [$this, 'calculate_price']);
        add_action('wp_ajax_nopriv_ale_calculate_price', [$this, 'calculate_price']);
    }
    
    public function calculate_price() {
        check_ajax_referer('ale_metric_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $values = $_POST['values'] ?? [];
        
        $base_price = floatval(get_post_meta($product_id, '_ale_metric_base_price', true));
        $text_pricing = get_post_meta($product_id, '_ale_metric_text_pricing', true) ?: [];
        $numeric_attributes = get_post_meta($product_id, '_ale_metric_numeric_attributes', true) ?: [];
        
        $dimension1 = get_post_meta($product_id, '_ale_metric_dimension1', true);
        $dimension2 = get_post_meta($product_id, '_ale_metric_dimension2', true);
        $dimension3 = get_post_meta($product_id, '_ale_metric_dimension3', true);
        $calculation_type = get_post_meta($product_id, '_ale_metric_calculation_type', true) ?: 'm2';
        
        if ($base_price <= 0) {
            wp_send_json_error('Prezzo base non configurato');
            return;
        }
        
        $measure = $this->calculate_measure($values, $dimension1, $dimension2, $dimension3, $calculation_type);
        if ($measure <= 0) {
            wp_send_json_error('Impossibile calcolare la misura - verifica configurazione dimensioni');
            return;
        }
        
        $total_price = $base_price * $measure;
        $text_cost = $this->calculate_text_costs($values, $text_pricing, $measure);
        $total_price += $text_cost;
        
        // Calcola costi accessori SOLO se selezionati
        $accessories_cost = 0;
        $selected_accessories = [];
        foreach ($values as $key => $value) {
            if (strpos($key, 'ale_accessory_') === 0 && $value === '1') {
                $index = str_replace('ale_accessory_', '', $key);
                $selected_accessories[$index] = '1';
            }
        }
        
        if (!empty($selected_accessories)) {
            $accessories = get_post_meta($product_id, '_ale_metric_accessories', true) ?: [];
            foreach ($selected_accessories as $index => $selected) {
                if (isset($accessories[$index])) {
                    $acc = $accessories[$index];
                    $base = floatval($acc['base_price'] ?? 0);
                    $increment = floatval($acc['increment_price'] ?? 0);
                    
                    if ($measure <= 1) {
                        $accessories_cost += $base;
                    } else {
                        $accessories_cost += $base + ($increment * ($measure - 1));
                    }
                }
            }
        }
        
        $total_price += $accessories_cost;
        
        $unit = '';
        switch($calculation_type) {
            case 'm3':
                $unit = 'm³';
                break;
            case 'ml':
                $unit = 'm';
                break;
            default:
                $unit = 'm²';
        }
        
        wp_send_json_success([
            'price' => number_format($total_price, 2, '.', ''),
            'formatted' => wc_price($total_price),
            'area' => $measure,
            'measure' => $measure,
            'unit' => $unit,
            'base_cost' => $base_price * $measure,
            'text_cost' => $text_cost,
            'accessories_cost' => $accessories_cost
        ]);
    }
    
    private function calculate_measure($values, $dimension1, $dimension2, $dimension3, $calculation_type) {
        switch($calculation_type) {
            case 'm3':
                if (empty($dimension1) || empty($dimension2) || empty($dimension3)) {
                    return 0;
                }
                
                $field1 = 'ale_' . $dimension1;
                $field2 = 'ale_' . $dimension2;
                $field3 = 'ale_' . $dimension3;
                
                if (!isset($values[$field1]) || !isset($values[$field2]) || !isset($values[$field3])) {
                    return 0;
                }
                
                $val1 = floatval($values[$field1]);
                $val2 = floatval($values[$field2]);
                $val3 = floatval($values[$field3]);
                
                if ($val1 <= 0 || $val2 <= 0 || $val3 <= 0) {
                    return 0;
                }
                
                return ($val1 * $val2 * $val3) / 1000000;
                
            case 'ml':
                if (empty($dimension1)) {
                    return 0;
                }
                
                $field1 = 'ale_' . $dimension1;
                
                if (!isset($values[$field1])) {
                    return 0;
                }
                
                $val1 = floatval($values[$field1]);
                
                if ($val1 <= 0) {
                    return 0;
                }
                
                return $val1 / 100;
                
            case 'm2':
            default:
                if (empty($dimension1) || empty($dimension2)) {
                    return 0;
                }
                
                $field1 = 'ale_' . $dimension1;
                $field2 = 'ale_' . $dimension2;
                
                if (!isset($values[$field1]) || !isset($values[$field2])) {
                    return 0;
                }
                
                $val1 = floatval($values[$field1]);
                $val2 = floatval($values[$field2]);
                
                if ($val1 <= 0 || $val2 <= 0) {
                    return 0;
                }
                
                return ($val1 * $val2) / 10000;
        }
    }
    
    private function calculate_text_costs($values, $text_pricing, $measure) {
        $total_text_cost = 0;
        
        foreach ($text_pricing as $attr_key => $value_prices) {
            $field_name = 'ale_' . $attr_key;
            
            if (!isset($values[$field_name])) {
                continue;
            }
            
            $selected_value = sanitize_title($values[$field_name]);
            
            if (isset($value_prices[$selected_value])) {
                $base = floatval($value_prices[$selected_value]['base'] ?? 0);
                $increment = floatval($value_prices[$selected_value]['increment'] ?? 0);
                
                if ($measure <= 1) {
                    $total_text_cost += $base;
                } else {
                    $total_text_cost += $base + ($increment * ($measure - 1));
                }
            }
        }
        
        return $total_text_cost;
    }
}