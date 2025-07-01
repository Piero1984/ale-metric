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
            $('#ale_price_display').html('<?php echo wc_price(0); ?>');
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
                    console.error('Errore calcolo:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Errore AJAX:', xhr.responseText);
                $priceDisplay.text('Errore AJAX');
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