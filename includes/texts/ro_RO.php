<?php
/**
 * Standardtekster: rumænsk.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'form_intro' => 'Completați formularul de mai jos pentru a vă retrage din achiziție. Veți primi prin e-mail o confirmare a solicitării dumneavoastră.',
    'terms_heading' => 'Funcția digitală de retragere',
    'terms_link_text' => 'Retragere din contract',
    'terms_section_text' => implode(
        "\n\n",
        array(
            'Vă puteți retrage din achiziție folosind funcția noastră digitală de retragere din magazinul online.',
            'Funcția este disponibilă pe pagina {fortrydelse_link}.',
            'Când completați și trimiteți formularul, trebuie să indicați numele, adresa de e-mail și numărul comenzii. Puteți alege dacă doriți să vă retrageți din întreaga comandă sau doar din anumite produse.',
            'După trimiterea formularului, veți primi fără întârzieri nejustificate o confirmare prin e-mail. Confirmarea atestă că am primit solicitarea dumneavoastră de retragere și include conținutul trimis, precum și data și ora trimiterii.',
            'Confirmarea atestă doar primirea solicitării dumneavoastră de retragere. Nu reprezintă o decizie finală în acest caz.',
            'Retragerea este considerată efectuată în termen dacă o trimiteți prin funcția digitală de retragere înainte de expirarea termenului de retragere.',
        )
    ),
    'customer_mail_template' => implode(
        "\n",
        array(
            'Am primit solicitarea dumneavoastră de retragere.',
            '',
            'Această confirmare atestă doar că am primit solicitarea dumneavoastră. Nu reprezintă o decizie finală în acest caz.',
            '',
            'Referință: {reference}',
            'Data și ora: {submitted_at}',
            'Nume: {customer_name}',
            'Adresa de e-mail: {customer_email}',
            'Numărul comenzii: {order_number}',
            'Retragere: {request_type}',
            '',
            'Conținutul trimiterii dumneavoastră:',
            '{submitted_content}',
            '',
            'Cu stimă,',
            '{site_name}',
        )
    ),
    'internal_mail_template' => implode(
        "\n",
        array(
            'A fost primită o nouă solicitare digitală de retragere.',
            '',
            'Referință: {reference}',
            'Data și ora: {submitted_at}',
            'Stare: {status}',
            'Nume: {customer_name}',
            'Adresa de e-mail indicată: {customer_email}',
            'Adresa de e-mail a comenzii: {order_email}',
            'E-mail diferit: {email_mismatch}',
            'ID comandă: {order_id}',
            'Numărul comenzii: {order_number}',
            'Data comenzii: {order_date}',
            'Termen: {deadline_at}',
            'Starea termenului: {deadline_status}',
            'Retragere: {request_type}',
            '',
            'Conținutul trimiterii:',
            '{submitted_content}',
        )
    ),
);
