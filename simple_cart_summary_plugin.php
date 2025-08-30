<?php
/**
 * Plugin Name: WooCommerce Cart Product Summary Pro
 * Description: Widget riepilogo carrello con pannello admin e shortcode personalizzabile
 * Version: 2.0
 * Author: Rocco Fusella
 * Author URI: https://roccofusella.it
 * Text Domain: wc-cart-summary
 */

// Previene l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Verifica se WooCommerce è attivo
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Il plugin "Cart Product Summary" richiede WooCommerce per funzionare.</p></div>';
    });
    return;
}

class WC_Cart_Product_Summary_Pro {
    
    private $default_options = array(
        'show_price_zero' => 'no',
        'auto_add_pages' => 'yes',
        'cart_bg_color' => '#e3f2fd',
        'cart_border_color' => '#2196f3',
        'selected_bg_color' => '#fff8e1',
        'selected_border_color' => '#ff9800',
        'total_bg_color' => '#e8f5e8',
        'total_border_color' => '#4caf50',
        'title_color' => '#333333',
        'title_size' => '20',
        'text_color' => '#555555',
        'text_size' => '14'
    );
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('cart_product_summary', array($this, 'display_cart_summary'));
        add_action('wp_ajax_get_cart_quantity', array($this, 'ajax_get_cart_quantity'));
        add_action('wp_ajax_nopriv_get_cart_quantity', array($this, 'ajax_get_cart_quantity'));
        add_action('wp_head', array($this, 'add_dynamic_styles'));
        
