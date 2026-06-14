# RS Digital Fortrydelsesret

WordPress/WooCommerce-plugin der:

1. Indsætter en kort info-boks med **link til digital fortrydelse** i kundens ordremails.
2. **Vedhæfter de aktuelle handelsbetingelser** (den side der er valgt i WooCommerce) automatisk som **PDF** til kundens ordremails — et "varigt medie".
3. **Regenererer PDF'en automatisk**, når handelsbetingelses-siden gemmes/opdateres — uanset om siden er bygget med Gutenberg, klassisk editor eller **Elementor**.

- **Version:** 1.2.0
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

### På et produktions-site (anbefalet)

1. Hent den nyeste **release-zip** fra
   [Releases](https://github.com/renesejling/rs-digital-fortrydelsesret/releases)
   (zip'en indeholder allerede `vendor/` med Dompdf — ingen Composer nødvendig).
2. WordPress-admin → Plugins → Tilføj nyt → Upload plugin → vælg zip → installer → aktivér.
3. Sørg for at der er valgt en **handelsbetingelses-side** under
   WooCommerce → Indstillinger → Avanceret / juridiske sider.

### Til udvikling (fra git)

1. Klon repo'et til `wp-content/plugins/`.
2. Installér afhængigheder med Composer:
   ```bash
   composer install
   ```
3. Aktivér pluginnet i WordPress-admin.

> **Bemærk:** `vendor/`-mappen er ikke i git. Kør `composer install` efter clone
> (kun nødvendigt ved udvikling — release-zip'en indeholder den allerede).

---

## Opdateringer

Pluginnet har **indbygget opdaterings-tjek** mod GitHub Releases via
[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).

- Når der udgives en ny version, vises **"Opdatering tilgængelig"** i WP-admin —
  præcis som for plugins fra WordPress.org.
- Opdateringer er **manuelle**: du/kunden trykker selv "Opdater".
- Hele opdaterings-zip'en (inkl. `vendor/`) bygges automatisk af en GitHub Action
  ved hvert nyt version-tag.

### Sådan udgives en ny version (for udvikleren)

```bash
# 1) Bump "Version:" i rs-digital-fortrydelsesret.php
# 2) Commit + push
git add -A && git commit -m "fix: ..." && git push
# 3) Tag og push tag (skal matche versionen i headeren)
git tag v1.2.1
git push origin v1.2.1
```

GitHub Action bygger derefter zip'en og opretter releasen automatisk.


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
