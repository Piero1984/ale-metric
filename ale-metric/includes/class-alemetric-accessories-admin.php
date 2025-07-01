<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Accessories_Admin {
    
    public function __construct() {
        // Costruttore vuoto - tutto gestito tramite render_accessories_section
    }
    
    public function render_accessories_section($post_id) {
        // MODIFICA: Rimosso il controllo su $enabled
        // $enabled = get_post_meta($post_id, '_ale_metric_enabled', true);
        // if (!$enabled) return;
        
        $accessories = get_post_meta($post_id, '_ale_metric_accessories', true) ?: [];
        $calculation_type = get_post_meta($post_id, '_ale_metric_calculation_type', true) ?: 'm2';
        
        // Determina l'unità di misura per il prezzo successivo
        $unit_text = 'm²';
        switch($calculation_type) {
            case 'm3':
                $unit_text = 'm³';
                break;
            case 'ml':
                $unit_text = 'm';
                break;
        }
        ?>
        
        <div id="ale_accessories_section">
            <h3>Area Accessori</h3>
            
            <table class="widefat" id="ale_accessories_table">
               <thead>
    <tr>
        <th>Titolo</th>
        <th>Descrizione</th>
        <th>Prezzo Base (€)</th>
        <th>Incremento al <span id="ale_accessory_unit"><?php echo $unit_text; ?></span> (€)</th>
        <th>Azioni</th>
    </tr>
</thead>

                <tbody>
                    <?php foreach($accessories as $i => $acc): ?>
                    <tr>
                        <td><input type="text" name="ale_accessories[<?php echo $i; ?>][title]" value="<?php echo esc_attr($acc['title']); ?>" style="width:100%"></td>
                        <td><textarea name="ale_accessories[<?php echo $i; ?>][description]" rows="2" style="width:100%"><?php echo esc_textarea($acc['description']); ?></textarea></td>
                        <td><input type="number" name="ale_accessories[<?php echo $i; ?>][base_price]" value="<?php echo esc_attr($acc['base_price']); ?>" step="0.01" style="width:80px"></td>
                        <td><input type="number" name="ale_accessories[<?php echo $i; ?>][increment_price]" value="<?php echo esc_attr($acc['increment_price']); ?>" step="0.01" style="width:80px"></td>
                        <td><button type="button" class="button ale-remove-accessory">Rimuovi</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="ale_add_accessory">+ Aggiungi Accessorio</button>
                <button type="button" class="button button-secondary" id="ale_update_accessories">Aggiorna</button>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // MODIFICA: Funzione corretta per riorganizzare gli indici
            function reindexAccessories() {
                $('#ale_accessories_table tbody tr').each(function(index) {
                    $(this).find('input, textarea').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            // Regex corretta che cattura l'intero pattern incluso il campo finale
                            var newName = name.replace(/ale_accessories\[\d+\](\[[^\]]+\])/, 'ale_accessories[' + index + ']$1');
                            $(this).attr('name', newName);
                        }
                    });
                });
            }
            
            $(document).on('click', '#ale_add_accessory', function() {
                var index = $('#ale_accessories_table tbody tr').length;
                var row = '<tr>' +
                    '<td><input type="text" name="ale_accessories[' + index + '][title]" style="width:100%"></td>' +
                    '<td><textarea name="ale_accessories[' + index + '][description]" rows="2" style="width:100%"></textarea></td>' +
                    '<td><input type="number" name="ale_accessories[' + index + '][base_price]" step="0.01" style="width:80px"></td>' +
                    '<td><input type="number" name="ale_accessories[' + index + '][increment_price]" step="0.01" style="width:80px"></td>' +
                    '<td><button type="button" class="button ale-remove-accessory">Rimuovi</button></td>' +
                    '</tr>';
                $('#ale_accessories_table tbody').append(row);
            });
            
            $(document).on('click', '.ale-remove-accessory', function() {
                $(this).closest('tr').remove();
                // MODIFICA: Chiamata alla funzione di reindicizzazione
                reindexAccessories();
            });
            
            $(document).on('click', '#ale_update_accessories', function() {
                $(this).text('✓ Aggiornato!').removeClass('button-secondary').addClass('button-primary');
                setTimeout(function() {
                    $('#ale_update_accessories').text('Aggiorna').removeClass('button-primary').addClass('button-secondary');
                }, 2000);
            });
            
            // Aggiorna l'unità di misura quando cambia il tipo di calcolo
            $('#ale_calculation_type').on('change', function() {
                var unitText = 'm²';
                switch($(this).val()) {
                    case 'm3':
                        unitText = 'm³';
                        break;
                    case 'ml':
                        unitText = 'm';
                        break;
                }
                $('#ale_accessory_unit').text(unitText);
            });
        });
        </script>
        <?php
    }
    
    public function save_accessories($post_id, $accessories_data) {
        $accessories = [];
        
        if (!empty($accessories_data)) {
            foreach ($accessories_data as $acc) {
                if (!empty($acc['title'])) {
                    $accessories[] = [
                        'title' => sanitize_text_field($acc['title']),
                        'description' => sanitize_textarea_field($acc['description']),
                        'base_price' => sanitize_text_field($acc['base_price'] ?? '0'),
                        'increment_price' => sanitize_text_field($acc['increment_price'] ?? '0')
                    ];
                }
            }
        }
        
        update_post_meta($post_id, '_ale_metric_accessories', $accessories);
    }
}