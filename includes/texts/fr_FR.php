<?php
/**
 * Standardtekster: fransk.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'form_intro' => 'Remplissez le formulaire ci-dessous pour vous rétracter de votre achat. Vous recevrez un accusé de réception de votre demande par e-mail.',
    'terms_heading' => 'Fonction numérique de rétractation',
    'terms_link_text' => 'Se rétracter du contrat',
    'terms_section_text' => implode(
        "\n\n",
        array(
            'Vous pouvez vous rétracter de votre achat en utilisant notre fonction numérique de rétractation sur la boutique en ligne.',
            'La fonction est disponible sur la page {fortrydelse_link}.',
            'Lorsque vous remplissez et envoyez le formulaire, vous devez indiquer votre nom, votre adresse e-mail et votre numéro de commande. Vous pouvez choisir de vous rétracter de l\'ensemble de la commande ou de certains produits seulement.',
            'Une fois le formulaire envoyé, vous recevez sans retard injustifié un accusé de réception par e-mail. Cet accusé confirme que nous avons bien reçu votre demande de rétractation et reprend le contenu envoyé ainsi que la date et l\'heure de l\'envoi.',
            'L\'accusé de réception confirme uniquement la réception de votre demande de rétractation. Il ne constitue pas une décision définitive sur le dossier.',
            'Votre rétractation est considérée comme exercée dans les délais si vous l\'envoyez via la fonction numérique de rétractation avant l\'expiration du délai de rétractation.',
        )
    ),
    'customer_mail_template' => implode(
        "\n",
        array(
            'Nous avons bien reçu votre demande de rétractation.',
            '',
            'Cet accusé de réception confirme uniquement que nous avons reçu votre demande. Il ne constitue pas une décision définitive sur le dossier.',
            '',
            'Référence : {reference}',
            'Date et heure : {submitted_at}',
            'Nom : {customer_name}',
            'Adresse e-mail : {customer_email}',
            'Numéro de commande : {order_number}',
            'Rétractation : {request_type}',
            '',
            'Contenu de votre envoi :',
            '{submitted_content}',
            '',
            'Cordialement,',
            '{site_name}',
        )
    ),
    'internal_mail_template' => implode(
        "\n",
        array(
            'Une nouvelle demande de rétractation numérique a été reçue.',
            '',
            'Référence : {reference}',
            'Date et heure : {submitted_at}',
            'Statut : {status}',
            'Nom : {customer_name}',
            'Adresse e-mail indiquée : {customer_email}',
            'Adresse e-mail de la commande : {order_email}',
            'E-mail différent : {email_mismatch}',
            'ID de commande : {order_id}',
            'Numéro de commande : {order_number}',
            'Date de commande : {order_date}',
            'Délai : {deadline_at}',
            'Statut du délai : {deadline_status}',
            'Rétractation : {request_type}',
            '',
            'Contenu de l\'envoi :',
            '{submitted_content}',
        )
    ),
);
