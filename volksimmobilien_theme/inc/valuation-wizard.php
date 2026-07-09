<?php
/**
 * Valuation wizard: collect submissions and email the configured recipient.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leadwerk option: recipient for wizard submissions.
 *
 * @return string
 */
function volks_get_valuation_wizard_recipient_email() {
	$email = '';
	if ( class_exists( 'Leadwerk_Fields_API' ) ) {
		$email = (string) Leadwerk_Fields_API::get_field( 'valuation_wizard_recipient_email', 'option' );
	} elseif ( function_exists( 'get_field' ) ) {
		$email = (string) get_field( 'valuation_wizard_recipient_email', 'option' );
	}
	if ( '' === $email ) {
		$email = (string) get_option( 'leadwerk_opt_valuation_wizard_recipient_email', '' );
	}

	return sanitize_email( $email );
}

/**
 * Human-readable labels for wizard payload keys.
 *
 * @return array<string,string>
 */
function volks_valuation_wizard_field_labels() {
	return array(
		'type'               => 'Immobilientyp',
		'subtype'            => 'Subtyp',
		'strasse'            => 'Straße und Hausnummer',
		'plz'                => 'Postleitzahl',
		'ort'                => 'Ort',
		'zimmer'             => 'Zimmer',
		'wohneinheiten'      => 'Anzahl Wohneinheiten',
		'baujahr'            => 'Baujahr',
		'flaeche'            => 'Wohnfläche (m²)',
		'grundstueck'        => 'Grundstücksfläche (m²)',
		'mieteinnahmen_jahr' => 'Jährliche Mieteinnahmen (€)',
		'ausstattung'        => 'Ausstattungsstandard',
		'zustand'            => 'Zustand & Modernisierung',
		'anlass'             => 'Anliegen',
		'vorname'            => 'Vorname',
		'nachname'           => 'Nachname',
		'email'              => 'E-Mail',
		'datenschutz'        => 'Datenschutz akzeptiert',
	);
}

/**
 * Display values for select / choice keys.
 *
 * @return array<string,array<string,string>>
 */
function volks_valuation_wizard_value_labels() {
	return array(
		'type' => array(
			'einfamilienhaus'   => 'Einfamilienhaus',
			'wohnung'           => 'Wohnung',
			'mehrfamilienhaus'  => 'Mehrfamilienhaus',
		),
		'subtype' => array(
			'freistehend'      => 'Freistehend',
			'doppelhaushaelfte' => 'Doppelhaushälfte',
			'reihenend'        => 'Reihenendhaus',
			'reihenmittel'     => 'Reihenmittelhaus',
			'etagenwohnung'    => 'Etagenwohnung',
			'dachgeschoss'     => 'Dachgeschosswohnung',
			'maisonette'       => 'Maisonette',
			'penthouse'        => 'Penthouse',
			'studio'           => 'Studio / Apartment',
			'erdgeschoss'      => 'Erdgeschosswohnung',
		),
		'ausstattung' => array(
			'einfach' => 'Einfach / funktional',
			'normal'  => 'Mittlerer Standard',
			'gehoben' => 'Gehoben',
			'luxus'   => 'Sehr hochwertig / Luxus',
		),
		'zustand' => array(
			'sanierungsbeduerftig' => 'Sanierungsbedürftig',
			'gepflegt'             => 'Gepflegt',
			'neuwertig'            => 'Neuwertig / wenig genutzt',
			'kernsaniert'          => 'Kernsaniert / wie neu',
		),
		'anlass' => array(
			'verkauf'    => 'Verkauf vorbereiten / verkaufen',
			'wert'       => 'Immobilienwert kennenlernen',
			'erbe'       => 'Erbe / Nachlass',
			'scheidung'  => 'Trennung / Scheidung',
			'umzug'      => 'Umzug / Neuanschaffung',
			'sonstiges'  => 'Sonstiges',
		),
	);
}

/**
 * Format one wizard value for the email body.
 *
 * @param string $key   Field key.
 * @param mixed  $value Raw value.
 * @return string
 */
function volks_format_valuation_wizard_value( $key, $value ) {
	if ( is_bool( $value ) ) {
		return $value ? 'Ja' : 'Nein';
	}

	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '—';
	}

	$maps = volks_valuation_wizard_value_labels();
	if ( isset( $maps[ $key ][ $value ] ) ) {
		return $maps[ $key ][ $value ];
	}

	return $value;
}

/**
 * Build plain-text email body from sanitized payload.
 *
 * @param array<string,mixed> $payload Sanitized data.
 * @return string
 */
function volks_build_valuation_wizard_email_body( array $payload ) {
	$labels = volks_valuation_wizard_field_labels();
	$lines  = array(
		'Neue Anfrage über den Online-Wertermittlungs-Wizard',
		'',
		'Seite: ' . ( isset( $payload['_page_url'] ) ? $payload['_page_url'] : home_url( '/' ) ),
		'Zeitpunkt: ' . ( isset( $payload['_submitted_at'] ) ? $payload['_submitted_at'] : wp_date( 'Y-m-d H:i:s' ) ),
		'',
	);

	unset( $payload['_page_url'], $payload['_submitted_at'], $payload['_honeypot'] );

	foreach ( $labels as $key => $label ) {
		if ( ! array_key_exists( $key, $payload ) ) {
			continue;
		}
		$lines[] = $label . ': ' . volks_format_valuation_wizard_value( $key, $payload[ $key ] );
	}

	/* Zusätzliche Detail-Felder (EFH/WHG/MFH). */
	$known = array_keys( $labels );
	foreach ( $payload as $key => $value ) {
		if ( in_array( $key, $known, true ) || '' === trim( (string) $value ) ) {
			continue;
		}
		$lines[] = ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . volks_format_valuation_wizard_value( $key, $value );
	}

	$lines[] = '';
	$lines[] = '---';
	$lines[] = 'volksimmobilien – automatische Wizard-Benachrichtigung';

	return implode( "\n", $lines );
}

