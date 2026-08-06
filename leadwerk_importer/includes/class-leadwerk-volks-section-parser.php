<?php
/**
 * Parse volksimmobilien homepage HTML sections into structured field rows.
 *
 * @package Leadwerk_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Homepage section HTML → structured layouts.
 */
class Leadwerk_Volks_Section_Parser {

	/**
	 * @var Leadwerk_ACF_Filler
	 */
	protected $filler;

	/**
	 * @param Leadwerk_ACF_Filler $filler Importer filler instance.
	 */
	public function __construct( Leadwerk_ACF_Filler $filler ) {
		$this->filler = $filler;
	}

	/**
	 * Parse one homepage section node.
	 *
	 * @param DOMNode $section_node Section element.
	 * @return array<string,mixed>
	 */
	/**
	 * Parse one landing-page section (structured layout or editable HTML).
	 *
	 * @param DOMNode $section_node Section element.
	 * @return array<string,mixed>
	 */
	public function parse_landing_section( $section_node ) {
		$section_id = '';
		if ( $section_node instanceof DOMElement ) {
			$section_id = trim( (string) $section_node->getAttribute( 'id' ) );
		}

		$map    = Leadwerk_Volks_Section_Schema::landing_section_id_map();
		$layout = $map[ $section_id ] ?? '';

		if ( '' !== $layout && $this->should_use_structured_layout( $section_node, $layout ) ) {
			switch ( $layout ) {
				case 'volks_hero':
					return $this->parse_hero( $section_node, $section_id );
				case 'volks_intro':
					return $this->parse_intro( $section_node, $section_id );
				case 'volks_valuation':
					return $this->parse_valuation( $section_node, $section_id );
				case 'volks_process':
					return $this->parse_process( $section_node, $section_id );
				case 'volks_region':
					return $this->parse_region( $section_node, $section_id );
				case 'volks_trust':
					return $this->parse_trust( $section_node, $section_id );
				case 'volks_cta':
					return $this->parse_cta( $section_node, $section_id );
				case 'volks_contact':
					return $this->parse_contact( $section_node, $section_id );
			}
		}

		return $this->parse_editable_html_section( $section_node );
	}

	/**
	 * Skip structured layout when markup clearly does not match (e.g. wizard vs. valuation cards).
	 *
	 * @param DOMNode $section_node Section node.
	 * @param string  $layout       Layout key.
	 * @return bool
	 */
	protected function should_use_structured_layout( $section_node, $layout ) {
		if ( ! $section_node instanceof DOMElement ) {
			return false;
		}

		if ( 'volks_valuation' === $layout ) {
			$cards = $this->query_nodes( $section_node, './/*[contains(@class,"valuation-option-card")]' );
			return ! empty( $cards );
		}

		if ( 'volks_hero' === $layout ) {
			if ( $this->query_nodes( $section_node, './/*[contains(@class,"hero-slider")]' ) ) {
				return false;
			}
			return (bool) $this->query_nodes( $section_node, './/*[contains(@class,"hero-title") or self::h1]' );
		}

		if ( 'volks_process' === $layout ) {
			if ( $this->query_nodes( $section_node, './/*[contains(@class,"process-reel")]' ) ) {
				return false;
			}
		}

		if ( 'volks_intro' === $layout ) {
			if ( $this->query_nodes( $section_node, './/*[contains(@class,"editorial-section") or contains(@class,"editorial-split")]' ) ) {
				return false;
			}
			return (bool) $this->query_nodes( $section_node, './/*[contains(@class,"intro-title") or contains(@class,"intro-lead")]' );
		}

		if ( 'volks_region' === $layout ) {
			if ( $this->query_nodes( $section_node, './/*[contains(@class,"region-section--mallorca")]' ) ) {
				return false;
			}
		}

		if ( 'volks_trust' === $layout ) {
			if ( $this->query_nodes( $section_node, './/*[contains(@class,"trust-section--mallorca")]' ) ) {
				return false;
			}
		}

		if ( 'volks_cta' === $layout ) {
			if ( $this->query_nodes( $section_node, './/*[contains(@class,"cta-section--mallorca")]' ) ) {
				return false;
			}
		}

		if ( 'volks_contact' === $layout ) {
			return (bool) $this->query_nodes( $section_node, './/*[contains(@class,"home-contact")]' );
		}

		return true;
	}

