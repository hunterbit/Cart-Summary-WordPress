# WooCommerce Cart Product Summary Pro
## Documentazione Ufficiale

**Versione:** 2.3
**Autore:** Rocco Fusella
**Website:** roccofusella.it
**Data:** 29 Settembre 2025

---

## Introduzione

Un plugin WordPress avanzato che mostra un riepilogo in tempo reale del prodotto corrente nel carrello WooCommerce, con pannello di amministrazione e shortcode altamente personalizzabile.

**Requisiti:**
- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+

---

## Caratteristiche Principali

### ✨ Funzionalità Core
- **Riepilogo in tempo reale** - Si aggiorna istantaneamente quando modifichi quantità o varianti
- **Pannello di amministrazione** - Interfaccia user-friendly per personalizzazioni globali
- **Shortcode flessibile** - Parametri per controllo granulare di ogni istanza
- **Sezioni modulari** - Mostra solo le sezioni che ti servono (Carrello, Selezione, Totale)

### 💰 Gestione Prezzi e IVA
- **Calcolo IVA automatico** - Visualizza l'IVA in base alla configurazione fiscale WooCommerce
- **Supporto opzioni YITH WAPO** - Gestisce automaticamente checkbox e opzioni aggiuntive
- **Breakdown prezzi** - Mostra dettaglio prezzo base + opzioni aggiuntive

### 🛒 Bottone Integrato "Aggiungi al Carrello"
- **Campo quantità personalizzabile** - Con input numerico indipendente
- **Colori configurabili** - Personalizza colore bottone e campo quantità
- **Layout responsive** - Ottimizzato per tutti i dispositivi

### 🎨 Personalizzazione Visiva
- **Personalizzazione completa** - Colori, tipografia e layout configurabili
- **Design responsive** - Perfetto su desktop, tablet e mobile
- **CSS customizzabile** - Classi CSS ben strutturate per personalizzazioni avanzate

### 🔄 Compatibilità
- **Supporto varianti** - Compatibile con prodotti semplici e variabili
- **Performance ottimizzate** - Codice leggero e veloce
- **Compatibilità temi** - Funziona con tutti i temi WooCommerce

---

## Plugin Compatibili (Opzionali)

- **YITH WooCommerce Product Add-Ons (WAPO)** - Per gestione automatica opzioni aggiuntive con prezzi
- **Qualsiasi plugin per varianti WooCommerce** - Completamente compatibile

---

## Installazione

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

---

## Configurazione

### Pannello di Amministrazione

Accedi alle impostazioni da `Impostazioni > Cart Summary`:

#### Opzioni Comportamento
- **Mostra prezzo quando quantità è zero** - Visualizza il prezzo anche con qty=0
- **Auto-aggiunta alle pagine prodotto** - Inserisce automaticamente il widget in tutte le pagine prodotto
- **Mostra calcolo IVA** - Visualizza l'IVA calcolata automaticamente dal prodotto WooCommerce
- **Mostra bottone "Aggiungi al carrello"** - Abilita il bottone integrato nel riepilogo

#### Personalizzazione Visiva
- **Colori sezioni** - Sfondo e bordo per ogni sezione (Carrello, Selezione, Totale)
- **Bottone Aggiungi al carrello** - Colore personalizzabile del bottone e campo quantità
- **Tipografia** - Colore e dimensione per titoli e testo

---

## Utilizzo Shortcode

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
| `show_vat` | yes/no | Impostazione globale | Mostra calcolo IVA automatico |
| `show_add_to_cart` | yes/no | Impostazione globale | Mostra bottone "Aggiungi al carrello" |
| `cart_color` | hex/colore | - | Colore sfondo sezione carrello |
| `selected_color` | hex/colore | - | Colore sfondo sezione selezione |
| `total_color` | hex/colore | - | Colore sfondo sezione totale |
| `add_to_cart_color` | hex/colore | - | Colore del bottone "Aggiungi al carrello" |
| `title_size` | numero | - | Dimensione titolo in px |
| `text_size` | numero | - | Dimensione testo in px |

