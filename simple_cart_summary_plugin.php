<?php
/**
 * Plugin Name: WooCommerce Cart Product Summary Pro
 * Description: Widget riepilogo carrello con pannello admin e shortcode personalizzabile
 * Version: 2.0
 * Author: Rocco Fusella
 * Author URI: https://roccofusella.it
 * Text Domain: wc-cart-summary
 * 
 * Questo plugin crea un widget per il riepilogo del carrello che mostra:
 * - Prodotti già nel carrello
 * - Prodotti che si stanno aggiungendo
 * - Totale complessivo
 * Supporta prodotti con varianti e prezzi differenti
 */

// Previene l'accesso diretto al file
if (!defined('ABSPATH')) {
    exit;
}

// Verifica se WooCommerce è attivo prima di inizializzare il plugin
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // Mostra un avviso nell'admin se WooCommerce non è attivo
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Il plugin "Cart Product Summary" richiede WooCommerce per funzionare.</p></div>';
    });
    return;
}

/**
 * Classe principale del plugin Cart Product Summary Pro
 * Gestisce la visualizzazione del riepilogo carrello nelle pagine prodotto
 */
class WC_Cart_Product_Summary_Pro {
    
    /**
     * Opzioni di default del plugin
     * Configurano colori, comportamenti e dimensioni del widget
     */
    private $default_options = array(
        'show_price_zero' => 'no',        // Mostra prezzo quando quantità è zero
        'auto_add_pages' => 'yes',        // Aggiunge automaticamente il widget a tutte le pagine prodotto
        'show_vat' => 'no',               // Mostra la visualizzazione dell'IVA
        'show_add_to_cart' => 'no',       // Mostra il bottone "Aggiungi al carrello" nel riepilogo
        'add_to_cart_color' => '#4caf50', // Colore del bottone "Aggiungi al carrello"
        'cart_bg_color' => '#e3f2fd',     // Colore sfondo sezione "Nel Carrello"
        'cart_border_color' => '#2196f3', // Colore bordo sezione "Nel Carrello"
        'selected_bg_color' => '#fff8e1',  // Colore sfondo sezione "Stai Aggiungendo"
        'selected_border_color' => '#ff9800', // Colore bordo sezione "Stai Aggiungendo"
        'total_bg_color' => '#e8f5e8',    // Colore sfondo sezione "Totale"
        'total_border_color' => '#4caf50', // Colore bordo sezione "Totale"
        'title_color' => '#333333',       // Colore del titolo
        'title_size' => '20',             // Dimensione font del titolo
        'text_color' => '#555555',        // Colore del testo
        'text_size' => '14'               // Dimensione font del testo
    );
    
    /**
     * Costruttore della classe - inizializza hooks e shortcode
     */
    public function __construct() {
        // Hook per inizializzazione plugin
        add_action('init', array($this, 'init'));
        
        // Hook per pannello amministrazione
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Hook per frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_dynamic_styles'));
        
        // Registra lo shortcode
        add_shortcode('cart_product_summary', array($this, 'display_cart_summary'));
        
        // Hook AJAX per aggiornamenti carrello (utenti loggati e non)
        add_action('wp_ajax_get_cart_quantity', array($this, 'ajax_get_cart_quantity'));
        add_action('wp_ajax_nopriv_get_cart_quantity', array($this, 'ajax_get_cart_quantity'));

        // Hook AJAX per ottenere aliquota IVA del prodotto
        add_action('wp_ajax_get_product_vat_rate', array($this, 'ajax_get_product_vat_rate'));
        add_action('wp_ajax_nopriv_get_product_vat_rate', array($this, 'ajax_get_product_vat_rate'));
        
        // Auto-add del widget se abilitato nelle impostazioni
        if ($this->get_option('auto_add_pages') === 'yes') {
            add_action('woocommerce_single_product_summary', array($this, 'auto_add_widget'), 25);
        }
    }
    
    /**
     * Inizializza il plugin
     * Crea le opzioni di default se non esistono già nel database
     */
    public function init() {
        // Inizializza opzioni default se non esistono
        if (!get_option('wc_cart_summary_options')) {
            update_option('wc_cart_summary_options', $this->default_options);
        }
    }
    
    /**
     * Aggiunge la pagina di amministrazione al menu Impostazioni
     */
    public function add_admin_menu() {
        add_options_page(
            'Cart Product Summary',     // Titolo pagina
            'Cart Summary',             // Titolo menu
            'manage_options',           // Capacità richiesta
            'cart-product-summary',     // Slug menu
            array($this, 'admin_page')  // Callback per il contenuto
        );
    }
    
    /**
     * Registra le impostazioni del plugin per il pannello admin
     */
    public function admin_init() {
        register_setting('wc_cart_summary_group', 'wc_cart_summary_options');
    }
    
    /**
     * Ottiene un'opzione specifica con fallback al valore di default
     * @param string $key Chiave dell'opzione da recuperare
     * @return mixed Valore dell'opzione
     */
    public function get_option($key) {
        $options = get_option('wc_cart_summary_options', array());
        // Unisce le opzioni salvate con quelle di default
        $options = array_merge($this->default_options, $options);
        return isset($options[$key]) ? $options[$key] : (isset($this->default_options[$key]) ? $this->default_options[$key] : '');
    }

    /**
     * Ottiene la quantità minima impostata dal plugin Min Max Step
     * @param WC_Product $product Prodotto WooCommerce
     * @return int Quantità minima (default 1)
     */
    public function get_min_quantity($product) {
        if (!$product) {
            return 1;
        }

        $min_qty = get_post_meta($product->get_id(), '_alg_wc_pq_min_qty', true);
        return !empty($min_qty) && $min_qty > 0 ? intval($min_qty) : 1;
    }

    /**
     * Ottiene la quantità massima impostata dal plugin Min Max Step
     * @param WC_Product $product Prodotto WooCommerce
     * @return int Quantità massima (default 0 = illimitato)
     */
    public function get_max_quantity($product) {
        if (!$product) {
            return 0;
        }

        $max_qty = get_post_meta($product->get_id(), '_alg_wc_pq_max_qty', true);
        return !empty($max_qty) && $max_qty > 0 ? intval($max_qty) : 0;
    }

    /**
     * Ottiene lo step di quantità impostato dal plugin Min Max Step
     * @param WC_Product $product Prodotto WooCommerce
     * @return int Step di quantità (default 1)
     */
    public function get_step_quantity($product) {
        if (!$product) {
            return 1;
        }

        $step_qty = get_post_meta($product->get_id(), '_alg_wc_pq_step_qty', true);
        return !empty($step_qty) && $step_qty > 0 ? intval($step_qty) : 1;
    }

    /**
     * Ottiene l'aliquota IVA del prodotto dalla sua classe fiscale WooCommerce
     * @param WC_Product $product Prodotto WooCommerce
     * @return float Aliquota IVA in percentuale
     */
    public function get_product_vat_rate($product) {
        if (!$product) {
            return 0;
        }

        // Ottieni la classe fiscale del prodotto
        $tax_class = $product->get_tax_class();

        // Se il prodotto non ha una classe fiscale specifica, usa quella standard
        if (empty($tax_class)) {
            $tax_class = '';
        }

        // Ottieni le aliquote fiscali per la classe del prodotto
        $tax_rates = WC_Tax::get_rates($tax_class);

        // Se ci sono aliquote configurate, prendi la prima (di solito l'IVA)
        if (!empty($tax_rates)) {
            $tax_rate = array_shift($tax_rates);
            return floatval($tax_rate['rate']);
        }

        return 0;
    }

    /**
     * Scurisce un colore esadecimale di una determinata percentuale
     * @param string $hex Colore esadecimale (es. #ff0000)
     * @param int $percent Percentuale di scurimento (default 20%)
     * @return string Colore scurito
     */
    private function darker_color($hex, $percent = 20) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
    
