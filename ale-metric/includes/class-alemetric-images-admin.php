<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Images_Admin {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_scripts']);
        add_action('wp_ajax_ale_save_image_config', [$this, 'save_image_config']);
        add_action('wp_ajax_ale_delete_image_config', [$this, 'delete_image_config']);
    }
    
    public function enqueue_media_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_media();
        }
    }
    
    public function render_images_section($post_id) {
        // MODIFICA: Rimosso il controllo su $enabled
        // $enabled = get_post_meta($post_id, '_ale_metric_enabled', true);
        // if (!$enabled) return;
        
        $numeric_attributes = get_post_meta($post_id, '_ale_metric_numeric_attributes', true) ?: [];
        $text_attributes = get_post_meta($post_id, '_ale_metric_text_attributes', true) ?: [];
        $image_configs = get_post_meta($post_id, '_ale_metric_image_configs', true) ?: [];
        
        ?>
        <div id="ale_images_section">
            <h3>Gestione Immagini</h3>
            
            <div id="ale_image_configs">
                <?php foreach($image_configs as $index => $config): ?>
                    <?php $this->render_image_config($index, $config, $numeric_attributes, $text_attributes); ?>
                <?php endforeach; ?>
            </div>
            
            <p>
                <button type="button" class="button" id="ale_add_image_config">+ Aggiungi Configurazione Immagine</button>
                <button type="button" class="button button-secondary" id="ale_update_images">Aggiorna</button>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var configIndex = <?php echo count($image_configs); ?>;
            
            $('#ale_add_image_config').on('click', function() {
                var numericAttrs = getCurrentNumericAttributes();
                var textAttrs = getCurrentTextAttributes();
                
                var html = buildImageConfigHtml(configIndex, {}, numericAttrs, textAttrs);
                $('#ale_image_configs').append(html);
                configIndex++;
            });
            
            $(document).on('click', '.ale-select-image', function() {
                var button = $(this);
                var frame = wp.media({
                    title: 'Seleziona Immagine',
                    button: { text: 'Usa questa immagine' },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    button.siblings('.ale-image-preview').html('<img src="' + attachment.url + '" style="max-width:150px;height:auto;">');
                    button.siblings('.ale-image-id').val(attachment.id);
                });
                
                frame.open();
            });
            
            $(document).on('click', '.ale-remove-image-config', function() {
                $(this).closest('.ale-image-config').remove();
            });
            
            function getCurrentNumericAttributes() {
                var attrs = [];
                $('#ale_numeric_table tbody tr').each(function() {
                    var label = $(this).find('input[name*="[label]"]').val();
                    var unit = $(this).find('input[name*="[unit]"]').val();
                    if (label) {
                        attrs.push({
                            label: label,
                            unit: unit || 'cm'
                        });
                    }
                });
                return attrs;
            }
            
            function getCurrentTextAttributes() {
                var attrs = [];
                $('#ale_text_table tbody tr').each(function() {
                    var label = $(this).find('input[name*="[label]"]').val();
                    var values = $(this).find('input[name*="[values]"]').val();
                    if (label && values) {
                        attrs.push({
                            label: label,
                            values: values
                        });
                    }
                });
                return attrs;
            }
            
            function buildImageConfigHtml(index, config, numericAttrs, textAttrs) {
                var html = '<div class="ale-image-config" style="border:1px solid #ddd;padding:15px;margin:10px 0;">';
                html += '<h4>Configurazione #' + (index + 1) + '</h4>';
                
                // Selezione immagine
                html += '<p><strong>Immagine:</strong></p>';
                html += '<button type="button" class="button ale-select-image">Seleziona Immagine</button>';
                html += '<div class="ale-image-preview"></div>';
                html += '<input type="hidden" class="ale-image-id" name="ale_image_configs[' + index + '][image_id]" value="' + (config.image_id || '') + '">';
                
                // Checkbox per immagine dinamica
                html += '<div style="background:#fff;padding:10px;margin:10px 0;border-radius:4px;">';
                html += '<p style="margin:0 0 10px 0;">';
                html += '<label>';
                var isChecked = config.dynamic_resize !== '0' ? 'checked' : ''; // Default a true
                html += '<input type="checkbox" name="ale_image_configs[' + index + '][dynamic_resize]" value="1" ' + isChecked + '>';
                html += '<strong> Immagine dinamica</strong> (si ridimensiona in base alle misure inserite)';
                html += '</label>';
                html += '</p>';
                html += '<p style="margin:0;font-size:12px;color:#666;font-style:italic;">';
                html += 'Nota: Per il calcolo al metro cubo (m³), l\'immagine si ridimensiona solo in base a larghezza e altezza.<br>';
                html += 'La profondità viene mostrata come indicazione testuale.';
                html += '</p>';
                html += '</div>';
                
                // Attributi fisici
                html += '<div class="text-attributes-section">';
                if (textAttrs.length > 0) {
                    html += '<p><strong>Attributi Fisici:</strong></p>';
                    textAttrs.forEach(function(attr) {
                        var attrKey = attr.label.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                        var values = attr.values.split('|').map(function(v) { return v.trim(); });
                        var selectedValue = config.text_attributes && config.text_attributes[attrKey] ? config.text_attributes[attrKey] : '';
                        
                        html += '<label>' + attr.label + ':</label>';
                        html += '<select name="ale_image_configs[' + index + '][text_attributes][' + attrKey + ']">';
                        html += '<option value="">-- Qualsiasi --</option>';
                        values.forEach(function(value) {
                            var selected = selectedValue === value ? 'selected' : '';
                            html += '<option value="' + value + '" ' + selected + '>' + value + '</option>';
                        });
                        html += '</select><br>';
                    });
                } else {
                    html += '<p>Nessun attributo fisico disponibile</p>';
                }
                html += '</div>';
                
                // Attributi dimensionali
                html += '<div class="numeric-attributes-section">';
                if (numericAttrs.length > 0) {
                    html += '<p><strong>Range Dimensionali:</strong></p>';
                    numericAttrs.forEach(function(attr) {
                        var attrKey = attr.label.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                        var minVal = config.numeric_ranges && config.numeric_ranges[attrKey] ? config.numeric_ranges[attrKey].min : '';
                        var maxVal = config.numeric_ranges && config.numeric_ranges[attrKey] ? config.numeric_ranges[attrKey].max : '';
                        
                        html += '<label>' + attr.label + ' (' + (attr.unit || 'cm') + '):</label>';
                        html += 'Min: <input type="number" name="ale_image_configs[' + index + '][numeric_ranges][' + attrKey + '][min]" value="' + minVal + '" style="width:80px;">';
                        html += ' Max: <input type="number" name="ale_image_configs[' + index + '][numeric_ranges][' + attrKey + '][max]" value="' + maxVal + '" style="width:80px;"><br>';
                    });
                } else {
                    html += '<p>Nessun attributo dimensionale disponibile</p>';
                }
                html += '</div>';
                
                html += '<p><button type="button" class="button ale-remove-image-config">Rimuovi Configurazione</button></p>';
                html += '</div>';
                
                return html;
            }
        });
        </script>
        <?php
    }
    
    private function render_image_config($index, $config, $numeric_attributes, $text_attributes) {
        ?>
        <div class="ale-image-config" style="background:#f9f9f9;border:1px solid #ddd;padding:15px;margin:10px 0;border-radius:4px;">
            <h4 style="margin:0 0 15px 0;">Configurazione #<?php echo ($index + 1); ?></h4>
            
            <div style="margin-bottom:15px;">
                <p style="margin:0 0 8px 0;"><strong>Immagine:</strong></p>
                <button type="button" class="button ale-select-image">Seleziona Immagine</button>
                <div class="ale-image-preview" style="margin-top:10px;">
                    <?php if (!empty($config['image_id'])): ?>
                        <?php echo wp_get_attachment_image($config['image_id'], 'thumbnail'); ?>
                    <?php endif; ?>
                </div>
                <input type="hidden" class="ale-image-id" name="ale_image_configs[<?php echo $index; ?>][image_id]" value="<?php echo esc_attr($config['image_id'] ?? ''); ?>">
            </div>
            
            <div style="background:#fff;padding:10px;margin:10px 0;border-radius:4px;">
                <p style="margin:0 0 10px 0;">
                    <label>
                        <input type="checkbox" name="ale_image_configs[<?php echo $index; ?>][dynamic_resize]" value="1" <?php checked(isset($config['dynamic_resize']) ? $config['dynamic_resize'] : '1', '1'); ?>>
                        <strong>Immagine dinamica</strong> (si ridimensiona in base alle misure inserite)
                    </label>
                </p>
                <p style="margin:0;font-size:12px;color:#666;font-style:italic;">
                    Nota: Per il calcolo al metro cubo (m³), l'immagine si ridimensiona solo in base a larghezza e altezza.<br>
                    La profondità viene mostrata come indicazione testuale.
                </p>
            </div>
            
            <div class="text-attributes-section" style="background:#fff;padding:10px;margin:10px 0;border-radius:4px;">
                <?php if (!empty($text_attributes)): ?>
                <p style="margin:0 0 10px 0;"><strong>Attributi Fisici:</strong></p>
                <?php foreach($text_attributes as $attr): ?>
                    <?php 
                    $attr_key = sanitize_title($attr['label']);
                    $values = array_map('trim', explode('|', $attr['values']));
                    $selected_value = $config['text_attributes'][$attr_key] ?? '';
                    ?>
                    <p style="margin:5px 0;">
                        <label style="display:inline-block;width:120px;"><?php echo esc_html($attr['label']); ?>:</label>
                        <select name="ale_image_configs[<?php echo $index; ?>][text_attributes][<?php echo $attr_key; ?>]">
                            <option value="">-- Qualsiasi --</option>
                            <?php foreach($values as $value): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_value, $value); ?>>
                                    <?php echo esc_html($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                <?php endforeach; ?>
                <?php else: ?>
                <p style="margin:0;">Nessun attributo fisico disponibile</p>
                <?php endif; ?>
            </div>
            
            <div class="numeric-attributes-section" style="background:#fff;padding:10px;margin:10px 0;border-radius:4px;">
                <?php if (!empty($numeric_attributes)): ?>
                <p style="margin:0 0 10px 0;"><strong>Range Dimensionali:</strong></p>
                <?php foreach($numeric_attributes as $attr): ?>
                    <?php 
                    $attr_key = sanitize_title($attr['label']);
                    $min_val = $config['numeric_ranges'][$attr_key]['min'] ?? '';
                    $max_val = $config['numeric_ranges'][$attr_key]['max'] ?? '';
                    ?>
                    <p style="margin:5px 0;">
                        <label style="display:inline-block;width:120px;"><?php echo esc_html($attr['label']); ?> (<?php echo esc_html($attr['unit']); ?>):</label>
                        Min: <input type="number" name="ale_image_configs[<?php echo $index; ?>][numeric_ranges][<?php echo $attr_key; ?>][min]" value="<?php echo esc_attr($min_val); ?>" style="width:70px;">
                        Max: <input type="number" name="ale_image_configs[<?php echo $index; ?>][numeric_ranges][<?php echo $attr_key; ?>][max]" value="<?php echo esc_attr($max_val); ?>" style="width:70px;">
                    </p>
                <?php endforeach; ?>
                <?php else: ?>
                <p style="margin:0;">Nessun attributo dimensionale disponibile</p>
                <?php endif; ?>
            </div>
            
            <p style="margin:0;"><button type="button" class="button ale-remove-image-config">Rimuovi Configurazione</button></p>
        </div>
        <?php
    }
    
    public function save_image_configs($post_id, $configs_data) {
        $image_configs = [];
        
        if (!empty($configs_data)) {
            foreach ($configs_data as $config) {
                if (!empty($config['image_id'])) {
                    $image_configs[] = [
                        'image_id' => intval($config['image_id']),
                        'text_attributes' => $config['text_attributes'] ?? [],
                        'numeric_ranges' => $config['numeric_ranges'] ?? [],
                        'dynamic_resize' => isset($config['dynamic_resize']) ? '1' : '0'
                    ];
                }
            }
        }
        
        update_post_meta($post_id, '_ale_metric_image_configs', $image_configs);
    }
}