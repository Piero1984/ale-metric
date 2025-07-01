<?php
if (!defined('ABSPATH')) exit;

class AleMetric_Menu {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 9);
    }
    
    public function add_admin_menu() {
        // Aggiungi menu principale
        add_menu_page(
            'Ale Metric',
            'Ale Metric',
            'manage_options',
            'ale-metric',
            [$this, 'render_dashboard'],
            'dashicons-chart-area',
            30
        );
        
        // Aggiungi submenu Dashboard
        add_submenu_page(
            'ale-metric',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ale-metric',
            [$this, 'render_dashboard']
        );
        
        // Aggiungi submenu Vetrina Form
        add_submenu_page(
            'ale-metric',
            'Vetrina Form',
            'Vetrina Form',
            'manage_options',
            'ale-metric-showcase',
            [$this, 'render_showcase']
        );
    }
    
    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>Ale Metric - Dashboard</h1>
            
            <div style="background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; margin-top: 20px;">
                <h2>Benvenuto in Ale Metric</h2>
                <p>Sistema avanzato per prodotti WooCommerce con misure personalizzabili.</p>
                
                <h3>Funzionalità principali:</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>Gestione attributi dimensionali e fisici personalizzabili</li>
                    <li>Calcolo automatico prezzi (metro quadrato, metro cubo, metro lineare)</li>
                    <li>Sistema di immagini dinamiche basate su configurazione</li>
                    <li>Accessori con prezzi incrementali</li>
                    <li>Vetrina prodotti con form ridotti</li>
                </ul>
                
                <h3>Come iniziare:</h3>
                <ol style="list-style: decimal; margin-left: 20px;">
                    <li>Vai alla pagina di modifica di un prodotto</li>
                    <li>Attiva "Ale Metric" nel metabox dedicato</li>
                    <li>Configura attributi, prezzi e opzioni</li>
                    <li>Usa la <a href="<?php echo admin_url('admin.php?page=ale-metric-showcase'); ?>">Vetrina Form</a> per creare form rapidi</li>
                </ol>
                
                <h3>Shortcode disponibili:</h3>
                <p><code>[ale_metric_showcase]</code> - Mostra la vetrina dei prodotti configurati</p>
            </div>
            
            <div style="background: #f0f0f1; padding: 15px; margin-top: 20px; border-radius: 4px;">
                <p><strong>Versione:</strong> 1.1 | <strong>Richiede:</strong> WooCommerce 3.0+ | <strong>PHP:</strong> 7.2+</p>
            </div>
        </div>
        <?php
    }
    
    public function render_showcase() {
        // Delega alla classe Showcase
        $showcase = new AleMetric_Showcase();
        $showcase->render_page();
    }
}