	/**
	 * @param DOMNode $section_node Section element.
	 * @return array<string,mixed>
	 */
	public function parse_home_section( $section_node ) {
		$section_id = '';
		if ( $section_node instanceof DOMElement ) {
			$section_id = trim( (string) $section_node->getAttribute( 'id' ) );
		}

		$map    = Leadwerk_Volks_Section_Schema::home_section_id_map();
		$layout = $map[ $section_id ] ?? '';

		switch ( $layout ) {
			case 'volks_hero':
				return $this->parse_hero( $section_node, $section_id );
			case 'volks_intro':
				return $this->parse_intro( $section_node, $section_id );
			case 'volks_valuation':
				return $this->parse_valuation( $section_node, $section_id );
			case 'volks_process':
				return $this->parse_process( $section_node, $section_id );
			case 'volks_region':
				return $this->parse_region( $section_node, $section_id );
			case 'volks_weil_slider':
				return $this->parse_weil_slider( $section_node, $section_id );
			case 'volks_trust':
				return $this->parse_trust( $section_node, $section_id );
			case 'volks_cta':
				return $this->parse_cta( $section_node, $section_id );
			case 'volks_contact':
				return $this->parse_contact( $section_node, $section_id );
			default:
				return $this->call_filler( 'parse_volks_html_section', array( $section_node ) );
		}
	}

	/**
	 * Parse one non-home landing section into an editable HTML shell plus individual fields.
	 *
	 * @param DOMNode $section_node Section element.
	 * @return array<string,mixed>
	 */
	public function parse_editable_html_section( $section_node ) {
		$section_id = '';
		if ( $section_node instanceof DOMElement ) {
			$section_id = trim( (string) $section_node->getAttribute( 'id' ) );
		}

		$html = $section_node instanceof DOMNode ? (string) $section_node->ownerDocument->saveHTML( $section_node ) : '';
		$html = (string) $this->call_filler( 'rewrite_volks_html_links', array( $html ) );

		$headings = array();
		foreach ( $this->query_nodes( $section_node, './/*[self::h1 or self::h2 or self::h3]' ) as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$headings[] = array(
				'level' => strtolower( (string) $node->nodeName ),
				'html'  => $this->node_inner_html( $node ),
			);
		}

		$paragraphs = array();
		foreach ( $this->query_nodes( $section_node, './/p[normalize-space()][not(contains(@class,"section-eyebrow"))]' ) as $node ) {
			if ( ! $node instanceof DOMNode ) {
				continue;
			}
			$text = $this->node_plain_text( $node );
			if ( '' !== $text ) {
				$paragraphs[] = array( 'text' => $text );
			}
		}

		$links = array();
		foreach ( $this->query_nodes( $section_node, './/a[@href]' ) as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$class = (string) $node->getAttribute( 'class' );
			if ( false !== strpos( $class, 'intent-tile' ) ) {
				continue;
			}
			$label = $this->node_plain_text( $node );
			$href  = trim( (string) $node->getAttribute( 'href' ) );
			if ( '' === $label && '' === $href ) {
				continue;
			}
			$links[] = array(
				'label' => $label,
				'url'   => (string) $this->call_filler( 'resolve_volks_import_href', array( $href ) ),
			);
		}

		$images = array();
		foreach ( $this->query_nodes( $section_node, './/img[@src]' ) as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$images[] = array(
				'image' => $this->image_id( (string) $node->getAttribute( 'src' ) ),
				'alt'   => trim( (string) $node->getAttribute( 'alt' ) ),
			);
		}

		$background_images = array();
		foreach ( $this->query_nodes( $section_node, './/*[@style][contains(@style,"url(")]' ) as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$image_id = $this->image_id_from_style( (string) $node->getAttribute( 'style' ) );
			$background_images[] = array(
				'image'     => max( 0, (int) $image_id ),
				'css_class' => '',
			);
		}

		foreach ( $this->query_nodes( $section_node, './/div[contains(@class,"hero-static-bg")]' ) as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$image_id = $this->image_id_from_style( (string) $node->getAttribute( 'style' ) );
			if ( $image_id <= 0 ) {
				$image_id = $this->parse_hero_background_image( $section_node );
			}
			if ( $image_id > 0 ) {
				$background_images[] = array(
					'image'     => $image_id,
					'css_class' => '',
				);
			}
		}

		$this->append_css_class_background_images( $section_node, $background_images );

		$css_section_background       = 0;
		$css_section_background_class = '';
		foreach ( $background_images as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$class_key = trim( (string) ( $row['css_class'] ?? '' ) );
			if ( '' === $class_key ) {
				continue;
			}
			$image_id = (int) ( $row['image'] ?? 0 );
			if ( $image_id > 0 ) {
				$css_section_background       = $image_id;
				$css_section_background_class = $class_key;
				break;
			}
		}

		return array(
			'acf_fc_layout'                => 'volks_editable_html_section',
			'section_id'                   => $section_id,
			'admin_label'                  => $this->build_admin_label( $section_id, $headings ),
			'original_html'                => $html,
			'headings'                     => $headings,
			'paragraphs'                   => $paragraphs,
			'links'                        => $links,
			'images'                       => $images,
			'background_images'            => $background_images,
			'css_section_background'       => $css_section_background,
			'css_section_background_class' => $css_section_background_class,
		);
	}

