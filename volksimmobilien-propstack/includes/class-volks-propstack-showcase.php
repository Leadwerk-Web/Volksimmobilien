<?php
/**
 * Selectable Propstack cards for the Kaufen page showcase.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Volks_Propstack_Showcase {
	const OPTION_KEY = 'volks_propstack_showcase_ids';
	const PAGE_SLUG  = 'volks-propstack-showcase';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_post_volks_propstack_save_showcase', array( __CLASS__, 'save' ) );
		add_filter( 'volks_offers_showcase_html', array( __CLASS__, 'replace_showcase_cards' ) );
	}

	public static function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=' . Volks_Propstack_Post_Type::POST_TYPE,
			'Kaufen-Vitrine',
			'Kaufen-Vitrine',
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Return two required properties and an optional third property.
	 * Unsaved required slots fall back to the newest objects; zero preserves the static third card.
	 *
	 * @return int[]
	 */
	public static function selected_ids() {
		$stored   = array_values( array_map( 'absint', (array) get_option( self::OPTION_KEY, array() ) ) );
		$selected = array_values( array_unique( array_filter( array_slice( $stored, 0, 2 ) ) ) );
		$selected = array_values(
			array_filter(
				$selected,
				static function ( $post_id ) {
					return self::is_active_property( $post_id );
				}
			)
		);

		if ( count( $selected ) < 2 ) {
			$fallback = get_posts(
				array(
					'post_type'      => Volks_Propstack_Post_Type::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => 2,
					'post__not_in'   => $selected,
					'orderby'        => 'modified',
					'order'          => 'DESC',
					'fields'         => 'ids',
					'meta_query'     => self::active_meta_query(),
				)
			);
			$selected = array_merge( $selected, array_map( 'absint', $fallback ) );
		}

		$selected = array_slice( $selected, 0, 2 );
		$third    = absint( $stored[2] ?? 0 );
		if (
			$third &&
			( ! self::is_active_property( $third ) || in_array( $third, $selected, true ) )
		) {
			$third = 0;
		}
		$selected[] = $third;

		return $selected;
	}

	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie dürfen diese Aktion nicht ausführen.' ), 403 );
		}
		check_admin_referer( 'volks_propstack_save_showcase' );

		$raw = isset( $_POST['showcase_ids'] ) ? (array) wp_unslash( $_POST['showcase_ids'] ) : array();
		$ids = array(
			absint( $raw[0] ?? 0 ),
			absint( $raw[1] ?? 0 ),
			absint( $raw[2] ?? 0 ),
		);
		$chosen = array_filter( $ids );
		if ( ! $ids[0] || ! $ids[1] || count( array_unique( $chosen ) ) !== count( $chosen ) ) {
			self::redirect( 'error', 'Bitte wählen Sie für alle aktiven Karten unterschiedliche Immobilien aus.' );
		}

		foreach ( $chosen as $post_id ) {
			if ( ! self::is_active_property( $post_id ) ) {
				self::redirect( 'error', 'Mindestens eine gewählte Immobilie ist nicht mehr als aktives Angebot veröffentlicht.' );
			}
		}

		update_option( self::OPTION_KEY, $ids, false );
		self::redirect( 'success', 'Die Auswahl der Kaufen-Vitrine wurde gespeichert.' );
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie dürfen diese Seite nicht aufrufen.' ) );
		}

		$properties = get_posts(
			array(
				'post_type'      => Volks_Propstack_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_query'     => self::active_meta_query(),
			)
		);
		$selected   = self::selected_ids();
		$notice     = isset( $_GET['vps_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['vps_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message    = isset( $_GET['vps_message'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['vps_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1>Kaufen-Vitrine</h1>
			<p>Die ersten zwei Karten werden aus Propstack gewählt. Bei Karte 3 können Sie die bestehende statische Karte behalten oder sie jederzeit durch eine weitere Propstack-Immobilie ersetzen.</p>

			<?php if ( $message ) : ?>
				<div class="notice notice-<?php echo esc_attr( in_array( $notice, array( 'success', 'error', 'warning' ), true ) ? $notice : 'info' ); ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
			<?php endif; ?>

			<?php if ( count( $properties ) < 2 ) : ?>
				<div class="notice notice-warning"><p>Für die dynamische Vitrine müssen mindestens zwei veröffentlichte Propstack-Immobilien vorhanden sein.</p></div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:900px">
					<input type="hidden" name="action" value="volks_propstack_save_showcase">
					<?php wp_nonce_field( 'volks_propstack_save_showcase' ); ?>
					<table class="form-table" role="presentation">
						<?php for ( $slot = 0; $slot < 3; $slot++ ) : ?>
							<tr>
								<th scope="row"><label for="vps-showcase-<?php echo esc_attr( (string) $slot ); ?>">Karte <?php echo esc_html( (string) ( $slot + 1 ) ); ?></label></th>
								<td>
									<select class="regular-text" id="vps-showcase-<?php echo esc_attr( (string) $slot ); ?>" name="showcase_ids[]" <?php echo $slot < 2 ? 'required' : ''; ?>>
										<option value=""><?php echo 2 === $slot ? 'Bestehende statische Karte beibehalten' : 'Immobilie auswählen'; ?></option>
										<?php foreach ( $properties as $property ) : ?>
											<?php
											$city  = (string) get_post_meta( $property->ID, '_vps_city', true );
											$label = html_entity_decode( $property->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . ( $city ? ' — ' . $city : '' ) . ' (ID ' . $property->ID . ')';
											?>
											<option value="<?php echo esc_attr( (string) $property->ID ); ?>" <?php selected( $selected[ $slot ] ?? 0, $property->ID ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endfor; ?>
					</table>
					<?php submit_button( 'Vitrine speichern' ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function is_active_property( $post_id ) {
		if ( Volks_Propstack_Post_Type::POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return false;
		}
		$state = (string) get_post_meta( $post_id, '_vps_inventory_state', true );
		return '' === $state || 'active' === $state;
	}

	private static function active_meta_query() {
		return array(
			'relation' => 'OR',
			array(
				'key'     => '_vps_inventory_state',
				'value'   => 'active',
				'compare' => '=',
			),
			array(
				'key'     => '_vps_inventory_state',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/**
	 * Replace the first two cards and, when selected, the third source card.
	 *
	 * @param string $html Normalized offers section.
	 * @return string
	 */
	public static function replace_showcase_cards( $html ) {
		$ids = self::selected_ids();
		if ( count( $ids ) < 3 || ! $ids[0] || ! $ids[1] || ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}

		libxml_use_internal_errors( true );
		$document = new DOMDocument( '1.0', 'UTF-8' );
		$loaded   = $document->loadHTML(
			'<?xml encoding="utf-8" ?><div id="vps-showcase-root">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		if ( ! $loaded ) {
			return $html;
		}

		$xpath = new DOMXPath( $document );
		$cards = $xpath->query( '//*[@id="vps-showcase-root"]//*[contains(concat(" ", normalize-space(@class), " "), " listings-grid ")]/*[contains(concat(" ", normalize-space(@class), " "), " listing-card ")]' );
		if ( ! $cards || $cards->length < 3 ) {
			return $html;
		}

		$replace_count = $ids[2] ? 3 : 2;
		for ( $slot = 0; $slot < $replace_count; $slot++ ) {
			$replacement = self::create_card_node( $document, $ids[ $slot ], $slot + 1 );
			if ( $replacement ) {
				$cards->item( $slot )->parentNode->replaceChild( $replacement, $cards->item( $slot ) );
			}
		}

		$root   = $document->getElementById( 'vps-showcase-root' );
		$output = '';
		if ( $root ) {
			foreach ( $root->childNodes as $child ) {
				$output .= $document->saveHTML( $child );
			}
		}

		return '' !== $output ? $output : $html;
	}

	private static function create_card_node( DOMDocument $document, $post_id, $slot ) {
		$title     = html_entity_decode( get_the_title( $post_id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$images    = Volks_Propstack_Frontend::images( $post_id );
		$image     = (string) ( $images[0]['url'] ?? '' );
		$status    = (string) Volks_Propstack_Frontend::meta( $post_id, 'status' );
		$type      = (string) Volks_Propstack_Frontend::meta( $post_id, 'type_label', 'Immobilie' );
		$zip       = (string) Volks_Propstack_Frontend::meta( $post_id, 'zip_code' );
		$city      = (string) Volks_Propstack_Frontend::meta( $post_id, 'city' );
		$area      = Volks_Propstack_Frontend::meta( $post_id, 'living_space', null );
		$rooms     = Volks_Propstack_Frontend::meta( $post_id, 'rooms', null );
		$plot      = Volks_Propstack_Frontend::meta( $post_id, 'plot_area', null );
		$floor     = Volks_Propstack_Frontend::meta( $post_id, 'floor', null );
		$teaser    = get_the_excerpt( $post_id );
		$teaser    = $teaser ? wp_trim_words( html_entity_decode( wp_strip_all_tags( $teaser ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 20, ' …' ) : 'Aktuelles Immobilienangebot aus unserem Bestand.';

		$card = $document->createElement( 'a' );
		$card->setAttribute( 'href', get_permalink( $post_id ) );
		$card->setAttribute( 'class', 'listing-card listing-card--dynamic' );
		$card->setAttribute( 'data-property-id', (string) $post_id );
		$card->setAttribute( 'data-showcase-slot', (string) $slot );

		$media = $document->createElement( 'div' );
		$media->setAttribute( 'class', 'listing-card-image' );
		if ( $image ) {
			$img = $document->createElement( 'img' );
			$img->setAttribute( 'src', $image );
			$img->setAttribute( 'alt', $title );
			$img->setAttribute( 'loading', 'lazy' );
			$img->setAttribute( 'decoding', 'async' );
			$media->appendChild( $img );
		}
		if ( $status ) {
			$badge = $document->createElement( 'span' );
			$badge->setAttribute( 'class', 'listing-card-badge' );
			$badge->appendChild( $document->createTextNode( $status ) );
			$media->appendChild( $badge );
		}
		$card->appendChild( $media );

		$body = $document->createElement( 'div' );
		$body->setAttribute( 'class', 'listing-card-body' );
		self::append_text_element( $document, $body, 'span', 'listing-card-type', $type . ' · ' . Volks_Propstack_Frontend::marketing_label( $post_id ) );
		self::append_text_element( $document, $body, 'h3', '', $title );
		self::append_text_element( $document, $body, 'p', 'listing-card-teaser', $teaser );

		$location = $document->createElement( 'p' );
		$location->setAttribute( 'class', 'listing-card-location' );
		$svg = $document->createDocumentFragment();
		$svg->appendXML( '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' );
		$location->appendChild( $svg );
		$location->appendChild( $document->createTextNode( trim( $zip . ' ' . $city ) ?: 'Region auf Anfrage' ) );
		$body->appendChild( $location );

		$details = $document->createElement( 'div' );
		$details->setAttribute( 'class', 'listing-card-details' );
		if ( is_numeric( $area ) && (float) $area > 0 ) {
			self::append_text_element( $document, $details, 'span', '', number_format_i18n( (float) $area, 0 ) . ' m²' );
		}
		if ( is_numeric( $rooms ) && (float) $rooms > 0 ) {
			$digits = floor( (float) $rooms ) === (float) $rooms ? 0 : 1;
			self::append_text_element( $document, $details, 'span', '', number_format_i18n( (float) $rooms, $digits ) . ' Zimmer' );
		}
		if ( is_numeric( $plot ) && (float) $plot > 0 ) {
			self::append_text_element( $document, $details, 'span', '', number_format_i18n( (float) $plot, 0 ) . ' m² Grund' );
		} elseif ( is_numeric( $floor ) ) {
			self::append_text_element( $document, $details, 'span', '', number_format_i18n( (float) $floor, 0 ) . '. OG' );
		}
		$body->appendChild( $details );
		self::append_text_element( $document, $body, 'p', 'listing-card-price', Volks_Propstack_Frontend::format_price( $post_id ) );
		self::append_text_element( $document, $body, 'span', 'listing-card-cta', 'Immobilienangebot ansehen' );
		$card->appendChild( $body );

		return $card;
	}

	private static function append_text_element( DOMDocument $document, DOMNode $parent, $tag, $class, $text ) {
		$element = $document->createElement( $tag );
		if ( $class ) {
			$element->setAttribute( 'class', $class );
		}
		$element->appendChild( $document->createTextNode( (string) $text ) );
		$parent->appendChild( $element );
	}

	private static function redirect( $type, $message ) {
		$url = add_query_arg(
			array(
				'post_type'   => Volks_Propstack_Post_Type::POST_TYPE,
				'page'        => self::PAGE_SLUG,
				'vps_notice'  => sanitize_key( $type ),
				'vps_message' => $message,
			),
			admin_url( 'edit.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
