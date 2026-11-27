<?php
/**
 * Standardtekster: italiensk.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'form_intro' => 'Compili il modulo qui sotto per recedere dal suo acquisto. Riceverà una conferma della sua richiesta via e-mail.',
    'terms_heading' => 'Funzione digitale di recesso',
    'terms_link_text' => 'Recedi dal contratto',
    'terms_section_text' => implode(
        "\n\n",
        array(
            'Può recedere dal suo acquisto utilizzando la nostra funzione digitale di recesso sul negozio online.',
            'La funzione è disponibile nella pagina {fortrydelse_link}.',
            'Quando compila e invia il modulo, deve indicare nome, indirizzo e-mail e numero d\'ordine. Può scegliere se recedere dall\'intero ordine o solo da singoli prodotti.',
            'Dopo l\'invio del modulo, riceverà senza indebito ritardo una conferma via e-mail. La conferma attesta che abbiamo ricevuto la sua richiesta di recesso e riporta il contenuto inviato nonché la data e l\'ora dell\'invio.',
            'La conferma attesta soltanto la ricezione della sua richiesta di recesso. Non costituisce una decisione definitiva sulla pratica.',
            'Il recesso si considera esercitato in tempo se lo invia tramite la funzione digitale di recesso prima della scadenza del termine di recesso.',
        )
    ),
    'customer_mail_template' => implode(
        "\n",
        array(
            'Abbiamo ricevuto la sua richiesta di recesso.',
            '',
            'La presente conferma attesta soltanto che abbiamo ricevuto la sua richiesta. Non costituisce una decisione definitiva sulla pratica.',
            '',
            'Riferimento: {reference}',
            'Data e ora: {submitted_at}',
            'Nome: {customer_name}',
            'Indirizzo e-mail: {customer_email}',
            'Numero d\'ordine: {order_number}',
            'Recesso: {request_type}',
            '',
            'Contenuto del suo invio:',
            '{submitted_content}',
            '',
            'Cordiali saluti',
            '{site_name}',
        )
    ),
    'internal_mail_template' => implode(
        "\n",
        array(
            'È stata ricevuta una nuova richiesta di recesso digitale.',
            '',
            'Riferimento: {reference}',
            'Data e ora: {submitted_at}',
            'Stato: {status}',
            'Nome: {customer_name}',
            'Indirizzo e-mail indicato: {customer_email}',
            'Indirizzo e-mail dell\'ordine: {order_email}',
            'E-mail difforme: {email_mismatch}',
            'ID ordine: {order_id}',
            'Numero d\'ordine: {order_number}',
            'Data dell\'ordine: {order_date}',
            'Termine: {deadline_at}',
            'Stato del termine: {deadline_status}',
            'Recesso: {request_type}',
            '',
            'Contenuto dell\'invio:',
            '{submitted_content}',
        )
    ),
);