        // Auto-add se abilitato
        if ($this->get_option('auto_add_pages') === 'yes') {
            add_action('woocommerce_single_product_summary', array($this, 'auto_add_widget'), 25);
        }
    }
    
    public function init() {
        // Inizializza opzioni default se non esistono
        if (!get_option('wc_cart_summary_options')) {
            update_option('wc_cart_summary_options', $this->default_options);
        }
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Cart Product Summary',
            'Cart Summary',
            'manage_options',
            'cart-product-summary',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('wc_cart_summary_group', 'wc_cart_summary_options');
    }
    
    public function get_option($key) {
        $options = get_option('wc_cart_summary_options', $this->default_options);
        return isset($options[$key]) ? $options[$key] : $this->default_options[$key];
    }
    
    public function admin_page() {
        $options = get_option('wc_cart_summary_options', $this->default_options);
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
            </div>
        </div>
        <?php
    }
    
    public function add_dynamic_styles() {
        if (is_product()) {
            ?>
            <style type="text/css">
                .wc-cart-product-summary {
                    background: #f8f9fa !important;
                    border: 2px solid #e9ecef !important;
                    border-radius: 10px !important;
                    padding: 20px !important;
                    margin: 20px 0 !important;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
                }
                .wc-cart-product-summary .summary-title {
                    margin: 0 0 20px 0 !important;
                    color: <?php echo $this->get_option('title_color'); ?> !important;
                    font-size: <?php echo $this->get_option('title_size'); ?>px !important;
                    font-weight: 700 !important;
                    border-bottom: 3px solid #007cba !important;
                    padding-bottom: 10px !important;
                    text-align: center !important;
                }
                .wc-cart-product-summary .summary-section {
                    margin-bottom: 15px !important;
                    padding: 15px !important;
                    border-radius: 8px !important;
                    border: 2px solid !important;
                }
                .wc-cart-product-summary .summary-label,
                .wc-cart-product-summary .summary-value {
                    color: <?php echo $this->get_option('text_color'); ?> !important;
                    font-size: <?php echo $this->get_option('text_size'); ?>px !important;
                }
                .wc-cart-product-summary .cart-section {
                    background: <?php echo $this->get_option('cart_bg_color'); ?> !important;
                    border-color: <?php echo $this->get_option('cart_border_color'); ?> !important;
                }
                .wc-cart-product-summary .selected-section {
                    background: <?php echo $this->get_option('selected_bg_color'); ?> !important;
                    border-color: <?php echo $this->get_option('selected_border_color'); ?> !important;
                }
                .wc-cart-product-summary .total-section {
                    background: <?php echo $this->get_option('total_bg_color'); ?> !important;
                    border-color: <?php echo $this->get_option('total_border_color'); ?> !important;
                }
                .wc-cart-product-summary .section-title {
                    font-weight: 700 !important;
                    color: #333 !important;
                    margin-bottom: 12px !important;
                    font-size: 14px !important;
                    text-transform: uppercase !important;
                    letter-spacing: 1px !important;
                }
                .wc-cart-product-summary .cart-quantity {
                    background: <?php echo $this->get_option('cart_border_color'); ?> !important;
                    color: white !important;
                    padding: 5px 10px !important;
                    border-radius: 5px !important;
                    font-weight: bold !important;
                }
                .wc-cart-product-summary .selected-quantity {
                    background: <?php echo $this->get_option('selected_border_color'); ?> !important;
                    color: white !important;
                    padding: 5px 10px !important;
                    border-radius: 5px !important;
                    font-weight: bold !important;
                }
                .wc-cart-product-summary .total-quantity {
                    background: <?php echo $this->get_option('total_border_color'); ?> !important;
                    color: white !important;
                    padding: 5px 10px !important;
                    border-radius: 5px !important;
                    font-weight: bold !important;
                }
                .wc-cart-product-summary .grand-total {
                    background: <?php echo $this->get_option('total_border_color'); ?> !important;
                    color: white !important;
                    padding: 8px 12px !important;
                    border-radius: 6px !important;
                    font-size: 16px !important;
                    font-weight: bold !important;
                }
                .wc-cart-product-summary .summary-row {
                    display: flex !important;
                    justify-content: space-between !important;
                    align-items: center !important;
                    padding: 8px 0 !important;
                    border-bottom: 1px dotted #ccc !important;
                }
                .wc-cart-product-summary .summary-row:last-child {
                    border-bottom: none !important;
                }
            </style>
            <?php
        }
    }
    
    public function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_script('jquery');
            
            wp_add_inline_script('jquery', '
                jQuery(document).ready(function($) {
                    
                    var cartQuantity = 0;
                    var cartTotal = 0;
                    var currentVariationData = null;
                    
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
                    
                    function getCurrentPrice() {
                        var price = 0;
                        var priceText = "";
                        
                        if (currentVariationData && currentVariationData.display_price) {
                            price = parseFloat(currentVariationData.display_price);
                            priceText = currentVariationData.price_html || "";
                        } else {
                            var $priceElement = $(".woocommerce-variation-price .amount, .price .amount, .woocommerce-Price-amount").first();
                            if ($priceElement.length) {
                                priceText = $priceElement.text();
                                var priceMatch = priceText.match(/[\d.,]+/);
                                price = priceMatch ? parseFloat(priceMatch[0].replace(",", ".")) : 0;
                            }
                        }
                        
                        return {
                            price: price,
                            formatted: priceText || price.toLocaleString("it-IT", {
                                style: "currency",
                                currency: "EUR"
                            })
                        };
                    }
                    
                    function updateSummary() {
                        var $summary = $(".wc-cart-product-summary");
                        var $quantityInput = $("input.qty, input[name=quantity]").first();
                        
                        if ($summary.length && $quantityInput.length) {
                            var selectedQuantity = parseInt($quantityInput.val()) || 0;
                            var showPriceZero = $summary.data("show-price-zero") === "yes";
                            var showCart = $summary.data("show-cart") === "yes";
                            var showSelected = $summary.data("show-selected") === "yes";
                            var showTotal = $summary.data("show-total") === "yes";
                            
                            var priceData = getCurrentPrice();
                            var unitPrice = priceData.price;
                            var formattedPrice = priceData.formatted;
                            
                            // Se quantità è zero e non si vuole mostrare il prezzo
                            if (selectedQuantity === 0 && !showPriceZero) {
                                unitPrice = 0;
                                formattedPrice = "€0,00";
                            }
                            
                            var selectedTotal = selectedQuantity * unitPrice;
                            var totalQuantity = cartQuantity + selectedQuantity;
                            var grandTotal = cartTotal + selectedTotal;
                            
                            // Aggiorna i valori
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
                            }
                            
                            if (showTotal) {
                                $summary.find(".total-quantity").text(totalQuantity);
                                $summary.find(".grand-total").text(grandTotal.toLocaleString("it-IT", {
                                    style: "currency",
                                    currency: "EUR"
                                }));
                            }
                            
                            // Logica di visualizzazione sezioni
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
                            
                            // Logica per mostrare il widget
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
                        }
                    }
                    
                    $(document).on("input change keyup", "input.qty, input[name=quantity]", function() {
                        setTimeout(updateSummary, 100);
                    });
                    
                    $(document).on("click", ".quantity .plus, .quantity .minus", function() {
                        setTimeout(updateSummary, 200);
                    });
                    
                    $(".variations_form").on("found_variation", function(event, variation) {
                        currentVariationData = variation;
                        setTimeout(updateSummary, 100);
                    });
                    
                    $(".variations_form").on("reset_data", function() {
                        currentVariationData = null;
                        setTimeout(updateSummary, 100);
                    });
                    
                    $(document.body).on("added_to_cart", function() {
                        setTimeout(getCartData, 1000);
                    });
                    
                    getCartData();
                    setTimeout(updateSummary, 1000);
                });
            ');
        }
    }
    
    public function display_cart_summary($atts = array()) {
        if (!is_product()) {
            return '';
        }
        
        global $product;
        if (!$product) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'title' => 'Riepilogo Completo Prodotto',
            'show_price_zero' => $this->get_option('show_price_zero'),
            'show_cart' => 'yes',
            'show_selected' => 'yes', 
            'show_total' => 'yes',
            'cart_color' => '',
            'selected_color' => '',
            'total_color' => '',
            'title_size' => '',
            'text_size' => ''
        ), $atts);
        
        $product_id = $product->get_id();
        $unit_price = $product->get_price();
        
        // CSS inline per parametri shortcode
        $inline_style = '';
        if (!empty($atts['cart_color'])) {
            $inline_style .= '.cart-section { background-color: ' . esc_attr($atts['cart_color']) . ' !important; }';
        }
        if (!empty($atts['selected_color'])) {
            $inline_style .= '.selected-section { background-color: ' . esc_attr($atts['selected_color']) . ' !important; }';
        }
        if (!empty($atts['total_color'])) {
            $inline_style .= '.total-section { background-color: ' . esc_attr($atts['total_color']) . ' !important; }';
        }
        if (!empty($atts['title_size'])) {
            $inline_style .= '.summary-title { font-size: ' . intval($atts['title_size']) . 'px !important; }';
        }
        if (!empty($atts['text_size'])) {
            $inline_style .= '.summary-label, .summary-value { font-size: ' . intval($atts['text_size']) . 'px !important; }';
        }
        
        ob_start();
        
        if ($inline_style) {
            echo '<style>' . $inline_style . '</style>';
        }
        ?>
        <div class="wc-cart-product-summary" 
             data-product-id="<?php echo esc_attr($product_id); ?>"
             data-show-price-zero="<?php echo esc_attr($atts['show_price_zero']); ?>"
             data-show-cart="<?php echo esc_attr($atts['show_cart']); ?>"
             data-show-selected="<?php echo esc_attr($atts['show_selected']); ?>"
             data-show-total="<?php echo esc_attr($atts['show_total']); ?>">
            
            <h4 class="summary-title"><?php echo esc_html($atts['title']); ?></h4>
            
            <div class="summary-content" style="display: none;">
                
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
                
                <?php if ($atts['show_selected'] === 'yes'): ?>
                <div class="summary-section selected-section" style="display: none;">
                    <div class="section-title">Stai Aggiungendo</div>
                    <div class="summary-row">
                        <span class="summary-label">Quantità:</span>
                        <span class="summary-value selected-quantity">0</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Prezzo unitario:</span>
                        <span class="summary-value summary-unit-price">€0,00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Subtotale:</span>
                        <span class="summary-value selected-total">€0,00</span>
                    </div>
                </div>
                <?php endif; ?>
                
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
                </div>
                <?php endif; ?>
            </div>
            
            <div class="no-quantity">
                <p>Seleziona una quantità per vedere il riepilogo completo</p>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    public function auto_add_widget() {
        echo do_shortcode('[cart_product_summary]');
    }
    
    public function ajax_get_cart_quantity() {
        check_ajax_referer('cart_summary_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $cart_quantity = 0;
        $cart_total = 0;
        
        if (WC()->cart && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                if ($cart_item['product_id'] == $product_id || $cart_item['variation_id'] == $product_id) {
                    $cart_quantity += $cart_item['quantity'];
                    $cart_total += $cart_item['line_total'];
                }
            }
        }
        
        wp_send_json_success(array(
            'quantity' => $cart_quantity,
            'total' => $cart_total
        ));
    }
}

// Inizializza il plugin
new WC_Cart_Product_Summary_Pro();