	/**
	 * @param string $method Method name on filler.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	protected function call_filler( $method, array $args = array() ) {
		$callable = \Closure::bind(
			function ( $name, $arguments ) {
				return $this->{$name}( ...$arguments );
			},
			$this->filler,
			Leadwerk_ACF_Filler::class
		);

		return $callable( $method, $args );
	}

	/**
	 * @param DOMNode $context Context node.
	 * @param string  $query   XPath query.
	 * @return string
	 */
	protected function text( $context, $query ) {
		return (string) $this->call_filler( 'text', array( $context, $query ) );
	}

	/**
	 * @param DOMNode $context Context node.
	 * @param string  $query   XPath query.
	 * @return string
	 */
	protected function html( $context, $query ) {
		return (string) $this->call_filler( 'html', array( $context, $query ) );
	}

	/**
	 * @param DOMNode $context Context node.
	 * @param string  $query   XPath query.
	 * @return string
	 */
	protected function attr( $context, $query, $name ) {
		return (string) $this->call_filler( 'attr', array( $context, $query, $name ) );
	}

	/**
	 * @param string $src Image src.
	 * @return int
	 */
	protected function image_id( $src ) {
		$field = $this->call_filler( 'resolve_image_field', array( $src ) );
		return is_array( $field ) ? (int) ( $field['id'] ?? 0 ) : 0;
	}

	/**
	 * @param string $style Inline style.
	 * @return int
	 */
	protected function image_id_from_style( $style ) {
		$field = $this->call_filler( 'resolve_image_from_style', array( $style ) );
		return is_array( $field ) ? (int) ( $field['id'] ?? 0 ) : 0;
	}

	/**
	 * Theme-CSS backgrounds (no inline url) for editable sections.
	 *
	 * @param DOMElement          $section_node       Section root.
	 * @param array<int,mixed>    $background_images  Mutable rows.
	 * @return void
	 */
	protected function append_css_class_background_images( DOMElement $section_node, array &$background_images ) {
		$class_attr = (string) $section_node->getAttribute( 'class' );
		if ( '' === $class_attr ) {
			return;
		}

		$map = array(
			'editorial-section--sell-bg' => 'Fotos/Mallorca%203.webp',
			'cta-section--mallorca-bg'   => 'Fotos/Mallorca%202.webp',
		);

		foreach ( $map as $class_key => $path ) {
			if ( false === strpos( $class_attr, $class_key ) ) {
				continue;
			}
			foreach ( $background_images as $row ) {
				if ( is_array( $row ) && (string) ( $row['css_class'] ?? '' ) === $class_key ) {
					continue 2;
				}
			}
			$image_id = $this->image_id( $path );
			if ( $image_id > 0 ) {
				$background_images[] = array(
					'image'     => $image_id,
					'css_class' => $class_key,
				);

				// #region agent log
				if ( class_exists( 'Leadwerk_ACF_Filler' ) && method_exists( $this, 'call_filler' ) ) {
					$log_path = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/uploads/volks-debug-4d0610.log' : '';
					if ( '' !== $log_path ) {
						$line = wp_json_encode(
							array(
								'sessionId'    => '4d0610',
								'hypothesisId' => 'H-import-bg',
								'location'     => 'class-leadwerk-volks-section-parser.php:append_css_class_background_images',
								'message'      => 'css section background imported to field',
								'data'         => array(
									'css_class' => $class_key,
									'image_id'  => $image_id,
									'path'      => $path,
								),
								'timestamp'    => (int) round( microtime( true ) * 1000 ),
							)
						) . "\n";
						// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						@file_put_contents( $log_path, $line, FILE_APPEND | LOCK_EX );
					}
				}
				// #endregion
			}
		}
	}

	/**
	 * @param DOMNode $context Context.
	 * @param string  $query   Query.
	 * @return array{label:string,url:string}
	 */
	protected function query_nodes( $context, $query ) {
		$nodes = $this->call_filler( 'query_nodes', array( $context, $query ) );
		return is_array( $nodes ) ? $nodes : array();
	}

	/**
	 * XPath predicate matching one exact CSS class token (avoids weil-slide matching weil-slide-media).
	 *
	 * @param string $tag   Element name, e.g. div.
	 * @param string $token Class token.
	 * @return string
	 */
	protected function xpath_has_class( $tag, $token ) {
		$tag   = preg_replace( '/[^a-z0-9:_-]/i', '', (string) $tag );
		$token = preg_replace( '/[^a-z0-9_-]/i', '', (string) $token );
		if ( '' === $tag || '' === $token ) {
			return '';
		}

		return 'contains(concat(" ", normalize-space(@class), " "), " ' . $token . ' ")';
	}

