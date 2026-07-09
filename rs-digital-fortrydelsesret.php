<?php
/**
 * Plugin Name: RS Digital Fortrydelsesret
 * Description: Digital fortrydelsesret til WooCommerce: fortrydelsesformular (shortcode [digital_fortrydelse]), kvitterings-/notifikationsmails, admin-sagsbehandling, GDPR-retention, Min Konto-visning og handelsbetingelser som PDF. WPML/Polylang-klar.
 * Version:     2.2.0

 * Author:      ReneSejling.dk

 * Author URI:  https://www.renesejling.dk
 * Update URI:  https://github.com/renesejling/rs-digital-fortrydelsesret
 * Text Domain: rs-digital-fortrydelsesret
 * Domain Path: /languages

 * Requires PHP: 8.1
 * WC requires at least: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Dompdf + Plugin Update Checker indlæses via composer i denne mappe (kør: composer install).
$rs_fr_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $rs_fr_autoload ) ) {
	require_once $rs_fr_autoload;
}

/* ------------------------------------------------------------------ *
 * Plugin-konstanter (bruges af klasserne i includes/)                *
 * ------------------------------------------------------------------ */
if ( ! defined( 'RS_FR_VERSION' ) ) {
	define( 'RS_FR_VERSION', '2.2.0' );

}


if ( ! defined( 'RS_FR_DB_VERSION' ) ) {
	define( 'RS_FR_DB_VERSION', '2' );
}

