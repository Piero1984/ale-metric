<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Accessories_Frontend {
    
    public function __construct() {
        // Costruttore vuoto - tutto gestito tramite render_accessories_section
    }
    
    public function render_accessories_section($product_id) {
        $enabled = get_post_meta($product_id, '_ale_metric_enabled', true);
        $accessories = get_post_meta($product_id, '_ale_metric_accessories', true) ?: [];
        
        // MODIFICA: Verifica che ci siano effettivamente accessori da mostrare
        if (!$enabled || empty($accessories)) {
            return;
        }
        
        // MODIFICA: Filtra gli accessori vuoti o non validi
        $valid_accessories = array_filter($accessories, function($acc) {
            return !empty($acc['title']);
        });
        
        // Se dopo il filtro non ci sono accessori validi, non mostrare nulla
        if (empty($valid_accessories)) {
            return;
        }
        ?>
        
        <style>
        .ale-accessory-item {
            border: 1px solid #e3e6ea;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .ale-accessory-header {
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }
        .ale-accessory-title {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ale-accessory-toggle {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 0 5px;
            color: #667;
        }
        .ale-accessory-checkbox {
            margin: 0; 
        }
        .ale-accessory-description {
            padding: 0 10px 10px 10px;
            color: #667;
            font-size: 13px;
            line-height: 1.5;
        }
        </style>
        
        <div class="ale-metric-section" id="ale_accessories_section">
			 <h4>Accessori <span style="font-size:11px;font-weight:normal;color:#666;">(opzionale)</span></h4>
            <?php foreach ($valid_accessories as $index => $acc): ?>
                <div class="ale-accessory-item">
                    <div class="ale-accessory-header">
                        <div class="ale-accessory-title">
                            <button type="button" class="ale-accessory-toggle">+</button>
                            <span><?php echo esc_html($acc['title']); ?></span>
                        </div>
                        <input type="checkbox" name="ale_accessory_<?php echo $index; ?>" value="1" class="ale-accessory-checkbox">
                    </div>
                    <div class="ale-accessory-description" style="display:none;">
                        <?php echo esc_html($acc['description']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $(document).on('click', '.ale-accessory-header', function(e) {
                if (!$(e.target).is('.ale-accessory-checkbox')) {
                    var $desc = $(this).closest('.ale-accessory-item').find('.ale-accessory-description');
                    var $toggle = $(this).find('.ale-accessory-toggle');
                    var isVisible = $desc.is(':visible');
                    $desc.slideToggle(200);
                    $toggle.text(isVisible ? '+' : '−');
                }
            });
            
            $(document).on('change', '.ale-accessory-checkbox', function(e) {
                e.stopPropagation();
                if (typeof calculatePrice === 'function') {
                    calculatePrice();
                }
            });
        });
        </script>
        <?php
    }
    
    public function calculate_accessories_cost($product_id, $measure, $selected_accessories) {
        $accessories = get_post_meta($product_id, '_ale_metric_accessories', true) ?: [];
        $total_cost = 0;
        
        foreach ($selected_accessories as $index => $selected) {
            if ($selected && isset($accessories[$index])) {
                $acc = $accessories[$index];
                $base = floatval($acc['base_price'] ?? 0);
                $increment = floatval($acc['increment_price'] ?? 0);
                
                if ($measure <= 1) {
                    $total_cost += $base;
                } else {
                    $total_cost += $base + ($increment * ($measure - 1));
                }
            }
        }
        
        return $total_cost;
    }
}