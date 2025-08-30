# WooCommerce Cart Product Summary Pro

Un plugin WordPress avanzato che mostra un riepilogo in tempo reale del prodotto corrente nel carrello WooCommerce, con pannello di amministrazione e shortcode altamente personalizzabile.

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-3.0%2B-purple.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-green.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-red.svg)

## ✨ Caratteristiche

- 📊 **Riepilogo in tempo reale** - Si aggiorna istantaneamente quando modifichi quantità o varianti
- 🎨 **Pannello di amministrazione** - Interfaccia user-friendly per personalizzazioni globali
- 🏷️ **Shortcode flessibile** - Parametri per controllo granulare di ogni istanza
- 🎯 **Sezioni modulari** - Mostra solo le sezioni che ti servono (Carrello, Selezione, Totale)
- 🌈 **Personalizzazione completa** - Colori, tipografia e layout configurabili
- 📱 **Design responsive** - Perfetto su desktop, tablet e mobile
- 🔄 **Supporto varianti** - Compatibile con prodotti semplici e variabili
- ⚡ **Performance ottimizzate** - Codice leggero e veloce

## 📋 Requisiti

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+

## 🚀 Installazione

### Metodo 1: Upload manuale

1. Scarica il plugin dal repository
2. Carica la cartella `wc-cart-summary-pro` in `/wp-content/plugins/`
3. Attiva il plugin dal pannello WordPress
4. Vai in `Impostazioni > Cart Summary` per configurare

### Metodo 2: Via WordPress Admin

1. Vai in `Plugin > Aggiungi nuovo`
2. Carica il file `.zip` del plugin
3. Attiva il plugin
4. Configura dalle impostazioni

## ⚙️ Configurazione

### Pannello di Amministrazione

Accedi alle impostazioni da `Impostazioni > Cart Summary`:

#### Comportamento
- **Mostra prezzo quando quantità è zero** - Visualizza il prezzo anche con qty=0
- **Auto-aggiunta alle pagine prodotto** - Inserisce automaticamente il widget in tutte le pagine prodotto

#### Personalizzazione Visiva
- **Colori sezioni** - Sfondo e bordo per ogni sezione (Carrello, Selezione, Totale)
- **Tipografia** - Colore e dimensione per titoli e testo

## 📝 Utilizzo Shortcode

### Shortcode Base
```
[cart_product_summary]
```

### Parametri Disponibili

| Parametro | Valori | Default | Descrizione |
|-----------|--------|---------|-------------|
| `title` | stringa | "Riepilogo Completo Prodotto" | Titolo personalizzato |
| `show_price_zero` | yes/no | Impostazione globale | Mostra prezzo con qty=0 |
| `show_cart` | yes/no | yes | Mostra sezione "Nel Carrello" |
| `show_selected` | yes/no | yes | Mostra sezione "Stai Aggiungendo" |
| `show_total` | yes/no | yes | Mostra sezione "Totale Complessivo" |
| `cart_color` | hex/colore | - | Colore sfondo sezione carrello |
| `selected_color` | hex/colore | - | Colore sfondo sezione selezione |
| `total_color` | hex/colore | - | Colore sfondo sezione totale |
| `title_size` | numero | - | Dimensione titolo in px |
| `text_size` | numero | - | Dimensione testo in px |

## 🎯 Esempi di Utilizzo

### Esempio 1: Widget Completo
```
[cart_product_summary]
```

### Esempio 2: Solo Sezione Aggiunta
```
[cart_product_summary show_cart="no" show_total="no" title="Aggiungi al Carrello"]
```

### Esempio 3: Solo Totale Complessivo
```
[cart_product_summary show_cart="no" show_selected="no" title="Riepilogo Finale"]
```

### Esempio 4: Personalizzazione Completa
```
[cart_product_summary 
    title="Offerta Speciale" 
    show_cart="no" 
    selected_color="#ffeb3b" 
    title_size="24" 
    show_price_zero="yes"]
```

### Esempio 5: Carrello + Totale
```
[cart_product_summary show_selected="no" title="Il Tuo Carrello"]
```

## 🎨 Sezioni del Widget