if ( ! defined( 'RS_FR_PLUGIN_FILE' ) ) {
	define( 'RS_FR_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'RS_FR_PLUGIN_PATH' ) ) {
	define( 'RS_FR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'RS_FR_PLUGIN_URL' ) ) {
	define( 'RS_FR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
// Navn på den brugerdefinerede DB-tabel (uden wpdb-prefix).
if ( ! defined( 'RS_FR_TABLE' ) ) {
	define( 'RS_FR_TABLE', 'digital_fortrydelser' );
}

/* ------------------------------------------------------------------ *
 * Indlæs klasserne til fortrydelsesformular, sagsbehandling m.m.     *
 * ------------------------------------------------------------------ */
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-schema.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-retention.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-account.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-repository.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-woocommerce.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-mailer.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-admin.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-settings.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-frontend.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-activator.php';
require_once RS_FR_PLUGIN_PATH . 'includes/class-rs-fr-deactivator.php';

// Aktivering/deaktivering (opret DB-tabel, capabilities, cron, rewrite-endpoint).
register_activation_hook( __FILE__, array( 'RS_FR_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RS_FR_Deactivator', 'deactivate' ) );

/**
 * Initialisér fortrydelses-modulet (formular, mails, admin, retention, Min Konto).
 *
 * Kører på 'plugins_loaded', så WooCommerce er indlæst. Selve mail-boksen +
 * PDF-vedhæftningen længere nede i filen hænger sig på WooCommerce-hooks og
 * kører uafhængigt af dette.
 */
add_action( 'plugins_loaded', 'rs_fr_bootstrap_modules' );
function rs_fr_bootstrap_modules() {
	load_plugin_textdomain(
		'rs-digital-fortrydelsesret',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// Hold den gemte version i sync efter kode-opdateringer. Ved en ny version
	// sikrer vi også capabilities, så admin-menuerne dukker op efter en
	// opdatering (hvor aktiverings-hooket ikke kører igen).
	if ( get_option( 'digital_fortrydelse_version' ) !== RS_FR_VERSION ) {
		update_option( 'digital_fortrydelse_version', RS_FR_VERSION, false );
		RS_FR_Activator::add_capabilities();
	}

	// Selvhelende DB-skema: hvis DB-versionen ikke matcher (fx fordi pluginnet
	// er kommet ind via en auto-opdatering, hvor aktiverings-hooket ikke kører),
	// så opretter/opdaterer vi den brugerdefinerede tabel via dbDelta. Det sikrer
	// at fortrydelses-indsendelser kan gemmes, også uden manuel re-aktivering.
	if ( (string) get_option( 'digital_fortrydelse_db_version' ) !== (string) RS_FR_DB_VERSION ) {
		RS_FR_Activator::maybe_upgrade();
	}

	RS_FR_Admin::init();


	RS_FR_Account::init();
	RS_FR_Retention::init();
	RS_FR_Settings::init();
	RS_FR_Frontend::init();
}


/* ------------------------------------------------------------------ *
 * Automatiske opdateringer fra GitHub Releases                       *
 * Viser "Opdatering tilgængelig" i WP-admin når der laves en ny      *
 * release (tag vX.Y.Z) på GitHub. Henter release-zip'en med vendor/. *
 * ------------------------------------------------------------------ */
if ( class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
	$rs_fr_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/renesejling/rs-digital-fortrydelsesret/',
		__FILE__,
		'rs-digital-fortrydelsesret'
	);

	// Brug GitHub Releases (de zip-assets vores GitHub Action bygger med vendor/ inkluderet).
	$rs_fr_update_checker->getVcsApi()->enableReleaseAssets();
}


// Slug til den digitale fortrydelsesside (pluginnets side). Bruges som fallback,
// hvis der ikke kan findes en rigtig WP-side (se rs_fr_get_withdrawal_url()).
const RS_FR_PATH = '/fortrydelsesret/';

// Hvilke kundemails skal have note + PDF.
const RS_FR_MAILS = array( 'customer_processing_order', 'customer_completed_order' );

// Gruppenavn der vises i WPML/Polylang's String Translation-modul.
const RS_FR_STRINGS_GROUP = 'RS Digital Fortrydelsesret';

/* ------------------------------------------------------------------ *
 * 0) Oversættelse (indbygget + WPML/Polylang String Translation)     *
 * ------------------------------------------------------------------ *
 * Mail-boksens tekster er indbygget på dansk, engelsk, tysk, svensk  *
 * og norsk (bokmål), så det virker out-of-the-box uden opsætning.    *
 * Sproget bestemmes ud fra Polylang/WPML/WordPress ved afsendelse.   *
 *                                                                    *
 * Derudover registreres de danske strenge i WPML/Polylang's String   *
 * Translation, så man kan tilføje flere sprog eller finjustere       *
 * teksterne dér (override). Rækkefølge i rs_fr_t():                   *
 *   String Translation-oversættelse → indbygget sprog → dansk.       *
 * ------------------------------------------------------------------ */

/**
 * Indbyggede oversættelser pr. sprogkode.
 *
 * @return array<string,array<string,string>>
 */
function rs_fr_translations() {
	return array(
		'da' => array(
			'heading'        => 'Fortrydelsesret',
			'intro'          => 'Du har 14 dages fortrydelsesret. Du kan fortryde dit køb direkte via vores digitale fortrydelsesfunktion:',
			'link_text'      => 'Gå til fortrydelse',
			'pdf_note'       => 'Dine handelsbetingelser er vedhæftet denne mail som PDF.',
			'intro_plain'    => 'Du har 14 dages fortrydelsesret. Fortryd dit kob direkte her:',
			'pdf_note_plain' => 'Dine handelsbetingelser er vedhaeftet denne mail som PDF.',
		),
		'en' => array(
			'heading'        => 'Right of withdrawal',
			'intro'          => 'You have a 14-day right of withdrawal. You can cancel your purchase directly via our digital withdrawal function:',
			'link_text'      => 'Go to withdrawal',
			'pdf_note'       => 'Your terms and conditions are attached to this email as a PDF.',
			'intro_plain'    => 'You have a 14-day right of withdrawal. Cancel your purchase directly here:',
			'pdf_note_plain' => 'Your terms and conditions are attached to this email as a PDF.',
		),
		'de' => array(
			'heading'        => 'Widerrufsrecht',
			'intro'          => 'Sie haben ein 14-tägiges Widerrufsrecht. Sie können Ihren Kauf direkt über unsere digitale Widerrufsfunktion widerrufen:',
			'link_text'      => 'Zum Widerruf',
			'pdf_note'       => 'Ihre Allgemeinen Geschäftsbedingungen sind dieser E-Mail als PDF beigefügt.',
			'intro_plain'    => 'Sie haben ein 14-tägiges Widerrufsrecht. Widerrufen Sie Ihren Kauf direkt hier:',
			'pdf_note_plain' => 'Ihre Allgemeinen Geschaeftsbedingungen sind dieser E-Mail als PDF beigefuegt.',
		),
		'sv' => array(
			'heading'        => 'Ångerrätt',
			'intro'          => 'Du har 14 dagars ångerrätt. Du kan ångra ditt köp direkt via vår digitala ångerfunktion:',
			'link_text'      => 'Gå till ångerrätt',
			'pdf_note'       => 'Dina köpvillkor bifogas detta mejl som PDF.',
			'intro_plain'    => 'Du har 14 dagars angerratt. Angra ditt kop direkt har:',
			'pdf_note_plain' => 'Dina kopvillkor bifogas detta mejl som PDF.',
		),
		'nb' => array(
			'heading'        => 'Angrerett',
			'intro'          => 'Du har 14 dagers angrerett. Du kan angre kjøpet ditt direkte via vår digitale angrefunksjon:',
			'link_text'      => 'Gå til angrerett',
			'pdf_note'       => 'Dine kjøpsvilkår er vedlagt denne e-posten som PDF.',
			'intro_plain'    => 'Du har 14 dagers angrerett. Angre kjopet ditt direkte her:',
			'pdf_note_plain' => 'Dine kjopsvilkar er vedlagt denne e-posten som PDF.',
		),
	);
}

/**
 * Bestem det aktuelle sprog som en 2-bogstavs kode (fx 'da', 'en', 'de').
 * Rækkefølge: Polylang → WPML → WordPress determine_locale() → 'da'.
 *
 * @return string Sprogkode (lowercase, 2 tegn).
 */
function rs_fr_current_lang() {
	// Polylang.
	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language( 'slug' );
		if ( $lang ) {
			return strtolower( substr( $lang, 0, 2 ) );
		}
	}

	// WPML.
	if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
		return strtolower( substr( ICL_LANGUAGE_CODE, 0, 2 ) );
	}

	// WordPress-locale (fx 'en_US' → 'en', 'nb_NO' → 'nb').
	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	if ( $locale ) {
		return strtolower( substr( $locale, 0, 2 ) );
	}

	return 'da';
}

/**
 * De danske standardstrenge (bruges som "name"/originaltekst i String Translation).
 *
 * @return array<string,string>
 */
function rs_fr_strings() {
	$translations = rs_fr_translations();
	return $translations['da'];
}

/**
 * Registrér strengene til både Polylang og WPML, så de dukker op i
 * String Translation. Helt ufarligt hvis ingen af plugins er aktive.
 */
add_action( 'init', 'rs_fr_register_strings' );
function rs_fr_register_strings() {
	$strings = rs_fr_strings();

	// Polylang: pll_register_string( $name, $string, $group, $multiline ).
	if ( function_exists( 'pll_register_string' ) ) {
		foreach ( $strings as $name => $value ) {
			pll_register_string( $name, $value, RS_FR_STRINGS_GROUP );
		}
	}

	// WPML: registrér via action-hook (kræver String Translation-modulet).
	if ( has_action( 'wpml_register_single_string' ) ) {
		foreach ( $strings as $name => $value ) {
			do_action( 'wpml_register_single_string', RS_FR_STRINGS_GROUP, $name, $value );
		}
	}
}

/**
 * Oversæt en streng til det aktuelle sprog.
 *
 * Rækkefølge:
 *   1. String Translation-oversættelse (hvis udfyldt i WPML/Polylang).
 *   2. Indbygget oversættelse for det aktuelle sprog (da/en/de/sv/nb).
 *   3. Dansk standard.
 *
 * @param string $name Nøglen fra rs_fr_translations().
 * @return string Oversat tekst.
 */
function rs_fr_t( $name ) {
	$translations = rs_fr_translations();
	$danish       = isset( $translations['da'][ $name ] ) ? $translations['da'][ $name ] : '';

	// Indbygget oversættelse for det aktuelle sprog (fallback til dansk).
	$lang     = rs_fr_current_lang();
	$built_in = ( isset( $translations[ $lang ][ $name ] ) && '' !== $translations[ $lang ][ $name ] )
		? $translations[ $lang ][ $name ]
		: $danish;

	// 1) WPML String Translation – brug kun hvis der faktisk findes en oversættelse
	//    (ellers returnerer den bare originalen, og så foretrækker vi vores indbyggede).
	if ( has_filter( 'wpml_translate_single_string' ) ) {
		$translated = apply_filters( 'wpml_translate_single_string', $danish, RS_FR_STRINGS_GROUP, $name );
		if ( $translated && $translated !== $danish ) {
			return $translated;
		}
	}

	// 2) Polylang String Translation – samme princip.
	if ( function_exists( 'pll__' ) && function_exists( 'pll_current_language' ) ) {
		$translated = pll__( $danish );
		if ( $translated && $translated !== $danish ) {
			return $translated;
		}
	}

	// 3) Indbygget oversættelse / dansk standard.
	return $built_in;
}

/**
 * Find side-ID'et for fortrydelsessiden / en valgt side på det aktuelle sprog.
 * Bruger Polylang/WPML's kobling mellem original og oversættelse.
 *
 * @param int    $base_id   Original-sidens ID.
 * @param string $post_type Posttype (bruges af WPML), standard 'page'.
 * @return int Det oversatte side-ID (eller originalen hvis ingen oversættelse).
 */
function rs_fr_translated_post_id( $base_id, $post_type = 'page' ) {
	$base_id = (int) $base_id;
	if ( ! $base_id ) {
		return 0;
	}

	// Polylang.
	if ( function_exists( 'pll_get_post' ) ) {
		$candidate = pll_get_post( $base_id );
		if ( $candidate ) {
			return (int) $candidate;
		}
	} elseif ( has_filter( 'wpml_object_id' ) ) {
		// WPML (true = fald tilbage til original hvis ingen oversættelse).
		$candidate = apply_filters( 'wpml_object_id', $base_id, $post_type, true );
		if ( $candidate ) {
			return (int) $candidate;
		}
	}

	return $base_id;
}

/**
 * Find URL'en til fortrydelsessiden på det aktuelle sprog.
 *
 * Forsøger i rækkefølge:
 *   1. Den oversatte WP-side koblet til original-siden (Polylang/WPML).
 *   2. Original-siden fundet ud fra stien RS_FR_PATH.
 *   3. home_url( RS_FR_PATH ) som ren fallback.
 *
 * @return string Færdig, escaped URL.
 */
function rs_fr_get_withdrawal_url() {
	// Find original-sidens ID ud fra stien (fx /fortrydelsesret/).
	$base_id = url_to_postid( home_url( RS_FR_PATH ) );

	if ( $base_id ) {
		$translated_id = rs_fr_translated_post_id( $base_id );

		$permalink = get_permalink( $translated_id );
		if ( $permalink ) {
			return esc_url( $permalink );
		}
	}

	// Fallback: byg ud fra stien (WPML/Polylang tilføjer selv sprog-prefix hvis relevant).
	return esc_url( home_url( RS_FR_PATH ) );
}

/**
 * Hent teksten til info-boksen i ordremailen.
 *
 * Bruger den brugerdefinerede tekst fra plugin-indstillingerne hvis den er
 * udfyldt, ellers falder vi tilbage til den indbyggede/oversatte standardtekst
 * via rs_fr_t(). På den måde kan fx en shop, der også sælger specialfremstillede
 * varer uden fortrydelsesret, tilpasse teksten uden at røre koden.
 *
 * @param string $name Nøglen (heading|intro|link_text|pdf_note).
 * @return string Teksten der skal vises.
 */
function rs_fr_email_note_text( $name ) {
	$map = array(
		'heading'   => 'order_email_heading',
		'intro'     => 'order_email_intro',
		'link_text' => 'order_email_link_text',
		'pdf_note'  => 'order_email_pdf_note',
	);

	if ( isset( $map[ $name ] ) && class_exists( 'RS_FR_Settings' ) ) {
		$settings = RS_FR_Settings::get_settings();
		$custom   = isset( $settings[ $map[ $name ] ] ) ? trim( (string) $settings[ $map[ $name ] ] ) : '';

		if ( '' !== $custom ) {
			return $custom;
		}
	}

	// Fald tilbage til indbygget/oversat standardtekst.
	return rs_fr_t( $name );
}

/* ------------------------------------------------------------------ *
 * 1) Kort info-boks + link + note om vedhæftning i kundens ordremails *
 * ------------------------------------------------------------------ */
add_action( 'woocommerce_email_after_order_table', 'rs_fr_email_note', 20, 4 );
function rs_fr_email_note( $order, $sent_to_admin, $plain_text, $email ) {
	if ( $sent_to_admin || ! in_array( $email->id, RS_FR_MAILS, true ) ) {
		return;
	}

	$url = rs_fr_get_withdrawal_url();

	// Brug den brugerdefinerede intro/pdf-note også i plain-text-versionen hvis
	// den er udfyldt; ellers den indbyggede plain-text-standard.
	$intro_plain    = rs_fr_email_note_text( 'intro' );
	$pdf_note_plain = rs_fr_email_note_text( 'pdf_note' );

	if ( $intro_plain === rs_fr_t( 'intro' ) ) {
		$intro_plain = rs_fr_t( 'intro_plain' );
	}
	if ( $pdf_note_plain === rs_fr_t( 'pdf_note' ) ) {
		$pdf_note_plain = rs_fr_t( 'pdf_note_plain' );
	}

	if ( $plain_text ) {
		echo "\n\n----------------------------------------\n";
		echo esc_html( rs_fr_email_note_text( 'heading' ) ) . "\n";
		echo esc_html( $intro_plain ) . "\n" . $url . "\n";
		echo esc_html( $pdf_note_plain ) . "\n";
		echo "----------------------------------------\n";
		return;
	}
	?>
	<div style="margin-top:30px;padding:15px;border:1px solid #e5e5e5;background:#f9f9f9;border-radius:4px;font-family:Helvetica,Arial,sans-serif;font-size:13px;color:#636363;line-height:150%;">
		<h4 style="margin:0 0 8px;color:#333;"><?php echo esc_html( rs_fr_email_note_text( 'heading' ) ); ?></h4>
		<p style="margin:0 0 8px;"><?php echo nl2br( esc_html( rs_fr_email_note_text( 'intro' ) ) ); ?></p>
		<p style="margin:0 0 8px;"><a href="<?php echo $url; ?>" style="color:#111;text-decoration:underline;font-weight:bold;"><?php echo esc_html( rs_fr_email_note_text( 'link_text' ) ); ?></a></p>
		<p style="margin:0;"><?php echo nl2br( esc_html( rs_fr_email_note_text( 'pdf_note' ) ) ); ?></p>
	</div>
	<?php
}



/* ------------------------------------------------------- *
 * 2) Vedhæft den cachede handelsbetingelses-PDF til mailen *
 * ------------------------------------------------------- *
 * Vedhæfter PDF'en for den oversatte betingelses-side der  *
 * matcher kundens sprog (falder tilbage til originalen).   *
 * ------------------------------------------------------- */
add_filter( 'woocommerce_email_attachments', 'rs_fr_attach_pdf', 10, 3 );
function rs_fr_attach_pdf( $attachments, $email_id, $order ) {
	if ( ! in_array( $email_id, RS_FR_MAILS, true ) ) {
		return $attachments;
	}

	// Find den betingelses-side der passer til det aktuelle sprog.
	$terms_id = rs_fr_current_terms_id();
	if ( ! $terms_id ) {
		return $attachments;
	}

	$pdf = rs_fr_pdf_path( $terms_id );
	if ( ! file_exists( $pdf ) ) {
		rs_fr_generate_pdf( $terms_id ); // Lazy generation hvis filen endnu ikke findes.
	}
	if ( file_exists( $pdf ) ) {
		$attachments[] = $pdf;
	}
	return $attachments;
}

/* --------------------------------------------------------------- *
 * 3) Regenerér PDF når handelsbetingelses-siden gemmes/opdateres   *
 *    => kunden får altid den nyeste version automatisk.            *
 *    Virker både for original-siden og dens oversættelser.         *
 * --------------------------------------------------------------- */

// Standard WordPress-gem (Gutenberg/klassisk redigering).
add_action( 'save_post', 'rs_fr_maybe_regenerate', 20, 1 );
function rs_fr_maybe_regenerate( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( rs_fr_is_terms_page( $post_id ) ) {
		rs_fr_generate_pdf( (int) $post_id );
	}
}

// Elementor-gem (redigering via Elementor-editoren rammer ikke altid save_post rent).
add_action( 'elementor/document/after_save', 'rs_fr_after_elementor_save', 10, 2 );
function rs_fr_after_elementor_save( $document, $data ) {
	if ( method_exists( $document, 'get_main_id' ) ) {
		$post_id = (int) $document->get_main_id();
		if ( rs_fr_is_terms_page( $post_id ) ) {
			rs_fr_generate_pdf( $post_id );
		}
	}
}

/* ----------------- *
 * Hjælpefunktioner   *
 * ----------------- */

/**
 * Sti til den cachede PDF for en given betingelses-side.
 * Filen navngives efter side-ID'et, så hvert sprog/side får sin egen.
 *
 * @param int $terms_id Side-ID. 0 = den i WooCommerce valgte original.
 * @return string Absolut filsti.
 */
function rs_fr_pdf_path( $terms_id = 0 ) {
	$up   = wp_upload_dir();
	$base = trailingslashit( $up['basedir'] ) . 'rs-fortrydelsesret/';

	$terms_id = (int) $terms_id;
	if ( $terms_id ) {
		return $base . 'handelsbetingelser-' . $terms_id . '.pdf';
	}

	// Bagudkompatibel standardsti (original).
	return $base . 'handelsbetingelser.pdf';
}

/**
 * Find betingelses-sidens ID for det aktuelle sprog.
 * Tager WooCommerce-original-siden og slår dens oversættelse op.
 *
 * @return int Side-ID (0 hvis ingen betingelses-side er valgt).
 */
function rs_fr_current_terms_id() {
	$base_id = (int) get_option( 'woocommerce_terms_page_id' );
	if ( ! $base_id ) {
		return 0;
	}
	return rs_fr_translated_post_id( $base_id );
}

/**
 * Afgør om et givet side-ID er WooCommerce-betingelses-siden ELLER en
 * af dens oversættelser (Polylang/WPML).
 *
 * @param int $post_id Side-ID der skal tjekkes.
 * @return bool
 */
function rs_fr_is_terms_page( $post_id ) {
	$post_id = (int) $post_id;
	$base_id = (int) get_option( 'woocommerce_terms_page_id' );
	if ( ! $base_id || ! $post_id ) {
		return false;
	}

	if ( $post_id === $base_id ) {
		return true;
	}

	// Polylang: sammenlign på tværs af alle oversættelser af original-siden.
	if ( function_exists( 'pll_get_post_translations' ) ) {
		$translations = pll_get_post_translations( $base_id );
		if ( is_array( $translations ) && in_array( $post_id, array_map( 'intval', $translations ), true ) ) {
			return true;
		}
	}

	// WPML: slå original-ID'et op ud fra det gemte ID og sammenlign.
	if ( has_filter( 'wpml_object_id' ) ) {
		$original = apply_filters( 'wpml_object_id', $post_id, 'page', true );
		if ( (int) $original === $base_id ) {
			return true;
		}
	}

	return false;
}

/**
 * Hent betingelses-indholdet korrekt uanset byggemetode.
 * Tjekker både om Elementor kører OG om netop denne side er bygget med Elementor.
 */
function rs_fr_get_terms_html( $terms_id ) {
	if (
		did_action( 'elementor/loaded' )
		&& class_exists( '\Elementor\Plugin' )
		&& isset( \Elementor\Plugin::$instance->documents )
	) {
		$document = \Elementor\Plugin::$instance->documents->get( $terms_id );

		if ( $document && $document->is_built_with_elementor() && isset( \Elementor\Plugin::$instance->frontend ) ) {
			// $with_css = false: vi vil have det rene tekstindhold, ikke Elementors layout-CSS.
			$html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $terms_id, false );
			if ( '' !== trim( $html ) ) {
				return $html;
			}
		}
	}

	// Standard (Gutenberg/klassisk): 'the_content' kører do_blocks().
	$page = get_post( $terms_id );
	return $page ? apply_filters( 'the_content', $page->post_content ) : '';
}

/**
 * Byg den rå PDF-binær ud fra en handelsbetingelses-side.
 * Genbruges af både cache-generering (mail-vedhæftning) og test-download.
 *
 * @param int $terms_id Side-ID. 0 = den i WooCommerce valgte original-side.
 * @return string|WP_Error PDF-binær ved succes, ellers WP_Error med årsag.
 */
function rs_fr_build_pdf_output( $terms_id = 0 ) {
	if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
		return new WP_Error( 'rs_fr_no_dompdf', 'Dompdf er ikke installeret (kør "composer install").' );
	}

	$terms_id = (int) $terms_id;
	if ( ! $terms_id ) {
		$terms_id = (int) get_option( 'woocommerce_terms_page_id' );
	}
	if ( ! $terms_id ) {
		return new WP_Error( 'rs_fr_no_terms_page', 'Der er ikke valgt en handelsbetingelses-side i WooCommerce (WooCommerce → Indstillinger → Avanceret → Sidekonfiguration).' );
	}

	$page = get_post( $terms_id );
	if ( ! $page ) {
		return new WP_Error( 'rs_fr_missing_page', 'Den valgte handelsbetingelses-side findes ikke længere.' );
	}

	$title   = get_the_title( $terms_id );
	$shop    = get_bloginfo( 'name' );
	$date    = date_i18n( 'd-m-Y H:i' );
	$content = rs_fr_get_terms_html( $terms_id );

	$html  = '<html><head><meta charset="utf-8"><style>'
		. 'body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#333;line-height:1.5;}'
		. 'h1{font-size:18px;margin:0 0 4px;} small{color:#888;} hr{border:none;border-top:1px solid #ddd;margin:10px 0;}'
		. '</style></head><body>';
	$html .= '<h1>' . esc_html( $title ) . '</h1>';
	$html .= '<small>' . esc_html( $shop ) . ' &middot; Version pr. ' . esc_html( $date ) . '</small><hr>';
	$html .= $content;
	$html .= '</body></html>';

	$dompdf = new \Dompdf\Dompdf( array( 'isRemoteEnabled' => false ) );
	$dompdf->loadHtml( $html, 'UTF-8' );
	$dompdf->setPaper( 'A4', 'portrait' );
	$dompdf->render();

	return $dompdf->output();
}

