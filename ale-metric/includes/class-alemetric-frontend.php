<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('woocommerce_before_add_to_cart_button', [$this, 'add_metric_fields']);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_add_to_cart'], 10, 3);
        add_filter('woocommerce_product_get_price', [$this, 'get_dynamic_price'], 10, 2);
        
        // Nascondi quantità per prodotti Ale Metric
        add_filter('woocommerce_is_sold_individually', [$this, 'hide_quantity_field'], 10, 2);
        add_filter('woocommerce_cart_item_quantity', [$this, 'hide_cart_quantity'], 10, 3);
        add_filter('woocommerce_checkout_cart_item_quantity', [$this, 'hide_checkout_quantity'], 10, 3);
    }
    
    public function enqueue_scripts() {
        if (is_product()) {
            // Carica CSS
            wp_enqueue_style('ale-metric-style', ALE_METRIC_URL . 'assets/ale-metric.css', [], '1.0');
            
            // JavaScript
            $js_file = ALE_METRIC_PATH . 'assets/frontend.js';
            if (file_exists($js_file)) {
                wp_enqueue_script('ale-metric-frontend', ALE_METRIC_URL . 'assets/frontend.js', ['jquery'], '1.0', true);
                wp_localize_script('ale-metric-frontend', 'ale_metric_ajax', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ale_metric_nonce')
                ]);
            } else {
                add_action('wp_footer', [$this, 'inline_frontend_js']);
            }
        }
    }
    
    public function inline_frontend_js() {
        ?>
        <script>
        var ale_metric_ajax = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('ale_metric_nonce'); ?>'
        };
        
        jQuery(document).ready(function($) {
            if (!$('.ale-metric-fields').length) return;
            
            var $priceDisplay = $('#ale_calculated_price');
            var $areaDisplay = $('#ale_calculated_area');
            var $hiddenPrice = $('#ale_metric_calculated_price');
            var $form = $('form.cart');
            var productId = $('.ale-metric-fields').data('product-id');
            
            $('.ale-metric-fields').on('change input', 'input, select', function() {
                calculatePrice();
            });
            
            $form.on('submit', function(e) {
                if ($hiddenPrice.val() === '') {
                    e.preventDefault();
                    calculatePrice(function() {
                        $form.submit();
                    });
                }
            });
            
            function calculatePrice(callback) {
                var formData = {};
                var allFilled = true;
                
                $('.ale-metric-fields input[name^="ale_"], .ale-metric-fields select[name^="ale_"]').each(function() {
                    var name = $(this).attr('name');
                    var value = $(this).val();
                    
                    if (name) {
                        formData[name] = value;
                        if (!value) allFilled = false;
                    }
                });
                
                if (!allFilled) {
                    $priceDisplay.text('-');
                    $areaDisplay.text('-');
                    $hiddenPrice.val('');
                    return;
                }
                
                $.ajax({
                    url: ale_metric_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ale_calculate_price',
                        product_id: productId,
                        values: formData,
                        nonce: ale_metric_ajax.nonce
                    },
                    success: function(response) {
                        console.log('Risposta:', response);
                        if (response.success) {
                            $priceDisplay.text(response.data.price);
                            $areaDisplay.text(response.data.measure.toFixed(4) + ' ' + response.data.unit);
                            $hiddenPrice.val(response.data.price);
                            $('#ale_price_display').html(response.data.formatted);
                            if (callback) callback();
                        } else {
                            $priceDisplay.text('Errore');
                            console.error('Errore:', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        $priceDisplay.text('Errore AJAX');
                        console.error('AJAX Error:', status, error, xhr.responseText);
                    }
                });
            }
            
            calculatePrice();
        });
        </script>
        <?php
    }
    
    public function add_metric_fields() {
        global $product;
        
        if (!get_post_meta($product->get_id(), '_ale_metric_enabled', true)) {
            return;
        }
        
        $numeric_attributes = get_post_meta($product->get_id(), '_ale_metric_numeric_attributes', true) ?: [];
        $text_attributes = get_post_meta($product->get_id(), '_ale_metric_text_attributes', true) ?: [];
        $calculation_type = get_post_meta($product->get_id(), '_ale_metric_calculation_type', true) ?: 'm2';
        $base_price = get_post_meta($product->get_id(), '_ale_metric_base_price', true) ?: 0;
        
        // Determina quali dimensioni mostrare
        $dimension1 = get_post_meta($product->get_id(), '_ale_metric_dimension1', true);
        $dimension2 = get_post_meta($product->get_id(), '_ale_metric_dimension2', true);
        $dimension3 = get_post_meta($product->get_id(), '_ale_metric_dimension3', true);
        
        $dimensions_to_show = [];
        switch($calculation_type) {
            case 'm3':
                if ($dimension1) $dimensions_to_show[] = $dimension1;
                if ($dimension2) $dimensions_to_show[] = $dimension2;
                if ($dimension3) $dimensions_to_show[] = $dimension3;
                break;
            case 'ml':
                if ($dimension1) $dimensions_to_show[] = $dimension1;
                break;
            default: // m2
                if ($dimension1) $dimensions_to_show[] = $dimension1;
                if ($dimension2) $dimensions_to_show[] = $dimension2;
        }
        
        ?>
        <div class="ale-metric-price">
            <span id="ale_price_display"><?php echo wc_price($base_price); ?></span>
        </div>
        
        <div class="ale-metric-fields" data-product-id="<?php echo $product->get_id(); ?>">
            
            <?php if ($numeric_attributes): ?>
            <div class="ale-metric-section">
                <h4>Dimensioni <span style="font-size:11px;font-weight:normal;color:#666;">(inserisci valori numerici)</span></h4>
                <?php foreach ($numeric_attributes as $attr): ?>
                    <?php 
                    $field_name = 'ale_' . sanitize_title($attr['label']);
                    $attr_key = sanitize_title($attr['label']);
                    
                    // Mostra solo le dimensioni configurate per il tipo di calcolo
                    if (!in_array($attr_key, $dimensions_to_show)) continue;
                    
                    list($min, $max) = explode('-', $attr['range']);
                    ?>
                    <div class="ale-metric-field">
                        <label><?php echo esc_html($attr['label']); ?>:</label>
                        <input type="number" 
                               name="<?php echo $field_name; ?>" 
                               min="<?php echo floatval($min); ?>" 
                               max="<?php echo floatval($max); ?>" 
                               step="<?php echo floatval($attr['step']); ?>"
                               placeholder="Min: <?php echo floatval($min); ?> - Max: <?php echo floatval($max); ?>"
                               required>
                        <span><?php echo esc_html($attr['unit']); ?></span>
                        <div style="font-size:11px;color:#666;margin-top:2px;">
                            ℹ️ Valore consentito: da <?php echo floatval($min); ?> a <?php echo floatval($max); ?> <?php echo esc_html($attr['unit']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($text_attributes): ?>
            <div class="ale-metric-section">
                <h4>Opzioni <span style="font-size:11px;font-weight:normal;color:#666;">(scegli le caratteristiche)</span></h4>
                <div style="font-size:12px;color:#666;margin-bottom:10px;background:#f9f9f9;padding:8px;border-radius:4px;">
    <em>Seleziona le opzioni desiderate dal menu a tendina</em>
</div>
                <?php foreach ($text_attributes as $attr): ?>
                    <?php 
                    $field_name = 'ale_' . sanitize_title($attr['label']);
                    $options = array_map('trim', explode('|', $attr['values']));
                    ?>
                    <div class="ale-metric-field">
                        <label><?php echo esc_html($attr['label']); ?>:</label>
                        <select name="<?php echo $field_name; ?>" required>
                            <option value="">Seleziona <?php echo strtolower($attr['label']); ?>...</option>
                            <?php foreach ($options as $option): ?>
                                <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php 
            $accessories_frontend = new AleMetric_Accessories_Frontend();
            $accessories_frontend->render_accessories_section($product->get_id());
            ?>
            
            <div class="ale-metric-price-section">
                <strong>Prezzo: € <span id="ale_calculated_price">-</span></strong>
                <small>(<span id="ale_calculated_area">-</span>)</small>
                <div style="font-size:11px;color:#28a745;margin-top:5px;">
                     Il prezzo include tutte le personalizzazioni e gli accessori selezionati
                </div>
            </div>
            
            <input type="hidden" id="ale_metric_calculated_price" name="ale_metric_calculated_price">
        </div>
        
        <div class="ale-metric-signature">Ale Metric by Fusion.43</div>
        
        <script>
        jQuery(document).ready(function($) {
            console.log('Script ALE METRIC caricato');
            
            var $priceDisplay = $('#ale_calculated_price');
            var $areaDisplay = $('#ale_calculated_area');
            var $hiddenPrice = $('#ale_metric_calculated_price');
            var $form = $('form.cart');
            var productId = <?php echo $product->get_id(); ?>;
            
            function calculatePrice() {
                console.log('Calcolo prezzo...');
                var formData = {};
                var allFilled = true;
                
                // Raccogli tutti i campi
                $('.ale-metric-fields input[name^="ale_"], .ale-metric-fields select[name^="ale_"]').each(function() {
                    var name = $(this).attr('name');
                    var value;
                    
                    // Skip del campo prezzo calcolato
                    if (name === 'ale_metric_calculated_price') return;
                    
                    // Gestione checkbox
                    if ($(this).is(':checkbox')) {
                        value = $(this).is(':checked') ? '1' : '0';
                    } else {
                        value = $(this).val();
                    }
                    
                    formData[name] = value;
                    
                    // Verifica campi obbligatori (non checkbox)
                    if (!$(this).is(':checkbox') && !value) {
                        allFilled = false;
                    }
                });
                
                console.log('Dati form:', formData, 'Tutti compilati:', allFilled);
                
                if (!allFilled) {
                    $priceDisplay.text('-');
                    $areaDisplay.text('-');
                    $hiddenPrice.val('');
                    $('#ale_price_display').html('<?php echo wc_price($base_price); ?>');
                    return;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'ale_calculate_price',
                        product_id: productId,
                        values: formData,
                        nonce: '<?php echo wp_create_nonce('ale_metric_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('Risposta AJAX:', response);
                        if (response.success) {
                            $priceDisplay.text(response.data.price);
                            $areaDisplay.text(response.data.measure.toFixed(4) + ' ' + response.data.unit);
                            $hiddenPrice.val(response.data.price);
                            $('#ale_price_display').html(response.data.formatted);
                        } else {
                            $priceDisplay.text('Errore');
                            $('#ale_price_display').html('<?php echo wc_price(0); ?>');
                            console.error('Errore calcolo:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Errore AJAX:', xhr.responseText);
                        $priceDisplay.text('Errore AJAX');
                        $('#ale_price_display').html('<?php echo wc_price(0); ?>');
                    }
                });
            }
            
            // Event listeners
            $('.ale-metric-fields').on('change input', 'input[type="number"], select', function() {
                console.log('Campo cambiato:', $(this).attr('name'), $(this).val());
                calculatePrice();
            });
            
            // Listener specifico per checkbox con debounce
            var checkboxTimer;
            $('.ale-metric-fields').on('change', 'input[type="checkbox"]', function() {
                console.log('Checkbox cambiato:', $(this).attr('name'), $(this).is(':checked'));
                clearTimeout(checkboxTimer);
                checkboxTimer = setTimeout(function() {
                    calculatePrice();
                }, 100);
            });
            
            // Previeni submit se prezzo non calcolato
            $form.on('submit', function(e) {
                if (!$hiddenPrice.val() || $hiddenPrice.val() === '') {
                    e.preventDefault();
                    calculatePrice();
                    setTimeout(function() {
                        if ($hiddenPrice.val() && $hiddenPrice.val() !== '') {
                            $form.submit();
                        }
                    }, 500);
                }
            });
            
            // Calcolo iniziale
            setTimeout(calculatePrice, 300);
        });
        </script>
        <?php
    }
    
    public function validate_add_to_cart($passed, $product_id, $quantity) {
        if (!get_post_meta($product_id, '_ale_metric_enabled', true)) {
            return $passed;
        }
        
        $numeric_attributes = get_post_meta($product_id, '_ale_metric_numeric_attributes', true) ?: [];
        $text_attributes = get_post_meta($product_id, '_ale_metric_text_attributes', true) ?: [];
        $calculation_type = get_post_meta($product_id, '_ale_metric_calculation_type', true) ?: 'm2';
        
        // Ottieni dimensioni da validare
        $dimension1 = get_post_meta($product_id, '_ale_metric_dimension1', true);
        $dimension2 = get_post_meta($product_id, '_ale_metric_dimension2', true);
        $dimension3 = get_post_meta($product_id, '_ale_metric_dimension3', true);
        
        $dimensions_to_validate = [];
        switch($calculation_type) {
            case 'm3':
                if ($dimension1) $dimensions_to_validate[] = $dimension1;
                if ($dimension2) $dimensions_to_validate[] = $dimension2;
                if ($dimension3) $dimensions_to_validate[] = $dimension3;
                break;
            case 'ml':
                if ($dimension1) $dimensions_to_validate[] = $dimension1;
                break;
            default:
                if ($dimension1) $dimensions_to_validate[] = $dimension1;
                if ($dimension2) $dimensions_to_validate[] = $dimension2;
        }
        
        foreach ($numeric_attributes as $attr) {
            $attr_key = sanitize_title($attr['label']);
            if (in_array($attr_key, $dimensions_to_validate)) {
                $key = 'ale_' . $attr_key;
                if (!isset($_POST[$key]) || empty($_POST[$key])) {
                    wc_add_notice('⚠️ Per favore inserisci: ' . $attr['label'], 'error');
                    return false;
                }
                
                // Validazione min/max
                $value = floatval($_POST[$key]);
                list($min, $max) = explode('-', $attr['range']);
                if ($value < floatval($min) || $value > floatval($max)) {
                    wc_add_notice('⚠️ ' . $attr['label'] . ' deve essere tra ' . floatval($min) . ' e ' . floatval($max) . ' ' . $attr['unit'], 'error');
                    return false;
                }
            }
        }
        
        foreach ($text_attributes as $attr) {
            $key = 'ale_' . sanitize_title($attr['label']);
            if (!isset($_POST[$key]) || empty($_POST[$key])) {
                wc_add_notice('⚠️ Per favore seleziona: ' . $attr['label'], 'error');
                return false;
            }
        }
        
        // Verifica prezzo calcolato
        if (!isset($_POST['ale_metric_calculated_price']) || empty($_POST['ale_metric_calculated_price'])) {
            wc_add_notice('⚠️ Errore nel calcolo del prezzo. Riprova.', 'error');
            return false;
        }
        
        return $passed;
    }
    
    public function get_dynamic_price($price, $product) {
        if (isset($_POST['ale_metric_calculated_price']) && !empty($_POST['ale_metric_calculated_price'])) {
            return floatval($_POST['ale_metric_calculated_price']);
        }
        return $price;
    }
    
    public function hide_quantity_field($sold_individually, $product) {
        if (get_post_meta($product->get_id(), '_ale_metric_enabled', true)) {
            return true;
        }
        return $sold_individually;
    }
    
    public function hide_cart_quantity($product_quantity, $cart_item_key, $cart_item) {
        if (isset($cart_item['ale_metric'])) {
            return '<span class="quantity">1</span>';
        }
        return $product_quantity;
    }
    
    public function hide_checkout_quantity($quantity, $cart_item, $cart_item_key) {
        if (isset($cart_item['ale_metric'])) {
            return '<span class="quantity">1</span>';
        }
        return $quantity;
    }
}