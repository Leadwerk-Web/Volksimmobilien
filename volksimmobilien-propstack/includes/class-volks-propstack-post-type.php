<?php
/**
 * Property post type and filter taxonomies.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Volks_Propstack_Post_Type {
	const POST_TYPE    = 'volks_property';
	const TAX_TYPE     = 'volks_property_type';
	const TAX_LOCATION = 'volks_property_location';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
	}

	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'               => 'Immobilien',
					'singular_name'      => 'Immobilie',
					'add_new_item'       => 'Immobilie hinzufügen',
					'edit_item'          => 'Immobilie ansehen',
					'view_item'          => 'Immobilie ansehen',
					'search_items'       => 'Immobilien durchsuchen',
					'not_found'          => 'Keine Immobilien gefunden',
					'all_items'          => 'Alle Immobilien',
					'menu_name'          => 'Immobilien',
					'name_admin_bar'     => 'Immobilie',
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => false,
				'has_archive'        => 'immobilien',
				'rewrite'            => array(
					'slug'       => 'immobilie',
					'with_front' => false,
				),
				'menu_icon'          => 'dashicons-building',
				'supports'           => array( 'title', 'excerpt' ),
				'exclude_from_search'=> false,
				'can_export'         => true,
				'delete_with_user'   => false,
				'map_meta_cap'       => true,
			) 
		);
	}

	public static function register_taxonomies() {
		register_taxonomy(
			self::TAX_TYPE,
			self::POST_TYPE,
			array(
				'labels'            => array( 'name' => 'Immobilientypen', 'singular_name' => 'Immobilientyp' ),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => false,
				'hierarchical'      => false,
			)
		);

		register_taxonomy(
			self::TAX_LOCATION,
			self::POST_TYPE,
			array(
				'labels'            => array( 'name' => 'Orte', 'singular_name' => 'Ort' ),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => false,
				'hierarchical'      => false,
			)
		);
	}

	/**
	 * Translate Propstack type codes for public output.
	 */
	public static function type_label( $rs_type, $rs_category = '' ) {
		$category_labels = array(
			'PENTHOUSE'             => 'Penthouse',
			'ROOF_STOREY'           => 'Dachgeschosswohnung',
			'MAISONETTE'            => 'Maisonette',
			'GROUND_FLOOR'          => 'Erdgeschosswohnung',
			'SINGLE_FAMILY_HOUSE'   => 'Einfamilienhaus',
			'TWO_FAMILY_HOUSE'      => 'Zweifamilienhaus',
			'TERRACE_HOUSE'         => 'Reihenhaus',
			'MID_TERRACE_HOUSE'     => 'Reihenmittelhaus',
			'TERRACE_END_HOUSE'     => 'Reihenendhaus',
			'MULTI_FAMILY_HOUSE'    => 'Mehrfamilienhaus',
			'SEMIDETACHED_HOUSE'    => 'Doppelhaushälfte',
			'VILLA'                 => 'Villa',
			'FINCA'                 => 'Finca',
			'BUNGALOW'              => 'Bungalow',
			'OFFICE'                => 'Büro',
			'STORE'                 => 'Ladenfläche',
			'COMMERCIAL_UNIT'       => 'Gewerbeeinheit',
			'INVEST_LIVING_BUSINESS_HOUSE' => 'Wohn- und Geschäftshaus',
		);
		$rs_category = strtoupper( sanitize_key( (string) $rs_category ) );
		if ( isset( $category_labels[ $rs_category ] ) ) {
			return $category_labels[ $rs_category ];
		}

		$type_labels = array(
			'APARTMENT'  => 'Wohnung',
			'HOUSE'      => 'Haus',
			'TRADE_SITE' => 'Grundstück',
			'GARAGE'     => 'Stellplatz / Garage',
			'OFFICE'     => 'Büro',
			'GASTRONOMY' => 'Gastronomie',
			'INDUSTRY'   => 'Industrie / Lager',
			'STORE'      => 'Ladenfläche',
			'INVESTMENT' => 'Investment',
		);
		$rs_type = strtoupper( sanitize_key( (string) $rs_type ) );
		return $type_labels[ $rs_type ] ?? ucwords( strtolower( str_replace( '_', ' ', $rs_type ) ) );
	}
}