    /**
     * Renderizza la pagina di amministrazione del plugin
     * Mostra il form per configurare colori, tipografia e comportamenti
     */
    public function admin_page() {
        $options = get_option('wc_cart_summary_options', array());

        // Se le opzioni sono vuote, inizializza con i valori di default
        if (empty($options)) {
            $options = $this->default_options;
            update_option('wc_cart_summary_options', $options);
        } else {
            // Unisce le opzioni salvate con quelle di default per evitare chiavi mancanti
            $options = array_merge($this->default_options, $options);
        }
        ?>
        <div class="wrap">
            <h1>🛒 Cart Product Summary - Impostazioni</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('wc_cart_summary_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Comportamento</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wc_cart_summary_options[show_price_zero]" value="yes" <?php checked($options['show_price_zero'], 'yes'); ?>>
                                Mostra prezzo anche quando quantità è zero
                            </label><br>
                            <label>
                                <input type="checkbox" name="wc_cart_summary_options[auto_add_pages]" value="yes" <?php checked($options['auto_add_pages'], 'yes'); ?>>
                                Aggiungi automaticamente a tutte le pagine prodotto
                            </label><br>
                            <label>
                                <input type="checkbox" name="wc_cart_summary_options[show_vat]" value="yes" <?php checked($options['show_vat'], 'yes'); ?>>
                                Mostra calcolo IVA
                            </label><br>
                            <label>
                                <input type="checkbox" name="wc_cart_summary_options[show_add_to_cart]" value="yes" <?php checked($options['show_add_to_cart'], 'yes'); ?>>
                                Mostra bottone "Aggiungi al carrello" nel riepilogo
                            </label>
                        </td>
                    </tr>

                    
                    <tr>
                        <th scope="row">Colori Sezione "Nel Carrello"</th>
                        <td>
                            <input type="color" name="wc_cart_summary_options[cart_bg_color]" value="<?php echo esc_attr($options['cart_bg_color']); ?>"> Sfondo<br>
                            <input type="color" name="wc_cart_summary_options[cart_border_color]" value="<?php echo esc_attr($options['cart_border_color']); ?>"> Bordo
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Colori Sezione "Stai Aggiungendo"</th>
                        <td>
                            <input type="color" name="wc_cart_summary_options[selected_bg_color]" value="<?php echo esc_attr($options['selected_bg_color']); ?>"> Sfondo<br>
                            <input type="color" name="wc_cart_summary_options[selected_border_color]" value="<?php echo esc_attr($options['selected_border_color']); ?>"> Bordo
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Colori Sezione "Totale"</th>
                        <td>
                            <input type="color" name="wc_cart_summary_options[total_bg_color]" value="<?php echo esc_attr($options['total_bg_color']); ?>"> Sfondo<br>
                            <input type="color" name="wc_cart_summary_options[total_border_color]" value="<?php echo esc_attr($options['total_border_color']); ?>"> Bordo
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Tipografia Titolo</th>
                        <td>
                            <input type="color" name="wc_cart_summary_options[title_color]" value="<?php echo esc_attr($options['title_color']); ?>"> Colore<br>
                            <input type="number" name="wc_cart_summary_options[title_size]" value="<?php echo esc_attr($options['title_size']); ?>" min="12" max="30"> Dimensione (px)
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Tipografia Testo</th>
                        <td>
                            <input type="color" name="wc_cart_summary_options[text_color]" value="<?php echo esc_attr($options['text_color']); ?>"> Colore<br>
                            <input type="number" name="wc_cart_summary_options[text_size]" value="<?php echo esc_attr($options['text_size']); ?>" min="10" max="20"> Dimensione (px)
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Bottone Aggiungi al Carrello</th>
                        <td>
                            <input type="color" name="wc_cart_summary_options[add_to_cart_color]" value="<?php echo esc_attr($options['add_to_cart_color']); ?>"> Colore bottone
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div style="margin-top: 30px; padding: 20px; background: #f0f0f1; border-radius: 5px;">
                <h3>📝 Come Usare lo Shortcode</h3>
                <p><strong>Shortcode base:</strong> <code>[cart_product_summary]</code></p>
                
                <p><strong>Con parametri personalizzati:</strong></p>
                <code>[cart_product_summary title="Il Mio Riepilogo" show_price_zero="yes" cart_color="#ff0000"]</code>
                
                <h4>Parametri Disponibili:</h4>
                <ul>
                    <li><code>title</code> - Titolo personalizzato</li>
                    <li><code>show_price_zero</code> - "yes"/"no" mostra prezzo quando qty=0</li>
                    <li><code>show_cart</code> - "yes"/"no" mostra sezione "Nel Carrello"</li>
                    <li><code>show_selected</code> - "yes"/"no" mostra sezione "Stai Aggiungendo"</li>
                    <li><code>show_total</code> - "yes"/"no" mostra sezione "Totale Complessivo"</li>
                    <li><code>show_vat</code> - "yes"/"no" mostra calcolo IVA (usa l'aliquota del prodotto)</li>
                    <li><code>show_add_to_cart</code> - "yes"/"no" mostra bottone "Aggiungi al carrello"</li>
                    <li><code>add_to_cart_color</code> - Colore del bottone "Aggiungi al carrello"</li>
                    <li><code>cart_color</code> - Colore sezione carrello</li>
                    <li><code>selected_color</code> - Colore sezione selezione</li>
                    <li><code>total_color</code> - Colore sezione totale</li>
                    <li><code>title_size</code> - Dimensione titolo</li>
                    <li><code>text_size</code> - Dimensione testo</li>
                </ul>
                
                <h4>Esempi Avanzati:</h4>
                <p><strong>Solo sezione "Stai Aggiungendo":</strong><br>
                <code>[cart_product_summary show_cart="no" show_total="no"]</code></p>
                
                <p><strong>Solo totale complessivo:</strong><br>
                <code>[cart_product_summary show_cart="no" show_selected="no"]</code></p>
                
                <p><strong>Carrello + Totale (senza sezione intermedia):</strong><br>
                <code>[cart_product_summary show_selected="no"]</code></p>

                <p><strong>Con visualizzazione IVA automatica:</strong><br>
                <code>[cart_product_summary show_vat="yes"]</code></p>

                <p><strong>Con bottone Aggiungi al carrello:</strong><br>
                <code>[cart_product_summary show_add_to_cart="yes"]</code></p>

                <p><strong>Con bottone personalizzato:</strong><br>
                <code>[cart_product_summary show_add_to_cart="yes" add_to_cart_color="#ff6600"]</code></p>

                <p><strong>Configurazione completa con tutte le opzioni:</strong><br>
                <code>[cart_product_summary show_vat="yes" show_add_to_cart="yes" add_to_cart_color="#ff6600" title="Il Mio Carrello"]</code></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Aggiunge gli stili CSS dinamici basati sulle impostazioni del plugin
     * Gli stili vengono inseriti solo nelle pagine prodotto
     */
    public function add_dynamic_styles() {
        // Applica stili solo nelle pagine prodotto
        if (is_product()) {
            ?>
            <style type="text/css">
                /* Stili principali del container del widget */
                .wc-cart-product-summary {
                    background: #f8f9fa !important;
                    border: 2px solid #e9ecef !important;
                    border-radius: 10px !important;
                    padding: 20px !important;
                    margin: 20px 0 !important;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
                }
                /* Stili per il titolo del widget */
                .wc-cart-product-summary .summary-title {
                    margin: 0 0 20px 0 !important;
                    color: <?php echo $this->get_option('title_color'); ?> !important;
                    font-size: <?php echo $this->get_option('title_size'); ?>px !important;
                    font-weight: 700 !important;
                    border-bottom: 3px solid #007cba !important;
                    padding-bottom: 10px !important;
                    text-align: center !important;
                }
                /* Stili generali per le sezioni del riepilogo */
                .wc-cart-product-summary .summary-section {
                    margin-bottom: 15px !important;
                    padding: 15px !important;
                    border-radius: 8px !important;
                    border: 2px solid !important;
                }
                /* Stili per etichette e valori all'interno delle sezioni */
                .wc-cart-product-summary .summary-label,
                .wc-cart-product-summary .summary-value {
                    color: <?php echo $this->get_option('text_color'); ?> !important;
                    font-size: <?php echo $this->get_option('text_size'); ?>px !important;
                }
                /* Colori specifici per la sezione "Nel Carrello" */
                .wc-cart-product-summary .cart-section {
                    background: <?php echo $this->get_option('cart_bg_color'); ?> !important;
                    border-color: <?php echo $this->get_option('cart_border_color'); ?> !important;
                }
                /* Colori specifici per la sezione "Stai Aggiungendo" */
                .wc-cart-product-summary .selected-section {
                    background: <?php echo $this->get_option('selected_bg_color'); ?> !important;
                    border-color: <?php echo $this->get_option('selected_border_color'); ?> !important;
                }
                /* Colori specifici per la sezione "Totale Complessivo" */
                .wc-cart-product-summary .total-section {
                    background: <?php echo $this->get_option('total_bg_color'); ?> !important;
                    border-color: <?php echo $this->get_option('total_border_color'); ?> !important;
                }
                /* Stili per i titoli delle sezioni */
                .wc-cart-product-summary .section-title {
                    font-weight: 700 !important;
                    color: #333 !important;
                    margin-bottom: 12px !important;
                    font-size: 14px !important;
                    text-transform: uppercase !important;
                    letter-spacing: 1px !important;
                }
                /* Badge per la quantità nella sezione carrello */
                .wc-cart-product-summary .cart-quantity {
                    background: <?php echo $this->get_option('cart_border_color'); ?> !important;
                    color: white !important;
                    padding: 5px 10px !important;
                    border-radius: 5px !important;
                    font-weight: bold !important;
                }
                /* Badge per la quantità nella sezione selezione */
                .wc-cart-product-summary .selected-quantity {
                    background: <?php echo $this->get_option('selected_border_color'); ?> !important;
                    color: white !important;
                    padding: 5px 10px !important;
                    border-radius: 5px !important;
                    font-weight: bold !important;
                }
                /* Badge per la quantità totale */
                .wc-cart-product-summary .total-quantity {
                    background: <?php echo $this->get_option('total_border_color'); ?> !important;
                    color: white !important;
                    padding: 5px 10px !important;
                    border-radius: 5px !important;
                    font-weight: bold !important;
                }
                /* Badge per il totale complessivo (più prominente) */
                .wc-cart-product-summary .grand-total {
                    background: <?php echo $this->get_option('total_border_color'); ?> !important;
                    color: white !important;
                    padding: 8px 12px !important;
                    border-radius: 6px !important;
                    font-size: 16px !important;
                    font-weight: bold !important;
                }
                /* Stili per la visualizzazione dell'IVA */
                .wc-cart-product-summary .vat-info {
                    font-style: italic !important;
                    color: #666 !important;
                    font-size: 13px !important;
                    margin-top: 5px !important;
                }
                /* Layout per le righe del riepilogo */
                .wc-cart-product-summary .summary-row {
                    display: flex !important;
                    justify-content: space-between !important;
                    align-items: center !important;
                    padding: 8px 0 !important;
                    border-bottom: 1px dotted #ccc !important;
                }
                /* Rimuove il bordo dall'ultima riga di ogni sezione */
                .wc-cart-product-summary .summary-row:last-child {
                    border-bottom: none !important;
                }
                /* Stili per il container del bottone e quantità */
                .wc-cart-product-summary .add-to-cart-container {
                    display: flex !important;
                    gap: 10px !important;
                    align-items: center !important;
                    margin-top: 15px !important;
                }
                /* Container per i controlli quantità */
                .wc-cart-product-summary .quantity-controls {
                    display: flex !important;
                    align-items: center !important;
                    gap: 5px !important;
                }
                /* Stili per il campo quantità */
                .wc-cart-product-summary .cart-quantity-input {
                    width: 80px !important;
                    padding: 8px !important;
                    border: 2px solid <?php echo $this->get_option('add_to_cart_color'); ?> !important;
                    border-radius: 5px !important;
                    text-align: center !important;
                    font-size: 14px !important;
                    font-weight: bold !important;
                }
                /* Pulsanti + e - */
                .wc-cart-product-summary .qty-btn {
                    width: 35px !important;
                    height: 35px !important;
                    padding: 0 !important;
                    background: <?php echo $this->get_option('add_to_cart_color'); ?> !important;
                    color: white !important;
                    border: none !important;
                    border-radius: 5px !important;
                    font-size: 18px !important;
                    font-weight: bold !important;
                    cursor: pointer !important;
                    transition: background-color 0.3s ease !important;
                    line-height: 1 !important;
                }
                .wc-cart-product-summary .qty-btn:hover {
                    background: <?php echo $this->darker_color($this->get_option('add_to_cart_color')); ?> !important;
                }
                .wc-cart-product-summary .qty-btn:disabled {
                    background: #ccc !important;
                    cursor: not-allowed !important;
                }
                /* Nascondi le freccette native del browser per il campo quantità */
                .wc-cart-product-summary .cart-quantity-input::-webkit-outer-spin-button,
                .wc-cart-product-summary .cart-quantity-input::-webkit-inner-spin-button {
                    -webkit-appearance: none !important;
                    margin: 0 !important;
                }
                .wc-cart-product-summary .cart-quantity-input[type=number] {
                    -moz-appearance: textfield !important;
                }
                /* Stili per il bottone Aggiungi al carrello */
                .wc-cart-product-summary .add-to-cart-button {
                    flex: 1 !important;
                    padding: 12px 20px !important;
                    background: <?php echo $this->get_option('add_to_cart_color'); ?> !important;
                    color: white !important;
                    border: none !important;
                    border-radius: 8px !important;
                    font-size: 16px !important;
                    font-weight: bold !important;
                    text-transform: uppercase !important;
                    cursor: pointer !important;
                    transition: background-color 0.3s ease !important;
                }
                .wc-cart-product-summary .add-to-cart-button:hover {
                    background: <?php echo $this->darker_color($this->get_option('add_to_cart_color')); ?> !important;
                    transform: translateY(-1px) !important;
                }
                .wc-cart-product-summary .add-to-cart-button:disabled {
                    background: #ccc !important;
                    cursor: not-allowed !important;
                    transform: none !important;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Carica gli script JavaScript necessari per il funzionamento del widget
     * Include la logica per aggiornare il riepilogo in tempo reale
     */
    public function enqueue_scripts() {
        // Carica script solo nelle pagine prodotto
        if (is_product()) {
            wp_enqueue_script('jquery');

            wp_add_inline_script('jquery', '
                jQuery(document).ready(function($) {

                    var cartQuantity = 0;
                    var cartTotal = 0;
                    var currentVariationData = null;
                    var showVat = ' . ($this->get_option('show_vat') === 'yes' ? 'true' : 'false') . ';
                    var productVatRate = 0;
                    var minQuantity = 1;
                    var maxQuantity = 0;
                    var stepQuantity = 1;

                    // Funzione per leggere i limiti dal campo quantità originale
                    function readQuantityLimits() {
                        var $originalQty = $("input.qty, input[name=quantity]").first();
                        if ($originalQty.length) {
                            var min = parseInt($originalQty.attr("min")) || 1;
                            var max = parseInt($originalQty.attr("max")) || 0;
                            var step = parseInt($originalQty.attr("step")) || 1;

                            minQuantity = min;
                            maxQuantity = max;
                            stepQuantity = step;

                            // Nascondi il campo quantità originale
                            $originalQty.closest(".quantity").hide();
                        }
                    }

                    // Inizializza il widget con un leggero ritardo per assicurarsi che tutto sia caricato
                    setTimeout(function() {
                        // Leggi i limiti dal campo quantità esistente
                        readQuantityLimits();

                        // Nascondi il prezzo dinamico originale
                        hideDynamicPrice();

                        // Carica i dati del carrello
                        getCartData();

                        updateSummary();
                    }, 1000);
                    
                    function getCartData() {
                        var productId = $(".wc-cart-product-summary").data("product-id");

                        $.ajax({
                            url: "' . admin_url('admin-ajax.php') . '",
                            type: "POST",
                            data: {
                                action: "get_cart_quantity",
                                product_id: productId,
                                nonce: "' . wp_create_nonce('cart_summary_nonce') . '"
                            },
                            success: function(response) {
                                if (response.success) {
                                    cartQuantity = parseInt(response.data.quantity) || 0;
                                    cartTotal = parseFloat(response.data.total) || 0;
                                    updateSummary();
                                }
                            }
                        });
                    }

                    function getProductVatRate(productId) {
                        if (!showVat) return;

                        // Per il debugging, forziamo l\'aliquota IVA al 22%
                        productVatRate = 22;
                        updateSummary();

                        // Commentiamo temporaneamente la chiamata AJAX per il debug
                        /*
                        $.ajax({
                            url: "' . admin_url('admin-ajax.php') . '",
                            type: "POST",
                            data: {
                                action: "get_product_vat_rate",
                                product_id: productId,
                                variation_id: currentVariationData ? currentVariationData.variation_id : 0,
                                nonce: "' . wp_create_nonce('vat_rate_nonce') . '"
                            },
                            success: function(response) {
                                if (response.success) {
                                    productVatRate = parseFloat(response.data.vat_rate) || 0;
                                    updateSummary();
                                }
                            }
                        });
                        */
                    }
                    
                    function getCurrentPrice() {
                        var price = 0;
                        var priceText = "";
                        var isSquareMeter = false;

                        // PRIORITÀ 1: Cerca SEMPRE prima il prezzo calcolato dinamicamente
                        // Questo è il prezzo mostrato da Extra Product Options o altri plugin di calcolo
                        var $dynamicPrice = $(".tc-price-wrap .price.tc-price .woocommerce-Price-amount.amount, .price.tc-price .woocommerce-Price-amount.amount");

                        if ($dynamicPrice.length > 0 && $dynamicPrice.first().text().trim()) {
                            var dynamicPriceText = $dynamicPrice.first().text().trim();
                            // Estrai solo i numeri dal testo (esempio: "29,51 €" -> "29.51")
                            var dynamicPriceMatch = dynamicPriceText.match(/([\\d.,]+)/);

                            if (dynamicPriceMatch) {
                                price = parseFloat(dynamicPriceMatch[1].replace(",", "."));
                                priceText = price.toLocaleString("it-IT", {
                                    style: "currency",
                                    currency: "EUR"
                                });

                                // Se abbiamo trovato un prezzo dinamico, saltiamo tutto il resto
                                // e andiamo direttamente alla sezione opzioni YITH
                                var optionsPrice = 0;
                                $(".yith-wapo-option-value:checked").each(function() {
                                    var $option = $(this);
                                    var optionPrice = parseFloat($option.data("price")) || 0;
                                    var priceMethod = $option.data("price-method") || "increase";

                                    if (priceMethod === "increase") {
                                        optionsPrice += optionPrice;
                                    }
                                });

                                var totalPrice = price + optionsPrice;

                                return {
                                    price: totalPrice,
                                    basePrice: price,
                                    optionsPrice: optionsPrice,
                                    isSquareMeter: false,
                                    formatted: priceText
                                };
                            }
                        }

                        // Controllo per calcolo al metro quadro
                        // Cerca campi di larghezza e altezza con selettori più specifici
                        var $widthField = $("input[name*=\\\'width\\\'], input[name*=\\\'larghezza\\\'], input[name*=\\\'lunghezza\\\'], input[name*=\\\'Width\\\'], input[name*=\\\'Larghezza\\\'], input[name*=\\\'Lunghezza\\\'], input[id*=\\\'width\\\'], input[id*=\\\'larghezza\\\'], input[id*=\\\'lunghezza\\\'], input[class*=\\\'width\\\'], input[class*=\\\'larghezza\\\'], input[class*=\\\'dimension\\\']");
                        var $heightField = $("input[name*=\\\'height\\\'], input[name*=\\\'altezza\\\'], input[name*=\\\'Height\\\'], input[name*=\\\'Altezza\\\'], input[id*=\\\'height\\\'], input[id*=\\\'altezza\\\'], input[class*=\\\'height\\\'], input[class*=\\\'altezza\\\'], input[class*=\\\'dimension\\\']:eq(1)");

                        // Controlla anche se esiste un sistema di calcolo dinamico
                        var $dynamicCalcField = $("input.tmcp-field.tc-is-math, input[data-rules], input[data-formula]");
                        var hasDynamicCalculation = $dynamicCalcField.length > 0;

                        if (($widthField.length && $heightField.length) || hasDynamicCalculation) {
                            isSquareMeter = true;
                            var widthCm = 0;
                            var heightCm = 0;
                            var area = 0;

                            if ($widthField.length && $heightField.length) {
                                // Calcolo tradizionale con campi separati
                                widthCm = parseFloat($widthField.first().val()) || 0;
                                heightCm = parseFloat($heightField.first().val()) || 0;
                                var widthM = widthCm / 100;
                                var heightM = heightCm / 100;
                                area = widthM * heightM;
                            } else if (hasDynamicCalculation) {
                                // Sistema di calcolo dinamico - NON trattiamolo più come metro quadro
                                // Saltiamo direttamente alla logica normale del prodotto
                                isSquareMeter = false; // Forza la modalità normale
                            }

                            // Solo se area > 0 procedi con la logica metro quadro
                            if (isSquareMeter && area > 0) {
                                // Prima verifica se esiste già un prezzo totale calcolato (scenario 1)
                                // Cerca specificatamente nel container del calcolo dinamico
                                var $totalPriceElement = $(".tc-price-wrap .price.tc-price .woocommerce-Price-amount.amount, .price.tc-price .woocommerce-Price-amount.amount");

                                if ($totalPriceElement.length && $totalPriceElement.text().trim()) {
                                    // Usa il prezzo totale già calcolato dal sistema
                                    var totalPriceText = $totalPriceElement.text().trim();
                                    var totalPriceMatch = totalPriceText.match(/[\\d.,]+/);
                                    if (totalPriceMatch) {
                                        price = parseFloat(totalPriceMatch[0].replace(",", "."));
                                        priceText = price.toLocaleString("it-IT", {
                                            style: "currency",
                                            currency: "EUR"
                                        });
                                    }
                                } else {
                                // Scenario 2: Calcola manualmente il prezzo (prezzo base * area)
                                var basePricePerSqm = 0;
                                if (currentVariationData && currentVariationData.display_price) {
                                    basePricePerSqm = parseFloat(currentVariationData.display_price);
                                } else {
                                    // Cerca il prezzo base in vari selettori (escludo tc-price per evitare conflitti)
                                    var priceSelectors = [
                                        ".summary .price .amount:not(.tc-price .amount)",
                                        "p.price .amount:not(.tc-price .amount)",
                                        ".price .amount:not(.tc-price .amount)",
                                        ".woocommerce-Price-amount:not(.tc-price .amount)",
                                        ".single-product .price .amount:not(.tc-price .amount)",
                                        ".product .price .amount:not(.tc-price .amount)"
                                    ];

                                    for (var i = 0; i < priceSelectors.length; i++) {
                                        var $priceElement = $(priceSelectors[i]);
                                        if ($priceElement.length && $priceElement.text().trim()) {
                                            var priceElementText = $priceElement.text().trim();
                                            var priceMatch = priceElementText.match(/[\\d.,]+/);
                                            if (priceMatch) {
                                                basePricePerSqm = parseFloat(priceMatch[0].replace(",", "."));
                                                break;
                                            }
                                        }
                                    }
                                }

                                // Calcola il prezzo totale: prezzo per m² * area
                                if (area > 0 && basePricePerSqm > 0) {
                                    price = basePricePerSqm * area;
                                    priceText = price.toLocaleString("it-IT", {
                                        style: "currency",
                                        currency: "EUR"
                                    });
                                } else {
                                    price = 0;
                                    priceText = "€0,00";
                                }
                                }
                            }
                        }

                        // Se isSquareMeter è false O non abbiamo trovato prezzo, usa la logica normale
                        if (!isSquareMeter || price === 0) {
                            // Logica normale per prodotti non al metro quadro
                            if (currentVariationData && currentVariationData.display_price) {
                                price = parseFloat(currentVariationData.display_price);

                                if (currentVariationData.price_html) {
                                    var tempDiv = $("<div>").html(currentVariationData.price_html);
                                    var extractedPrice = tempDiv.find(".amount").last().text() || tempDiv.text();
                                    var cleanPrice = extractedPrice.replace(/[^\\d.,]/g, "").replace(",", ".");
                                    if (cleanPrice) {
                                        priceText = extractedPrice;
                                    }
                                }

                                if (!priceText) {
                                    priceText = price.toLocaleString("it-IT", {
                                        style: "currency",
                                        currency: "EUR"
                                    });
                                }
                            } else {
                                // Cerca il prezzo in vari selettori per prodotti semplici
                                var priceSelectors = [
                                    ".price.tc-price .woocommerce-Price-amount.amount", // Prezzo calcolato dinamicamente
                                    ".summary .price .amount",
                                    "p.price .amount",
                                    ".price .amount",
                                    ".woocommerce-Price-amount",
                                    ".single-product .price .amount",
                                    ".product .price .amount"
                                ];

                                for (var i = 0; i < priceSelectors.length; i++) {
                                    var $priceElement = $(priceSelectors[i]);

                                    if ($priceElement.length && $priceElement.text().trim()) {
                                        priceText = $priceElement.text().trim();
                                        var priceMatch = priceText.match(/[\\d.,]+/);
                                        if (priceMatch) {
                                            price = parseFloat(priceMatch[0].replace(",", "."));
                                            break;
                                        }
                                    }
                                }

                                // Se non ha ancora trovato il prezzo, cerca negli attributi
                                if (price === 0) {
                                    var productPrice = $("form.cart").data("product_price") || $(".single-product").data("price");
                                    if (productPrice) {
                                        price = parseFloat(productPrice);
                                        priceText = price.toLocaleString("it-IT", {
                                            style: "currency",
                                            currency: "EUR"
                                        });
                                    }
                                }
                            }
                        }

                        // Aggiungi i costi delle opzioni YITH WAPO selezionate
                        var optionsPrice = 0;
                        $(".yith-wapo-option-value:checked").each(function() {
                            var $option = $(this);
                            var optionPrice = parseFloat($option.data("price")) || 0;
                            var priceMethod = $option.data("price-method") || "increase";

                            if (priceMethod === "increase") {
                                optionsPrice += optionPrice;
                            }
                        });

                        // Somma prezzo base + opzioni
                        var totalPrice = price + optionsPrice;

                        return {
                            price: totalPrice,
                            basePrice: price,
                            optionsPrice: optionsPrice,
                            isSquareMeter: isSquareMeter,
                            formatted: isSquareMeter ? priceText : totalPrice.toLocaleString("it-IT", {
                                style: "currency",
                                currency: "EUR"
                            })
                        };
                    }
                    
                    function updateSummary() {
                        var $summary = $(".wc-cart-product-summary");
                        var $quantityInput = $("input.qty, input[name=quantity]").first();

                        if ($summary.length) {
                            // Per la sezione "Stai Aggiungendo", usa il campo quantità personalizzato se esiste
                            var $customQuantity = $("#summary-quantity");
                            var selectedQuantity;

                            if ($customQuantity.length && $summary.data("show-add-to-cart") === "yes") {
                                // Inizializza il campo con la quantità minima se non impostato
                                if (!$customQuantity.val() || parseInt($customQuantity.val()) < minQuantity) {
                                    $customQuantity.val(minQuantity);
                                }
                                // Usa la quantità dal campo personalizzato del riepilogo
                                selectedQuantity = parseInt($customQuantity.val()) || minQuantity;
                            } else if ($quantityInput.length) {
                                // Usa la quantità dal campo originale del prodotto
                                selectedQuantity = parseInt($quantityInput.val()) || 0;
                            } else {
                                selectedQuantity = 0;
                            }

                            var showPriceZero = $summary.data("show-price-zero") === "yes";
                            var showCart = $summary.data("show-cart") === "yes";
                            var showSelected = $summary.data("show-selected") === "yes";
                            var showTotal = $summary.data("show-total") === "yes";
                            var currentShowVat = $summary.data("show-vat") === "yes";
                            var showAddToCart = $summary.data("show-add-to-cart") === "yes";
                            
                            var priceData = getCurrentPrice();
                            var unitPrice = priceData.price;
                            var formattedPrice = priceData.formatted;

                            if (selectedQuantity === 0 && !showPriceZero) {
                                unitPrice = 0;
                                formattedPrice = "€0,00";
                            }
                            
                            var selectedTotal = selectedQuantity * unitPrice;
                            var totalQuantity = cartQuantity + selectedQuantity;
                            var grandTotal = cartTotal + selectedTotal;
                            
                            if (showCart) {
                                $summary.find(".cart-quantity").text(cartQuantity);
                                $summary.find(".cart-total").text(cartTotal.toLocaleString("it-IT", {
                                    style: "currency",
                                    currency: "EUR"
                                }));
                            }
                            
                            if (showSelected) {
                                $summary.find(".selected-quantity").text(selectedQuantity);
                                $summary.find(".selected-total").text(selectedTotal.toLocaleString("it-IT", {
                                    style: "currency",
                                    currency: "EUR"
                                }));
                                $summary.find(".summary-unit-price").text(formattedPrice);

                                // Mostra il dettaglio del prezzo se ci sono opzioni
                                if (priceData.optionsPrice > 0) {
                                    var baseFormatted = priceData.basePrice.toLocaleString("it-IT", {
                                        style: "currency",
                                        currency: "EUR"
                                    });
                                    var optionsFormatted = priceData.optionsPrice.toLocaleString("it-IT", {
                                        style: "currency",
                                        currency: "EUR"
                                    });

                                    $summary.find(".price-breakdown").html(
                                        "<small>Prodotto: " + baseFormatted + " + Opzioni: " + optionsFormatted + "</small>"
                                    ).show();
                                } else {
                                    $summary.find(".price-breakdown").hide();
                                }

                                // Calcola e mostra l\'IVA per la sezione "Stai Aggiungendo" se abilitata
                                if (currentShowVat) {
                                    var selectedVatAmount = 0;
                                    if (productVatRate > 0) {
                                        selectedVatAmount = selectedTotal * (productVatRate / (100 + productVatRate));
                                    }
                                    $summary.find(".vat-amount-selected").text(selectedVatAmount.toLocaleString("it-IT", {
                                        style: "currency",
                                        currency: "EUR"
                                    }));
                                }
                            }
                            
                            if (showTotal) {
                                $summary.find(".total-quantity").text(totalQuantity);
                                $summary.find(".grand-total").text(grandTotal.toLocaleString("it-IT", {
                                    style: "currency",
                                    currency: "EUR"
                                }));

                                // Calcola e mostra l\'IVA se abilitato
                                if (currentShowVat) {
                                    var vatAmount = 0;
                                    if (productVatRate > 0) {
                                        vatAmount = grandTotal * (productVatRate / (100 + productVatRate));
                                    }
                                    $summary.find(".vat-amount").text(vatAmount.toLocaleString("it-IT", {
                                        style: "currency",
                                        currency: "EUR"
                                    }));

                                    // Debug console per controllare i valori
                                    console.log("VAT Debug:", {
                                        showVat: currentShowVat,
                                        vatRate: productVatRate,
                                        grandTotal: grandTotal,
                                        vatAmount: vatAmount
                                    });
                                }
                            }
                            
                            if (showCart && cartQuantity > 0) {
                                $summary.find(".cart-section").show();
                            } else {
                                $summary.find(".cart-section").hide();
                            }
                            
                            if (showSelected && selectedQuantity > 0) {
                                $summary.find(".selected-section").show();
                            } else {
                                $summary.find(".selected-section").hide();
                            }
                            
                            var shouldShowWidget = false;
                            if (showCart && cartQuantity > 0) shouldShowWidget = true;
                            if (showSelected && selectedQuantity > 0) shouldShowWidget = true;
                            if (showTotal && totalQuantity > 0) shouldShowWidget = true;
                            
                            if (shouldShowWidget) {
                                $summary.find(".summary-content").show();
                                $summary.find(".no-quantity").hide();
                            } else {
                                $summary.find(".summary-content").hide();
                                $summary.find(".no-quantity").show();
                            }

                            // Gestione stato del bottone Aggiungi al carrello
                            if (showAddToCart) {
                                var $addButton = $("#summary-add-to-cart");
                                var $customQuantity = $("#summary-quantity");
                                var customQty = parseInt($customQuantity.val()) || 1;

                                if (selectedQuantity > 0 && customQty > 0) {
                                    $addButton.prop("disabled", false);
                                    $addButton.text("🛒 Aggiungi " + customQty + " al Carrello");
                                } else {
                                    $addButton.prop("disabled", true);
                                    $addButton.text("🛒 Aggiungi al Carrello");
                                }

                                // Aggiorna lo stato dei pulsanti +/- se esistono
                                if (typeof updateQuantityButtons === "function") {
                                    updateQuantityButtons();
                                }
                            }
                        }
                    }

                    // Nascondi il prezzo dinamico originale sulla pagina se presente
                    function hideDynamicPrice() {
                        var $priceWrap = $(".tc-price-wrap");
                        if ($priceWrap.length) {
                            $priceWrap.hide();
                        }
                    }
                    
                    $(document).on("input change keyup", "input.qty, input[name=quantity]", function() {
                        setTimeout(updateSummary, 100);
                    });
                    
                    $(document).on("click", ".quantity .plus, .quantity .minus", function() {
                        setTimeout(updateSummary, 200);
                    });

                    // Event listener per i checkbox delle opzioni YITH WAPO
                    $(document).on("change", ".yith-wapo-option-value", function() {
                        setTimeout(updateSummary, 100);
                    });

                    // Funzione per validare e correggere la quantità
                    function validateQuantity($input) {
                        var value = parseInt($input.val()) || minQuantity;
                        var originalValue = value;

                        // Valida il valore contro i limiti min/max/step
                        if (value < minQuantity) {
                            value = minQuantity;
                        } else if (maxQuantity > 0 && value > maxQuantity) {
                            value = maxQuantity;
                        } else {
                            // Calcola il valore valido più vicino in base allo step
                            // I valori validi sono: min, min+step, min+2*step, min+3*step, etc.
                            var diff = value - minQuantity;
                            var remainder = diff % stepQuantity;

                            if (remainder !== 0) {
                                // Arrotonda al multiplo di step più vicino
                                var stepsFromMin = Math.round(diff / stepQuantity);
                                value = minQuantity + (stepsFromMin * stepQuantity);

                                // Assicurati che rimanga nei limiti
                                if (value < minQuantity) value = minQuantity;
                                if (maxQuantity > 0 && value > maxQuantity) {
                                    value = minQuantity + (Math.floor((maxQuantity - minQuantity) / stepQuantity) * stepQuantity);
                                }
                            }
                        }

                        // Aggiorna il campo solo se il valore è cambiato
                        if (value !== originalValue) {
                            $input.val(value);
                        }

                        return value;
                    }

                    // Event listener per il campo quantità personalizzato del riepilogo
                    $(document).on("input change blur", "#summary-quantity", function() {
                        var $input = $(this);
                        validateQuantity($input);
                        setTimeout(updateSummary, 100);
                    });

                    // Gestione tasti freccia su/giù per rispettare gli step
                    $(document).on("keydown", "#summary-quantity", function(e) {
                        var $input = $(this);
                        var currentValue = parseInt($input.val()) || minQuantity;
                        var newValue = currentValue;

                        // Freccia su (codice 38)
                        if (e.keyCode === 38) {
                            e.preventDefault();
                            newValue = currentValue + stepQuantity;
                            if (maxQuantity > 0 && newValue > maxQuantity) {
                                newValue = maxQuantity;
                            }
                            $input.val(newValue);
                            validateQuantity($input);
                            setTimeout(updateSummary, 100);
                        }
                        // Freccia giù (codice 40)
                        else if (e.keyCode === 40) {
                            e.preventDefault();
                            newValue = currentValue - stepQuantity;
                            if (newValue < minQuantity) {
                                newValue = minQuantity;
                            }
                            $input.val(newValue);
                            validateQuantity($input);
                            setTimeout(updateSummary, 100);
                        }
                    });

                    // Event handler per il pulsante + (incrementa quantità)
                    $(document).on("click", "#qty-plus", function() {
                        var $input = $("#summary-quantity");
                        var currentValue = parseInt($input.val()) || minQuantity;
                        var newValue = currentValue + stepQuantity;

                        if (maxQuantity > 0 && newValue > maxQuantity) {
                            newValue = maxQuantity;
                        }

                        $input.val(newValue);
                        validateQuantity($input);
                        setTimeout(updateSummary, 100);
                    });

                    // Event handler per il pulsante - (decrementa quantità)
                    $(document).on("click", "#qty-minus", function() {
                        var $input = $("#summary-quantity");
                        var currentValue = parseInt($input.val()) || minQuantity;
                        var newValue = currentValue - stepQuantity;

                        if (newValue < minQuantity) {
                            newValue = minQuantity;
                        }

                        $input.val(newValue);
                        validateQuantity($input);
                        setTimeout(updateSummary, 100);
                    });

                    // Aggiorna lo stato dei pulsanti +/- in base ai limiti
                    function updateQuantityButtons() {
                        var $input = $("#summary-quantity");
                        var currentValue = parseInt($input.val()) || minQuantity;

                        // Disabilita il pulsante - se siamo al minimo
                        if (currentValue <= minQuantity) {
                            $("#qty-minus").prop("disabled", true);
                        } else {
                            $("#qty-minus").prop("disabled", false);
                        }

                        // Disabilita il pulsante + se siamo al massimo
                        if (maxQuantity > 0 && currentValue >= maxQuantity) {
                            $("#qty-plus").prop("disabled", true);
                        } else {
                            $("#qty-plus").prop("disabled", false);
                        }
                    }

                    // Event listener potenziati per i campi larghezza e altezza (calcolo al metro quadro)
                    // Selettori multipli per catturare tutti i possibili campi dimensione
                    var dimensionSelectors = [
                        "input[name*=\\\'width\\\']", "input[name*=\\\'larghezza\\\']", "input[name*=\\\'lunghezza\\\']",
                        "input[name*=\\\'Width\\\']", "input[name*=\\\'Larghezza\\\']", "input[name*=\\\'Lunghezza\\\']",
                        "input[name*=\\\'height\\\']", "input[name*=\\\'altezza\\\']", "input[name*=\\\'Height\\\']", "input[name*=\\\'Altezza\\\']",
                        "input[id*=\\\'width\\\']", "input[id*=\\\'larghezza\\\']", "input[id*=\\\'lunghezza\\\']",
                        "input[id*=\\\'height\\\']", "input[id*=\\\'altezza\\\']",
                        "input[class*=\\\'width\\\']", "input[class*=\\\'larghezza\\\']",
                        "input[class*=\\\'height\\\']", "input[class*=\\\'altezza\\\']",
                        "input[class*=\\\'dimension\\\']", "input[type=\\\'number\\\']",
                        // Aggiungi selettori specifici per i campi di calcolo dinamico
                        "input.tmcp-field", "input.tmcp-dynamic", "input.tmcp-textfield",
                        "input[data-rules]", "input[data-formula]", "input.tc-is-math"
                    ].join(", ");

                    $(document).on("input change keyup blur paste", dimensionSelectors, function() {
                        setTimeout(updateSummary, 50);
                    });

                    // Event listener specifici per i campi di calcolo dinamico
                    $(document).on("input change keyup", "input[name*=\\\'tmcp\\\']", function() {
                        setTimeout(updateSummary, 100);
                    });

                    // Observer potenziato per monitorare cambiamenti nel prezzo
                    var priceObserver = new MutationObserver(function(mutations) {
                        var shouldUpdate = false;
                        mutations.forEach(function(mutation) {
                            if (mutation.type === "childList" || mutation.type === "characterData") {
                                var $target = $(mutation.target);
                                // Monitora vari elementi prezzo
                                if ($target.closest(".price").length ||
                                    $target.closest(".woocommerce-Price-amount").length ||
                                    $target.hasClass("amount") ||
                                    $target.hasClass("price") ||
                                    $target.hasClass("tc-price")) {
                                    shouldUpdate = true;
                                }
                            }
                        });
                        if (shouldUpdate) {
                            setTimeout(updateSummary, 25);
                        }
                    });

                    // Osserva cambiamenti in tutti gli elementi prezzo possibili
                    var priceSelectors = [".price", ".woocommerce-Price-amount", ".amount", ".tc-price", ".tc-price-wrap"];
                    priceSelectors.forEach(function(selector) {
                        var elements = document.querySelectorAll(selector);
                        elements.forEach(function(element) {
                            priceObserver.observe(element, {
                                childList: true,
                                subtree: true,
                                characterData: true,
                                attributes: true,
                                attributeFilter: ["class", "data-price", "data-rules"]
                            });
                        });
                    });

                    // Observer specifico per l\\\'intero container del calcolo dinamico
                    var dynamicCalcContainers = document.querySelectorAll(".tc-element-container, .tmcp-ul-wrap, .tm-extra-product-options-dynamic");
                    dynamicCalcContainers.forEach(function(container) {
                        priceObserver.observe(container, {
                            childList: true,
                            subtree: true,
                            characterData: true,
                            attributes: true
                        });
                    });

                    // Timer aggiuntivo per monitoraggio continuo del prezzo (fallback)
                    var lastPriceCheck = "";
                    setInterval(function() {
                        var currentPrice = $(".tc-price-wrap .price.tc-price .woocommerce-Price-amount.amount").text() ||
                                         $(".price.tc-price .woocommerce-Price-amount.amount").text();
                        if (currentPrice && currentPrice !== lastPriceCheck) {
                            lastPriceCheck = currentPrice;
                            updateSummary();
                        }
                    }, 300);
                    
                    $(".variations_form").on("found_variation", function(event, variation) {
                        currentVariationData = variation;
                        var productId = $(".wc-cart-product-summary").data("product-id");
                        getProductVatRate(productId);
                        setTimeout(updateSummary, 100);
                    });

                    $(".variations_form").on("reset_data", function() {
                        currentVariationData = null;
                        var productId = $(".wc-cart-product-summary").data("product-id");
                        getProductVatRate(productId);
                        setTimeout(updateSummary, 100);
                    });
                    
                    $(document.body).on("added_to_cart", function() {
                        setTimeout(getCartData, 1000);
                    });
                    
                    // Gestore click del bottone Aggiungi al carrello
                    $(document).on("click", "#summary-add-to-cart", function(e) {
                        e.preventDefault();

                        var $addButton = $(this);
                        var $customQuantity = $("#summary-quantity");
                        var quantity = parseInt($customQuantity.val()) || 1;

                        if (quantity <= 0) {
                            return false;
                        }

                        // Disabilita temporaneamente il bottone
                        $addButton.prop("disabled", true).text("Aggiungendo...");

                        // Imposta la quantità nel campo del prodotto originale
                        var $originalQuantityInput = $("input.qty, input[name=quantity]").first();
                        if ($originalQuantityInput.length) {
                            $originalQuantityInput.val(quantity);
                        }

                        // Simula il click del bottone originale "Aggiungi al carrello"
                        var $originalButton = $("button[name=add-to-cart], .single_add_to_cart_button").first();

                        if ($originalButton.length) {
                            $originalButton.trigger("click");

                            // Riabilita il bottone dopo un breve delay
                            setTimeout(function() {
                                $addButton.prop("disabled", false).text("🛒 Aggiungi " + quantity + " al Carrello");
                            }, 2000);
                        } else {
                            // Fallback: sottomette il form del prodotto
                            var $form = $("form.cart, .variations_form").first();
                            if ($form.length) {
                                $form.submit();
                            }

                            setTimeout(function() {
                                $addButton.prop("disabled", false).text("🛒 Aggiungi " + quantity + " al Carrello");
                            }, 2000);
                        }
                    });

                    getCartData();
                    var productId = $(".wc-cart-product-summary").data("product-id");
                    getProductVatRate(productId);
                    setTimeout(updateSummary, 1000);

                    // Trigger aggiuntivo per prodotti semplici - aggiorna dopo il caricamento completo
                    $(window).on("load", function() {
                        setTimeout(updateSummary, 500);
                    });
                });
            ');
        }
    }
    
    /**
     * Funzione principale per visualizzare il widget riepilogo carrello
     * Gestisce lo shortcode [cart_product_summary] con tutti i suoi parametri
     * @param array $atts Attributi dello shortcode
     * @return string HTML del widget
     */
    public function display_cart_summary($atts = array()) {
        // Verifica se l'utente è loggato
        if (!is_user_logged_in()) {
            return '';
        }

        // Mostra il widget solo nelle pagine prodotto
        if (!is_product()) {
            return '';
        }

        global $product;
        if (!$product) {
            return '';
        }
        
        // Unisce i parametri dello shortcode con i valori di default
        $atts = shortcode_atts(array(
            'title' => 'Riepilogo Completo Prodotto',              // Titolo del widget
            'show_price_zero' => $this->get_option('show_price_zero'), // Mostra prezzo quando qty=0
            'show_cart' => 'yes',          // Mostra sezione "Nel Carrello"
            'show_selected' => 'yes',      // Mostra sezione "Stai Aggiungendo"
            'show_total' => 'yes',         // Mostra sezione "Totale Complessivo"
            'show_vat' => $this->get_option('show_vat'),           // Mostra calcolo IVA
            'show_add_to_cart' => $this->get_option('show_add_to_cart'), // Mostra bottone Aggiungi al carrello
            'cart_color' => '',            // Colore personalizzato sezione carrello
            'selected_color' => '',        // Colore personalizzato sezione selezione
            'total_color' => '',           // Colore personalizzato sezione totale
            'add_to_cart_color' => '',     // Colore personalizzato bottone Aggiungi al carrello
            'title_size' => '',            // Dimensione personalizzata titolo
            'text_size' => ''              // Dimensione personalizzata testo
        ), $atts);
        
        // Ottiene i dati di base del prodotto
        $product_id = $product->get_id();
        $unit_price = $product->get_price();
        
        // Genera CSS inline per i parametri personalizzati dello shortcode
        $inline_style = '';
        
        // Colore personalizzato per la sezione carrello
        if (!empty($atts['cart_color'])) {
            $inline_style .= '.cart-section { background-color: ' . esc_attr($atts['cart_color']) . ' !important; }';
        }
        // Colore personalizzato per la sezione selezione
        if (!empty($atts['selected_color'])) {
            $inline_style .= '.selected-section { background-color: ' . esc_attr($atts['selected_color']) . ' !important; }';
        }
        // Colore personalizzato per la sezione totale
        if (!empty($atts['total_color'])) {
            $inline_style .= '.total-section { background-color: ' . esc_attr($atts['total_color']) . ' !important; }';
        }
        // Dimensione personalizzata del titolo
        if (!empty($atts['title_size'])) {
            $inline_style .= '.summary-title { font-size: ' . intval($atts['title_size']) . 'px !important; }';
        }
        // Dimensione personalizzata del testo
        if (!empty($atts['text_size'])) {
            $inline_style .= '.summary-label, .summary-value { font-size: ' . intval($atts['text_size']) . 'px !important; }';
        }
        // Colore personalizzato del bottone
        if (!empty($atts['add_to_cart_color'])) {
            $inline_style .= '.add-to-cart-button { background: ' . esc_attr($atts['add_to_cart_color']) . ' !important; }';
            $inline_style .= '.cart-quantity-input { border-color: ' . esc_attr($atts['add_to_cart_color']) . ' !important; }';
            $inline_style .= '.add-to-cart-button:hover { background: ' . $this->darker_color($atts['add_to_cart_color']) . ' !important; }';
        }
        
        // Inizia il buffer di output per catturare l'HTML generato
        ob_start();
        
        // Inserisce gli stili personalizzati se presenti
        if ($inline_style) {
            echo '<style>' . $inline_style . '</style>';
        }
        ?>
        <!-- Container principale del widget con attributi di configurazione -->
        <div class="wc-cart-product-summary"
             data-product-id="<?php echo esc_attr($product_id); ?>"
             data-show-price-zero="<?php echo esc_attr($atts['show_price_zero']); ?>"
             data-show-cart="<?php echo esc_attr($atts['show_cart']); ?>"
             data-show-selected="<?php echo esc_attr($atts['show_selected']); ?>"
             data-show-total="<?php echo esc_attr($atts['show_total']); ?>"
             data-show-vat="<?php echo esc_attr($atts['show_vat']); ?>"
             data-show-add-to-cart="<?php echo esc_attr($atts['show_add_to_cart']); ?>">
            
            <!-- Titolo del widget -->
            <h4 class="summary-title"><?php echo esc_html($atts['title']); ?></h4>
            
            <!-- Contenuto principale del widget (nascosto inizialmente) -->
            <div class="summary-content" style="display: none;">
                
                <!-- Sezione "Nel Carrello" - mostra prodotti già aggiunti -->
                <?php if ($atts['show_cart'] === 'yes'): ?>
                <div class="summary-section cart-section" style="display: none;">
                    <div class="section-title">Nel Carrello</div>
                    <div class="summary-row">
                        <span class="summary-label">Quantità:</span>
                        <span class="summary-value cart-quantity">0</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Totale:</span>
                        <span class="summary-value cart-total">€0,00</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Sezione "Stai Aggiungendo" - mostra selezione corrente -->
                <?php if ($atts['show_selected'] === 'yes'): ?>
                <div class="summary-section selected-section" style="display: none;">
                    <div class="section-title">Stai Aggiungendo</div>
                    <div class="summary-row">
                        <span class="summary-label">Quantità:</span>
                        <span class="summary-value selected-quantity">0</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Prezzo unitario:</span>
                        <!-- Qui viene mostrato il prezzo formattato correttamente -->
                        <span class="summary-value summary-unit-price">€0,00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Subtotale:</span>
                        <span class="summary-value selected-total">€0,00</span>
                    </div>
                    <div class="price-breakdown" style="display: none; margin-top: 5px; font-style: italic; color: #666;"></div>
                    <!-- Visualizzazione IVA nella sezione "Stai Aggiungendo" se abilitata -->
                    <?php if ($atts['show_vat'] === 'yes'): ?>
                    <div class="vat-info">
                        di cui IVA <span class="vat-amount-selected">€0,00</span>
                    </div>
                    <?php endif; ?>

                    <!-- Bottone Aggiungi al carrello se abilitato -->
                    <?php if ($atts['show_add_to_cart'] === 'yes'):
                        $min_qty = $this->get_min_quantity($product);
                        $max_qty = $this->get_max_quantity($product);
                        $step_qty = $this->get_step_quantity($product);
                    ?>
                    <div class="add-to-cart-container">
                        <div class="quantity-controls">
                            <button type="button" class="qty-btn qty-minus" id="qty-minus">−</button>
                            <input type="number"
                                   class="cart-quantity-input"
                                   id="summary-quantity"
                                   min="<?php echo esc_attr($min_qty); ?>"
                                   <?php if ($max_qty > 0): ?>max="<?php echo esc_attr($max_qty); ?>"<?php endif; ?>
                                   step="<?php echo esc_attr($step_qty); ?>"
                                   value="<?php echo esc_attr($min_qty); ?>"
                                   title="Quantità">
                            <button type="button" class="qty-btn qty-plus" id="qty-plus">+</button>
                        </div>
                        <button type="button" class="add-to-cart-button" id="summary-add-to-cart" disabled>
                            🛒 Aggiungi al Carrello
                        </button>
                    </div>
                    <?php
                    // Mostra info sui limiti di quantità se esistono
                    $qty_info = array();
                    if ($min_qty > 1) {
                        $qty_info[] = 'Min: ' . $min_qty;
                    }
                    if ($max_qty > 0) {
                        $qty_info[] = 'Max: ' . $max_qty;
                    }
                    if ($step_qty > 1) {
                        $qty_info[] = 'Step: ' . $step_qty;
                    }
                    if (!empty($qty_info)):
                    ?>
                    <div style="font-size: 12px; color: #666; font-style: italic; margin-top: 5px;">
                        <?php echo esc_html(implode(' | ', $qty_info)); ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Sezione "Totale Complessivo" - somma carrello + selezione -->
                <?php if ($atts['show_total'] === 'yes'): ?>
                <div class="summary-section total-section">
                    <div class="section-title">Totale Complessivo</div>
                    <div class="summary-row">
                        <span class="summary-label">Quantità totale:</span>
                        <span class="summary-value total-quantity">0</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Valore totale:</span>
                        <span class="summary-value grand-total">€0,00</span>
                    </div>
                    <!-- Visualizzazione IVA se abilitata -->
                    <?php if ($atts['show_vat'] === 'yes'): ?>
                    <div class="vat-info">
                        di cui IVA <span class="vat-amount">€0,00</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Messaggio mostrato quando non ci sono quantità selezionate -->
            <div class="no-quantity">
                <p>Seleziona una quantità per vedere il riepilogo completo</p>
            </div>
        </div>
        <?php
        
        // Ritorna l'HTML generato e pulisce il buffer
        return ob_get_clean();
    }
    
    /**
     * Aggiunge automaticamente il widget alle pagine prodotto
     * Utilizzata quando l'auto-add è abilitato nelle impostazioni
     */
    public function auto_add_widget() {
        echo do_shortcode('[cart_product_summary]');
    }
    
    /**
     * Handler AJAX per recuperare quantità e totale del prodotto nel carrello
     * Utilizzata dal JavaScript per aggiornare il widget in tempo reale
     */
    public function ajax_get_cart_quantity() {
        // Verifica il nonce per sicurezza
        check_ajax_referer('cart_summary_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);
        $cart_quantity = 0;  // Quantità totale del prodotto nel carrello
        $cart_total = 0;     // Valore totale del prodotto nel carrello

        // Controlla se il carrello esiste e non è vuoto
        if (WC()->cart && !WC()->cart->is_empty()) {
            // Itera attraverso tutti gli item del carrello
            foreach (WC()->cart->get_cart() as $cart_item) {
                // Trova gli item che corrispondono al prodotto corrente (incluse le varianti)
                if ($cart_item['product_id'] == $product_id || $cart_item['variation_id'] == $product_id) {
                    $cart_quantity += $cart_item['quantity'];
                    $cart_total += $cart_item['line_total'];
                }
            }
        }

        // Ritorna i dati in formato JSON
        wp_send_json_success(array(
            'quantity' => $cart_quantity,
            'total' => $cart_total
        ));
    }

    /**
     * Handler AJAX per recuperare l'aliquota IVA del prodotto
     * Utilizzata dal JavaScript per calcolare l'IVA in tempo reale
     */
    public function ajax_get_product_vat_rate() {
        // Verifica il nonce per sicurezza
        check_ajax_referer('vat_rate_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);
        $variation_id = intval($_POST['variation_id']);

        // Se c'è una variante, usa quella; altrimenti usa il prodotto principale
        if ($variation_id > 0) {
            $product = wc_get_product($variation_id);
        } else {
            $product = wc_get_product($product_id);
        }

        $vat_rate = $this->get_product_vat_rate($product);

        // Debug: logga le informazioni per troubleshooting
        error_log("VAT Rate Debug - Product ID: $product_id, Variation ID: $variation_id, VAT Rate: $vat_rate");

        // Ritorna l'aliquota IVA in formato JSON
        wp_send_json_success(array(
            'vat_rate' => $vat_rate,
            'debug' => array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'tax_class' => $product ? $product->get_tax_class() : "N/A"
            )
        ));
    }
}

/**
 * Inizializzazione del plugin
 * Crea un'istanza della classe principale per attivare tutte le funzionalità
 */
new WC_Cart_Product_Summary_Pro();