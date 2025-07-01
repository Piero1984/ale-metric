jQuery(document).ready(function($) {
    
    // Toggle settings visibility
    $('input[name="ale_metric_enabled"]').change(function() {
        $('.ale-metric-settings').toggle(this.checked);
    });
    
    // Aggiungi attributo numerico
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
        updateDimensionSelects();
    });
    
    // Rimuovi attributo numerico
    $(document).on('click', '.ale-remove-numeric', function() {
        $(this).closest('tr').remove();
        updateDimensionSelects();
    });
    
    // Aggiungi attributo testuale
    $(document).on('click', '#ale_add_text', function() {
        var index = $('#ale_text_table tbody tr').length;
        var row = '<tr>' +
            '<td><input type="text" name="ale_text[' + index + '][label]" /></td>' +
            '<td><input type="text" name="ale_text[' + index + '][values]" style="width:100%" placeholder="rosso|blu|verde" /></td>' +
            '<td><button type="button" class="button ale-remove-text">Rimuovi</button></td>' +
            '</tr>';
        $('#ale_text_table tbody').append(row);
    });
    
    // Rimuovi attributo testuale
    $(document).on('click', '.ale-remove-text', function() {
        $(this).closest('tr').remove();
        updateTextPricing();
    });
    
    // Aggiorna prezzi testuali quando cambiano gli attributi
    $(document).on('blur', '#ale_text_table input[name*="[label]"], #ale_text_table input[name*="[values]"]', function() {
        updateTextPricing();
    });
    
    // Aggiorna dropdown dimensioni quando cambiano attributi numerici
    $(document).on('blur', '#ale_numeric_table input[name*="[label]"]', function() {
        updateDimensionSelects();
    });
    
    function updateDimensionSelects() {
        var dim1Val = $('#ale_dimension1').val();
        var dim2Val = $('#ale_dimension2').val();
        
        var options = '<option value="">-- Seleziona --</option>';
        
        $('#ale_numeric_table tbody tr').each(function() {
            var label = $(this).find('input[name*="[label]"]').val();
            if (label) {
                var key = label.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                options += '<option value="' + key + '">' + label + '</option>';
            }
        });
        
        $('#ale_dimension1').html(options).val(dim1Val);
        $('#ale_dimension2').html(options).val(dim2Val);
    }
    
    function updateTextPricing() {
        var $pricingDiv = $('#ale_text_pricing');
        var html = '<h4>Prezzi Attributi Testuali</h4>';
        
        $('#ale_text_table tbody tr').each(function() {
            var label = $(this).find('input[name*="[label]"]').val();
            var values = $(this).find('input[name*="[values]"]').val();
            
            if (label && values) {
                var attrKey = label.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                var valuesList = values.split('|').map(function(v) { return v.trim(); });
                
                html += '<div style="background:#f8f8f8;padding:10px;margin:10px 0;border-radius:4px;">';
                html += '<strong>' + label + ':</strong>';
                html += '<table style="width:100%;margin-top:10px;">';
                html += '<thead><tr><th style="text-align:left;">Valore</th><th style="text-align:left;">Prezzo Base (€)</th><th style="text-align:left;">Incremento al m² (€)</th></tr></thead>';
                html += '<tbody>';
                
                valuesList.forEach(function(value) {
                    if (value) {
                        var valueKey = value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                        html += '<tr>';
                        html += '<td>' + value + '</td>';
                        html += '<td><input type="number" name="ale_text_pricing[' + attrKey + '][' + valueKey + '][base]" value="0" step="0.01" style="width:100px;"></td>';
                        html += '<td><input type="number" name="ale_text_pricing[' + attrKey + '][' + valueKey + '][increment]" value="0" step="0.01" style="width:100px;"></td>';
                        html += '</tr>';
                    }
                });
                
                html += '</tbody></table></div>';
            }
        });
        
        $pricingDiv.html(html);
    }
    
});