/**
 * Sanitize incoming wizard payload from the browser.
 *
 * @param array<string,mixed> $raw Raw POST JSON.
 * @return array<string,mixed>
 */
function volks_sanitize_valuation_wizard_payload( array $raw ) {
	$allowed = array_keys( volks_valuation_wizard_field_labels() );
	$out     = array();

	foreach ( $allowed as $key ) {
		if ( ! array_key_exists( $key, $raw ) ) {
			continue;
		}
		$value = $raw[ $key ];
		if ( 'email' === $key ) {
			$out[ $key ] = sanitize_email( (string) $value );
		} elseif ( 'datenschutz' === $key ) {
			$out[ $key ] = ! empty( $value );
		} elseif ( in_array( $key, array( 'zimmer', 'wohneinheiten', 'baujahr', 'flaeche', 'grundstueck', 'mieteinnahmen_jahr' ), true ) ) {
			$out[ $key ] = sanitize_text_field( (string) $value );
		} else {
			$out[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	/* Detail-Felder (data-detail-name). */
	foreach ( $raw as $key => $value ) {
		if ( isset( $out[ $key ] ) || ! is_string( $key ) || ! preg_match( '/^[a-z0-9_-]+$/i', $key ) ) {
			continue;
		}
		$out[ $key ] = sanitize_text_field( (string) $value );
	}

	$out['_page_url']      = esc_url_raw( isset( $raw['_page_url'] ) ? (string) $raw['_page_url'] : '' );
	$out['_submitted_at']  = sanitize_text_field( isset( $raw['_submitted_at'] ) ? (string) $raw['_submitted_at'] : '' );
	$out['_honeypot']      = sanitize_text_field( isset( $raw['_honeypot'] ) ? (string) $raw['_honeypot'] : '' );

	return $out;
}

/**
 * AJAX: send valuation wizard email.
 */
function volks_ajax_submit_valuation_wizard() {
	check_ajax_referer( 'volks_valuation_wizard', 'nonce' );

	if ( ! empty( $_POST['company_website'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Anfrage konnte nicht gesendet werden.', 'volksimmobilien' ) ), 400 );
	}

	$recipient = volks_get_valuation_wizard_recipient_email();
	if ( '' === $recipient || ! is_email( $recipient ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Empfänger-E-Mail ist in den Leadwerk-Optionen nicht konfiguriert.', 'volksimmobilien' ),
			),
			503
		);
	}

	$raw_json = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
	$raw      = json_decode( (string) $raw_json, true );
	if ( ! is_array( $raw ) ) {
		wp_send_json_error( array( 'message' => __( 'Ungültige Formulardaten.', 'volksimmobilien' ) ), 400 );
	}

	$payload = volks_sanitize_valuation_wizard_payload( $raw );

	if ( empty( $payload['type'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Bitte wähle einen Immobilientyp.', 'volksimmobilien' ) ), 400 );
	}

	if ( empty( $payload['vorname'] ) || empty( $payload['nachname'] ) || empty( $payload['email'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Bitte Kontaktdaten vollständig ausfüllen.', 'volksimmobilien' ) ), 400 );
	}

	if ( ! is_email( $payload['email'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Bitte eine gültige E-Mail-Adresse angeben.', 'volksimmobilien' ) ), 400 );
	}

	if ( empty( $payload['datenschutz'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Bitte die Datenschutzerklärung bestätigen.', 'volksimmobilien' ) ), 400 );
	}

	$user_email = $payload['email'];
	$name       = trim( $payload['vorname'] . ' ' . $payload['nachname'] );
	$subject    = sprintf(
		/* translators: %s: submitter name */
		__( 'Wertermittlung: Neue Anfrage von %s', 'volksimmobilien' ),
		$name
	);

	$body = volks_build_valuation_wizard_email_body( $payload );

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( is_email( $user_email ) ) {
		$headers[] = 'Reply-To: ' . $name . ' <' . $user_email . '>';
	}

	$sent = wp_mail( $recipient, $subject, $body, $headers );

	if ( ! $sent ) {
		wp_send_json_error( array( 'message' => __( 'E-Mail konnte nicht versendet werden. Bitte später erneut versuchen.', 'volksimmobilien' ) ), 500 );
	}

	wp_send_json_success( array( 'message' => __( 'Anfrage wurde gesendet.', 'volksimmobilien' ) ) );
}

add_action( 'wp_ajax_volks_submit_valuation_wizard', 'volks_ajax_submit_valuation_wizard' );
add_action( 'wp_ajax_nopriv_volks_submit_valuation_wizard', 'volks_ajax_submit_valuation_wizard' );

/**
 * Localize wizard AJAX config when the wizard markup is on the page.
 */
function volks_localize_valuation_wizard_script() {
	if ( ! wp_script_is( 'volks-main', 'enqueued' ) ) {
		return;
	}

	wp_localize_script(
		'volks-main',
		'volksWizard',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'volks_valuation_wizard' ),
			'sendingLabel' => __( 'Wird gesendet …', 'volksimmobilien' ),
			'submitLabel'  => __( 'Absenden', 'volksimmobilien' ),
			'errorMessage' => __( 'Die Anfrage konnte nicht gesendet werden. Bitte versuche es erneut oder kontaktiere uns telefonisch.', 'volksimmobilien' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'volks_localize_valuation_wizard_script', 20 );
