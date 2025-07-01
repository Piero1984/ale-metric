<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Admin {
    
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_metabox']);
        add_action('save_post_product', [$this, 'save_metabox']);
    }
    
    public function add_metabox() {
        add_meta_box(
            'ale_metric',
            'Ale Metric - Prodotto Personalizzabile',
            [$this, 'render_metabox'],
            'product',
            'normal',
            'high'
        );
    }
    
    public function render_metabox($post) {
        wp_nonce_field('ale_metric_save', 'ale_metric_nonce');
        
        $enabled = get_post_meta($post->ID, '_ale_metric_enabled', true);
        $numeric_attributes = get_post_meta($post->ID, '_ale_metric_numeric_attributes', true) ?: [];
        $text_attributes = get_post_meta($post->ID, '_ale_metric_text_attributes', true) ?: [];
        $base_price = get_post_meta($post->ID, '_ale_metric_base_price', true) ?: '';
        $text_pricing = get_post_meta($post->ID, '_ale_metric_text_pricing', true) ?: [];
        
        $dimension1 = get_post_meta($post->ID, '_ale_metric_dimension1', true) ?: '';
        $dimension2 = get_post_meta($post->ID, '_ale_metric_dimension2', true) ?: '';
        $dimension3 = get_post_meta($post->ID, '_ale_metric_dimension3', true) ?: '';
        $calculation_type = get_post_meta($post->ID, '_ale_metric_calculation_type', true) ?: 'm2';
        ?>
        
        <div style="background:#e7f3ff;border:1px solid #2271b1;padding:10px;margin:10px 0;border-radius:4px;">
            <p style="margin:0;"><strong>ℹ️ Nota:</strong> Dopo aver modificato attributi, ricordati di <strong>aggiornare il prodotto</strong> per salvare le modifiche.</p>
        </div>
        
        <p>
            <label>
                <input type="checkbox" name="ale_metric_enabled" value="1" <?php checked($enabled, '1'); ?>>
                <strong>Attiva Ale Metric per questo prodotto</strong>
            </label>
        </p>
        
        <div class="ale-metric-settings" style="<?php echo $enabled ? '' : 'display:none'; ?>">
            
            <h3>Attributi Dimensionali</h3>
            <table class="widefat" id="ale_numeric_table">
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Range (min-max)</th>
                        <th>Step</th>
                        <th>Unità</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($numeric_attributes as $i => $attr): ?>
                    <tr>
                        <td><input type="text" name="ale_numeric[<?php echo $i; ?>][label]" value="<?php echo esc_attr($attr['label']); ?>"></td>
                        <td><input type="text" name="ale_numeric[<?php echo $i; ?>][range]" value="<?php echo esc_attr($attr['range']); ?>" placeholder="10-200"></td>
                        <td><input type="text" name="ale_numeric[<?php echo $i; ?>][step]" value="<?php echo esc_attr($attr['step']); ?>" size="5" placeholder="0.1"></td>
                        <td><input type="text" name="ale_numeric[<?php echo $i; ?>][unit]" value="<?php echo esc_attr($attr['unit']); ?>" size="5" placeholder="cm"></td>
                        <td><button type="button" class="button ale-remove-numeric">Rimuovi</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="ale_add_numeric">+ Aggiungi Attributo Dimensionale</button>
                <button type="button" class="button button-secondary" id="ale_update_dimensions">Aggiorna</button>
            </p>
            
            <h3>Attributi Fisici</h3>
            <table class="widefat" id="ale_text_table">
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Valori (separati da |)</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($text_attributes as $i => $attr): ?>
                    <tr>
                        <td><input type="text" name="ale_text[<?php echo $i; ?>][label]" value="<?php echo esc_attr($attr['label']); ?>"></td>
                        <td><input type="text" name="ale_text[<?php echo $i; ?>][values]" value="<?php echo esc_attr($attr['values']); ?>" style="width:100%" placeholder="rosso|blu|verde"></td>
                        <td><button type="button" class="button ale-remove-text">Rimuovi</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="ale_add_text">+ Aggiungi Attributo Fisico</button>
                <button type="button" class="button button-secondary" id="ale_update_text_pricing">Aggiorna</button>
            </p>
            
            <h3>Calcolo Prezzi</h3>
            
            <div style="background:#f0f0f1;padding:10px;margin:10px 0;border-radius:4px;">
                <h4>Tipo di Calcolo</h4>
                <p>
                    <label>Seleziona tipo di calcolo:</label>
                    <select name="ale_calculation_type" id="ale_calculation_type">
                        <option value="m2" <?php selected($calculation_type, 'm2'); ?>>Metro Quadrato (m²)</option>
                        <option value="m3" <?php selected($calculation_type, 'm3'); ?>>Metro Cubo (m³)</option>
                        <option value="ml" <?php selected($calculation_type, 'ml'); ?>>Metro Lineare (m)</option>
                    </select>
                </p>
            </div>
            
            <p>
                <label><strong>Prezzo base al <span id="ale_price_unit"><?php 
                    switch($calculation_type) {
                        case 'm3': echo 'm³'; break;
                        case 'ml': echo 'm'; break;
                        default: echo 'm²';
                    }
                ?></span> (€):</strong></label><br>
                <input type="number" name="ale_base_price" value="<?php echo esc_attr($base_price); ?>" step="0.01" placeholder="25.00">
            </p>
            
            <div style="background:#f0f0f1;padding:10px;margin:10px 0;border-radius:4px;">
                <h4>Selezione Dimensioni per Calcolo</h4>
                <div id="dimension1_container" style="<?php echo ($calculation_type == 'ml' || $calculation_type == 'm2' || $calculation_type == 'm3') ? '' : 'display:none;'; ?>">
                    <p>
                        <label>Dimensione 1:</label>
                        <select name="ale_dimension1" id="ale_dimension1">
                            <option value="">-- Seleziona --</option>
                            <?php foreach($numeric_attributes as $attr): ?>
                                <option value="<?php echo esc_attr(sanitize_title($attr['label'])); ?>" <?php selected($dimension1, sanitize_title($attr['label'])); ?>>
                                    <?php echo esc_html($attr['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </div>
                <div id="dimension2_container" style="<?php echo ($calculation_type == 'm2' || $calculation_type == 'm3') ? '' : 'display:none;'; ?>">
                    <p>
                        <label>Dimensione 2:</label>
                        <select name="ale_dimension2" id="ale_dimension2">
                            <option value="">-- Seleziona --</option>
                            <?php foreach($numeric_attributes as $attr): ?>
                                <option value="<?php echo esc_attr(sanitize_title($attr['label'])); ?>" <?php selected($dimension2, sanitize_title($attr['label'])); ?>>
                                    <?php echo esc_html($attr['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </div>
                <div id="dimension3_container" style="<?php echo ($calculation_type == 'm3') ? '' : 'display:none;'; ?>">
                    <p>
                        <label>Dimensione 3:</label>
                        <select name="ale_dimension3" id="ale_dimension3">
                            <option value="">-- Seleziona --</option>
                            <?php foreach($numeric_attributes as $attr): ?>
                                <option value="<?php echo esc_attr(sanitize_title($attr['label'])); ?>" <?php selected($dimension3, sanitize_title($attr['label'])); ?>>
                                    <?php echo esc_html($attr['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </div>
            </div>
            
            <div id="ale_text_pricing">
                <h4>Prezzi Attributi Fisici</h4>
                <?php foreach($text_attributes as $attr): ?>
                    <?php if(!empty($attr['label']) && !empty($attr['values'])): ?>
                        <?php 
                        $attr_key = sanitize_title($attr['label']);
                        $values = array_map('trim', explode('|', $attr['values']));
                        $pricing_data = isset($text_pricing[$attr_key]) ? $text_pricing[$attr_key] : [];
                        ?>
                        <div class="text-pricing-section" data-attr-key="<?php echo $attr_key; ?>">
                            <strong><?php echo esc_html($attr['label']); ?>:</strong>
                            <table style="width:100%;margin-top:10px;">
                                <thead>
                                    <tr>
                                        <th>Valore</th>
                                        <th>Prezzo Base (€)</th>
                                        <th>Incremento al m² (€)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($values as $value): ?>
                                        <?php 
                                        $value_key = sanitize_title($value);
                                        $base = isset($pricing_data[$value_key]['base']) ? $pricing_data[$value_key]['base'] : '0';
                                        $increment = isset($pricing_data[$value_key]['increment']) ? $pricing_data[$value_key]['increment'] : '0';
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html($value); ?></td>
                                            <td>
                                                <input type="number" 
                                                       name="ale_text_pricing[<?php echo $attr_key; ?>][<?php echo $value_key; ?>][base]" 
                                                       value="<?php echo esc_attr($base); ?>" 
                                                       step="0.01" 
                                                       style="width:100px;">
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="ale_text_pricing[<?php echo $attr_key; ?>][<?php echo $value_key; ?>][increment]" 
                                                       value="<?php echo esc_attr($increment); ?>" 
                                                       step="0.01" 
                                                       style="width:100px;">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <?php 
            $images_admin = new AleMetric_Images_Admin();
            $images_admin->render_images_section($post->ID);
            ?>
            
            <?php 
            $accessories_admin = new AleMetric_Accessories_Admin();
            $accessories_admin->render_accessories_section($post->ID);
            ?>
            
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('input[name="ale_metric_enabled"]').change(function() {
                $('.ale-metric-settings').toggle(this.checked);
            });
            
            $(document).on('click', '#ale_add_numeric', function() {
                var index = $('#ale_numeric_table tbody tr').length;
                var row = '<tr>' +
                    '<td><input type="text" name="ale_numeric[' + index + '][label]" /></td>' +
                    '<td><input type="text" name="ale_numeric[' + index + '][range]" placeholder="10-200" /></td>' +
                    '<td><input type="text" name="ale_numeric[' + index + '][step]" size="5" placeholder="0.1" /></td>' +
                    '<td><input type="text" name="ale_numeric[' + index + '][unit]" size="5" placeholder="cm" /></td>' +
                    '<td><button type="button" class="button ale-remove-numeric">Rimuovi</button></td>' +
                    '</tr>';
                $('#ale_numeric_table tbody').append(row);
            });
            
            $(document).on('click', '.ale-remove-numeric', function() {
                $(this).closest('tr').remove();
            });
            
            $(document).on('click', '#ale_add_text', function() {
                var index = $('#ale_text_table tbody tr').length;
                var row = '<tr>' +
                    '<td><input type="text" name="ale_text[' + index + '][label]" /></td>' +
                    '<td><input type="text" name="ale_text[' + index + '][values]" style="width:100%" placeholder="rosso|blu|verde" /></td>' +
                    '<td><button type="button" class="button ale-remove-text">Rimuovi</button></td>' +
                    '</tr>';
                $('#ale_text_table tbody').append(row);
            });
            
            $(document).on('click', '.ale-remove-text', function() {
                $(this).closest('tr').remove();
            });
            
            // PULSANTI AGGIORNA
            $(document).on('click', '#ale_update_dimensions', function() {
                updateDimensionSelects();
                updateImageConfigsForDimensions();
                $(this).text('✓ Aggiornato!').removeClass('button-secondary').addClass('button-primary');
                setTimeout(function() {
                    $('#ale_update_dimensions').text('Aggiorna').removeClass('button-primary').addClass('button-secondary');
                }, 2000);
            });
            
            $(document).on('click', '#ale_update_text_pricing', function() {
                updateTextPricing();
                updateImageConfigsForText();
                $(this).text('✓ Aggiornato!').removeClass('button-secondary').addClass('button-primary');
                setTimeout(function() {
                    $('#ale_update_text_pricing').text('Aggiorna').removeClass('button-primary').addClass('button-secondary');
                }, 2000);
            });
            
            $(document).on('click', '#ale_update_images', function() {
                updateAllImageConfigs();
                $(this).text('✓ Aggiornato!').removeClass('button-secondary').addClass('button-primary');
                setTimeout(function() {
                    $('#ale_update_images').text('Aggiorna').removeClass('button-primary').addClass('button-secondary');
                }, 2000);
            });
            
            function updateDimensionSelects() {
                var dim1Val = $('#ale_dimension1').val();
                var dim2Val = $('#ale_dimension2').val();
                var dim3Val = $('#ale_dimension3').val();
                
                var options = '<option value="">-- Seleziona --</option>';
                
                $('#ale_numeric_table tbody tr').each(function() {
                    var label = $(this).find('input[name*="[label]"]').val();
                    if (label) {
                        var key = label.toLowerCase().replace(/[àáâãäå]/g, 'a').replace(/[èéêë]/g, 'e').replace(/[ìíîï]/g, 'i').replace(/[òóôõö]/g, 'o').replace(/[ùúûü]/g, 'u').replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                        options += '<option value="' + key + '">' + label + '</option>';
                    }
                });
                
                $('#ale_dimension1').html(options).val(dim1Val);
                $('#ale_dimension2').html(options).val(dim2Val);
                $('#ale_dimension3').html(options).val(dim3Val);
            }
            
            function updateTextPricing() {
                // Salva lo stato degli input esistenti
                var existingValues = {};
                $('#ale_text_pricing input').each(function() {
                    existingValues[$(this).attr('name')] = $(this).val();
                });
                
                var $pricingDiv = $('#ale_text_pricing');
                var html = '<h4>Prezzi Attributi Fisici</h4>';
                
                $('#ale_text_table tbody tr').each(function() {
                    var label = $(this).find('input[name*="[label]"]').val();
                    var values = $(this).find('input[name*="[values]"]').val();
                    
                    if (label && values) {
                        var attrKey = label.toLowerCase()
                            .replace(/[àáâãäå]/g, 'a')
                            .replace(/[èéêë]/g, 'e')
                            .replace(/[ìíîï]/g, 'i')
                            .replace(/[òóôõö]/g, 'o')
                            .replace(/[ùúûü]/g, 'u')
                            .replace(/\s+/g, '-')
                            .replace(/[^a-z0-9-]/g, '');
                        var valuesList = values.split('|').map(function(v) { return v.trim(); }).filter(Boolean);
                        
                        html += '<div class="text-pricing-section" data-attr-key="' + attrKey + '" style="background:#f8f8f8;padding:10px;margin:10px 0;border-radius:4px;">';
                        html += '<strong>' + label + ':</strong>';
                        html += '<table style="width:100%;margin-top:10px;">';
                        html += '<thead><tr><th style="text-align:left;">Valore</th><th style="text-align:left;">Prezzo Base (€)</th><th style="text-align:left;">Incremento al ' + $('#ale_price_unit').text() + ' (€)</th></tr></thead>';
                        html += '<tbody>';
                        
                        valuesList.forEach(function(value) {
                            if (value) {
                                var valueKey = value.toLowerCase()
                                    .replace(/[àáâãäå]/g, 'a')
                                    .replace(/[èéêë]/g, 'e')
                                    .replace(/[ìíîï]/g, 'i')
                                    .replace(/[òóôõö]/g, 'o')
                                    .replace(/[ùúûü]/g, 'u')
                                    .replace(/\s+/g, '-')
                                    .replace(/[^a-z0-9-]/g, '');
                                
                                var baseName = 'ale_text_pricing[' + attrKey + '][' + valueKey + '][base]';
                                var incrementName = 'ale_text_pricing[' + attrKey + '][' + valueKey + '][increment]';
                                
                                var baseValue = existingValues[baseName] || '0';
                                var incrementValue = existingValues[incrementName] || '0';
                                
                                html += '<tr>';
                                html += '<td>' + value + '</td>';
                                html += '<td><input type="number" name="' + baseName + '" value="' + baseValue + '" step="0.01" style="width:100px;"></td>';
                                html += '<td><input type="number" name="' + incrementName + '" value="' + incrementValue + '" step="0.01" style="width:100px;"></td>';
                                html += '</tr>';
                            }
                        });
                        
                        html += '</tbody></table></div>';
                    }
                });
                
                $pricingDiv.html(html);
            }
            
            function updateImageConfigsForDimensions() {
                $('.ale-image-config').each(function(index) {
                    var $config = $(this);
                    var numericAttrs = getCurrentNumericAttributes();
                    var configData = getConfigData($config, index);
                    
                    var $numericSection = $config.find('.numeric-attributes-section');
                    if ($numericSection.length) {
                        var numericHtml = buildNumericAttributesHtml(numericAttrs, configData.numeric_ranges, index);
                        $numericSection.html(numericHtml);
                    }
                });
            }
            
            function updateImageConfigsForText() {
                $('.ale-image-config').each(function(index) {
                    var $config = $(this);
                    var textAttrs = getCurrentTextAttributes();
                    var configData = getConfigData($config, index);
                    
                    var $textSection = $config.find('.text-attributes-section');
                    if ($textSection.length) {
                        var textHtml = buildTextAttributesHtml(textAttrs, configData.text_attributes, index);
                        $textSection.html(textHtml);
                    }
                });
            }
            
            function updateAllImageConfigs() {
                updateImageConfigsForDimensions();
                updateImageConfigsForText();
            }
            
            // Funzioni helper per le immagini
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
            
            function getConfigData($config, index) {
                var data = {
                    text_attributes: {},
                    numeric_ranges: {}
                };
                
                $config.find('select[name*="[text_attributes]"]').each(function() {
                    var name = $(this).attr('name');
                    var match = name.match(/\[text_attributes\]\[([^\]]+)\]/);
                    if (match) {
                        data.text_attributes[match[1]] = $(this).val();
                    }
                });
                
                $config.find('input[name*="[numeric_ranges]"]').each(function() {
                    var name = $(this).attr('name');
                    var match = name.match(/\[numeric_ranges\]\[([^\]]+)\]\[([^\]]+)\]/);
                    if (match) {
                        if (!data.numeric_ranges[match[1]]) {
                            data.numeric_ranges[match[1]] = {};
                        }
                        data.numeric_ranges[match[1]][match[2]] = $(this).val();
                    }
                });
                
                return data;
            }
            
          function buildTextAttributesHtml(textAttrs, selectedValues, configIndex) {
    if (textAttrs.length === 0) return '<p>Nessun attributo fisico disponibile</p>';
    
    var html = '<p><strong>Attributi Fisici:</strong></p>';
    textAttrs.forEach(function(attr) {
        var attrKey = attr.label.toLowerCase().replace(/[àáâãäå]/g, 'a').replace(/[èéêë]/g, 'e').replace(/[ìíîï]/g, 'i').replace(/[òóôõö]/g, 'o').replace(/[ùúûü]/g, 'u').replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
        var values = attr.values.split('|').map(function(v) { return v.trim(); });
        var selectedValue = selectedValues[attrKey] || '';
        
        html += '<div style="margin-bottom: 8px;">';
        html += '<label style="display:inline-block;min-width:120px;">' + attr.label + ':</label>';
        html += '<select name="ale_image_configs[' + configIndex + '][text_attributes][' + attrKey + ']" style="width:200px;">';
        html += '<option value="">-- Qualsiasi --</option>';
        values.forEach(function(value) {
            var selected = selectedValue === value ? 'selected' : '';
            html += '<option value="' + value + '" ' + selected + '>' + value + '</option>';
        });
        html += '</select>';
        html += '</div>';
    });
    return html;
}
            
           function buildNumericAttributesHtml(numericAttrs, selectedRanges, configIndex) {
    if (numericAttrs.length === 0) return '<p>Nessun attributo dimensionale disponibile</p>';
    
    var html = '<p><strong>Range Dimensionali:</strong></p>';
    numericAttrs.forEach(function(attr) {
        var attrKey = attr.label.toLowerCase().replace(/[àáâãäå]/g, 'a').replace(/[èéêë]/g, 'e').replace(/[ìíîï]/g, 'i').replace(/[òóôõö]/g, 'o').replace(/[ùúûü]/g, 'u').replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
        var minVal = selectedRanges[attrKey] ? selectedRanges[attrKey].min : '';
        var maxVal = selectedRanges[attrKey] ? selectedRanges[attrKey].max : '';
        
        html += '<div style="margin-bottom: 8px;">';
        html += '<label style="display:inline-block;min-width:120px;">' + attr.label + ' (' + attr.unit + '):</label>';
        html += 'Min: <input type="number" name="ale_image_configs[' + configIndex + '][numeric_ranges][' + attrKey + '][min]" value="' + minVal + '" style="width:80px;">';
        html += ' Max: <input type="number" name="ale_image_configs[' + configIndex + '][numeric_ranges][' + attrKey + '][max]" value="' + maxVal + '" style="width:80px;">';
        html += '</div>';
    });
    return html;
}
            
            function updateCalculationType(type) {
                var unitText = 'm²';
                switch(type) {
                    case 'm3':
                        unitText = 'm³';
                        $('#dimension1_container').show();
                        $('#dimension2_container').show();
                        $('#dimension3_container').show();
                        break;
                    case 'ml':
                        unitText = 'm';
                        $('#dimension1_container').show();
                        $('#dimension2_container').hide();
                        $('#dimension3_container').hide();
                        break;
                    default:
                        unitText = 'm²';
                        $('#dimension1_container').show();
                        $('#dimension2_container').show();
                        $('#dimension3_container').hide();
                }
                $('#ale_price_unit').text(unitText);
                updateTextPricing();
            }
            
            $('#ale_calculation_type').on('change', function() {
                updateCalculationType($(this).val());
            });
            
            updateCalculationType($('#ale_calculation_type').val() || 'm2');
        });
        </script>
        <?php
    }
    
    public function save_metabox($post_id) {
        if (!isset($_POST['ale_metric_nonce']) || !wp_verify_nonce($_POST['ale_metric_nonce'], 'ale_metric_save')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        update_post_meta($post_id, '_ale_metric_enabled', isset($_POST['ale_metric_enabled']) ? '1' : '');
        
        $numeric_attributes = [];
        if (isset($_POST['ale_numeric'])) {
            foreach ($_POST['ale_numeric'] as $attr) {
                if (!empty($attr['label']) && !empty($attr['range'])) {
                    $numeric_attributes[] = [
                        'label' => sanitize_text_field($attr['label']),
                        'range' => sanitize_text_field($attr['range']),
                        'step' => sanitize_text_field($attr['step']),
                        'unit' => sanitize_text_field($attr['unit'])
                    ];
                }
            }
        }
        update_post_meta($post_id, '_ale_metric_numeric_attributes', $numeric_attributes);
        
        $text_attributes = [];
        if (isset($_POST['ale_text'])) {
            foreach ($_POST['ale_text'] as $attr) {
                if (!empty($attr['label']) && !empty($attr['values'])) {
                    $text_attributes[] = [
                        'label' => sanitize_text_field($attr['label']),
                        'values' => sanitize_text_field($attr['values'])
                    ];
                }
            }
        }
        update_post_meta($post_id, '_ale_metric_text_attributes', $text_attributes);
        
        update_post_meta($post_id, '_ale_metric_base_price', sanitize_text_field($_POST['ale_base_price']));
        update_post_meta($post_id, '_ale_metric_dimension1', sanitize_text_field($_POST['ale_dimension1'] ?? ''));
        update_post_meta($post_id, '_ale_metric_dimension2', sanitize_text_field($_POST['ale_dimension2'] ?? ''));
        update_post_meta($post_id, '_ale_metric_dimension3', sanitize_text_field($_POST['ale_dimension3'] ?? ''));
        update_post_meta($post_id, '_ale_metric_calculation_type', sanitize_text_field($_POST['ale_calculation_type'] ?? 'm2'));
        
        $text_pricing = [];
        if (isset($_POST['ale_text_pricing'])) {
            foreach ($_POST['ale_text_pricing'] as $attr_key => $values) {
                foreach ($values as $value_key => $prices) {
                    $text_pricing[$attr_key][$value_key] = [
                        'base' => sanitize_text_field($prices['base'] ?? '0'),
                        'increment' => sanitize_text_field($prices['increment'] ?? '0')
                    ];
                }
            }
        }
        update_post_meta($post_id, '_ale_metric_text_pricing', $text_pricing);
        
        if (isset($_POST['ale_image_configs'])) {
            $images_admin = new AleMetric_Images_Admin();
            $images_admin->save_image_configs($post_id, $_POST['ale_image_configs']);
        }
        
		
		$accessories_data = isset($_POST['ale_accessories']) ? $_POST['ale_accessories'] : [];
$accessories_admin = new AleMetric_Accessories_Admin();
$accessories_admin->save_accessories($post_id, $accessories_data);
		
		
    }
}