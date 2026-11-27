<?php
/**
 * Standardtekster: ungarsk.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'form_intro' => 'Töltse ki az alábbi űrlapot a vásárlástól való elálláshoz. A kérelméről e-mailben visszaigazolást kap.',
    'terms_heading' => 'Digitális elállási funkció',
    'terms_link_text' => 'Elállás a szerződéstől',
    'terms_section_text' => implode(
        "\n\n",
        array(
            'A vásárlástól a webáruházban elérhető digitális elállási funkcióval állhat el.',
            'A funkció a(z) {fortrydelse_link} oldalon érhető el.',
            'Az űrlap kitöltésekor és elküldésekor meg kell adnia a nevét, az e-mail-címét és a rendelésszámát. Választhat, hogy a teljes rendeléstől vagy csak egyes termékektől kíván-e elállni.',
            'Az űrlap elküldése után indokolatlan késedelem nélkül e-mailben visszaigazolást kap. A visszaigazolás megerősíti, hogy megkaptuk az elállási kérelmét, és tartalmazza a beküldött tartalmat, valamint a beküldés dátumát és időpontját.',
            'A visszaigazolás kizárólag az elállási kérelem beérkezését igazolja. Nem jelent végleges döntést az ügyben.',
            'Az elállást határidőben megtettnek tekintjük, ha az elállási határidő lejárta előtt elküldi a digitális elállási funkción keresztül.',
        )
    ),
    'customer_mail_template' => implode(
        "\n",
        array(
            'Megkaptuk az elállási kérelmét.',
            '',
            'Ez a visszaigazolás kizárólag azt igazolja, hogy megkaptuk a kérelmét. Nem jelent végleges döntést az ügyben.',
            '',
            'Hivatkozási szám: {reference}',
            'Dátum és idő: {submitted_at}',
            'Név: {customer_name}',
            'E-mail-cím: {customer_email}',
            'Rendelésszám: {order_number}',
            'Elállás: {request_type}',
            '',
            'A beküldött tartalom:',
            '{submitted_content}',
            '',
            'Üdvözlettel:',
            '{site_name}',
        )
    ),
    'internal_mail_template' => implode(
        "\n",
        array(
            'Új digitális elállási kérelem érkezett.',
            '',
            'Hivatkozási szám: {reference}',
            'Dátum és idő: {submitted_at}',
            'Státusz: {status}',
            'Név: {customer_name}',
            'Megadott e-mail-cím: {customer_email}',
            'A rendelés e-mail-címe: {order_email}',
            'Eltérő e-mail-cím: {email_mismatch}',
            'Rendelésazonosító: {order_id}',
            'Rendelésszám: {order_number}',
            'Rendelés dátuma: {order_date}',
            'Határidő: {deadline_at}',
            'Határidő státusza: {deadline_status}',
            'Elállás: {request_type}',
            '',
            'A beküldött tartalom:',
            '{submitted_content}',
        )
    ),
);
