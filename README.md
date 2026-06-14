# RS Digital Fortrydelsesret

WordPress/WooCommerce-plugin der:

1. Indsætter en kort info-boks med **link til digital fortrydelse** i kundens ordremails.
2. **Vedhæfter de aktuelle handelsbetingelser** (den side der er valgt i WooCommerce) automatisk som **PDF** til kundens ordremails — et "varigt medie".
3. **Regenererer PDF'en automatisk**, når handelsbetingelses-siden gemmes/opdateres — uanset om siden er bygget med Gutenberg, klassisk editor eller **Elementor**.

- **Version:** 1.1.0
- **Forfatter:** [ReneSejling.dk](https://renesejling.dk)

---

## Hvordan det virker

| Funktion | Hook | Beskrivelse |
|----------|------|-------------|
| Info-boks + link | `woocommerce_email_after_order_table` | Vises i kundens behandler-/færdigbehandlet-mails |
| PDF-vedhæftning | `woocommerce_email_attachments` | Vedhæfter cachet PDF (lazy-genereres hvis den mangler) |
| Auto-regenerering (klassisk/Gutenberg) | `save_post` | Genererer PDF når betingelses-siden gemmes |
| Auto-regenerering (Elementor) | `elementor/document/after_save` | Genererer PDF efter Elementor-gem |

PDF'en gemmes i: `wp-content/uploads/rs-fortrydelsesret/handelsbetingelser.pdf`

De mails der får note + PDF styres af konstanten `RS_FR_MAILS`
(standard: `customer_processing_order`, `customer_completed_order`).

Stien til fortrydelsessiden styres af `RS_FR_PATH` (standard: `/fortrydelsesret/`).

---

## Installation

1. Kopiér plugin-mappen til `wp-content/plugins/`.
2. Installér afhængigheder (Dompdf) med Composer:
   ```bash
   composer install
   ```
3. Aktivér pluginnet i WordPress-admin.
4. Sørg for at der er valgt en **handelsbetingelses-side** under
   WooCommerce → Indstillinger → Avanceret / juridiske sider.

> **Bemærk:** `vendor/`-mappen er ikke i git. Kør `composer install` efter clone.

---

## Krav

- PHP >= 7.4
- WordPress + WooCommerce
- [dompdf/dompdf](https://github.com/dompdf/dompdf) ^3.0 (installeres via Composer)

---

## Udvikling

```bash
# Installér afhængigheder
composer install

# Opdatér Dompdf
composer update dompdf/dompdf
```

---

## Licens

Proprietær — © ReneSejling.dk. Alle rettigheder forbeholdes.
