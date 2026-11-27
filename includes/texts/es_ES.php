<?php
/**
 * Standardtekster: spansk.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'form_intro' => 'Rellene el siguiente formulario para desistir de su compra. Recibirá por correo electrónico un acuse de recibo de su solicitud.',
    'terms_heading' => 'Función digital de desistimiento',
    'terms_link_text' => 'Desistir del contrato',
    'terms_section_text' => implode(
        "\n\n",
        array(
            'Puede desistir de su compra utilizando nuestra función digital de desistimiento en la tienda online.',
            'La función está disponible en la página {fortrydelse_link}.',
            'Al rellenar y enviar el formulario, debe indicar su nombre, su dirección de correo electrónico y su número de pedido. Puede elegir si desea desistir de todo el pedido o solo de determinados productos.',
            'Una vez enviado el formulario, recibirá sin demora indebida un acuse de recibo por correo electrónico. El acuse confirma que hemos recibido su solicitud de desistimiento e incluye el contenido enviado, así como la fecha y la hora del envío.',
            'El acuse de recibo solo confirma la recepción de su solicitud de desistimiento. No constituye una decisión definitiva sobre el asunto.',
            'Se considera que ha desistido en plazo si envía su solicitud a través de la función digital de desistimiento antes de que venza el plazo de desistimiento.',
        )
    ),
    'customer_mail_template' => implode(
        "\n",
        array(
            'Hemos recibido su solicitud de desistimiento.',
            '',
            'Este acuse de recibo solo confirma que hemos recibido su solicitud. No constituye una decisión definitiva sobre el asunto.',
            '',
            'Referencia: {reference}',
            'Fecha y hora: {submitted_at}',
            'Nombre: {customer_name}',
            'Correo electrónico: {customer_email}',
            'Número de pedido: {order_number}',
            'Desistimiento: {request_type}',
            '',
            'Contenido de su envío:',
            '{submitted_content}',
            '',
            'Atentamente,',
            '{site_name}',
        )
    ),
    'internal_mail_template' => implode(
        "\n",
        array(
            'Se ha recibido una nueva solicitud digital de desistimiento.',
            '',
            'Referencia: {reference}',
            'Fecha y hora: {submitted_at}',
            'Estado: {status}',
            'Nombre: {customer_name}',
            'Correo electrónico indicado: {customer_email}',
            'Correo electrónico del pedido: {order_email}',
            'Correo electrónico distinto: {email_mismatch}',
            'ID del pedido: {order_id}',
            'Número de pedido: {order_number}',
            'Fecha del pedido: {order_date}',
            'Plazo: {deadline_at}',
            'Estado del plazo: {deadline_status}',
            'Desistimiento: {request_type}',
            '',
            'Contenido del envío:',
            '{submitted_content}',
        )
    ),
);