---

## Esempi di Utilizzo

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

### Esempio 6: Con IVA Automatica
```
[cart_product_summary show_vat="yes" title="Riepilogo con IVA"]
```

### Esempio 7: Con Bottone "Aggiungi al Carrello"
```
[cart_product_summary show_add_to_cart="yes" title="Aggiungi Prodotto"]
```

### Esempio 8: Bottone Personalizzato
```
[cart_product_summary
    show_add_to_cart="yes"
    add_to_cart_color="#ff6600"
    title="Ordina Subito"]
```

### Esempio 9: Configurazione Completa Avanzata
```
[cart_product_summary
    title="Il Tuo Ordine"
    show_vat="yes"
    show_add_to_cart="yes"
    add_to_cart_color="#e91e63"
    selected_color="#fff3e0"
    total_color="#e8f5e8"
    title_size="22"
    text_size="15"]
```

---

## Sezioni del Widget

### 🛒 Nel Carrello (show_cart)
- Quantità già presente nel carrello per questo prodotto
- Valore totale già nel carrello

### ➕ Stai Aggiungendo (show_selected)
- Quantità attualmente selezionata
- Prezzo unitario del prodotto/variante (include opzioni YITH WAPO)
- Breakdown prezzo base + opzioni aggiuntive (quando presenti)
- Subtotale della selezione corrente
- IVA sulla selezione (se abilitata)
- Bottone "Aggiungi al Carrello" con campo quantità personalizzabile (se abilitato)

### 🎯 Totale Complessivo (show_total)
- Quantità totale (carrello + selezione)
- Valore totale complessivo
- IVA totale (se abilitata)

---

## Personalizzazione CSS

### Classi CSS Principali

```css
.wc-cart-product-summary                         /* Container principale */
.wc-cart-product-summary .summary-title          /* Titolo */
.wc-cart-product-summary .cart-section           /* Sezione carrello */
.wc-cart-product-summary .selected-section       /* Sezione selezione */
.wc-cart-product-summary .total-section          /* Sezione totale */
.wc-cart-product-summary .summary-row            /* Riga dati */
.wc-cart-product-summary .summary-label          /* Etichetta */
.wc-cart-product-summary .summary-value          /* Valore */
.wc-cart-product-summary .vat-info               /* Visualizzazione IVA */
.wc-cart-product-summary .vat-amount             /* Importo IVA */
.wc-cart-product-summary .price-breakdown        /* Dettaglio prezzo base + opzioni */
.wc-cart-product-summary .add-to-cart-container  /* Container bottone e quantità */
.wc-cart-product-summary .cart-quantity-input    /* Campo quantità personalizzato */
.wc-cart-product-summary .add-to-cart-button     /* Bottone "Aggiungi al carrello" */
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

/* Personalizzazione bottone "Aggiungi al carrello" */
.wc-cart-product-summary .add-to-cart-button {
    background: linear-gradient(45deg, #ff6b35, #ff8e53) !important;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3) !important;
}

.wc-cart-product-summary .cart-quantity-input {
    border: 2px solid #ff6b35 !important;
    border-radius: 5px !important;
}
```

---

## Integrazione Template

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

---

## Risoluzione Problemi

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

### L'IVA non viene visualizzata
1. Verifica che l'opzione "Mostra calcolo IVA" sia abilitata nel pannello admin
2. Controlla che il prodotto abbia una classe fiscale configurata in WooCommerce
3. Assicurati che ci sia una quantità selezionata nel prodotto
4. Verifica che le imposte siano abilitate in WooCommerce (Impostazioni > Generale)
5. Controlla che siano configurate le aliquote fiscali in WooCommerce (Impostazioni > Imposte)

