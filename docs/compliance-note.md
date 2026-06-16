# Compliance-note

## Kvitteringstekst

Standardteksterne i pluginet bekræfter kun modtagelse af kundens anmodning om fortrydelse.

Verificeret tekstspor:

- Formularen viser: "Kvitteringen bekræfter kun, at vi har modtaget din anmodning om fortrydelse. Den er ikke en endelig afgørelse af sagen."
- Succesbeskeden viser reference og at kvittering er sendt.
- Standard kundemail siger: "Denne kvittering bekræfter kun, at vi har modtaget din anmodning. Den er ikke en endelig afgørelse af sagen."
- Standard intern mail er en intern notifikation og bruges ikke som kundens afgørelse.

Hvis mailtemplates ændres i admin, skal kundekvitteringen stadig beskrive modtagelse af anmodningen og må ikke formuleres som en godkendelse, refundering eller endelig accept.

## Dokumentationsspor

Pluginet gemmer dokumentationsspor i custom-tabellen `wp_digital_fortrydelser`.

Relevante felter:

- `reference`: unik reference til sagen.
- `customer_name`, `customer_email`, `order_number`: kundens minimumsoplysninger.
- `request_type`: hele ordre eller enkelte produkter.
- `requested_items` og `request_message`: kundens bemærkning/produktvalg.
- `request_payload`: samlet indsendt formularindhold inkl. ordre-kontekst.
- `submitted_at`: tidspunkt for indsendelse.
- `receipt_sent_at`: tidspunkt for kundekvittering, hvis sendt.
- `internal_notification_sent_at`: tidspunkt for intern mail, hvis sendt.
- `order_id`, `order_date`, `order_email`: WooCommerce-kontekst, hvis ordren blev fundet.
- `email_mismatch`: markering ved afvigelse mellem indsendt e-mail og ordre-e-mail.
- `deadline_at`, `deadline_status`: fristberegning og status.
- `retention_until`: retentiondato til oprydning.

Adminvisningen viser reference, tidspunkt, kunde, ordre, status, friststatus, e-mailafvigelse, indsendt indhold og mailtidspunkter.

CSV-eksporten giver et kompakt dokumentationsudtræk til manuel behandling eller arkivering.
