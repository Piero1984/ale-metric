<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Showcase {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu'], 20);
        add_action('admin_post_save_ale_showcase', [$this, 'save_showcase']);
    }

    public function add_submenu() {
        // Questo metodo è gestito da AleMetric_Menu, quindi lo lasciamo vuoto.
    }

    public function render_page() {
        $showcase_data = get_option('ale_metric_showcase', ['products' => []]);

        // Ottieni tutti i prodotti con Ale Metric attivo
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_key' => '_ale_metric_enabled',
            'meta_value' => '1'
        ];
        $products = get_posts($args);
        ?>
        <div class="wrap">
            <h1>Ale Metric - Configurazione Vetrina Form</h1>

            <?php if (isset($_GET['updated'])): ?>
            <div class="notice notice-success is-dismissible">
                <p>Configurazione salvata con successo!</p>
            </div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_ale_showcase">
                <?php wp_nonce_field('ale_showcase_nonce', 'ale_showcase_nonce_field'); ?>

                <h2>Prodotti in Vetrina</h2>
                <div style="overflow-x: auto;">
                    <table class="widefat" id="showcase_products_table">
                        <thead>
                            <tr>
                                <th>Prodotto</th>
                                <th>Tipo Calcolo</th>
                                <th>Dimensione 1</th>
                                <th>Dimensione 2</th>
                                <th>Dimensione 3</th>
                                <th>Unità</th>
                                <th>Prezzo al <span id="showcase_unit">m²</span></th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($showcase_data['products'] as $i => $product_config): 
                                $calc_type = $product_config['calculation_type'] ?? 'm2';
                                $dimensions = $product_config['dimensions'] ?? [];
                                $unit = $product_config['unit'] ?? 'cm';
                                $price = $product_config['price'] ?? 0;
                            ?>
                            <tr>
                                <td>
                                    <select name="ale_showcase[products][<?php echo $i; ?>][id]" style="width:100%">
                                        <option value="">-- Seleziona Prodotto --</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?php echo $p->ID; ?>" <?php selected($product_config['id'] ?? '', $p->ID); ?>>
                                                <?php echo esc_html($p->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="ale_showcase[products][<?php echo $i; ?>][calculation_type]" class="calculation-type-select">
                                        <option value="ml" <?php selected($calc_type, 'ml'); ?>>Metro Lineare (m)</option>
                                        <option value="m2" <?php selected($calc_type, 'm2'); ?>>Metro Quadrato (m²)</option>
                                        <option value="m3" <?php selected($calc_type, 'm3'); ?>>Metro Cubo (m³)</option>
                                    </select>
                                </td>
                                <?php for ($dim = 0; $dim < 3; $dim++): 
                                    $dim_data = $dimensions[$dim] ?? ['label' => '', 'min' => '', 'max' => '', 'step' => '0.01'];
                                ?>
                                <td class="dimension-field" data-dim="<?php echo $dim; ?>">
                                    <div class="dimension-group">
                                        <label>Label: <input type="text" name="ale_showcase[products][<?php echo $i; ?>][dimensions][<?php echo $dim; ?>][label]" value="<?php echo esc_attr($dim_data['label']); ?>" class="input-field"></label>
                                        <label>Min: <input type="number" name="ale_showcase[products][<?php echo $i; ?>][dimensions][<?php echo $dim; ?>][min]" value="<?php echo esc_attr($dim_data['min']); ?>" step="0.01" class="input-field"></label>
                                        <label>Max: <input type="number" name="ale_showcase[products][<?php echo $i; ?>][dimensions][<?php echo $dim; ?>][max]" value="<?php echo esc_attr($dim_data['max']); ?>" step="0.01" class="input-field"></label>
                                        <label>Step: <input type="number" name="ale_showcase[products][<?php echo $i; ?>][dimensions][<?php echo $dim; ?>][step]" value="<?php echo esc_attr($dim_data['step']); ?>" step="0.01" class="input-field"></label>
                                    </div>
                                </td>
                                <?php endfor; ?>
                                <td>
                                    <select name="ale_showcase[products][<?php echo $i; ?>][unit]" style="width:100%">
                                        <option value="cm" <?php selected($unit, 'cm'); ?>>cm</option>
                                        <option value="m" <?php selected($unit, 'm'); ?>>m</option>
                                        <option value="mm" <?php selected($unit, 'mm'); ?>>mm</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="ale_showcase[products][<?php echo $i; ?>][price]" value="<?php echo esc_attr($price); ?>" step="0.01" style="width:100%">
                                </td>
                                <td>
                                    <button type="button" class="button remove-showcase-product">Rimuovi</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p>
                    <button type="button" class="button" id="add_showcase_product">+ Aggiungi Prodotto</button>
                </p>

                <p class="submit">
                    <input type="submit" class="button-primary" value="Salva Configurazione">
                </p>
            </form>

            <hr>

            <h2>Come Usare</h2>
            <p>Usa lo shortcode <code>[ale_metric_showcase]</code> per visualizzare la vetrina dei form nel frontend.</p>
        </div>

        <style>
            .dimension-group {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .input-field {
                width: 100px; /* Uniforma la larghezza dei campi */
            }

            .widefat th, .widefat td {
                vertical-align: middle;
                padding: 10px;
            }

            .widefat select, .widefat input[type="number"] {
                width: 100%;
                box-sizing: border-box;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Prepara le opzioni prodotto per JavaScript
            var productOptions = '<option value="">-- Seleziona Prodotto --</option>';
            <?php foreach ($products as $p): ?>
            productOptions += '<option value="<?php echo $p->ID; ?>"><?php echo esc_js($p->post_title); ?></option>';
            <?php endforeach; ?>

            function reindexShowcaseProducts() {
                $('#showcase_products_table tbody tr').each(function(index) {
                    $(this).find('input, select').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            var newName = name.replace(/ale_showcase\[products\]\[\d+\]/, 'ale_showcase[products][' + index + ']');
                            $(this).attr('name', newName);
                        }
                    });
                });
            }

            function updateDimensionFields(row) {
                var calcType = row.find('.calculation-type-select').val();
                var numDims = calcType === 'ml' ? 1 : (calcType === 'm3' ? 3 : 2);
                row.find('.dimension-field').each(function(index) {
                    if (index < numDims) {
                        $(this).find('input').prop('disabled', false);
                    } else {
                        $(this).find('input').prop('disabled', true);
                    }
                });
            }

            $('#add_showcase_product').on('click', function() {
                var index = $('#showcase_products_table tbody tr').length;
                var row = '<tr>' +
                    '<td><select name="ale_showcase[products][' + index + '][id]" style="width:100%">' + productOptions + '</select></td>' +
                    '<td><select name="ale_showcase[products][' + index + '][calculation_type]" class="calculation-type-select">' +
                    '<option value="ml">Metro Lineare (m)</option>' +
                    '<option value="m2">Metro Quadrato (m²)</option>' +
                    '<option value="m3">Metro Cubo (m³)</option>' +
                    '</select></td>' +
                    '<td class="dimension-field"><div class="dimension-group">' +
                    '<label>Label: <input type="text" name="ale_showcase[products][' + index + '][dimensions][0][label]" class="input-field"></label>' +
                    '<label>Min: <input type="number" name="ale_showcase[products][' + index + '][dimensions][0][min]" step="0.01" class="input-field"></label>' +
                    '<label>Max: <input type="number" name="ale_showcase[products][' + index + '][dimensions][0][max]" step="0.01" class="input-field"></label>' +
                    '<label>Step: <input type="number" name="ale_showcase[products][' + index + '][dimensions][0][step]" value="0.01" step="0.01" class="input-field"></label>' +
                    '</div></td>' +
                    '<td class="dimension-field"><div class="dimension-group">' +
                    '<label>Label: <input type="text" name="ale_showcase[products][' + index + '][dimensions][1][label]" class="input-field"></label>' +
                    '<label>Min: <input type="number" name="ale_showcase[products][' + index + '][dimensions][1][min]" step="0.01" class="input-field"></label>' +
                    '<label>Max: <input type="number" name="ale_showcase[products][' + index + '][dimensions][1][max]" step="0.01" class="input-field"></label>' +
                    '<label>Step: <input type="number" name="ale_showcase[products][' + index + '][dimensions][1][step]" value="0.01" step="0.01" class="input-field"></label>' +
                    '</div></td>' +
                    '<td class="dimension-field"><div class="dimension-group">' +
                    '<label>Label: <input type="text" name="ale_showcase[products][' + index + '][dimensions][2][label]" class="input-field"></label>' +
                    '<label>Min: <input type="number" name="ale_showcase[products][' + index + '][dimensions][2][min]" step="0.01" class="input-field"></label>' +
                    '<label>Max: <input type="number" name="ale_showcase[products][' + index + '][dimensions][2][max]" step="0.01" class="input-field"></label>' +
                    '<label>Step: <input type="number" name="ale_showcase[products][' + index + '][dimensions][2][step]" value="0.01" step="0.01" class="input-field"></label>' +
                    '</div></td>' +
                    '<td><select name="ale_showcase[products][' + index + '][unit]" style="width:100%">' +
                    '<option value="cm">cm</option><option value="m">m</option><option value="mm">mm</option>' +
                    '</select></td>' +
                    '<td><input type="number" name="ale_showcase[products][' + index + '][price]" step="0.01" style="width:100%"></td>' +
                    '<td><button type="button" class="button remove-showcase-product">Rimuovi</button></td>' +
                    '</tr>';
                var newRow = $(row);
                $('#showcase_products_table tbody').append(newRow);
                updateDimensionFields(newRow);
            });

            $(document).on('click', '.remove-showcase-product', function() {
                $(this).closest('tr').remove();
                reindexShowcaseProducts();
            });

            $(document).on('change', '.calculation-type-select', function() {
                var row = $(this).closest('tr');
                updateDimensionFields(row);
            });

            // Inizializza lo stato dei campi
            $('#showcase_products_table tbody tr').each(function() {
                updateDimensionFields($(this));
            });
        });
        </script>
        <?php
    }

    public function save_showcase() {
        if (!isset($_POST['ale_showcase_nonce_field']) || !wp_verify_nonce($_POST['ale_showcase_nonce_field'], 'ale_showcase_nonce')) {
            wp_die('Nonce verification failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $showcase_data = ['products' => []];

        if (isset($_POST['ale_showcase']['products'])) {
            foreach ($_POST['ale_showcase']['products'] as $product) {
                if (!empty($product['id'])) {
                    $calc_type = sanitize_text_field($product['calculation_type']);
                    $num_dims = ($calc_type == 'ml') ? 1 : (($calc_type == 'm3') ? 3 : 2);
                    $dimensions = [];
                    for ($j = 0; $j < $num_dims; $j++) {
                        $dimensions[] = [
                            'label' => sanitize_text_field($product['dimensions'][$j]['label']),
                            'min' => floatval($product['dimensions'][$j]['min']),
                            'max' => floatval($product['dimensions'][$j]['max']),
                            'step' => floatval($product['dimensions'][$j]['step']),
                        ];
                    }
                    $showcase_data['products'][] = [
                        'id' => intval($product['id']),
                        'calculation_type' => $calc_type,
                        'dimensions' => $dimensions,
                        'unit' => sanitize_text_field($product['unit']),
                        'price' => floatval($product['price']),
                    ];
                }
            }
        }

        update_option('ale_metric_showcase', $showcase_data);

        wp_redirect(admin_url('admin.php?page=ale-metric-showcase&updated=true'));
        exit;
    }
}