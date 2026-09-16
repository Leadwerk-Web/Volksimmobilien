<?php
/**
 * Valuation wizard: submit through WPForms (entries + SMTP notification).
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VOLKS_VALUATION_WPFORMS_PURPOSE', 'valuation' );
define( 'VOLKS_VALUATION_WPFORMS_FORM_VERSION', '3' );
define( 'VOLKS_VALUATION_WPFORMS_SENDER_EMAIL', 'webseite@volksimmobilien.eu' );
define( 'VOLKS_VALUATION_WPFORMS_DEFAULT_RECIPIENT', 'anfragen@volksimmobilien.eu' );
define( 'VOLKS_VALUATION_WPFORMS_POST_META', '_volks_wpforms_purpose' );

/**
 * Default recipient for wizard submissions.
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

	$email = sanitize_email( $email );
	if ( '' === $email || ! is_email( $email ) ) {
		return VOLKS_VALUATION_WPFORMS_DEFAULT_RECIPIENT;
	}

	return $email;
}

/**
 * Stored WPForms form ID for the valuation wizard.
 *
 * @return int
 */
function volks_get_valuation_wpforms_form_id() {
	$form_id = 0;
	if ( class_exists( 'Leadwerk_Fields_API' ) ) {
		$form_id = absint( Leadwerk_Fields_API::get_field( 'wpforms_form_id_valuation', 'option' ) );
	}
	if ( $form_id <= 0 ) {
		$form_id = absint( get_option( 'leadwerk_opt_wpforms_form_id_valuation', 0 ) );
	}

	return $form_id;
}

/**
 * Persist the valuation WPForms form ID in Leadwerk options.
 *
 * @param int $form_id Form ID.
 * @return void
 */
function volks_set_valuation_wpforms_form_id( $form_id ) {
	$form_id = absint( $form_id );
	if ( $form_id <= 0 ) {
		return;
	}

	if ( class_exists( 'Leadwerk_Fields_API' ) ) {
		Leadwerk_Fields_API::update_field( 'wpforms_form_id_valuation', (string) $form_id, 'option' );
	}
	update_option( 'leadwerk_opt_wpforms_form_id_valuation', (string) $form_id, false );
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
		'telefon'            => 'Telefon',
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
		'type'        => array(
			'einfamilienhaus'  => 'Einfamilienhaus',
			'wohnung'          => 'Wohnung',
			'mehrfamilienhaus' => 'Mehrfamilienhaus',
			'grundstueck'      => 'Grundstück',
		),
		'subtype'     => array(
			'freistehend'       => 'Freistehend',
			'doppelhaushaelfte' => 'Doppelhaushälfte',
			'reihenend'         => 'Reihenendhaus',
			'reihenmittel'      => 'Reihenmittelhaus',
			'etagenwohnung'     => 'Etagenwohnung',
			'dachgeschoss'      => 'Dachgeschosswohnung',
			'maisonette'        => 'Maisonette',
			'penthouse'         => 'Penthouse',
			'studio'            => 'Studio / Apartment',
			'erdgeschoss'       => 'Erdgeschosswohnung',
		),
		'ausstattung' => array(
			'einfach' => 'Einfach / funktional',
			'normal'  => 'Mittlerer Standard',
			'gehoben' => 'Gehoben',
			'luxus'   => 'Sehr hochwertig / Luxus',
		),
		'zustand'     => array(
			'sanierungsbeduerftig' => 'Sanierungsbedürftig',
			'gepflegt'             => 'Gepflegt',
			'neuwertig'            => 'Neuwertig / wenig genutzt',
			'kernsaniert'          => 'Kernsaniert / wie neu',
		),
		'anlass'      => array(
			'verkauf'   => 'Verkauf vorbereiten / verkaufen',
			'wert'      => 'Immobilienwert kennenlernen',
			'erbe'      => 'Erbe / Nachlass',
			'scheidung' => 'Trennung / Scheidung',
			'umzug'     => 'Umzug / Neuanschaffung',
			'sonstiges' => 'Sonstiges',
		),
	);
}

/**
 * Format one wizard value for fallback email body.
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
 * Build plain-text email body from sanitized payload (SMTP fallback).
 *
 * @param array<string,mixed> $payload Sanitized data.
 * @return string
 */
