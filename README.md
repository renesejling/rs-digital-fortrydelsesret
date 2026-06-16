# RS Digital Fortrydelsesret

Komplet WordPress/WooCommerce-plugin til digital fortrydelsesret. Pluginnet samler
to ting: **kundens fortrydelses-flow** (offentlig formular + sagsbehandling) og
**dokumentation/oplysning i ordremails** (info-boks + handelsbetingelser som PDF).

### A) Fortrydelses-flow (formular + sagsbehandling)

1. **Offentlig fortrydelsesformular** via shortcode `[digital_fortrydelse]`.
   Kunden angiver navn, e-mail og ordrenummer og vælger hele ordren eller enkelte produkter.
2. **Kvitteringsmail til kunden** og **intern notifikation til butikken** sendes
   automatisk (via `wp_mail`) når formularen indsendes. Mail-tekster kan tilpasses i indstillingerne.
3. **Validering mod WooCommerce-ordren**: ordrenummer + e-mail skal matche en rigtig
   ordre, fristberegning (14 dage), og dubletter blokeres.
4. **Sagsbehandling i admin** under *WooCommerce → Fortrydelser*: statusstyring,
   intern note, friststatus-badges og CSV-eksport (regnearks-sikret).
5. **Min Konto-visning**: kunden kan se sine egne fortrydelser under *Mine fortrydelser*.
6. **GDPR-retention**: sager slettes automatisk efter et valgt antal år (daglig cron).

### B) Ordremails (info-boks + PDF på varigt medie)

7. Indsætter en kort info-boks med **link til digital fortrydelse** i kundens ordremails.
8. **Vedhæfter de aktuelle handelsbetingelser** (den side der er valgt i WooCommerce) automatisk som **PDF** til kundens ordremails — et "varigt medie".
9. **Regenererer PDF'en automatisk**, når handelsbetingelses-siden gemmes/opdateres — uanset om siden er bygget med Gutenberg, klassisk editor eller **Elementor**.
10. Er **WPML- og Polylang-kompatibel**: mail-boksens tekster er indbygget på fem sprog (da/en/de/sv/nb), linket peger automatisk på den oversatte fortrydelsesside, og den vedhæftede PDF tages fra den oversatte handelsbetingelses-side der matcher kundens sprog.

> **Bemærk:** De to dele kolliderer ikke. Info-boks + PDF hænger på WooCommerce'
> eksisterende ordremails, mens kvitterings-/notifikationsmails udløses af selve
> formular-indsendelsen.

- **Version:** 2.0.0
- **Forfatter:** [ReneSejling.dk](https://renesejling.dk)





---

## Hvordan det virker

| Funktion | Hook | Beskrivelse |
|----------|------|-------------|
| Info-boks + link | `woocommerce_email_after_order_table` | Vises i kundens behandler-/færdigbehandlet-mails |
| PDF-vedhæftning | `woocommerce_email_attachments` | Vedhæfter cachet PDF (lazy-genereres hvis den mangler) |
| Auto-regenerering (klassisk/Gutenberg) | `save_post` | Genererer PDF når betingelses-siden gemmes |
| Auto-regenerering (Elementor) | `elementor/document/after_save` | Genererer PDF efter Elementor-gem |

PDF'en gemmes i: `wp-content/uploads/rs-fortrydelsesret/` — én fil pr.
betingelses-side/sprog, navngivet efter side-ID'et (fx
`handelsbetingelser-123.pdf`).


De mails der får note + PDF styres af konstanten `RS_FR_MAILS`
(standard: `customer_processing_order`, `customer_completed_order`).

Stien til fortrydelsessiden styres af `RS_FR_PATH` (standard: `/fortrydelsesret/`).

---

## Oversættelse (WPML & Polylang)

Pluginnet er kompatibelt med både **WPML** og **Polylang** og virker
**out-of-the-box** uden ekstra opsætning.

### Tekster i mail-boksen

Teksterne er **indbygget på fem sprog**: dansk, engelsk, tysk, svensk og norsk
(bokmål). Pluginnet vælger automatisk sproget ud fra ordrens/kundens sprog
(Polylang/WPML) eller WordPress' locale. Er sproget ikke et af de fem, bruges dansk.

**Override / flere sprog:** strengene registreres også i **String Translation**
under gruppen **"RS Digital Fortrydelsesret"**, så du kan tilføje yderligere sprog
eller finjustere teksterne dér:

- WPML: **WPML → String Translation** → filtrér på gruppen.
- Polylang: **Sprog → Strings translations** → filtrér på gruppen.

Rækkefølgen er: *String Translation-oversættelse (hvis udfyldt) → indbygget sprog → dansk.*

### Linket til fortrydelsessiden

Linket findes **automatisk** på det rigtige sprog: pluginnet slår den oversatte side op,
der er koblet til original-siden (`/fortrydelsesret/`) i WPML/Polylang, og bruger dens
permalink. Findes der ingen oversættelse, falder det tilbage til original-siden.

### Vedhæftet handelsbetingelses-PDF

PDF'en tages **automatisk fra den oversatte handelsbetingelses-side** der matcher
kundens sprog. Pluginnet finder den WooCommerce-valgte betingelses-sides oversættelse
(Polylang/WPML), genererer PDF'en ud fra dens indhold (titel + tekst på det rigtige
sprog) og cacher en separat fil pr. side. Findes der ingen oversættelse, bruges den
originale side. PDF'en regenereres automatisk når en hvilken som helst oversættelse
af betingelses-siden gemmes.

---

## Installation


### På et produktions-site (anbefalet)

1. Hent den nyeste **release-zip** fra
   [Releases](https://github.com/renesejling/rs-digital-fortrydelsesret/releases)
   (zip'en indeholder allerede `vendor/` med Dompdf — ingen Composer nødvendig).
2. WordPress-admin → Plugins → Tilføj nyt → Upload plugin → vælg zip → installer → aktivér.
3. Sørg for at der er valgt en **handelsbetingelses-side** under
   WooCommerce → Indstillinger → Avanceret / juridiske sider.
4. Opret en side (fx `/fortrydelsesret/`) med shortcoden `[digital_fortrydelse]`
   til den offentlige fortrydelsesformular.
5. Gennemgå indstillinger under **WooCommerce → Fortrydelse indstillinger**
   (modtager, opbevaring/retention, formulartekst, mail-skabeloner og handelsbetingelser).
6. Fortrydelsessager håndteres under **WooCommerce → Fortrydelser**.


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

- PHP >= 8.1
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
