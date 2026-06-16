<?php
/**
 * Plugin Name: RS Digital Fortrydelsesret
 * Description: Kort info + link til digital fortrydelse i kundens ordremails, og auto-vedhæftning af de aktuelle handelsbetingelser (valgt i WooCommerce) som PDF på et varigt medie. Henter indhold korrekt fra både Gutenberg/klassisk og Elementor.
 * Version:     1.4.0
 * Author:      ReneSejling.dk
 * Author URI:  https://www.renesejling.dk
 * Update URI:  https://github.com/renesejling/rs-digital-fortrydelsesret
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


// Slug til den digitale fortrydelsesside (pluginnets side).
const RS_FR_PATH = '/fortrydelsesret/';

// Hvilke kundemails skal have note + PDF.
const RS_FR_MAILS = array( 'customer_processing_order', 'customer_completed_order' );

/* ------------------------------------------------------------------ *
 * 1) Kort info-boks + link + note om vedhæftning i kundens ordremails *
 * ------------------------------------------------------------------ */
add_action( 'woocommerce_email_after_order_table', 'rs_fr_email_note', 20, 4 );
function rs_fr_email_note( $order, $sent_to_admin, $plain_text, $email ) {
	if ( $sent_to_admin || ! in_array( $email->id, RS_FR_MAILS, true ) ) {
		return;
	}

	$url = esc_url( home_url( RS_FR_PATH ) );

	if ( $plain_text ) {
		echo "\n\n----------------------------------------\n";
		echo "FORTRYDELSESRET\n";
		echo "Du har 14 dages fortrydelsesret. Fortryd dit kob direkte her:\n" . $url . "\n";
		echo "Dine handelsbetingelser er vedhaeftet denne mail som PDF.\n";
		echo "----------------------------------------\n";
		return;
	}
	?>
	<div style="margin-top:30px;padding:15px;border:1px solid #e5e5e5;background:#f9f9f9;border-radius:4px;font-family:Helvetica,Arial,sans-serif;font-size:13px;color:#636363;line-height:150%;">
		<h4 style="margin:0 0 8px;color:#333;">Fortrydelsesret</h4>
		<p style="margin:0 0 8px;">Du har 14 dages fortrydelsesret. Du kan fortryde dit køb direkte via vores digitale fortrydelsesfunktion:</p>
		<p style="margin:0 0 8px;"><a href="<?php echo $url; ?>" style="color:#111;text-decoration:underline;font-weight:bold;">Gå til fortrydelse</a></p>
		<p style="margin:0;">Dine handelsbetingelser er vedhæftet denne mail som PDF.</p>
	</div>
	<?php
}

/* ------------------------------------------------------- *
 * 2) Vedhæft den cachede handelsbetingelses-PDF til mailen *
 * ------------------------------------------------------- */
add_filter( 'woocommerce_email_attachments', 'rs_fr_attach_pdf', 10, 3 );
function rs_fr_attach_pdf( $attachments, $email_id, $order ) {
	if ( ! in_array( $email_id, RS_FR_MAILS, true ) ) {
		return $attachments;
	}

	$pdf = rs_fr_pdf_path();
	if ( ! file_exists( $pdf ) ) {
		rs_fr_generate_pdf(); // Lazy generation hvis filen endnu ikke findes.
	}
	if ( file_exists( $pdf ) ) {
		$attachments[] = $pdf;
	}
	return $attachments;
}

/* --------------------------------------------------------------- *
 * 3) Regenerér PDF når handelsbetingelses-siden gemmes/opdateres   *
 *    => kunden får altid den nyeste version automatisk.            *
 * --------------------------------------------------------------- */

// Standard WordPress-gem (Gutenberg/klassisk redigering).
add_action( 'save_post', 'rs_fr_maybe_regenerate', 20, 1 );
function rs_fr_maybe_regenerate( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$terms_id = (int) get_option( 'woocommerce_terms_page_id' );
	if ( $terms_id && (int) $post_id === $terms_id ) {
		rs_fr_generate_pdf();
	}
}

// Elementor-gem (redigering via Elementor-editoren rammer ikke altid save_post rent).
add_action( 'elementor/document/after_save', 'rs_fr_after_elementor_save', 10, 2 );
function rs_fr_after_elementor_save( $document, $data ) {
	$terms_id = (int) get_option( 'woocommerce_terms_page_id' );
	if ( $terms_id && method_exists( $document, 'get_main_id' ) && (int) $document->get_main_id() === $terms_id ) {
		rs_fr_generate_pdf();
	}
}

/* ----------------- *
 * Hjælpefunktioner   *
 * ----------------- */
function rs_fr_pdf_path() {
	$up = wp_upload_dir();
	return trailingslashit( $up['basedir'] ) . 'rs-fortrydelsesret/handelsbetingelser.pdf';
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
 * Byg den rå PDF-binær ud fra den i WooCommerce valgte handelsbetingelses-side.
 * Genbruges af både cache-generering (mail-vedhæftning) og test-download.
 *
 * @return string|WP_Error PDF-binær ved succes, ellers WP_Error med årsag.
 */
function rs_fr_build_pdf_output() {
	if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
		return new WP_Error( 'rs_fr_no_dompdf', 'Dompdf er ikke installeret (kør "composer install").' );
	}

	$terms_id = (int) get_option( 'woocommerce_terms_page_id' );
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
 * Generér PDF ud fra den i WooCommerce valgte handelsbetingelses-side
 * og gem den som cachet fil (den der vedhæftes kundens mails).
 */
function rs_fr_generate_pdf() {
	$output = rs_fr_build_pdf_output();
	if ( is_wp_error( $output ) ) {
		return false;
	}

	$dir = dirname( rs_fr_pdf_path() );
	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	return (bool) file_put_contents( rs_fr_pdf_path(), $output );
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

