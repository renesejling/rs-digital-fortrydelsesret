# CLAUDE.md

Instruktioner til Claude (Cline) for arbejde i dette repo.

## Om projektet

**RS Digital Fortrydelsesret** er et WordPress/WooCommerce-plugin der:
- Indsætter info-boks + link til digital fortrydelse i kundens ordremails.
- Vedhæfter handelsbetingelserne som auto-genereret PDF (varigt medie).
- Regenererer PDF'en når betingelses-siden gemmes (Gutenberg/klassisk + Elementor).

Hovedfil: `rs-digital-fortrydelsesret.php`
Afhængigheder (via Composer i `vendor/`):
- `dompdf/dompdf` – PDF-generering
- `yahnis-elsts/plugin-update-checker` – automatiske opdateringer fra GitHub Releases

## Distribution & opdateringer

- Repo'et er **offentligt**: https://github.com/renesejling/rs-digital-fortrydelsesret
- Pluginnet tjekker GitHub **Releases** for nye versioner og viser "Opdatering tilgængelig" i WP-admin på alle sites (ingen auto-opdatering – brugeren trykker selv "Opdater").
- En **GitHub Action** (`.github/workflows/release.yml`) bygger automatisk en komplet plugin-zip **inkl. `vendor/`** (Dompdf m.m.) ved hvert version-tag, og vedhæfter den til en GitHub Release.
- `vendor/` er ignoreret i git, men kommer MED i release-zip'en (styret af `.distignore`).


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

## Rutine: Udgiv en ny version (release)

Når en funktionel ændring skal ud til alle sites:

1. Bump version i plugin-headeren (`Version: x.y.z`).
2. Commit + push til `main` (se ovenfor).
3. Opret og push et version-tag der matcher headeren:
   ```bash
   git tag v<x.y.z>
   git push origin v<x.y.z>
   ```
4. GitHub Action (`release.yml`) bygger automatisk en zip **inkl. `vendor/`** og opretter en **GitHub Release**.
5. Sites ser opdateringen i WP-admin (typisk inden for få timer, eller straks ved "Søg efter opdateringer").

> **Vigtigt:** Tag-versionen SKAL matche `Version:` i plugin-headeren, ellers opdager update-checkeren ikke opdateringen korrekt.

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