/**
 * Generér PDF ud fra en handelsbetingelses-side og gem den som cachet fil
 * (den der vedhæftes kundens mails). Filnavnet følger side-ID'et, så hvert
 * sprog/side får sin egen cachede PDF.
 *
 * @param int $terms_id Side-ID. 0 = den i WooCommerce valgte original-side.
 * @return bool
 */
function rs_fr_generate_pdf( $terms_id = 0 ) {
	$terms_id = (int) $terms_id;
	if ( ! $terms_id ) {
		$terms_id = (int) get_option( 'woocommerce_terms_page_id' );
	}

	$output = rs_fr_build_pdf_output( $terms_id );
	if ( is_wp_error( $output ) ) {
		return false;
	}

	$path = rs_fr_pdf_path( $terms_id );
	$dir  = dirname( $path );
	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	return (bool) file_put_contents( $path, $output );
}

/* ------------------------------------------------------------------ *
 * 4) "Test PDF"-link på plugin-siden                                 *
 *    Genererer PDF'en ud fra den valgte side og viser den inline i   *
 *    browseren (ny fane), uden at overskrive den cachede mail-PDF.   *
 * ------------------------------------------------------------------ */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'rs_fr_action_links' );
function rs_fr_action_links( $links ) {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return $links;
	}

	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=rs_fr_test_pdf' ),
		'rs_fr_test_pdf'
	);

	$links[] = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Test PDF</a>';
	return $links;
}

add_action( 'admin_post_rs_fr_test_pdf', 'rs_fr_handle_test_pdf' );
function rs_fr_handle_test_pdf() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Du har ikke rettigheder til at se denne side.', 'Adgang nægtet', array( 'response' => 403 ) );
	}

	check_admin_referer( 'rs_fr_test_pdf' );

	$output = rs_fr_build_pdf_output();
	if ( is_wp_error( $output ) ) {
		wp_die( esc_html( $output->get_error_message() ), 'Kunne ikke generere PDF', array( 'response' => 500 ) );
	}

	// Stream PDF'en inline i browseren.
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="handelsbetingelser-test.pdf"' );
	header( 'Content-Length: ' . strlen( $output ) );
	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rå PDF-binær.
	exit;
}

