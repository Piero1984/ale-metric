<?php
/**
 * Plugin Name: Ale Metric
 * Description: Sistema avanzato per prodotti WooCommerce con misure personalizzabili
 * Version: 1.1
 * Requires PHP: 7.2
 */
if (!defined('ABSPATH')) exit;

// Costanti
define('ALE_METRIC_PATH', plugin_dir_path(__FILE__));
define('ALE_METRIC_URL', plugin_dir_url(__FILE__));

// Autoload classi
spl_autoload_register(function ($class) {
    $prefix = 'AleMetric_';
    if (strpos($class, $prefix) === 0) {
        $file = ALE_METRIC_PATH . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Inizializza plugin
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Ale Metric richiede WooCommerce!</p></div>';
        });
        return;
    }
    
    // Inizializza classi essenziali
    new AleMetric_Admin();
    new AleMetric_Frontend();
    new AleMetric_Cart();
    new AleMetric_Ajax();
    
    // Inizializza nuove classi per immagini
    new AleMetric_Images_Admin();
    new AleMetric_Images_Frontend();
    
    // Inizializza nuove classi per accessori
    new AleMetric_Accessories_Admin();
    new AleMetric_Accessories_Frontend();
    new AleMetric_Accessories_Cart();
    
    // Inizializza menu principale
    if (is_admin()) {
        new AleMetric_Menu();
        new AleMetric_Showcase();
    }
    
    // Inizializza shortcode vetrina (sia admin che frontend)
    new AleMetric_Showcase_Shortcode();
});