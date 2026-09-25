# Standardtekst til handelsbetingelser

## Digital fortrydelsesfunktion

Du kan fortryde dit køb ved at bruge vores digitale fortrydelsesfunktion på webshoppen.

Funktionen findes på siden {fortrydelse_link}.

Når du udfylder og sender formularen, skal du oplyse navn, e-mailadresse og ordrenummer. Du kan vælge, om du ønsker at fortryde hele ordren eller enkelte produkter.

Når formularen er sendt, modtager du uden unødig forsinkelse en kvittering pr. e-mail. Kvitteringen bekræfter, at vi har modtaget din anmodning om fortrydelse, og indeholder det indsendte indhold samt dato og tidspunkt for indsendelsen.

Kvitteringen er alene en bekræftelse på modtagelse af din anmodning om fortrydelse. Den er ikke en endelig afgørelse af sagen.

Du anses for at have fortrudt rettidigt, hvis du sender fortrydelsen via den digitale fortrydelsesfunktion inden fortrydelsesfristen udløber.

## Placering ved go-live

Indsæt teksten under handelsbetingelsernes afsnit om fortrydelsesret. Teksten
og tokens vedligeholdes under **WooCommerce → Fortrydelse indstillinger →
Handelsbetingelser → Tekstafsnit**.

Tokenet `{fortrydelse_link}` udskrives automatisk som et klikbart link til
fortrydelsessiden (fx `https://dit-domæne.dk/fortrydelsesret/`). Der findes
også `{fortrydelse_url}`, som kun indsætter den rene URL.

Selve fortrydelsesformularen vises **ikke** af denne tekst. Den vises ved at
indsætte shortcoden `[digital_fortrydelse]` på den side, som tokenet peger på
(fx en side med adressen `/fortrydelsesret/`).
