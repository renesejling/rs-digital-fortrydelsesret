<?php
/**
 * Standardtekster: polsk.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'form_intro' => 'Wypełnij poniższy formularz, aby odstąpić od zakupu. Potwierdzenie Twojego zgłoszenia otrzymasz e-mailem.',
    'terms_heading' => 'Cyfrowa funkcja odstąpienia od umowy',
    'terms_link_text' => 'Odstąp od umowy',
    'terms_section_text' => implode(
        "\n\n",
        array(
            'Możesz odstąpić od zakupu, korzystając z naszej cyfrowej funkcji odstąpienia od umowy w sklepie internetowym.',
            'Funkcja jest dostępna na stronie {fortrydelse_link}.',
            'Po wypełnieniu i wysłaniu formularza podaj imię i nazwisko, adres e-mail oraz numer zamówienia. Możesz wybrać, czy chcesz odstąpić od całego zamówienia, czy tylko od wybranych produktów.',
            'Po wysłaniu formularza niezwłocznie otrzymasz potwierdzenie e-mailem. Potwierdzenie informuje, że otrzymaliśmy Twoje oświadczenie o odstąpieniu od umowy, i zawiera przesłaną treść oraz datę i godzinę wysłania.',
            'Potwierdzenie stanowi wyłącznie potwierdzenie otrzymania Twojego oświadczenia o odstąpieniu od umowy. Nie jest ostateczną decyzją w sprawie.',
            'Uznaje się, że odstąpiłeś od umowy w terminie, jeśli wyślesz oświadczenie za pomocą cyfrowej funkcji odstąpienia przed upływem terminu na odstąpienie od umowy.',
        )
    ),
    'customer_mail_template' => implode(
        "\n",
        array(
            'Otrzymaliśmy Twoje oświadczenie o odstąpieniu od umowy.',
            '',
            'Niniejsze potwierdzenie informuje wyłącznie, że otrzymaliśmy Twoje zgłoszenie. Nie jest ostateczną decyzją w sprawie.',
            '',
            'Numer referencyjny: {reference}',
            'Data i godzina: {submitted_at}',
            'Imię i nazwisko: {customer_name}',
            'Adres e-mail: {customer_email}',
            'Numer zamówienia: {order_number}',
            'Odstąpienie: {request_type}',
            '',
            'Treść Twojego zgłoszenia:',
            '{submitted_content}',
            '',
            'Z poważaniem',
            '{site_name}',
        )
    ),
    'internal_mail_template' => implode(
        "\n",
        array(
            'Otrzymano nowe cyfrowe oświadczenie o odstąpieniu od umowy.',
            '',
            'Numer referencyjny: {reference}',
            'Data i godzina: {submitted_at}',
            'Status: {status}',
            'Imię i nazwisko: {customer_name}',
            'Podany adres e-mail: {customer_email}',
            'Adres e-mail z zamówienia: {order_email}',
            'Rozbieżny adres e-mail: {email_mismatch}',
            'ID zamówienia: {order_id}',
            'Numer zamówienia: {order_number}',
            'Data zamówienia: {order_date}',
            'Termin: {deadline_at}',
            'Status terminu: {deadline_status}',
            'Odstąpienie: {request_type}',
            '',
            'Treść zgłoszenia:',
            '{submitted_content}',
        )
    ),
);
