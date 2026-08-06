<?php
/**
 * Propstack API V1 read-only client.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Volks_Propstack_API {
	const BASE_URL = 'https://api.propstack.de/v1';

	/**
	 * Whether a key can be resolved without exposing it.
	 */
	public function has_key() {
		return '' !== $this->get_key();
	}

	/**
	 * Retrieve property statuses.
	 *
	 * @return array|WP_Error
	 */
	public function get_statuses() {
		$response = $this->request( '/property_statuses' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			return array_values( $response['data'] );
		}

		return is_array( $response ) ? array_values( $response ) : array();
	}

	/**
	 * Retrieve all non-archived units for a known list of status IDs.
	 *
	 * @param int[] $status_ids Allowed Propstack status IDs.
	 * @return array|WP_Error
	 */
	public function get_units( $status_ids ) {
		$status_ids = array_values( array_filter( array_map( 'absint', (array) $status_ids ) ) );
		if ( empty( $status_ids ) ) {
			return new WP_Error( 'volks_propstack_no_status', 'Keine zulässigen Propstack-Status-IDs konfiguriert.' );
		}

		$units = array();
		$page  = 1;
		$per   = 100;

		do {
			$response = $this->request(
				'/units',
				array(
					'with_meta' => 1,
					'expand'    => 1,
					'archived'  => 0,
					'status'    => implode( ',', $status_ids ),
					'sort_by'   => 'updated_at',
					'order'     => 'desc',
					'page'      => $page,
					'per'       => $per,
				)
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$batch = isset( $response['data'] ) && is_array( $response['data'] )
				? array_values( $response['data'] )
				: array();
			$units = array_merge( $units, $batch );

			$total = isset( $response['meta']['total_count'] ) ? absint( $response['meta']['total_count'] ) : null;
			$page++;
			$more = null !== $total ? count( $units ) < $total : count( $batch ) === $per;
		} while ( $more && $page <= 100 );

		return $units;
	}

	/**
	 * Execute one authenticated GET request. No write method exists by design.
	 *
	 * @param string $endpoint API path beginning with a slash.
	 * @param array  $query    Query values.
	 * @return array|WP_Error
	 */
	public function request( $endpoint, $query = array() ) {
		$endpoint = '/' . ltrim( (string) $endpoint, '/' );
		$key      = $this->get_key();
		if ( '' === $key ) {
			return new WP_Error(
				'volks_propstack_missing_key',
				'Propstack API-Key fehlt. Bitte einen Key-Dateipfad konfigurieren; der Key darf nicht in WordPress gespeichert werden.'
			);
		}

		$pre = apply_filters( 'volks_propstack_pre_request', null, $endpoint, $query );
		if ( null !== $pre ) {
			return $pre;
		}

		$url = add_query_arg( $query, self::BASE_URL . $endpoint );
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'redirection' => 2,
				'headers'     => array(
					'Accept'     => 'application/json',
					'X-API-KEY'  => $key,
					'User-Agent' => 'Volksimmobilien-WordPress/' . VOLKS_PROPSTACK_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'volks_propstack_http', 'Propstack ist derzeit nicht erreichbar.' );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error(
				'volks_propstack_status_' . $code,
				sprintf( 'Propstack antwortete mit HTTP %d.', $code )
			);
		}

		$body = (string) wp_remote_retrieve_body( $response );
		if ( '' === $body || strlen( $body ) > 20 * MB_IN_BYTES ) {
			return new WP_Error( 'volks_propstack_body', 'Propstack lieferte keine gültige Antwortgröße.' );
		}

		$data = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return new WP_Error( 'volks_propstack_json', 'Propstack lieferte ungültiges JSON.' );
		}

		return $data;
	}

	/**
	 * Resolve the API key from a constant, environment variable, or protected file.
	 * The file path itself may be stored in WordPress; the key never is.
	 */
	private function get_key() {
		if ( defined( 'VOLKS_PROPSTACK_API_KEY' ) ) {
			return $this->sanitize_key( (string) VOLKS_PROPSTACK_API_KEY );
		}

		$environment = getenv( 'PROPSTACK_API_KEY' );
		if ( is_string( $environment ) && '' !== trim( $environment ) ) {
			return $this->sanitize_key( $environment );
		}

		$file = defined( 'VOLKS_PROPSTACK_API_KEY_FILE' )
			? (string) VOLKS_PROPSTACK_API_KEY_FILE
			: (string) get_option( 'volks_propstack_key_file', '' );
		$file = apply_filters( 'volks_propstack_key_file', $file );
		if ( '' === $file || ! is_readable( $file ) || is_dir( $file ) ) {
			return '';
		}

		$contents = file_get_contents( $file );
		if ( ! is_string( $contents ) || strlen( $contents ) > 4096 ) {
			return '';
		}

		foreach ( preg_split( '/\R/', $contents ) as $line ) {
			$line = trim( (string) $line );
			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				continue;
			}
			if ( str_starts_with( $line, 'PROPSTACK_API_KEY=' ) ) {
				return $this->sanitize_key( substr( $line, strlen( 'PROPSTACK_API_KEY=' ) ) );
			}
		}

		return $this->sanitize_key( $contents );
	}

	/**
	 * Accept the documented token character set only.
	 */
	private function sanitize_key( $key ) {
		$key = trim( (string) $key, " \t\n\r\0\x0B\"'" );
		return preg_match( '/^[A-Za-z0-9_-]{20,200}$/', $key ) ? $key : '';
	}
}