### Il bottone "Aggiungi al Carrello" non funziona
1. Verifica che l'opzione sia abilitata nel pannello admin o tramite parametro shortcode `show_add_to_cart="yes"`
2. Controlla che ci sia una quantità maggiore di 0 nel campo personalizzato
3. Assicurati che il tema non abbia conflitti JavaScript (controlla console browser F12)
4. Verifica che il prodotto sia in stock e acquistabile

### Le opzioni YITH WAPO non vengono calcolate
1. Assicurati che il plugin YITH WooCommerce Product Add-Ons sia attivo
2. Verifica che le opzioni abbiano prezzi configurati correttamente
3. Controlla la console del browser per eventuali errori JavaScript
4. Le opzioni devono avere la classe CSS `yith-wapo-option-value`

### Il prezzo dei prodotti semplici non viene visualizzato
1. Controlla la console del browser (F12) per vedere quale selettore trova il prezzo
2. Il plugin cerca il prezzo in questi selettori (in ordine): `.summary .price .amount`, `p.price .amount`, `.price .amount`
3. Verifica che il tema non nasconda gli elementi prezzo con CSS
4. Assicurati che WooCommerce visualizzi correttamente il prezzo del prodotto

---

## Changelog

### v2.3 (Ultima versione)
- ✨ **NUOVO:** Bottone "Aggiungi al Carrello" integrato nel riepilogo
- ✨ **NUOVO:** Campo quantità personalizzabile nel riepilogo
- ✨ **NUOVO:** Supporto completo per opzioni YITH WooCommerce Product Add-Ons
- ✨ **NUOVO:** Colore personalizzabile per bottone "Aggiungi al Carrello"
- ✨ **NUOVO:** Breakdown automatico prezzo base + opzioni aggiuntive
- 🔧 Migliorato sistema di rilevamento prezzo per prodotti semplici
- 🔧 Logica aggiornata per calcolo totali con quantità personalizzata
- 🐛 Risolti problemi di visualizzazione prezzo su diversi temi
- 🎨 Layout responsive ottimizzato per bottone e campo quantità

### v2.2
- ✨ **NUOVO:** Debug console per troubleshooting
- 🔧 Selettori CSS ottimizzati per compatibilità temi
- 🐛 Risolti problemi di timing nell'aggiornamento widget
- 🔧 Trigger aggiuntivi per caricamento pagina completo

### v2.1
- ✨ **NUOVO:** Calcolo automatico IVA basato su configurazione WooCommerce
- ✨ Visualizzazione IVA nelle sezioni "Stai Aggiungendo" e "Totale Complessivo"
- ✨ Supporto per classi fiscali diverse (22%, 10%, 4%, esentasse)
- ✨ Parametro shortcode `show_vat` per controllo visualizzazione IVA
- 🐛 Risolti warning PHP nella pagina di amministrazione
- 🔧 Migliorato calcolo IVA per prodotti con varianti

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

---

## Licenza

Questo plugin è rilasciato sotto licenza GPL v2 o successiva.

---

## Sviluppatore

**Rocco Fusella**
- 🌐 Website: roccofusella.it
- 📧 Email: info@roccofusella.it
- 💼 LinkedIn: Rocco Fusella

---

## Contribuire

I contributi sono benvenuti! Per contribuire:

1. Fai un fork del progetto
2. Crea un branch per la tua feature (`git checkout -b feature/AmazingFeature`)
3. Committa le modifiche (`git commit -m 'Add some AmazingFeature'`)
4. Pusha il branch (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

---

## Supporto

Se questo plugin ti è utile, considera di:
- ⭐ Dare una stella al repository
- 🐛 Segnalare bug o richiedere nuove funzionalità
- 📢 Condividere il progetto con altri sviluppatori

---

## Supporto Tecnico

Per supporto tecnico o richieste personalizzate:
- 📧 Email: support@roccofusella.it
- 🐛 Issue GitHub: Apri un issue su GitHub

---

**Made with ❤️ by Rocco Fusella**