function volks_build_valuation_wizard_email_body( array $payload ) {
	$labels = volks_valuation_wizard_field_labels();
	$lines  = array(
		'Neue Anfrage über den Online-Wertermittlungsrechner',
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

	$known = array_keys( $labels );
	foreach ( $payload as $key => $value ) {
		if ( in_array( $key, $known, true ) || '' === trim( (string) $value ) ) {
			continue;
		}
		$lines[] = ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . volks_format_valuation_wizard_value( $key, $value );
	}

	$lines[] = '';
	$lines[] = '---';
	$lines[] = 'volksimmobilien – Wertermittlungsrechner';

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
		} else {
			$out[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	foreach ( $raw as $key => $value ) {
		if ( isset( $out[ $key ] ) || ! is_string( $key ) || ! preg_match( '/^[a-z0-9_-]+$/i', $key ) ) {
			continue;
		}
		$out[ $key ] = sanitize_text_field( (string) $value );
	}

	$out['_page_url']     = esc_url_raw( isset( $raw['_page_url'] ) ? (string) $raw['_page_url'] : '' );
	$out['_submitted_at'] = sanitize_text_field( isset( $raw['_submitted_at'] ) ? (string) $raw['_submitted_at'] : '' );
	$out['_honeypot']     = sanitize_text_field( isset( $raw['_honeypot'] ) ? (string) $raw['_honeypot'] : '' );

	return $out;
}

/**
 * Build WPForms choice rows from value => label map.
 *
 * @param array<string,string> $map Value map.
 * @return array<int,array<string,string>>
 */
function volks_wpforms_choices_from_map( array $map ) {
	$choices = array();
	$index   = 1;
	foreach ( $map as $value => $label ) {
		$choices[ $index ] = array(
			'label'   => $label,
			'value'   => (string) $value,
			'default' => '',
		);
		++$index;
	}

	return $choices;
}

/**
 * Canonical WPForms field IDs for the valuation form.
 *
 * @return array<string,int>
 */
function volks_valuation_wpforms_field_ids() {
	return array(
		'name'               => 1,
		'email'              => 2,
		'telefon'            => 3,
		'type'               => 4,
		'subtype'            => 5,
		'strasse'            => 6,
		'plz'                => 7,
		'ort'                => 8,
		'zimmer'             => 9,
		'wohneinheiten'      => 10,
		'baujahr'            => 11,
		'flaeche'            => 12,
		'grundstueck'        => 13,
		'mieteinnahmen_jahr' => 14,
		'ausstattung'        => 15,
		'zustand'            => 16,
		'anlass'             => 17,
		'datenschutz'        => 18,
		'seite'              => 19,
	);
}

/**
 * Canonical form definition for the Wertermittlungsrechner WPForms form.
 *
 * @param int $form_id Existing form ID or 0.
 * @return array<string,mixed>
 */
function volks_valuation_wpforms_form_data( $form_id = 0 ) {
	$labels     = volks_valuation_wizard_field_labels();
	$value_maps = volks_valuation_wizard_value_labels();
	$ids        = volks_valuation_wpforms_field_ids();
	$recipient  = volks_get_valuation_wizard_recipient_email();
	$title      = 'Wertermittlungsrechner';

	$fields = array(
		(string) $ids['name']               => array(
			'id'                => (string) $ids['name'],
			'type'              => 'name',
			'label'             => 'Name',
			'format'            => 'first-last',
			'required'          => '1',
			'size'              => 'medium',
			'first_placeholder' => 'Vorname',
			'last_placeholder'  => 'Nachname',
		),
		(string) $ids['email']              => array(
			'id'          => (string) $ids['email'],
			'type'        => 'email',
			'label'       => $labels['email'],
			'required'    => '1',
			'size'        => 'medium',
			'placeholder' => 'deine@email.de',
		),
		(string) $ids['telefon']            => array(
			'id'          => (string) $ids['telefon'],
			'type'        => 'text',
			'label'       => $labels['telefon'],
			'required'    => '0',
			'size'        => 'medium',
			'placeholder' => 'z. B. 0170 …',
		),
		(string) $ids['type']               => array(
			'id'          => (string) $ids['type'],
			'type'        => 'select',
			'label'       => $labels['type'],
			'required'    => '1',
			'size'        => 'medium',
			'show_values' => '1',
			'choices'     => volks_wpforms_choices_from_map( $value_maps['type'] ),
		),
		(string) $ids['subtype']            => array(
			'id'          => (string) $ids['subtype'],
			'type'        => 'select',
			'label'       => $labels['subtype'],
			'required'    => '0',
			'size'        => 'medium',
			'show_values' => '1',
			'choices'     => volks_wpforms_choices_from_map( $value_maps['subtype'] ),
		),
		(string) $ids['strasse']            => array(
			'id'       => (string) $ids['strasse'],
			'type'     => 'text',
			'label'    => $labels['strasse'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['plz']                => array(
			'id'       => (string) $ids['plz'],
			'type'     => 'text',
			'label'    => $labels['plz'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['ort']                => array(
			'id'       => (string) $ids['ort'],
			'type'     => 'text',
			'label'    => $labels['ort'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['zimmer']             => array(
			'id'       => (string) $ids['zimmer'],
			'type'     => 'text',
			'label'    => $labels['zimmer'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['wohneinheiten']      => array(
			'id'       => (string) $ids['wohneinheiten'],
			'type'     => 'text',
			'label'    => $labels['wohneinheiten'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['baujahr']            => array(
			'id'       => (string) $ids['baujahr'],
			'type'     => 'text',
			'label'    => $labels['baujahr'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['flaeche']            => array(
			'id'       => (string) $ids['flaeche'],
			'type'     => 'text',
			'label'    => $labels['flaeche'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['grundstueck']        => array(
			'id'       => (string) $ids['grundstueck'],
			'type'     => 'text',
			'label'    => $labels['grundstueck'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['mieteinnahmen_jahr'] => array(
			'id'       => (string) $ids['mieteinnahmen_jahr'],
			'type'     => 'text',
			'label'    => $labels['mieteinnahmen_jahr'],
			'required' => '0',
			'size'     => 'medium',
		),
		(string) $ids['ausstattung']        => array(
			'id'          => (string) $ids['ausstattung'],
			'type'        => 'select',
			'label'       => $labels['ausstattung'],
			'required'    => '0',
			'size'        => 'medium',
			'show_values' => '1',
			'choices'     => volks_wpforms_choices_from_map( $value_maps['ausstattung'] ),
		),
		(string) $ids['zustand']            => array(
			'id'          => (string) $ids['zustand'],
			'type'        => 'select',
			'label'       => $labels['zustand'],
			'required'    => '0',
			'size'        => 'medium',
			'show_values' => '1',
			'choices'     => volks_wpforms_choices_from_map( $value_maps['zustand'] ),
		),
		(string) $ids['anlass']             => array(
			'id'          => (string) $ids['anlass'],
			'type'        => 'select',
			'label'       => $labels['anlass'],
			'required'    => '0',
			'size'        => 'medium',
			'show_values' => '1',
			'choices'     => volks_wpforms_choices_from_map( $value_maps['anlass'] ),
		),
		(string) $ids['datenschutz']        => array(
			'id'       => (string) $ids['datenschutz'],
			'type'     => 'gdpr-checkbox',
			'label'    => 'Datenschutz',
			'required' => '1',
			'choices'  => array(
				1 => array(
					'label'   => 'Ich habe die Datenschutzhinweise gelesen und stimme der Verarbeitung meiner Angaben zu.',
					'value'   => 'Ja',
					'image'   => '',
					'default' => '',
				),
			),
		),
		(string) $ids['seite']              => array(
			'id'       => (string) $ids['seite'],
			'type'     => 'text',
			'label'    => 'Seite',
			'required' => '0',
			'size'     => 'medium',
		),
	);

	return array(
		'id'       => (string) absint( $form_id ),
		'field_id' => '20',
		'fields'   => $fields,
		'settings' => array(
			'form_title'                 => $title,
			'form_desc'                  => 'Wird vom Online-Wertermittlungsrechner befüllt. Einträge unter WPForms → Einträge speichern.',
			'submit_text'                => 'Absenden',
			'submit_text_processing'     => 'Wird gesendet …',
			'notification_enable'        => '1',
			'notifications'              => array(
				'1' => array(
					'notification_name' => 'Wertermittlungsrechner',
					'email'             => $recipient,
					'subject'           => 'Wertermittlungsrechner: Neue Anfrage ({field_id="' . $ids['type'] . '"}) von {field_id="' . $ids['name'] . '"}',
					'sender_name'       => 'volksimmobilien Website',
					'sender_address'    => VOLKS_VALUATION_WPFORMS_SENDER_EMAIL,
					'replyto'           => '{field_id="' . $ids['email'] . '"}',
					'message'           => "Neue Anfrage über den Online-Wertermittlungsrechner.\n\n{all_fields}",
				),
			),
			'confirmations'              => array(
				'1' => array(
					'type'           => 'message',
					'message'        => 'Vielen Dank! Deine Anfrage über den Wertermittlungsrechner ist eingegangen.',
					'message_scroll' => '1',
				),
			),
			'anti_spam'                  => array(
				'time_limit'     => array(
					'enable'   => '0',
					'duration' => '2',
				),
				'country_filter' => array(
					'enable' => '0',
				),
				'keyword_filter' => array(
					'enable' => '0',
				),
			),
			'antispam_v3'                => '0',
			'store_spam_entries'         => '0',
		),
		'meta'     => array(
			'template'            => '',
			'volks_purpose'       => VOLKS_VALUATION_WPFORMS_PURPOSE,
			'volks_form_version'  => VOLKS_VALUATION_WPFORMS_FORM_VERSION,
		),
	);
}

/**
 * Whether WPForms is available for form CRUD.
 *
 * @return bool
 */
function volks_wpforms_is_ready() {
	return function_exists( 'wpforms' ) && function_exists( 'wpforms_encode' ) && function_exists( 'wpforms_decode' );
}

/**
 * Find an existing valuation WPForms form by stored ID or post meta.
 *
 * @return int
 */
function volks_find_valuation_wpforms_form_id() {
	$form_id = volks_get_valuation_wpforms_form_id();
	if ( $form_id > 0 ) {
		$post = get_post( $form_id );
		if ( $post && 'wpforms' === $post->post_type && 'publish' === $post->post_status ) {
			return $form_id;
		}
	}

	$found = get_posts(
		array(
			'post_type'      => 'wpforms',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => VOLKS_VALUATION_WPFORMS_POST_META,
			'meta_value'     => VOLKS_VALUATION_WPFORMS_PURPOSE,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	if ( ! empty( $found ) ) {
		return absint( $found[0] );
	}

	return 0;
}

/**
 * Create or refresh the valuation WPForms form.
 *
 * @return int Form ID or 0.
 */
function volks_ensure_valuation_wpforms_form() {
	if ( ! volks_wpforms_is_ready() ) {
		return 0;
	}

	$can_manage = is_admin()
		|| ( defined( 'WP_CLI' ) && WP_CLI )
		|| current_user_can( 'manage_options' );
	if ( ! $can_manage ) {
		return volks_find_valuation_wpforms_form_id();
	}

	$recipient = volks_get_valuation_wizard_recipient_email();
	if ( 'info@volksimmobilien.eu' === $recipient ) {
		if ( class_exists( 'Leadwerk_Fields_API' ) ) {
			Leadwerk_Fields_API::update_field( 'valuation_wizard_recipient_email', VOLKS_VALUATION_WPFORMS_DEFAULT_RECIPIENT, 'option' );
		} else {
			update_option( 'leadwerk_opt_valuation_wizard_recipient_email', VOLKS_VALUATION_WPFORMS_DEFAULT_RECIPIENT, false );
		}
	}

	$form_id = volks_find_valuation_wpforms_form_id();

	$from_wizard_ajax = wp_doing_ajax()
		&& isset( $_REQUEST['action'] )
		&& 'volks_submit_valuation' === $_REQUEST['action'];
	$existing_version = $form_id ? (string) get_post_meta( $form_id, '_volks_wpforms_form_version', true ) : '';
	if ( $form_id > 0 && ( $from_wizard_ajax || $existing_version === VOLKS_VALUATION_WPFORMS_FORM_VERSION ) ) {
		volks_set_valuation_wpforms_form_id( $form_id );
		return $form_id;
	}

	$form_data = volks_valuation_wpforms_form_data( $form_id );

	if ( $form_id <= 0 ) {
		$form_id = wp_insert_post(
			array(
				'post_title'   => $form_data['settings']['form_title'],
				'post_excerpt' => $form_data['settings']['form_desc'],
				'post_status'  => 'publish',
				'post_type'    => 'wpforms',
			),
			true
		);
		if ( is_wp_error( $form_id ) || absint( $form_id ) <= 0 ) {
			return 0;
		}
		$form_id           = absint( $form_id );
		$form_data['id']   = (string) $form_id;
	}

	$handler = wpforms()->obj( 'form' );
	if ( ! $handler ) {
		return 0;
	}

	$updated = $handler->update( $form_id, $form_data, array( 'cap' => false, 'skip_revision' => true ) );
	if ( ! $updated ) {
		return 0;
	}

	update_post_meta( $form_id, VOLKS_VALUATION_WPFORMS_POST_META, VOLKS_VALUATION_WPFORMS_PURPOSE );
	update_post_meta( $form_id, '_volks_wpforms_form_version', VOLKS_VALUATION_WPFORMS_FORM_VERSION );
	volks_set_valuation_wpforms_form_id( $form_id );

	return $form_id;
}
add_action( 'admin_init', 'volks_ensure_valuation_wpforms_form', 30 );
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	add_action( 'wp_loaded', 'volks_ensure_valuation_wpforms_form', 30 );
}

/**
 * Map sanitized wizard payload onto WPForms raw field values.
 *
 * @param array<string,mixed> $payload Sanitized wizard data.
 * @return array<int,mixed>
 */
function volks_valuation_payload_to_wpforms_fields( array $payload ) {
	$ids = volks_valuation_wpforms_field_ids();

	$fields = array(
		$ids['name']               => array(
			'first' => isset( $payload['vorname'] ) ? (string) $payload['vorname'] : '',
			'last'  => isset( $payload['nachname'] ) ? (string) $payload['nachname'] : '',
		),
		$ids['email']              => isset( $payload['email'] ) ? (string) $payload['email'] : '',
		$ids['telefon']            => isset( $payload['telefon'] ) ? (string) $payload['telefon'] : '',
		$ids['type']               => isset( $payload['type'] ) ? (string) $payload['type'] : '',
		$ids['subtype']            => isset( $payload['subtype'] ) ? (string) $payload['subtype'] : '',
		$ids['strasse']            => isset( $payload['strasse'] ) ? (string) $payload['strasse'] : '',
		$ids['plz']                => isset( $payload['plz'] ) ? (string) $payload['plz'] : '',
		$ids['ort']                => isset( $payload['ort'] ) ? (string) $payload['ort'] : '',
		$ids['zimmer']             => isset( $payload['zimmer'] ) ? (string) $payload['zimmer'] : '',
		$ids['wohneinheiten']      => isset( $payload['wohneinheiten'] ) ? (string) $payload['wohneinheiten'] : '',
		$ids['baujahr']            => isset( $payload['baujahr'] ) ? (string) $payload['baujahr'] : '',
		$ids['flaeche']            => isset( $payload['flaeche'] ) ? (string) $payload['flaeche'] : '',
		$ids['grundstueck']        => isset( $payload['grundstueck'] ) ? (string) $payload['grundstueck'] : '',
		$ids['mieteinnahmen_jahr'] => isset( $payload['mieteinnahmen_jahr'] ) ? (string) $payload['mieteinnahmen_jahr'] : '',
		$ids['ausstattung']        => isset( $payload['ausstattung'] ) ? (string) $payload['ausstattung'] : '',
		$ids['zustand']            => isset( $payload['zustand'] ) ? (string) $payload['zustand'] : '',
		$ids['anlass']             => isset( $payload['anlass'] ) ? (string) $payload['anlass'] : '',
		$ids['datenschutz']        => ! empty( $payload['datenschutz'] ) ? array( '1' ) : array(),
		$ids['seite']              => isset( $payload['_page_url'] ) ? (string) $payload['_page_url'] : '',
	);

	return $fields;
}

/**
 * Load form data for processing without capability checks.
 *
 * @param int $form_id Form ID.
 * @return array<string,mixed>
 */
function volks_get_valuation_wpforms_form_data( $form_id ) {
	$post = get_post( absint( $form_id ) );
	if ( ! $post || 'wpforms' !== $post->post_type ) {
		return array();
	}

	$data = wpforms_decode( $post->post_content );
	if ( ! is_array( $data ) || empty( $data['fields'] ) ) {
		return array();
	}

	$data['id'] = absint( $form_id );

	return $data;
}

/**
 * Save a WPForms entry and send the form notification (via WP Mail SMTP).
 *
 * @param array<string,mixed> $payload Sanitized wizard data.
 * @return array{ok:bool,entry_id:int,mailed:bool,message:string}
 */
function volks_submit_valuation_via_wpforms( array $payload ) {
	$result = array(
		'ok'       => false,
		'entry_id' => 0,
		'mailed'   => false,
		'message'  => '',
	);

	if ( ! volks_wpforms_is_ready() ) {
		$result['message'] = 'WPForms ist nicht aktiv.';
		return $result;
	}

	$form_id = volks_find_valuation_wpforms_form_id();
	if ( $form_id <= 0 ) {
		$form_id = volks_ensure_valuation_wpforms_form();
	}
	if ( $form_id <= 0 ) {
		$result['message'] = 'Das Wertermittlungs-Formular ist noch nicht angelegt.';
		return $result;
	}

	$form_data = volks_get_valuation_wpforms_form_data( $form_id );
	if ( empty( $form_data ) ) {
		$result['message'] = 'Das Wertermittlungs-Formular konnte nicht gelesen werden.';
		return $result;
	}

	$process = wpforms()->obj( 'process' );
	if ( ! $process ) {
		$result['message'] = 'WPForms-Verarbeitung ist nicht verfügbar.';
		return $result;
	}

	$entry = array(
		'id'     => $form_id,
		'fields' => volks_valuation_payload_to_wpforms_fields( $payload ),
	);

	$process->fields   = array();
	$process->errors   = array();
	$process->entry_id = 0;

	foreach ( $form_data['fields'] as $field_properties ) {
		$field_id     = $field_properties['id'];
		$field_type   = $field_properties['type'];
		$field_submit = $entry['fields'][ $field_id ] ?? '';
		do_action( "wpforms_process_format_{$field_type}", $field_id, $field_submit, $form_data );
	}

	$fields = $process->fields;
	if ( empty( $fields ) ) {
		$result['message'] = 'Die Formulardaten konnten nicht aufbereitet werden.';
		return $result;
	}

	add_filter( 'wpforms_tasks_entry_emails_trigger_send_same_process', '__return_true' );

	$entry_id = (int) $process->entry_save( $fields, $entry, $form_id, $form_data );
	if ( $entry_id <= 0 ) {
		$entry_id = (int) $process->entry_id;
	}

	$form_data['settings']['notification_enable'] = '1';
	if ( empty( $form_data['settings']['notifications']['1']['email'] ) ) {
		$form_data['settings']['notifications']['1']['email'] = volks_get_valuation_wizard_recipient_email();
	}
	$form_data['settings']['notifications']['1']['sender_address'] = VOLKS_VALUATION_WPFORMS_SENDER_EMAIL;

	$process->entry_email( $fields, $entry, $form_data, $entry_id, 'entry' );

	remove_filter( 'wpforms_tasks_entry_emails_trigger_send_same_process', '__return_true' );

	$is_pro = is_object( wpforms() ) && method_exists( wpforms(), 'is_pro' ) && wpforms()->is_pro();

	$result['entry_id'] = $entry_id;
	$result['mailed']   = true;
	$result['ok']       = $is_pro ? ( $entry_id > 0 ) : ! empty( $fields );
	$result['message']  = $result['ok']
		? 'Anfrage wurde in WPForms gespeichert.'
		: 'WPForms-Eintrag konnte nicht gespeichert werden.';

	return $result;
}

/**
 * Fallback: send via wp_mail (still goes through WP Mail SMTP).
 *
 * @param array<string,mixed> $payload Sanitized wizard data.
 * @return bool
 */
function volks_submit_valuation_via_mail( array $payload ) {
	$recipient = volks_get_valuation_wizard_recipient_email();
	if ( '' === $recipient || ! is_email( $recipient ) ) {
		return false;
	}

	$user_email = isset( $payload['email'] ) ? $payload['email'] : '';
	$name       = trim( ( isset( $payload['vorname'] ) ? $payload['vorname'] : '' ) . ' ' . ( isset( $payload['nachname'] ) ? $payload['nachname'] : '' ) );
	$type_label = volks_format_valuation_wizard_value( 'type', isset( $payload['type'] ) ? $payload['type'] : '' );
	$subject    = sprintf( 'Wertermittlungsrechner: Neue Anfrage (%s) von %s', $type_label, $name );
	$body       = volks_build_valuation_wizard_email_body( $payload );

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'From: volksimmobilien Website <' . VOLKS_VALUATION_WPFORMS_SENDER_EMAIL . '>',
	);
	if ( is_email( $user_email ) ) {
		$headers[] = 'Reply-To: ' . $name . ' <' . $user_email . '>';
	}

	return (bool) wp_mail( $recipient, $subject, $body, $headers );
}

/**
 * AJAX: store valuation wizard lead in WPForms and notify by mail.
 */
function volks_submit_valuation() {
	check_ajax_referer( 'volks_valuation_submit', 'nonce' );

	if ( ! empty( $_POST['company_website'] ) ) {
		wp_send_json_error( array( 'message' => 'Anfrage konnte nicht gesendet werden.' ), 400 );
	}

	$raw_json = isset( $_POST['payload'] ) ? wp_unslash( (string) $_POST['payload'] ) : '';
	if ( '' === $raw_json || strlen( $raw_json ) > 20000 ) {
		wp_send_json_error( array( 'message' => 'Die Anfrage konnte nicht verarbeitet werden.' ), 400 );
	}

	$raw = json_decode( $raw_json, true );
	if ( ! is_array( $raw ) ) {
		wp_send_json_error( array( 'message' => 'Die Anfrage enthält ungültige Daten.' ), 400 );
	}

	$payload = volks_sanitize_valuation_wizard_payload( $raw );

	if ( empty( $payload['type'] ) ) {
		wp_send_json_error( array( 'message' => 'Bitte wähle einen Immobilientyp.' ), 400 );
	}

	if ( empty( $payload['vorname'] ) || empty( $payload['nachname'] ) || empty( $payload['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Bitte Kontaktdaten vollständig ausfüllen.' ), 422 );
	}

	if ( ! is_email( $payload['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Bitte eine gültige E-Mail-Adresse angeben.' ), 422 );
	}

	if ( empty( $payload['datenschutz'] ) ) {
		wp_send_json_error( array( 'message' => 'Bitte die Datenschutzerklärung bestätigen.' ), 422 );
	}

	if ( empty( $payload['_page_url'] ) && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$payload['_page_url'] = esc_url_raw( wp_unslash( (string) $_SERVER['HTTP_REFERER'] ) );
	}
	if ( empty( $payload['_page_url'] ) ) {
		$payload['_page_url'] = home_url( '/' );
	}
	if ( empty( $payload['_submitted_at'] ) ) {
		$payload['_submitted_at'] = wp_date( 'Y-m-d H:i:s' );
	}

	$wpforms = volks_submit_valuation_via_wpforms( $payload );
	if ( ! empty( $wpforms['ok'] ) ) {
		wp_send_json_success( array( 'message' => 'Vielen Dank! Deine Anfrage wurde gesendet.' ) );
	}

	$mailed = volks_submit_valuation_via_mail( $payload );
	if ( $mailed ) {
		wp_send_json_success( array( 'message' => 'Vielen Dank! Deine Anfrage wurde gesendet.' ) );
	}

	wp_send_json_error(
		array(
			'message' => $wpforms['message']
				? $wpforms['message']
				: 'Die Anfrage konnte gerade nicht gesendet werden. Bitte versuche es erneut oder ruf uns an.',
		),
		500
	);
}
add_action( 'wp_ajax_volks_submit_valuation', 'volks_submit_valuation' );
add_action( 'wp_ajax_nopriv_volks_submit_valuation', 'volks_submit_valuation' );
