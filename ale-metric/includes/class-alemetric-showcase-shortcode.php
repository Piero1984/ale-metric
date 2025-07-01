<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Showcase_Shortcode {

    public function __construct() {
        add_shortcode('ale_metric_showcase', [$this, 'render_showcase']);
        // RIMOSSO: add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    // METODO RIMOSSO: enqueue_scripts() non è più necessario

    public function render_showcase($atts) {
        // AGGIUNTO: Carica il CSS solo quando lo shortcode viene effettivamente utilizzato
        wp_enqueue_style('ale-metric-shortcode-style', ALE_METRIC_URL . 'assets/ale-metric-shortcode.css', [], '1.0');
        
        $showcase_data = get_option('ale_metric_showcase', []);

        if (empty($showcase_data['products'])) {
            return '<p>Nessun prodotto configurato nella vetrina.</p>';
        }

        ob_start();
        ?>

        <style>
        .ale-metric-button-disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
        </style>

        <div class="ale-metric-showcase">
            <div class="ale-metric-fields">

                <!-- Selezione Prodotto -->
                <div class="ale-metric-section">
                    <h4>SELEZIONA PRODOTTO</h4>
                    <div class="ale-metric-field">
                        <label>Prodotto:</label>
                        <select id="ale_product_selector">
                            <option value="">-- Seleziona un prodotto --</option>
                            <?php foreach ($showcase_data['products'] as $index => $product_config):
                                $product = wc_get_product($product_config['id']);
                                if (!$product) continue;
                            ?>
                                <option value="<?php echo $index; ?>"
                                        data-product-id="<?php echo $product_config['id']; ?>"
                                        data-product-name="<?php echo esc_attr($product->get_name()); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Form Dimensioni (sempre visibile) -->
                <div id="ale_dimensions_form">
                    <?php foreach ($showcase_data['products'] as $index => $product_config):
                        $product = wc_get_product($product_config['id']);
                        if (!$product) continue;

                        $calc_type = $product_config['calculation_type'];
                        $dimensions = $product_config['dimensions'];
                        $unit = $product_config['unit'];
                        $price = $product_config['price'];
                        $unit_text = $calc_type === 'ml' ? 'm' : ($calc_type === 'm3' ? 'm³' : 'm²');
                    ?>
                    <div class="ale-product-form"
                         data-index="<?php echo $index; ?>"
                         data-product-id="<?php echo $product_config['id']; ?>"
                         data-calc-type="<?php echo $calc_type; ?>"
                         data-unit-text="<?php echo $unit_text; ?>"
                         data-base-price="<?php echo $price; ?>"
                         style="<?php echo $index === 0 ? 'display:block;' : 'display:none;'; ?>">
                         <!-- Lo stile sopra mostra il primo form per default -->

                        <div class="ale-metric-section">
                            <h4>Dimensioni <span style="font-size:11px;font-weight:normal;color:#666;">(inserisci valori numerici)</span></h4>

                            <?php foreach ($dimensions as $dim): ?>
                            <div class="ale-metric-field">
                                <label><?php echo esc_html($dim['label']); ?>:</label>
                                <input type="number"
                                       class="ale-dimension-input"
                                       min="<?php echo $dim['min']; ?>"
                                       max="<?php echo $dim['max']; ?>"
                                       step="<?php echo $dim['step']; ?>"
                                       placeholder="Min: <?php echo $dim['min']; ?> - Max: <?php echo $dim['max']; ?>"
                                       required>
                                <span><?php echo esc_html($unit); ?></span>
                                <div style="font-size:11px;color:#666;margin-top:2px;">
                                    ℹ️ Valore consentito: da <?php echo $dim['min']; ?> a <?php echo $dim['max']; ?> <?php echo esc_html($unit); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Sezione Prezzo -->
                        <div class="ale-metric-price-section">
                            <strong>Prezzo: € <span class="ale-calculated-price">-</span></strong>
                            <small>(<span class="ale-calculated-area">-</span>)</small>
                            <div style="font-size:11px;color:#28a745;margin-top:5px;">
                                Prezzo base al <?php echo $unit_text; ?>: € <?php echo number_format($price, 2, ',', '.'); ?>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div style="text-align: center; margin-top: 15px; padding: 15px; background: #e7f3ff; border-radius: 4px;">
                            <p style="margin: 0 0 10px 0; font-size: 14px;">Per il preventivo completo con tutte le opzioni:</p>
                            <a href="<?php echo get_permalink($product_config['id']); ?>"
                               class="button ale-metric-config-button"
                               style="background: #0073aa; color: white; padding: 8px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                                Configura prodotto completo →
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var currentProductForm = null;
            var firstProductForm = $('.ale-product-form').first();

            // Inizialmente disabilita tutti i campi e tutti i pulsanti
            $('.ale-dimension-input').prop('disabled', true);
            $('.ale-metric-config-button').addClass('ale-metric-button-disabled');

            // Mostra il primo form se esiste (con campi disabilitati)
            if (firstProductForm.length) {
                $('.ale-product-form').hide();
                firstProductForm.show();
            }

            $('#ale_product_selector').on('change', function() {
                var selectedIndex = $(this).val();

                // Disabilita tutti i campi e tutti i pulsanti
                $('.ale-dimension-input').prop('disabled', true);
                $('.ale-metric-config-button').addClass('ale-metric-button-disabled');

                if (selectedIndex) {
                    // Nascondi tutti i form
                    $('.ale-product-form').hide();

                    // Mostra il form selezionato
                    currentProductForm = $('.ale-product-form[data-index="' + selectedIndex + '"]');
                    currentProductForm.show();

                    // Abilita i campi del form selezionato
                    currentProductForm.find('.ale-dimension-input').prop('disabled', false);

                    // Abilita il pulsante del form selezionato
                    currentProductForm.find('.ale-metric-config-button').removeClass('ale-metric-button-disabled');

                    // Resetta i campi e i risultati
                    currentProductForm.find('input').val('');
                    currentProductForm.find('.ale-calculated-price').text('-');
                    currentProductForm.find('.ale-calculated-area').text('-');
                } else {
                    // Mostra il primo form (se esiste) con campi disabilitati
                    $('.ale-product-form').hide();
                    if (firstProductForm.length) {
                        firstProductForm.show();
                    }
                    currentProductForm = null;
                }
            });

            $(document).on('input', '.ale-dimension-input', function() {
                if (!currentProductForm) return;

                var calcType = currentProductForm.data('calc-type');
                var basePrice = parseFloat(currentProductForm.data('base-price'));
                var unitText = currentProductForm.data('unit-text');
                var unit = currentProductForm.find('.ale-dimension-input').first().next('span').text();

                var allFilled = true;
                var dimensions = [];
                var hasError = false;

                currentProductForm.find('.ale-dimension-input').each(function() {
                    var value = parseFloat($(this).val());
                    var min = parseFloat($(this).attr('min'));
                    var max = parseFloat($(this).attr('max'));

                    if (isNaN(value)) {
                        allFilled = false;
                    } else if (value < min || value > max) {
                        hasError = true;
                        $(this).css('border-color', '#dc3545');
                    } else {
                        $(this).css('border-color', '#ced4da');
                        var valueInMeters = value;
                        if (unit === 'cm') valueInMeters = value / 100;
                        else if (unit === 'mm') valueInMeters = value / 1000;
                        dimensions.push(valueInMeters);
                    }
                });

                if (allFilled && !hasError && dimensions.length > 0) {
                    var measure = 0;
                    if (calcType === 'ml') {
                        measure = dimensions[0];
                    } else if (calcType === 'm2') {
                        measure = dimensions[0] * dimensions[1];
                    } else { // m3
                        measure = dimensions[0] * dimensions[1] * dimensions[2];
                    }

                    var price = measure * basePrice;
                    currentProductForm.find('.ale-calculated-price').text(price.toFixed(2).replace('.', ','));
                    currentProductForm.find('.ale-calculated-area').text(measure.toFixed(4) + ' ' + unitText);
                } else {
                    currentProductForm.find('.ale-calculated-price').text('-');
                    currentProductForm.find('.ale-calculated-area').text('-');
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}