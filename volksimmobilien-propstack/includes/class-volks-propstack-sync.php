<?php
/**
 * Safe pull-only synchronization from Propstack into WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Volks_Propstack_Sync {
	const CRON_HOOK = 'volks_propstack_hourly_sync';
	const LOCK_KEY  = 'volks_propstack_sync_lock';

	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'cron_sync' ) );
		add_action( 'init', array( __CLASS__, 'ensure_schedule' ), 30 );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'volks-propstack', 'Volks_Propstack_CLI' );
		}
	}

	public static function ensure_schedule() {
		$enabled = (bool) get_option( 'volks_propstack_enabled', false );
		$next    = wp_next_scheduled( self::CRON_HOOK );

		if ( $enabled && ! $next ) {
			wp_schedule_event( time() + 300, 'hourly', self::CRON_HOOK );
		} elseif ( ! $enabled && $next ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
	}

	public static function cron_sync() {
		if ( get_option( 'volks_propstack_enabled', false ) ) {
			self::run( false );
		}
	}

	/**
	 * Perform a complete, read-only API pull and optional WordPress upsert.
	 *
	 * @param bool $dry_run If true, no WordPress content is changed.
	 * @return array|WP_Error
	 */
	public static function run( $dry_run = false ) {
		if ( get_transient( self::LOCK_KEY ) ) {
			return new WP_Error( 'volks_propstack_locked', 'Es läuft bereits eine Propstack-Synchronisierung.' );
		}
		set_transient( self::LOCK_KEY, 1, 15 * MINUTE_IN_SECONDS );

		try {
			$api      = new Volks_Propstack_API();
			$statuses = $api->get_statuses();
			if ( is_wp_error( $statuses ) ) {
				self::record_error( $statuses );
				return $statuses;
			}

			$allowed_statuses = self::allowed_statuses( $statuses );
			if ( is_wp_error( $allowed_statuses ) ) {
				self::record_error( $allowed_statuses );
				return $allowed_statuses;
			}

			$units = $api->get_units( array_keys( $allowed_statuses ) );
			if ( is_wp_error( $units ) ) {
				self::record_error( $units );
				return $units;
			}

			$result = array(
				'dry_run'       => (bool) $dry_run,
				'remote'        => count( $units ),
				'created'       => 0,
				'updated'       => 0,
				'unchanged'     => 0,
				'deactivated'   => 0,
				'skipped'       => 0,
				'allowed_status'=> array_values( $allowed_statuses ),
			);
			$seen_ids = array();

			foreach ( $units as $unit ) {
				$mapped = self::map_unit( $unit, $allowed_statuses );
				if ( is_wp_error( $mapped ) ) {
					$result['skipped']++;
					continue;
				}

				$seen_ids[] = (string) $mapped['id'];
				$upsert     = self::upsert( $mapped, (bool) $dry_run );
				if ( is_wp_error( $upsert ) ) {
					$result['skipped']++;
					continue;
				}
				$result[ $upsert ]++;
			}

			$result['deactivated'] = self::deactivate_missing( $seen_ids, (bool) $dry_run );

			if ( ! $dry_run ) {
				update_option( 'volks_propstack_last_sync', current_time( 'mysql', true ), false );
				update_option( 'volks_propstack_last_result', $result, false );
				delete_option( 'volks_propstack_last_error' );
			}

			return $result;
		} finally {
			delete_transient( self::LOCK_KEY );
		}
	}

	/**
	 * Resolve allowed public status IDs by explicit, configured status names.
	 * Fail closed when nothing matches.
	 *
	 * @param array $statuses Propstack status objects.
	 * @return array<int,string>|WP_Error
	 */
	public static function allowed_statuses( $statuses ) {
		$configured = array_filter(
			array_map(
				static function ( $value ) {
					return self::normalize_name( $value );
				},
				explode( ',', (string) get_option( 'volks_propstack_allowed_statuses', 'Vermarktung' ) )
			)
		);
		$configured = array_values( array_unique( array_merge( $configured, self::sold_status_names() ) ) );
		$allowed = array();

		foreach ( (array) $statuses as $status ) {
			if ( ! is_array( $status ) ) {
				continue;
			}
			$id        = absint( $status['id'] ?? 0 );
			$name      = sanitize_text_field( (string) ( $status['name'] ?? '' ) );
			$nonpublic = ! empty( $status['nonpublic'] );
			if ( $id && ! $nonpublic && in_array( self::normalize_name( $name ), $configured, true ) ) {
				$allowed[ $id ] = $name;
			}
		}

		if ( empty( $allowed ) ) {
			return new WP_Error(
				'volks_propstack_status_mismatch',
				'Kein öffentlicher Propstack-Status entspricht der konfigurierten Whitelist. Es wurde nichts importiert.'
			);
		}

		return $allowed;
	}

	/**
	 * Configured Propstack status names that belong in the sold gallery.
	 *
	 * @return string[]
	 */
	private static function sold_status_names() {
		return array_values(
			array_filter(
				array_unique(
					array_map(
						array( __CLASS__, 'normalize_name' ),
						explode( ',', (string) get_option( 'volks_propstack_sold_statuses', 'Verkauft' ) )
					)
				)
			)
		);
	}

	/**
	 * Map a Propstack unit to an explicit public-data whitelist.
	 * Contacts, owners, relationships and private assets are never retained.
	 *
	 * @param array             $unit             API object.
	 * @param array<int,string> $allowed_statuses Status whitelist.
	 * @return array|WP_Error
	 */
	public static function map_unit( $unit, $allowed_statuses ) {
		if ( ! is_array( $unit ) ) {
			return new WP_Error( 'volks_propstack_invalid_unit', 'Ungültiges Objekt.' );
		}

		$id = absint( self::value( $unit['id'] ?? 0 ) );
		if ( ! $id || ! empty( self::value( $unit['archived'] ?? false ) ) ) {
			return new WP_Error( 'volks_propstack_invalid_id', 'Objekt-ID fehlt oder Objekt ist archiviert.' );
		}

		$status    = isset( $unit['status'] ) && is_array( $unit['status'] )
			? $unit['status']
			: ( isset( $unit['property_status'] ) && is_array( $unit['property_status'] ) ? $unit['property_status'] : array() );
		$status_id = absint( self::value( $status['id'] ?? ( $unit['property_status_id'] ?? 0 ) ) );
		$status_name = isset( $allowed_statuses[ $status_id ] )
			? sanitize_text_field( (string) $allowed_statuses[ $status_id ] )
			: '';
		$is_sold = '' !== $status_name && in_array( self::normalize_name( $status_name ), self::sold_status_names(), true );
		if ( '' === $status_name || ( ! empty( $status['remove_from_portal'] ) && ! $is_sold ) ) {
			return new WP_Error( 'volks_propstack_disallowed_status', 'Objektstatus ist nicht freigegeben.' );
		}

		$title = html_entity_decode( sanitize_text_field( (string) self::value( $unit['title'] ?? '' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( '' === $title ) {
			$title = 'Immobilie ' . $id;
		}

		$marketing_type = strtoupper( sanitize_key( (string) self::value( $unit['marketing_type'] ?? '' ) ) );
		$rs_type        = strtoupper( sanitize_key( (string) self::value( $unit['rs_type'] ?? '' ) ) );
		$rs_category    = strtoupper( sanitize_key( (string) self::value( $unit['rs_category'] ?? '' ) ) );

		$mapped = array(
			'id'                => $id,
			'title'             => $title,
			'exposee_id'        => sanitize_text_field( (string) self::value( $unit['exposee_id'] ?? '' ) ),
			'unit_id'           => sanitize_text_field( (string) self::value( $unit['unit_id'] ?? '' ) ),
			'status_id'         => $status_id,
			'status'            => 'vermarktung' === self::normalize_name( $status_name ) ? 'Verfügbar' : $status_name,
			'inventory_state'   => $is_sold ? 'sold' : 'active',
			'marketing_type'    => in_array( $marketing_type, array( 'BUY', 'RENT' ), true ) ? $marketing_type : '',
			'object_type'       => strtoupper( sanitize_key( (string) self::value( $unit['object_type'] ?? '' ) ) ),
			'rs_type'           => $rs_type,
			'rs_category'       => $rs_category,
			'type_label'        => Volks_Propstack_Post_Type::type_label( $rs_type, $rs_category ),
			'city'              => sanitize_text_field( (string) self::value( $unit['city'] ?? '' ) ),
			'district'          => sanitize_text_field( (string) self::value( $unit['district'] ?? '' ) ),
			'region'            => sanitize_text_field( (string) self::value( $unit['region'] ?? '' ) ),
			'zip_code'          => sanitize_text_field( (string) self::value( $unit['zip_code'] ?? '' ) ),
			'country'           => sanitize_text_field( (string) self::value( $unit['country'] ?? '' ) ),
			'price'             => self::number( $unit['price'] ?? ( $unit['object_price'] ?? null ) ),
			'base_rent'         => self::number( $unit['base_rent'] ?? null ),
			'total_rent'        => self::number( $unit['total_rent'] ?? null ),
			'living_space'      => self::number( $unit['living_space'] ?? ( $unit['property_space_value'] ?? null ) ),
			'plot_area'         => self::number( $unit['plot_area'] ?? null ),
			'rooms'             => self::number( $unit['number_of_rooms'] ?? null ),
			'bedrooms'          => self::number( $unit['number_of_bed_rooms'] ?? null ),
			'bathrooms'         => self::number( $unit['number_of_bath_rooms'] ?? null ),
			'construction_year' => self::number( $unit['construction_year'] ?? null ),
			'floor'             => self::number( $unit['floor'] ?? null ),
			'description'       => self::html( $unit['description_note'] ?? ( $unit['long_description_note'] ?? '' ) ),
			'location_note'     => self::html( $unit['location_note'] ?? ( $unit['long_location_note'] ?? '' ) ),
			'furnishing_note'   => self::html( $unit['furnishing_note'] ?? ( $unit['long_furnishing_note'] ?? '' ) ),
			'other_note'        => self::html( $unit['other_note'] ?? ( $unit['long_other_note'] ?? '' ) ),
			'courtage'          => sanitize_text_field( (string) self::value( $unit['courtage'] ?? '' ) ),
			'courtage_note'     => self::html( $unit['courtage_note'] ?? '' ),
			'energy'            => self::map_energy( $unit ),
			'images'            => self::map_images( $unit['images'] ?? array() ),
			'floorplans'        => self::map_documents( $unit['floorplans'] ?? array() ),
			'documents'         => self::map_documents( $unit['documents'] ?? array() ),
			'broker'            => self::map_broker( $unit['broker'] ?? array() ),
			'updated_at'        => sanitize_text_field( (string) self::value( $unit['updated_at'] ?? '' ) ),
		);

		if ( 'RENT' === $mapped['marketing_type'] && null === $mapped['price'] ) {
			$mapped['price'] = $mapped['base_rent'] ?? $mapped['total_rent'];
		}
		if ( $is_sold ) {
			$mapped['price']         = null;
			$mapped['base_rent']     = null;
			$mapped['total_rent']    = null;
			$mapped['courtage']      = '';
			$mapped['courtage_note'] = '';
		}

		$mapped['hash'] = hash( 'sha256', wp_json_encode( $mapped, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		return $mapped;
	}

	/**
	 * Insert/update one mapped property.
	 *
	 * @return string|WP_Error created, updated or unchanged.
	 */
	private static function upsert( $mapped, $dry_run ) {
		$existing = get_posts(
			array(
				'post_type'      => Volks_Propstack_Post_Type::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_vps_id',
				'meta_value'     => (string) $mapped['id'],
			)
		);
		$post_id  = ! empty( $existing ) ? absint( $existing[0] ) : 0;
		$old_hash = $post_id ? (string) get_post_meta( $post_id, '_vps_hash', true ) : '';
		$state    = $post_id
			? ( hash_equals( $old_hash, $mapped['hash'] ) && 'publish' === get_post_status( $post_id ) ? 'unchanged' : 'updated' )
			: 'created';

		if ( $dry_run ) {
			return $state;
		}

		if ( 'unchanged' === $state ) {
			self::cleanup_meta( $post_id, array_keys( $mapped ) );
			update_post_meta( $post_id, '_vps_last_seen', current_time( 'mysql', true ) );
			return $state;
		}

		$post = array(
			'ID'           => $post_id,
			'post_type'    => Volks_Propstack_Post_Type::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $mapped['title'],
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $mapped['description'] ), 34, ' …' ),
			'post_name'    => $post_id ? get_post_field( 'post_name', $post_id ) : sanitize_title( $mapped['title'] . '-' . $mapped['id'] ),
		);
		$post_id = wp_insert_post( wp_slash( $post ), true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		self::cleanup_meta( $post_id, array_keys( $mapped ) );
		foreach ( $mapped as $key => $value ) {
			update_post_meta( $post_id, '_vps_' . sanitize_key( $key ), $value );
		}
		update_post_meta( $post_id, '_vps_last_seen', current_time( 'mysql', true ) );

		wp_set_object_terms( $post_id, '' !== $mapped['type_label'] ? array( $mapped['type_label'] ) : array(), Volks_Propstack_Post_Type::TAX_TYPE, false );
		wp_set_object_terms( $post_id, '' !== $mapped['city'] ? array( $mapped['city'] ) : array(), Volks_Propstack_Post_Type::TAX_LOCATION, false );

		return $state;
	}

	/**
	 * Remove legacy integration metadata that is no longer on the public allowlist.
	 */
	private static function cleanup_meta( $post_id, $mapped_keys ) {
		$allowed = array( '_vps_last_seen' );
		foreach ( (array) $mapped_keys as $key ) {
			$allowed[] = '_vps_' . sanitize_key( $key );
		}

		foreach ( array_keys( get_post_meta( $post_id ) ) as $meta_key ) {
			if ( str_starts_with( $meta_key, '_vps_' ) && ! in_array( $meta_key, $allowed, true ) ) {
				delete_post_meta( $post_id, $meta_key );
			}
		}
	}

	private static function deactivate_missing( $seen_ids, $dry_run ) {
		$seen_ids = array_map( 'strval', (array) $seen_ids );
		$posts    = get_posts(
			array(
				'post_type'      => Volks_Propstack_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_vps_id',
			)
		);
		$count = 0;
		foreach ( $posts as $post_id ) {
			$id = (string) get_post_meta( $post_id, '_vps_id', true );
			if ( '' !== $id && ! in_array( $id, $seen_ids, true ) ) {
				$count++;
				if ( ! $dry_run ) {
					wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
					update_post_meta( $post_id, '_vps_deactivated_at', current_time( 'mysql', true ) );
				}
			}
		}
		return $count;
	}

	private static function record_error( $error ) {
		if ( is_wp_error( $error ) ) {
			update_option(
				'volks_propstack_last_error',
				array( 'time' => current_time( 'mysql', true ), 'code' => $error->get_error_code(), 'message' => $error->get_error_message() ),
				false
			);
		}
	}

	private static function normalize_name( $value ) {
		return strtolower( remove_accents( trim( (string) $value ) ) );
	}

	private static function value( $value ) {
		if ( is_array( $value ) && array_key_exists( 'value', $value ) ) {
			return $value['value'];
		}
		return $value;
	}

	private static function number( $value ) {
		$value = self::value( $value );
		return is_numeric( $value ) ? (float) $value : null;
	}

	private static function html( $value ) {
		$value = (string) self::value( $value );
		return wp_kses_post( $value );
	}

	private static function map_images( $images ) {
		$out = array();
		foreach ( (array) $images as $image ) {
			if ( ! is_array( $image ) || ! empty( $image['is_private'] ) || ! empty( $image['is_floorplan'] ) ) {
				continue;
			}
			$url = $image['big_url'] ?? ( $image['big'] ?? ( $image['url'] ?? ( $image['original'] ?? '' ) ) );
			$url = esc_url_raw( (string) $url, array( 'https' ) );
			if ( '' === $url ) {
				continue;
			}
			$full = $image['original_url'] ?? ( $image['original'] ?? ( $image['url'] ?? $url ) );
			$full = esc_url_raw( (string) $full, array( 'https' ) );
			$out[] = array(
				'url'   => $url,
				'full'  => $full ?: $url,
				'thumb' => esc_url_raw( (string) ( $image['thumb_url'] ?? ( $image['thumb'] ?? $url ) ), array( 'https' ) ),
				'title' => sanitize_text_field( (string) ( $image['title'] ?? '' ) ),
			);
		}
		return $out;
	}

	private static function map_documents( $documents ) {
		$out = array();
		foreach ( (array) $documents as $document ) {
			if ( ! is_array( $document ) || ! empty( $document['is_private'] ) ) {
				continue;
			}
			$url = esc_url_raw( (string) ( $document['url'] ?? '' ), array( 'https' ) );
			if ( '' === $url ) {
				continue;
			}
			$out[] = array(
				'url'   => $url,
				'title' => sanitize_text_field( (string) ( $document['title'] ?? ( $document['name'] ?? 'Dokument' ) ) ),
			);
		}
		return array_slice( $out, 0, 20 );
	}

	private static function map_broker( $broker ) {
		if ( ! is_array( $broker ) ) {
			return array();
		}
		return array_filter(
			array(
				'name'       => sanitize_text_field( (string) self::value( $broker['name'] ?? '' ) ),
				'phone'      => sanitize_text_field( (string) self::value( $broker['phone'] ?? '' ) ),
				'email'      => sanitize_email( (string) self::value( $broker['email'] ?? '' ) ),
				'position'   => sanitize_text_field( (string) self::value( $broker['position'] ?? '' ) ),
				'avatar_url' => esc_url_raw( (string) self::value( $broker['avatar_url'] ?? ( $broker['avatar'] ?? '' ) ), array( 'https' ) ),
			)
		);
	}

	private static function map_energy( $unit ) {
		$fields = array(
			'energy_certificate_type' => 'Ausweisart',
			'energy_consumption'      => 'Energieverbrauch',
			'energy_requirement'      => 'Energiebedarf',
			'energy_efficiency_class' => 'Effizienzklasse',
			'primary_energy_source'   => 'Energieträger',
			'heating_type'            => 'Heizungsart',
			'energy_certificate_year' => 'Baujahr laut Energieausweis',
		);
		$out = array();
		foreach ( $fields as $key => $label ) {
			$value = self::value( $unit[ $key ] ?? '' );
			if ( null !== $value && '' !== (string) $value ) {
				$out[ $label ] = sanitize_text_field( (string) $value );
			}
		}
		return $out;
	}
}

/**
 * WP-CLI control plane. Commands are read-only unless sync runs without --dry-run,
 * and even then only local WordPress posts are changed.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	final class Volks_Propstack_CLI {
		/**
		 * Pull current public listings.
		 *
		 * ## OPTIONS
		 * [--dry-run]
		 * : Inspect without changing WordPress posts.
		 */
		public function sync( $args, $assoc_args ) {
			unset( $args );
			$result = Volks_Propstack_Sync::run( WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false ) );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}
			WP_CLI::success( wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}

		/** Show key presence and last synchronization state without exposing secrets. */
		public function status() {
			$api = new Volks_Propstack_API();
			WP_CLI::line( 'API key: ' . ( $api->has_key() ? 'available' : 'missing' ) );
			WP_CLI::line( 'Enabled: ' . ( get_option( 'volks_propstack_enabled', false ) ? 'yes' : 'no' ) );
			WP_CLI::line( 'Active statuses: ' . get_option( 'volks_propstack_allowed_statuses', 'Vermarktung' ) );
			WP_CLI::line( 'Sold statuses: ' . get_option( 'volks_propstack_sold_statuses', 'Verkauft' ) );
			WP_CLI::line( 'Last sync: ' . get_option( 'volks_propstack_last_sync', 'never' ) );
		}
	}
}
