<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Images_Frontend {

    public function __construct() {
        add_action('woocommerce_before_add_to_cart_form', [$this, 'render_image_container_hook']);
        add_action('wp_ajax_ale_get_matching_image', [$this, 'get_matching_image']);
        add_action('wp_ajax_nopriv_ale_get_matching_image', [$this, 'get_matching_image']);
    }

    public function render_image_container_hook() {
        global $product;
        $this->render_image_container($product->get_id());
    }

    public function render_image_container($product_id) {
        $enabled = get_post_meta($product_id, '_ale_metric_enabled', true);
        $image_configs = get_post_meta($product_id, '_ale_metric_image_configs', true) ?: [];
        $calculation_type = get_post_meta($product_id, '_ale_metric_calculation_type', true) ?: 'm2';
        $numeric_attributes = get_post_meta($product_id, '_ale_metric_numeric_attributes', true) ?: [];
        $text_attributes = get_post_meta($product_id, '_ale_metric_text_attributes', true) ?: [];

        if (!$enabled || empty($image_configs)) {
            return;
        }

        ?>
        <div id="ale_visual_container" style="position:relative;margin:20px 0;min-height:300px;border:1px solid #ddd;background:#f9f9f9;display:none;">
            <!-- Container per l'immagine con margini interni -->
            <div id="ale_image_wrapper" style="position:absolute;top:40px;left:40px;right:40px;bottom:40px;overflow:hidden;">
                <div id="ale_dynamic_image" style="width:100%;height:100%;display:flex;justify-content:center;align-items:center;"></div>
            </div>

            <!-- Dimensioni con frecce -->
            <div id="ale_dimensions_display">
                <!-- Larghezza (orizzontale sotto) -->
                <div id="ale_width_dimension" style="position:absolute;bottom:15px;left:50%;transform:translateX(-50%);display:none;">
                    <div style="display:flex;align-items:center;">
                        <span style="font-size:16px;line-height:1;font-family:sans-serif;">←</span>
                        <span id="ale_width_value" style="margin:0 10px;font-weight:bold;font-size:12px;"></span>
                        <span style="font-size:16px;line-height:1;font-family:sans-serif;transform:scaleX(-1);">←</span>
                    </div>
                </div>

                <!-- Altezza (verticale a destra) -->
                <div id="ale_height_dimension" style="position:absolute;right:15px;top:50%;transform:translateY(-50%);display:none;">
                    <div style="display:flex;flex-direction:column;align-items:center;">
                        <span style="font-size:16px;line-height:1;font-family:sans-serif;">↑</span>
                        <span id="ale_height_value" style="margin:5px 0;font-weight:bold;font-size:12px;writing-mode:vertical-lr;text-orientation:mixed;"></span>
                        <span style="font-size:16px;line-height:1;font-family:sans-serif;">↓</span>
                    </div>
                </div>

                <!-- Profondità (diagonale) -->
                <div id="ale_depth_dimension" style="position:absolute;bottom:30px;right:30px;display:none;">
                    <div style="display:flex;align-items:center;transform:rotate(45deg);">
                        <span style="font-size:16px;line-height:1;font-family:sans-serif;">←</span>
                        <span id="ale_depth_value" style="margin:0 10px;font-weight:bold;font-size:12px;">30 cm</span>
                        <span style="font-size:16px;line-height:1;font-family:sans-serif;transform:scaleX(-1);">←</span>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var calculationType = '<?php echo $calculation_type; ?>';
            var numericAttrs = <?php echo json_encode($numeric_attributes); ?>;
            var textAttrs = <?php echo json_encode($text_attributes); ?>;
            var currentImage = null;

            // Mappatura delle dimensioni
            var dimension1 = '<?php echo get_post_meta($product_id, "_ale_metric_dimension1", true); ?>';
            var dimension2 = '<?php echo get_post_meta($product_id, "_ale_metric_dimension2", true); ?>';
            var dimension3 = '<?php echo get_post_meta($product_id, "_ale_metric_dimension3", true); ?>';

            var dimensionFields = {};
            if (dimension1) dimensionFields['width'] = 'ale_' + dimension1;
            if (dimension2) dimensionFields['height'] = 'ale_' + dimension2;
            if (dimension3) dimensionFields['depth'] = 'ale_' + dimension3;

            function updateVisualDisplay() {
                var formData = {};
                var productId = <?php echo $product_id; ?>;

                $('input[name^="ale_"], select[name^="ale_"]').each(function() {
                    var name = $(this).attr('name');
                    var value = $(this).val();
                    if (name && name.indexOf('ale_metric_calculated_price') === -1 && value) {
                        formData[name] = value;
                    }
                });

                // Aggiorna dimensioni
                updateDimensionsDisplay(formData);

                // Aggiorna immagine
                updateDynamicImage(formData, productId);
            }

            function updateDimensionsDisplay(formData) {
                // Reset visibility
                $('#ale_width_dimension, #ale_height_dimension, #ale_depth_dimension').hide();

                if (calculationType === 'ml') {
                    if (dimension1 && formData['ale_' + dimension1]) {
                        var unit = getUnitForDimension(dimension1);
                        $('#ale_width_value').text(formData['ale_' + dimension1] + ' ' + unit);
                        $('#ale_width_dimension').show();
                    }
                } else if (calculationType === 'm2') {
                    if (dimension1 && formData['ale_' + dimension1]) {
                        var unit = getUnitForDimension(dimension1);
                        $('#ale_width_value').text(formData['ale_' + dimension1] + ' ' + unit);
                        $('#ale_width_dimension').show();
                    }
                    if (dimension2 && formData['ale_' + dimension2]) {
                        var unit = getUnitForDimension(dimension2);
                        $('#ale_height_value').text(formData['ale_' + dimension2] + ' ' + unit);
                        $('#ale_height_dimension').show();
                    }
                } else if (calculationType === 'm3') {
                    if (dimension1 && formData['ale_' + dimension1]) {
                        var unit = getUnitForDimension(dimension1);
                        $('#ale_width_value').text(formData['ale_' + dimension1] + ' ' + unit);
                        $('#ale_width_dimension').show();
                    }
                    if (dimension2 && formData['ale_' + dimension2]) {
                        var unit = getUnitForDimension(dimension2);
                        $('#ale_height_value').text(formData['ale_' + dimension2] + ' ' + unit);
                        $('#ale_height_dimension').show();
                    }
                    if (dimension3 && formData['ale_' + dimension3]) {
                        var unit = getUnitForDimension(dimension3);
                        $('#ale_depth_value').text(formData['ale_' + dimension3] + ' ' + unit);
                        $('#ale_depth_dimension').show();
                    }
                }
            }

            function getUnitForDimension(dimensionKey) {
                var unit = 'cm';
                numericAttrs.forEach(function(attr) {
                    var attrKey = attr.label.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                    if (attrKey === dimensionKey) {
                        unit = attr.unit || 'cm';
                    }
                });
                return unit;
            }

            function updateDynamicImage(formData, productId) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'ale_get_matching_image',
                        product_id: productId,
                        values: formData,
                        nonce: '<?php echo wp_create_nonce('ale_metric_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.image_url) {
                            var container = $('#ale_dynamic_image');
                            $('#ale_visual_container').show();
                            
                            // Controlla se l'immagine deve essere dinamica
                            var isDynamic = response.data.dynamic_resize !== '0';
                            if (isDynamic) {
                                container.html('<img id="resizable_image" src="' + response.data.image_url + '" style="max-width:100%;max-height:100%;object-fit:contain;">');
                                currentImage = document.getElementById('resizable_image');
                                resizeImageToDimensions(formData);
                            } else {
                                // Immagine statica - usa le dimensioni massime del wrapper
                                var wrapperWidth = $('#ale_image_wrapper').width() || 400;
                                var wrapperHeight = $('#ale_image_wrapper').height() || 400;
                                var staticSize = Math.min(wrapperWidth, wrapperHeight, 400);
                                
                                container.html('<img src="' + response.data.image_url + '" style="width:' + staticSize + 'px;height:' + staticSize + 'px;object-fit:contain;">');
                                currentImage = null;
                            }
                        } else {
                            $('#ale_dynamic_image').html('');
                            $('#ale_visual_container').hide();
                            currentImage = null;
                        }
                    },
                    error: function() {
                        $('#ale_visual_container').hide();
                        currentImage = null;
                    }
                });
            }

            function resizeImageToDimensions(formData) {
                if (!currentImage) return;

                var width = dimensionFields.width && formData[dimensionFields.width] ? parseInt(formData[dimensionFields.width]) : 0;
                var height = dimensionFields.height && formData[dimensionFields.height] ? parseInt(formData[dimensionFields.height]) : 0;

                if (width <= 0 && height <= 0) return;

                // Limita le dimensioni per rimanere nel wrapper
                var maxWidth = $('#ale_image_wrapper').width();
                var maxHeight = $('#ale_image_wrapper').height();
                
                var containerWidth = Math.min(width, maxWidth, 400);
                var containerHeight = Math.min(height, maxHeight, 400);

                $('#resizable_image').css({
                    width: containerWidth + 'px',
                    height: containerHeight + 'px',
                    objectFit: 'fill'
                });
            }

            // Event listener principale
            $(document).on('change input', 'input[name^="ale_"], select[name^="ale_"]', function() {
                updateVisualDisplay();
            });

            // Event listener per il ridimensionamento in tempo reale
            $(document).on('input', 'input[name^="ale_"]', function() {
                // Non fare nulla se l'immagine non è dinamica
                // Il controllo viene fatto nella risposta AJAX
            });

            setTimeout(updateVisualDisplay, 1000);
        });
        </script>
        <?php
    }

    public function get_matching_image() {
        check_ajax_referer('ale_metric_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);
        $values = $_POST['values'] ?? [];

        $image_configs = get_post_meta($product_id, '_ale_metric_image_configs', true) ?: [];

        if (empty($image_configs)) {
            wp_send_json_error('Nessuna configurazione immagine');
            return;
        }

        $matching_config = $this->find_matching_config($image_configs, $values);

        if ($matching_config) {
            $image_url = wp_get_attachment_image_url($matching_config['image_id'], 'large');
            wp_send_json_success([
                'image_url' => $image_url,
                'dynamic_resize' => isset($matching_config['dynamic_resize']) ? $matching_config['dynamic_resize'] : '1'
            ]);
        } else {
            wp_send_json_error('Nessuna immagine corrispondente');
        }
    }

    private function find_matching_config($image_configs, $values) {
        foreach ($image_configs as $config) {
            if ($this->matches_configuration($config, $values)) {
                return $config;
            }
        }
        return null;
    }

    private function matches_configuration($config, $values) {
        if (!empty($config['text_attributes'])) {
            foreach ($config['text_attributes'] as $attr_key => $expected_value) {
                if (!empty($expected_value)) {
                    $field_name = 'ale_' . $attr_key;
                    if (!isset($values[$field_name]) || $values[$field_name] !== $expected_value) {
                        return false;
                    }
                }
            }
        }

        if (!empty($config['numeric_ranges'])) {
            foreach ($config['numeric_ranges'] as $attr_key => $range) {
                if (!empty($range['min']) || !empty($range['max'])) {
                    $field_name = 'ale_' . $attr_key;
                    if (!isset($values[$field_name])) {
                        return false;
                    }

                    $value = floatval($values[$field_name]);

                    if (!empty($range['min']) && $value < floatval($range['min'])) {
                        return false;
                    }

                    if (!empty($range['max']) && $value > floatval($range['max'])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}