	/**
	 * Drop duplicate/empty repeater rows produced by legacy class-substring XPath imports.
	 *
	 * @param array<int,array<string,mixed>> $slides Slide rows.
	 * @return array<int,array<string,mixed>>
	 */
	protected function sanitize_weil_slides( array $slides ) {
		$out  = array();
		$seen = array();

		foreach ( $slides as $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}

			$claim = trim( (string) ( $slide['claim'] ?? '' ) );
			$text  = trim( (string) ( $slide['text'] ?? '' ) );
			if ( '' === $claim ) {
				continue;
			}
			if ( '' === $text && empty( $slide['image'] ) ) {
				continue;
			}

			$key = strtolower( $claim );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$out[]        = $slide;
		}

		return $out;
	}

	/**
	 * @param array<int,array<string,mixed>> $stats Region stat rows.
	 * @return array<int,array<string,mixed>>
	 */
	protected function sanitize_region_stats( array $stats ) {
		$out  = array();
		$seen = array();

		foreach ( $stats as $stat ) {
			if ( ! is_array( $stat ) ) {
				continue;
			}

			$number = trim( wp_strip_all_tags( (string) ( $stat['number'] ?? '' ) ) );
			$label  = trim( (string) ( $stat['label'] ?? '' ) );
			if ( '' === $number && '' === $label ) {
				continue;
			}

			$key = strtolower( $number . '|' . $label );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$out[]        = $stat;
		}

		return $out;
	}

	/**
	 * @param DOMNode $node Node.
	 * @return string
	 */
	protected function node_plain_text( $node ) {
		if ( ! $node instanceof DOMNode ) {
			return '';
		}

		$text = html_entity_decode( (string) $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
	}

	/**
	 * @param DOMNode $node Element node.
	 * @return string
	 */
	protected function node_inner_html( $node ) {
		if ( ! $node instanceof DOMNode || ! $node->ownerDocument ) {
			return '';
		}

		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}
		return trim( $html );
	}

	/**
	 * @param string $section_id Section ID.
	 * @param array<int,array<string,string>> $headings Heading rows.
	 * @return string
	 */
	protected function build_admin_label( $section_id, array $headings ) {
		foreach ( $headings as $heading ) {
			$text = $this->node_plain_text_from_html( (string) ( $heading['html'] ?? '' ) );
			if ( '' !== $text ) {
				return $text;
			}
		}
		return '' !== $section_id ? $section_id : 'Sektion';
	}

	/**
	 * @param string $html HTML fragment.
	 * @return string
	 */
	protected function node_plain_text_from_html( $html ) {
		$text = html_entity_decode( (string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
	}

	/**
	 * @param DOMNode $context Context.
	 * @param string  $query   Query.
	 * @return array{label:string,url:string}
	 */
	protected function parse_link( $context, $query ) {
		$node = $this->call_filler( 'first_node', array( $context, $query ) );
		if ( ! $node instanceof DOMElement ) {
			return array( 'label' => '', 'url' => '' );
		}

		$href = trim( (string) $node->getAttribute( 'href' ) );

		return array(
			'label' => $this->node_plain_text( $node ),
			'url'   => (string) $this->call_filler( 'resolve_volks_import_href', array( $href ) ),
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	/**
	 * @param DOMNode $section_node Section.
	 * @return string
	 */
	protected function parse_hero_modifier( $section_node ) {
		if ( ! $section_node instanceof DOMElement ) {
			return '';
		}
		$class = (string) $section_node->getAttribute( 'class' );
		if ( preg_match( '/\b(hero--[a-z0-9_-]+)\b/i', $class, $matches ) ) {
			return sanitize_html_class( $matches[1] );
		}
		return '';
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @return int Attachment ID.
	 */
	protected function parse_hero_background_image( $section_node ) {
		$src = $this->attr( $section_node, './/div[contains(@class,"hero-static-bg")]//img', 'src' );
		if ( '' === $src ) {
			$src = $this->attr( $section_node, './/div[contains(@class,"hero-bg")]//img[1]', 'src' );
		}
		if ( '' !== $src ) {
			return $this->image_id( $src );
		}

		$modifier = $this->parse_hero_modifier( $section_node );
		$defaults = array(
			'hero--bewerten' => 'Fotos/Vertrauen.webp',
			'hero--kauf'     => 'Fotos/11.webp',
			'hero--verkauf'  => 'Fotos/volksimmobilien_verkaufen.webp',
			'hero--mallorca' => 'Fotos/Mallorca 2.webp',
		);
		if ( isset( $defaults[ $modifier ] ) ) {
			return $this->image_id( $defaults[ $modifier ] );
		}

			$static_bg = $this->query_nodes( $section_node, './/div[contains(@class,"hero-static-bg")]' );
		if ( ! empty( $static_bg ) ) {
			return $this->image_id( 'Fotos/Vertrauen.webp' );
		}

		return 0;
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_hero( $section_node, $section_id ) {
		$video_src = $this->attr( $section_node, './/video/source[1]', 'src' );
		$video_id  = 0;
		if ( '' !== $video_src ) {
			$video_id = (int) $this->call_filler(
				'get_attachment_id_by_source',
				array( $this->call_filler( 'resolve_asset_source_path', array( $video_src ) ) )
			);
		}

		$title_html = $this->html( $section_node, './/h1[contains(@class,"hero-title")]' );
		if ( '' === $title_html ) {
			$title_html = $this->html( $section_node, './/h1[1]' );
		}

		$primary   = $this->parse_link( $section_node, './/div[contains(@class,"hero-actions")]//a[contains(@class,"btn-primary")][1]' );
		$secondary = $this->parse_link( $section_node, './/div[contains(@class,"hero-actions")]//a[contains(@class,"btn-outline-light")][1]' );
		if ( '' === $secondary['label'] ) {
			$secondary = $this->parse_link( $section_node, './/div[contains(@class,"hero-actions")]//a[not(contains(@class,"btn-primary"))][1]' );
		}

		$tiles      = array();
		$tile_nodes = $this->query_nodes( $section_node, './/a[contains(@class,"intent-tile")]' );
		if ( is_array( $tile_nodes ) ) {
			foreach ( $tile_nodes as $tile_node ) {
				if ( ! $tile_node instanceof DOMElement ) {
					continue;
				}
				$class = (string) $tile_node->getAttribute( 'class' );
				$modifier = '';
				if ( false !== strpos( $class, 'intent-tile--highlight' ) ) {
					$modifier = 'intent-tile--highlight';
				} elseif ( false !== strpos( $class, 'intent-tile--secondary' ) ) {
					$modifier = 'intent-tile--secondary';
				}
				$tiles[] = array(
					'icon'           => $this->image_id( $this->attr( $tile_node, './/img[1]', 'src' ) ),
					'label'          => $this->text( $tile_node, './/span[contains(@class,"intent-tile-label")]' ),
					'description'    => $this->text( $tile_node, './/span[contains(@class,"intent-tile-desc")]' ),
					'link_url'       => (string) $this->call_filler(
						'resolve_volks_import_href',
						array( trim( (string) $tile_node->getAttribute( 'href' ) ) )
					),
					'style_modifier' => $modifier,
				);
			}
		}

		return array(
			'acf_fc_layout'       => 'volks_hero',
			'section_id'          => $section_id,
			'hero_modifier'       => $this->parse_hero_modifier( $section_node ),
			'background_image'    => $this->parse_hero_background_image( $section_node ),
			'eyebrow'             => $this->text( $section_node, './/p[contains(@class,"section-eyebrow")][1]' ),
			'title'               => $title_html,
			'subtitle'            => $this->text( $section_node, './/p[contains(@class,"hero-sub")]' ),
			'video'               => $video_id,
			'btn_primary_label'   => $primary['label'],
			'btn_primary_url'     => $primary['url'],
			'btn_secondary_label' => $secondary['label'],
			'btn_secondary_url'   => $secondary['url'],
			'intent_tiles'        => $tiles,
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_intro( $section_node, $section_id ) {
		$primary   = $this->parse_link( $section_node, './/div[contains(@class,"intro-cta") or contains(@class,"section-cta-row")]//a[contains(@class,"btn-primary")][1]' );
		$secondary = $this->parse_link( $section_node, './/div[contains(@class,"intro-cta") or contains(@class,"section-cta-row")]//a[contains(@class,"btn-outline")][1]' );
		$stat_cta  = $this->parse_link( $section_node, './/a[contains(@class,"trust-stat-banner__cta")][1]' );
		$video_src = $this->attr( $section_node, './/div[contains(@class,"intro-video-stack")][1]', 'data-video-src' );
		$video_id  = 0;
		if ( '' !== $video_src ) {
			$video_id = (int) $this->call_filler(
				'get_attachment_id_by_source',
				array( $this->call_filler( 'resolve_asset_source_path', array( $video_src ) ) )
			);
		}

		return array(
			'acf_fc_layout'       => 'volks_intro',
			'section_id'          => $section_id,
			'eyebrow'             => $this->text( $section_node, './/span[contains(@class,"section-eyebrow")][1]' ),
			'title'               => $this->text( $section_node, './/h2[contains(@class,"intro-title")]' ),
			'lead'                => $this->text( $section_node, './/p[contains(@class,"intro-lead")]' ),
			'body'                => $this->text( $section_node, './/div[contains(@class,"intro-text")]//p[not(contains(@class,"intro-lead"))][1]' ),
			'cta_primary_label'   => $primary['label'],
			'cta_primary_url'     => $primary['url'],
			'cta_secondary_label' => $secondary['label'],
			'cta_secondary_url'   => $secondary['url'],
			'stat_value'          => $this->html( $section_node, './/span[contains(@class,"trust-stat-banner__value")]' ),
			'stat_title'          => $this->text( $section_node, './/span[contains(@class,"trust-stat-banner__title")]' ),
			'stat_desc'           => $this->text( $section_node, './/span[contains(@class,"trust-stat-banner__desc")]' ),
			'stat_cta_label'      => $stat_cta['label'],
			'stat_cta_url'        => $stat_cta['url'],
			'video_poster'        => $this->image_id( $this->attr( $section_node, './/img[contains(@class,"intro-video-teaser__poster")][1]', 'src' ) ),
			'video'               => $video_id,
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_valuation( $section_node, $section_id ) {
		$cards = array();
		$card_nodes = $this->query_nodes( $section_node, './/article[contains(@class,"valuation-option-card")]' );
		if ( is_array( $card_nodes ) ) {
			foreach ( $card_nodes as $card_node ) {
				if ( ! $card_node instanceof DOMElement ) {
					continue;
				}
				$class = (string) $card_node->getAttribute( 'class' );
				$features = array();
				$feature_nodes = $this->query_nodes( $card_node, './/ul[contains(@class,"valuation-option-card__features")]//li' );
				foreach ( $feature_nodes as $feature_node ) {
					$features[] = $this->node_plain_text( $feature_node );
				}
				$cta = $this->parse_link( $card_node, './/a[contains(@class,"valuation-option-card__cta")][1]' );
				$promo = $this->parse_link( $card_node, './/a[contains(@class,"valuation-promo-badge")][1]' );

				$cards[] = array(
					'badge'        => $this->text( $card_node, './/span[contains(@class,"valuation-option-card__badge")][1]' ),
					'icon'         => $this->image_id( $this->attr( $card_node, './/img[1]', 'src' ) ),
					'kicker'       => $this->text( $card_node, './/span[contains(@class,"valuation-option-card__kicker")]' ),
					'price_main'   => $this->html( $card_node, './/span[contains(@class,"valuation-option-card__price-main")]' ),
					'price_sub'    => $this->text( $card_node, './/span[contains(@class,"valuation-option-card__price-sub")]' ),
					'price_strike' => $this->html( $card_node, './/span[contains(@class,"valuation-option-card__price-strike")]' ),
					'cta_label'    => $cta['label'],
					'cta_url'      => $cta['url'],
					'features'     => implode( "\n", array_filter( $features ) ),
					'is_featured'  => false !== strpos( $class, 'valuation-option-card--featured' ),
					'promo_amount' => $this->html( $card_node, './/span[contains(@class,"valuation-promo-badge__amount")]' ),
					'promo_title'  => $this->text( $card_node, './/span[contains(@class,"valuation-promo-badge__title")]' ),
					'promo_sub'    => $this->text( $card_node, './/span[contains(@class,"valuation-promo-badge__sub")]' ),
					'promo_url'    => $promo['url'],
				);
			}
		}

		return array(
			'acf_fc_layout' => 'volks_valuation',
			'section_id'    => $section_id,
			'eyebrow'       => $this->text( $section_node, './/div[contains(@class,"section-header")]//span[contains(@class,"section-eyebrow")]' ),
			'title'         => $this->text( $section_node, './/h2[contains(@class,"section-title")]' ),
			'subtitle'      => $this->text( $section_node, './/p[contains(@class,"section-sub")]' ),
			'cards'         => $cards,
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_process( $section_node, $section_id ) {
		$steps = array();
		$step_nodes = $this->query_nodes( $section_node, './/div[contains(@class,"process-step")]' );
		if ( is_array( $step_nodes ) ) {
			foreach ( $step_nodes as $step_node ) {
				$steps[] = array(
					'number' => $this->text( $step_node, './/span[contains(@class,"step-number")]' ),
					'title'  => $this->text( $step_node, './/h3[1]' ),
					'text'   => $this->text( $step_node, './/p[1]' ),
				);
			}
		}

		$primary   = $this->parse_link( $section_node, './/div[contains(@class,"section-cta-row")]//a[contains(@class,"btn-primary")][1]' );
		$secondary = $this->parse_link( $section_node, './/div[contains(@class,"section-cta-row")]//a[contains(@class,"btn-outline")][1]' );

		return array(
			'acf_fc_layout'       => 'volks_process',
			'section_id'          => $section_id,
			'eyebrow'             => $this->text( $section_node, './/div[contains(@class,"section-header")]//span[contains(@class,"section-eyebrow")]' ),
			'title'               => $this->text( $section_node, './/h2[contains(@class,"section-title")]' ),
			'subtitle'            => $this->text( $section_node, './/p[contains(@class,"section-sub")]' ),
			'steps'               => $steps,
			'cta_primary_label'   => $primary['label'],
			'cta_primary_url'     => $primary['url'],
			'cta_secondary_label' => $secondary['label'],
			'cta_secondary_url'   => $secondary['url'],
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_region( $section_node, $section_id ) {
		$stats      = array();
		$class_expr = $this->xpath_has_class( 'div', 'region-stat' );
		$stat_nodes = '' !== $class_expr
			? $this->query_nodes( $section_node, './/div[' . $class_expr . ']' )
			: array();
		if ( is_array( $stat_nodes ) ) {
			foreach ( $stat_nodes as $stat_node ) {
				$stats[] = array(
					'number' => $this->html( $stat_node, './/span[contains(@class,"region-stat-number")]' ),
					'label'  => $this->text( $stat_node, './/span[contains(@class,"region-stat-label")]' ),
				);
			}
		}

		$map_svg  = '';
		$svg_node = $this->call_filler( 'first_node', array( $section_node, './/div[contains(@class,"kreis-map-svg-host")]/*[1]' ) );
		if ( $svg_node instanceof DOMNode && $svg_node->ownerDocument instanceof DOMDocument ) {
			$map_svg = (string) $svg_node->ownerDocument->saveHTML( $svg_node );
			if ( function_exists( 'volks_normalize_svg_markup' ) ) {
				$map_svg = volks_normalize_svg_markup( $map_svg );
			} else {
				$map_svg = preg_replace( '/\bviewbox\s*=/i', 'viewBox=', $map_svg );
			}
		}

		$paragraphs = $this->query_nodes( $section_node, './/div[contains(@class,"region-intro")]//p' );
		$p1         = isset( $paragraphs[0] ) ? $this->node_plain_text( $paragraphs[0] ) : '';
		$p2         = isset( $paragraphs[1] ) ? $this->node_plain_text( $paragraphs[1] ) : '';

		$title_html = $this->html( $section_node, './/h2[contains(@class,"region-title")]' );

		return array(
			'acf_fc_layout' => 'volks_region',
			'section_id'    => $section_id,
			'eyebrow'       => $this->text( $section_node, './/span[contains(@class,"section-eyebrow")][1]' ),
			'title'         => $title_html,
			'paragraph_1'   => $p1,
			'paragraph_2'   => $p2,
			'stats'         => $this->sanitize_region_stats( $stats ),
			'map_svg'       => $map_svg,
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_weil_slider( $section_node, $section_id ) {
		$slides     = array();
		$class_expr = $this->xpath_has_class( 'div', 'weil-slide' );
		$slide_nodes = '' !== $class_expr
			? $this->query_nodes(
				$section_node,
				'.//div[contains(@class,"weil-slider-track")]/div[' . $class_expr . ']'
			)
			: array();
		if ( is_array( $slide_nodes ) ) {
			foreach ( $slide_nodes as $slide_node ) {
				$style = '';
				$media = $this->call_filler( 'first_node', array( $slide_node, './/div[contains(@class,"weil-slide-media")]' ) );
				if ( $media instanceof DOMElement ) {
					$style = (string) $media->getAttribute( 'style' );
				}
				$slides[] = array(
					'image' => $this->image_id_from_style( $style ),
					'claim' => $this->text( $slide_node, './/h3[contains(@class,"weil-slide-claim")]' ),
					'text'  => $this->text( $slide_node, './/div[contains(@class,"weil-slide-content")]//p[1]' ),
				);
			}
		}

		return array(
			'acf_fc_layout' => 'volks_weil_slider',
			'section_id'    => $section_id,
			'slides'        => $this->sanitize_weil_slides( $slides ),
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_trust( $section_node, $section_id ) {
		$cards = array();
		$card_nodes = $this->query_nodes( $section_node, './/div[contains(@class,"trust-bento-card")]' );
		if ( is_array( $card_nodes ) ) {
			foreach ( $card_nodes as $card_node ) {
				$cards[] = array(
					'title' => $this->text( $card_node, './/h3[1]' ),
					'text'  => $this->text( $card_node, './/p[1]' ),
				);
			}
		}

		return array(
			'acf_fc_layout'       => 'volks_trust',
			'section_id'          => $section_id,
			'eyebrow'             => $this->text( $section_node, './/div[contains(@class,"section-header")]//span[contains(@class,"section-eyebrow")]' ),
			'title'               => $this->text( $section_node, './/h2[contains(@class,"section-title")]' ),
			'hero_image'          => $this->image_id( $this->attr( $section_node, './/div[contains(@class,"trust-bento-image")]//img[1]', 'src' ) ),
			'hero_image_alt'      => $this->attr( $section_node, './/div[contains(@class,"trust-bento-image")]//img[1]', 'alt' ),
			'badge_24h_label'     => $this->text( $section_node, './/div[contains(@class,"trust-badge--dark")]//span[contains(@class,"trust-badge-label")][1]' ),
			'badge_24h_sub'       => $this->text( $section_node, './/div[contains(@class,"trust-badge--dark")]//span[contains(@class,"trust-badge-sub")]' ),
			'badge_cert_label'    => $this->text( $section_node, './/div[contains(@class,"trust-badge--accent")]//span[contains(@class,"trust-badge-label")]' ),
			'badge_stat_number'   => $this->html( $section_node, './/div[contains(@class,"trust-badge--stat")]//span[contains(@class,"trust-badge-number")]' ),
			'badge_stat_label'    => $this->text( $section_node, './/div[contains(@class,"trust-badge--stat")]//span[contains(@class,"trust-badge-sub")]' ),
			'cards'               => $cards,
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_cta( $section_node, $section_id ) {
		$main      = $this->parse_link( $section_node, './/div[contains(@class,"cta-actions--main")]//a[1]' );
		$secondary = $this->parse_link( $section_node, './/div[contains(@class,"cta-actions--main")]//a[2]' );
		$tertiary  = $this->parse_link( $section_node, './/div[contains(@class,"cta-actions--secondary")]//a[contains(@class,"btn-outline-accent")][1]' );
		$email     = $this->parse_link( $section_node, './/div[contains(@class,"cta-actions--secondary")]//a[contains(@class,"btn-ghost-light")][1]' );

		return array(
			'acf_fc_layout'      => 'volks_cta',
			'section_id'         => $section_id,
			'eyebrow'            => $this->text( $section_node, './/span[contains(@class,"section-eyebrow")][1]' ),
			'title'              => $this->text( $section_node, './/h2[1]' ),
			'lead'               => $this->text( $section_node, './/p[contains(@class,"cta-lead")]' ),
			'btn_main_label'     => $main['label'],
			'btn_main_url'       => $main['url'],
			'btn_secondary_label'=> $secondary['label'],
			'btn_secondary_url'  => $secondary['url'],
			'btn_tertiary_label' => $tertiary['label'],
			'btn_tertiary_url'   => $tertiary['url'],
			'btn_email_label'    => $email['label'],
			'btn_email_url'      => $email['url'],
			'divider_text'       => $this->text( $section_node, './/p[contains(@class,"cta-divider")]//span' ),
			'microcopy'          => $this->text( $section_node, './/p[contains(@class,"cta-micro")]' ),
		);
	}

	/**
	 * @param DOMNode $section_node Section.
	 * @param string  $section_id   ID.
	 * @return array<string,mixed>
	 */
	protected function parse_contact( $section_node, $section_id ) {
		$cards = array();
		$card_nodes = $this->query_nodes( $section_node, './/div[contains(@class,"home-contact-info-card")]' );
		if ( is_array( $card_nodes ) ) {
			foreach ( $card_nodes as $card_node ) {
				$icon_html = '';
				$icon_node = $this->call_filler( 'first_node', array( $card_node, './/*[contains(@class,"home-contact-info-card__icon")][1]' ) );
				if ( $icon_node instanceof DOMNode && $icon_node->ownerDocument instanceof DOMDocument ) {
					foreach ( $icon_node->childNodes as $child ) {
						$icon_html .= $icon_node->ownerDocument->saveHTML( $child );
					}
					$icon_html = trim( $icon_html );
				}

				$cards[] = array(
					'title'     => $this->text( $card_node, './/h3[1]' ),
					'icon_html' => $icon_html,
					'text_html' => $this->html( $card_node, './/p[contains(@class,"home-contact-info-card__text")]' ),
					'note'      => $this->text( $card_node, './/p[contains(@class,"home-contact-info-card__note")]' ),
				);
			}
		}

		$form_html = $this->html( $section_node, './/form[contains(@class,"home-contact-form")]' );

		return array(
			'acf_fc_layout' => 'volks_contact',
			'section_id'    => $section_id,
			'eyebrow'       => $this->text( $section_node, './/div[contains(@class,"section-header")]//span[contains(@class,"section-eyebrow")]' ),
			'title'         => $this->text( $section_node, './/h2[contains(@class,"section-title")]' ),
			'subtitle'      => $this->text( $section_node, './/p[contains(@class,"section-sub")]' ),
			'info_cards'    => $cards,
			'form_html'     => $form_html,
			'form_hint'     => $this->text( $section_node, './/p[contains(@class,"home-contact-hint")]' ),
		);
	}
}
