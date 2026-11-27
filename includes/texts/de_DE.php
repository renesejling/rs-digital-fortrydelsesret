<?php
/**
 * Standardtekster: tysk.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'form_intro' => 'Füllen Sie das untenstehende Formular aus, um Ihren Kauf zu widerrufen. Sie erhalten per E-Mail eine Bestätigung Ihrer Anfrage.',
    'terms_heading' => 'Digitale Widerrufsfunktion',
    'terms_link_text' => 'Vertrag widerrufen',
    'terms_section_text' => implode(
        "\n\n",
        array(
            'Sie können Ihren Kauf widerrufen, indem Sie unsere digitale Widerrufsfunktion im Webshop nutzen.',
            'Die Funktion finden Sie auf der Seite {fortrydelse_link}.',
            'Wenn Sie das Formular ausfüllen und absenden, geben Sie bitte Ihren Namen, Ihre E-Mail-Adresse und Ihre Bestellnummer an. Sie können wählen, ob Sie die gesamte Bestellung oder nur einzelne Produkte widerrufen möchten.',
            'Nach dem Absenden erhalten Sie unverzüglich eine Bestätigung per E-Mail. Die Bestätigung belegt, dass wir Ihren Widerruf erhalten haben, und enthält den übermittelten Inhalt sowie Datum und Uhrzeit der Übermittlung.',
            'Die Bestätigung belegt lediglich den Eingang Ihres Widerrufs. Sie ist keine endgültige Entscheidung in der Sache.',
            'Ihr Widerruf gilt als rechtzeitig erklärt, wenn Sie ihn vor Ablauf der Widerrufsfrist über die digitale Widerrufsfunktion absenden.',
        )
    ),
    'customer_mail_template' => implode(
        "\n",
        array(
            'Wir haben Ihren Widerruf erhalten.',
            '',
            'Diese Bestätigung belegt lediglich, dass wir Ihre Anfrage erhalten haben. Sie ist keine endgültige Entscheidung in der Sache.',
            '',
            'Referenz: {reference}',
            'Datum und Uhrzeit: {submitted_at}',
            'Name: {customer_name}',
            'E-Mail-Adresse: {customer_email}',
            'Bestellnummer: {order_number}',
            'Widerruf: {request_type}',
            '',
            'Inhalt Ihrer Übermittlung:',
            '{submitted_content}',
            '',
            'Mit freundlichen Grüßen',
            '{site_name}',
        )
    ),
    'internal_mail_template' => implode(
        "\n",
        array(
            'Ein neuer digitaler Widerruf ist eingegangen.',
            '',
            'Referenz: {reference}',
            'Datum und Uhrzeit: {submitted_at}',
            'Status: {status}',
            'Name: {customer_name}',
            'Angegebene E-Mail-Adresse: {customer_email}',
            'E-Mail-Adresse der Bestellung: {order_email}',
            'Abweichende E-Mail: {email_mismatch}',
            'Bestell-ID: {order_id}',
            'Bestellnummer: {order_number}',
            'Bestelldatum: {order_date}',
            'Frist: {deadline_at}',
            'Fristenstatus: {deadline_status}',
            'Widerruf: {request_type}',
            '',
            'Inhalt der Übermittlung:',
            '{submitted_content}',
        )
    ),
);