### 🛒 Nel Carrello (show_cart)
- Quantità già presente nel carrello per questo prodotto
- Valore totale già nel carrello

### ➕ Stai Aggiungendo (show_selected)  
- Quantità attualmente selezionata
- Prezzo unitario del prodotto/variante
- Subtotale della selezione corrente

### 🎯 Totale Complessivo (show_total)
- Quantità totale (carrello + selezione)
- Valore totale complessivo

## 🔧 Personalizzazione CSS

### Classi CSS Principali

```css
.wc-cart-product-summary              /* Container principale */
.wc-cart-product-summary .summary-title         /* Titolo */
.wc-cart-product-summary .cart-section          /* Sezione carrello */
.wc-cart-product-summary .selected-section      /* Sezione selezione */
.wc-cart-product-summary .total-section         /* Sezione totale */
.wc-cart-product-summary .summary-row           /* Riga dati */
.wc-cart-product-summary .summary-label         /* Etichetta */
.wc-cart-product-summary .summary-value         /* Valore */
```

### CSS Personalizzato
```css
/* Esempio: Stile personalizzato per tema scuro */
.wc-cart-product-summary {
    background: #2c3e50 !important;
    color: #ecf0f1 !important;
}

.wc-cart-product-summary .summary-title {
    color: #3498db !important;
    border-bottom-color: #3498db !important;
}
```

## 🔌 Integrazione Template

### Nel file PHP del template
```php
// Aggiunta manuale nel template
echo do_shortcode('[cart_product_summary]');
```

### Hook WordPress
```php
// Aggiunta tramite hook personalizzato
add_action('woocommerce_single_product_summary', function() {
    echo do_shortcode('[cart_product_summary title="Il Tuo Ordine"]');
}, 30);
```

## 🐛 Risoluzione Problemi

### Il widget non si aggiorna
1. Verifica che jQuery sia caricato
2. Controlla la console del browser per errori JavaScript
3. Assicurati che WooCommerce sia attivo e aggiornato

### I colori non vengono applicati
1. Svuota la cache del browser (Ctrl+F5)
2. Disattiva plugin di cache temporaneamente  
3. Verifica conflitti con il tema

### Problemi con le varianti
1. Controlla che il prodotto abbia varianti configurate correttamente
2. Verifica che non ci siano script JavaScript in conflitto

## 🔄 Changelog

### v2.0
- ✨ Aggiunto pannello di amministrazione completo
- ✨ Nuovi parametri shortcode per controllo sezioni
- ✨ Personalizzazione colori e tipografia
- 🐛 Risolti problemi con varianti prodotto
- 🔧 Migliorata performance e compatibilità

### v1.0
- 🎉 Prima release
- ✨ Shortcode base con riepilogo carrello
- ✨ Aggiornamento in tempo reale
- ✨ Supporto prodotti variabili

## 📄 Licenza

Questo plugin è rilasciato sotto licenza GPL v2 o successiva.

## 👨‍💻 Sviluppatore

**Rocco Fusella**  
- 🌐 Website: [roccofusella.it](https://roccofusella.it)
- 📧 Email: info@roccofusella.it
- 💼 LinkedIn: [Rocco Fusella](https://linkedin.com/in/rocco-fusella)

## 🤝 Contribuire

I contributi sono benvenuti! Per contribuire:

1. Fai un fork del progetto
2. Crea un branch per la tua feature (`git checkout -b feature/AmazingFeature`)
3. Committa le modifiche (`git commit -m 'Add some AmazingFeature'`)
4. Pusha il branch (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

## ⭐ Supporto

Se questo plugin ti è utile, considera di:
- ⭐ Dare una stella al repository
- 🐛 Segnalare bug o richiedere nuove funzionalità
- 📢 Condividere il progetto con altri sviluppatori

## 📞 Supporto Tecnico

Per supporto tecnico o richieste personalizzate:
- 📧 Email: support@roccofusella.it
- 🐛 Issue GitHub: [Apri un issue](https://github.com/roccofusella/wc-cart-summary-pro/issues)

---

**Made with ❤️ by Rocco Fusella**