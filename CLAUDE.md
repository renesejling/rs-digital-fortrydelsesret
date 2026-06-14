# CLAUDE.md

Instruktioner til Claude (Cline) for arbejde i dette repo.

## Om projektet

**RS Digital Fortrydelsesret** er et WordPress/WooCommerce-plugin der:
- Indsætter info-boks + link til digital fortrydelse i kundens ordremails.
- Vedhæfter handelsbetingelserne som auto-genereret PDF (varigt medie).
- Regenererer PDF'en når betingelses-siden gemmes (Gutenberg/klassisk + Elementor).

Hovedfil: `rs-digital-fortrydelsesret.php`
Afhængighed: `dompdf/dompdf` (installeres via Composer i `vendor/`).

## Vigtige konventioner

- **Sprog:** Al brugervendt tekst og kommentarer er på dansk.
- **Funktions-prefix:** Alle funktioner og konstanter bruger prefikset `rs_fr_` / `RS_FR_` for at undgå navnekollisioner.
- **Versionering:** Følg [SemVer](https://semver.org/). Versionsnummeret står i plugin-headeren i `rs-digital-fortrydelsesret.php` (`Version: x.y.z`).
- **Sikkerhed:** Bevar `if ( ! defined( 'ABSPATH' ) ) exit;` i toppen. Escape alt output (`esc_html`, `esc_url`).
- **Filer der IKKE committes:** `/vendor/`, `*.pdf`, `.DS_Store` (se `.gitignore`).

## Rutine: Commit & push efter ændringer

Efter hver afsluttet ændring skal følgende gøres **automatisk**:

1. **Bump versionen** i plugin-headeren hvis ændringen er funktionel:
   - PATCH (x.y.Z) ved bugfix
   - MINOR (x.Y.0) ved ny funktion (bagudkompatibel)
   - MAJOR (X.0.0) ved breaking changes

2. **Stage, commit og push:**
   ```bash
   git add -A
   git commit -m "<type>: <kort beskrivelse på dansk>"
   git push
   ```

3. **Commit-besked-format** (Conventional Commits):
   - `feat:` ny funktion
   - `fix:` fejlrettelse
   - `docs:` kun dokumentation
   - `refactor:` omstrukturering uden funktionsændring
   - `chore:` vedligehold (deps, config m.m.)

   Eksempel:
   ```
   feat: tilføj note om fortrydelsesret i faktura-mail
   fix: undgå dobbelt PDF-generering ved autosave
   ```

4. **Push altid til `main`** (medmindre andet aftales).

## Før commit – tjekliste

- [ ] Kører `composer install` uden fejl (hvis afhængigheder er ændret).
- [ ] PHP-syntaks er gyldig (`php -l rs-digital-fortrydelsesret.php`).
- [ ] Versionsnummer er bumpet ved funktionelle ændringer.
- [ ] Ingen genererede filer (`vendor/`, `*.pdf`) er staged ved en fejl.

## Nyttige kommandoer

```bash
# Tjek PHP-syntaks
php -l rs-digital-fortrydelsesret.php

# Installér/opdatér afhængigheder
composer install
composer update dompdf/dompdf

# Se status / historik
git status
git log --oneline -10
```
