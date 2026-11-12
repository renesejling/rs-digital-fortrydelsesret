<?php
/**
 * Sprogvalg (dansk/engelsk) og standardtekster pr. sprog.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Håndterer det valgte pluginsprog og de indbyggede standardtekster.
 */
final class RS_FR_I18n
{
    const DOMAIN = 'rs-digital-fortrydelsesret';
    const DEFAULT_LOCALE = 'da_DK';

    /**
     * Tilgængelige sprog (vises på eget sprog, så de kan genkendes uanset valg).
     *
     * @return array<string,string>
     */
    public static function locales()
    {
        return array(
            'da_DK' => 'Dansk',
            'en_US' => 'English',
        );
    }

    /**
     * Det valgte sprog fra indstillingerne. Læser optionen direkte for at undgå
     * rekursion via RS_FR_Settings::get_settings().
     *
     * @return string
     */
    public static function current_locale()
    {
        $settings = get_option('digital_fortrydelse_settings', array());
        $locale = is_array($settings) && isset($settings['locale']) ? (string) $settings['locale'] : self::DEFAULT_LOCALE;

        return self::sanitize_locale($locale);
    }

    /**
     * Whitelist af sprogkode.
     *
     * @param string $locale Sprogkode.
     * @return string
     */
    public static function sanitize_locale($locale)
    {
        $locales = self::locales();

        return isset($locales[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    /**
     * Indlæs pluginnets oversættelsesfil for det valgte sprog.
     * Dansk er originalsproget i koden, så der indlæses ingen fil.
     *
     * @return void
     */
    public static function load_textdomain()
    {
        unload_textdomain(self::DOMAIN);

        $locale = self::current_locale();

        if (self::DEFAULT_LOCALE === $locale) {
            return;
        }

        load_textdomain(self::DOMAIN, RS_FR_PLUGIN_PATH . 'languages/' . self::DOMAIN . '-' . $locale . '.mo');
    }

    /**
     * Hent en indbygget standardtekst på et bestemt sprog.
     *
     * @param string      $key    Tekstnøgle.
     * @param string|null $locale Sprogkode (null = det valgte sprog).
     * @return string
     */
    public static function default_text($key, $locale = null)
    {
        $locale = null === $locale ? self::current_locale() : self::sanitize_locale($locale);
        $texts = self::texts();

        if (isset($texts[$locale][$key])) {
            return $texts[$locale][$key];
        }

        return isset($texts[self::DEFAULT_LOCALE][$key]) ? $texts[self::DEFAULT_LOCALE][$key] : '';
    }

    /**
     * Skift gemte standardtekster til det angivne sprog. Kun tekster, der er
     * identiske med en indbygget standardtekst, skiftes; egne tilpasninger røres ikke.
     *
     * @param array  $settings Indstillinger.
     * @param string $locale   Målsprog.
     * @return array
     */
    public static function localize_defaults(array $settings, $locale)
    {
        $locale = self::sanitize_locale($locale);

        foreach (array('form_intro', 'terms_section_text') as $key) {
            if (!isset($settings[$key])) {
                continue;
            }

            $current = self::normalize($settings[$key]);

            if ('' === $current) {
                continue;
            }

            foreach (array_keys(self::locales()) as $candidate) {
                if (self::normalize(self::default_text($key, $candidate)) === $current) {
                    $settings[$key] = self::default_text($key, $locale);
                    break;
                }
            }
        }

        return $settings;
    }

    /**
     * Normalisér tekst før sammenligning.
     *
     * @param mixed $text Tekst.
     * @return string
     */
    private static function normalize($text)
    {
        return trim(str_replace("\r\n", "\n", (string) $text));
    }

    /**
     * Alle indbyggede standardtekster pr. sprog.
     *
     * @return array<string,array<string,string>>
     */
    private static function texts()
    {
        return array(
            'da_DK' => array(
                'form_intro' => 'Udfyld formularen herunder for at fortryde dit køb. Du modtager en kvittering for din anmodning pr. e-mail.',
                'terms_heading' => 'Digital fortrydelsesfunktion',
                'terms_link_text' => 'Fortryd aftale',
                'terms_section_text' => implode(
                    "\n\n",
                    array(
                        'Du kan fortryde dit køb ved at bruge vores digitale fortrydelsesfunktion på webshoppen.',
                        'Funktionen findes på siden {fortrydelse_link}.',
                        'Når du udfylder og sender formularen, skal du oplyse navn, e-mailadresse og ordrenummer. Du kan vælge, om du ønsker at fortryde hele ordren eller enkelte produkter.',
                        'Når formularen er sendt, modtager du uden unødig forsinkelse en kvittering pr. e-mail. Kvitteringen bekræfter, at vi har modtaget din anmodning om fortrydelse, og indeholder det indsendte indhold samt dato og tidspunkt for indsendelsen.',
                        'Kvitteringen er alene en bekræftelse på modtagelse af din anmodning om fortrydelse. Den er ikke en endelig afgørelse af sagen.',
                        'Du anses for at have fortrudt rettidigt, hvis du sender fortrydelsen via den digitale fortrydelsesfunktion inden fortrydelsesfristen udløber.',
                    )
                ),
                'customer_mail_template' => implode(
                    "\n",
                    array(
                        'Vi har modtaget din anmodning om fortrydelse.',
                        '',
                        'Denne kvittering bekræfter kun, at vi har modtaget din anmodning. Den er ikke en endelig afgørelse af sagen.',
                        '',
                        'Reference: {reference}',
                        'Dato og tidspunkt: {submitted_at}',
                        'Navn: {customer_name}',
                        'Mailadresse: {customer_email}',
                        'Ordrenummer: {order_number}',
                        'Fortrydelse: {request_type}',
                        '',
                        'Indhold af din indsendelse:',
                        '{submitted_content}',
                        '',
                        'Venlig hilsen',
                        '{site_name}',
                    )
                ),
                'internal_mail_template' => implode(
                    "\n",
                    array(
                        'Der er modtaget en ny digital fortrydelse.',
                        '',
                        'Reference: {reference}',
                        'Dato og tidspunkt: {submitted_at}',
                        'Status: {status}',
                        'Navn: {customer_name}',
                        'Indsendt mailadresse: {customer_email}',
                        'Ordre-mailadresse: {order_email}',
                        'E-mailafvigelse: {email_mismatch}',
                        'Ordre-ID: {order_id}',
                        'Ordrenummer: {order_number}',
                        'Ordredato: {order_date}',
                        'Frist: {deadline_at}',
                        'Friststatus: {deadline_status}',
                        'Fortrydelse: {request_type}',
                        '',
                        'Indhold af indsendelsen:',
                        '{submitted_content}',
                    )
                ),
            ),
            'en_US' => array(
                'form_intro' => 'Fill in the form below to withdraw from your purchase. You will receive a receipt for your request by email.',
                'terms_heading' => 'Digital withdrawal function',
                'terms_link_text' => 'Withdraw from contract',
                'terms_section_text' => implode(
                    "\n\n",
                    array(
                        'You can withdraw from your purchase by using our digital withdrawal function on the webshop.',
                        'The function is available on the page {fortrydelse_link}.',
                        'When you fill in and submit the form, you must provide your name, email address and order number. You can choose whether you wish to withdraw from the entire order or individual products.',
                        'Once the form has been submitted, you will receive a receipt by email without undue delay. The receipt confirms that we have received your withdrawal request and contains the submitted content as well as the date and time of submission.',
                        'The receipt is solely a confirmation of receipt of your withdrawal request. It is not a final decision on the case.',
                        'Your withdrawal is considered timely if you submit it via the digital withdrawal function before the withdrawal period expires.',
                    )
                ),
                'customer_mail_template' => implode(
                    "\n",
                    array(
                        'We have received your withdrawal request.',
                        '',
                        'This receipt only confirms that we have received your request. It is not a final decision on the case.',
                        '',
                        'Reference: {reference}',
                        'Date and time: {submitted_at}',
                        'Name: {customer_name}',
                        'Email address: {customer_email}',
                        'Order number: {order_number}',
                        'Withdrawal: {request_type}',
                        '',
                        'Content of your submission:',
                        '{submitted_content}',
                        '',
                        'Kind regards',
                        '{site_name}',
                    )
                ),
                'internal_mail_template' => implode(
                    "\n",
                    array(
                        'A new digital withdrawal has been received.',
                        '',
                        'Reference: {reference}',
                        'Date and time: {submitted_at}',
                        'Status: {status}',
                        'Name: {customer_name}',
                        'Submitted email address: {customer_email}',
                        'Order email address: {order_email}',
                        'Email mismatch: {email_mismatch}',
                        'Order ID: {order_id}',
                        'Order number: {order_number}',
                        'Order date: {order_date}',
                        'Deadline: {deadline_at}',
                        'Deadline status: {deadline_status}',
                        'Withdrawal: {request_type}',
                        '',
                        'Content of the submission:',
                        '{submitted_content}',
                    )
                ),
            ),
        );
    }
}
