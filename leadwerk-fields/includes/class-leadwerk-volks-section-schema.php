<?php
/**
 * Structured field layouts for volksimmobilien homepage sections.
 *
 * @package Leadwerk_Fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Volks homepage section layouts (granular text / image / repeater fields).
 */
class Leadwerk_Volks_Section_Schema {

	/**
	 * Layouts for volks_home_sections (plus html_section fallback).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function home_layouts() {
		return array(
			'volks_hero'           => self::layout_hero(),
			'volks_intro'          => self::layout_intro(),
			'volks_valuation'      => self::layout_valuation(),
			'volks_process'        => self::layout_process(),
			'volks_region'         => self::layout_region(),
			'volks_weil_slider'    => self::layout_weil_slider(),
			'volks_trust'          => self::layout_trust(),
			'volks_cta'            => self::layout_cta(),
			'volks_contact'        => self::layout_contact(),
			'html_section'         => Leadwerk_Content_Schema::get_layout_volks_html_section(),
		);
	}

	/**
	 * Generic editable layouts for imported Volks landing pages.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function landing_layouts() {
		$home = self::home_layouts();

		return array_merge(
			$home,
			array(
				'volks_editable_html_section' => self::layout_editable_html_section(),
			)
		);
	}

	/**
	 * Landing field groups that must keep full section HTML (slider, reels, editorial layouts).
	 *
	 * @return string[]
	 */
	public static function landing_fields_preserve_full_html() {
		return array(
			'volks_mallorca_sections',
			'volks_ausland_sections',
			'volks_kaufen_sections',
			'volks_verkaufen_sections',
			'volks_bewerten_sections',
		);
	}

	/**
	 * @param string $field_name ACF field group name.
	 * @return bool
	 */
	public static function landing_field_preserves_full_html( $field_name ) {
		return in_array( (string) $field_name, self::landing_fields_preserve_full_html(), true );
	}

	/**
	 * Labels for CSS-class section backgrounds (import → background_images repeater).
	 *
	 * @return array<string,string>
	 */
	public static function editable_background_css_labels() {
		return array(
			''                             => 'Inline (style-Attribut)',
			'editorial-section--sell-bg'   => 'Sektions-Hintergrund Verkaufen',
			'cta-section--mallorca-bg'     => 'Sektions-Hintergrund CTA (Mallorca)',
		);
	}

	/**
	 * Map section DOM id → structured layout for landing pages (unmapped → editable HTML).
	 *
	 * @return array<string,string>
	 */
	public static function landing_section_id_map() {
		return array(
			'hero'                    => 'volks_hero',
			'einleitung'              => 'volks_intro',
			'einstieg'                => 'volks_intro',
			'persoenliche-bewertung'  => 'volks_valuation',
			'prozess'                 => 'volks_process',
			'ablauf'                  => 'volks_process',
			'regionen'                => 'volks_region',
			'region'                  => 'volks_region',
			'marktgebiet'             => 'volks_region',
			'vertrauen'               => 'volks_trust',
			'vertrauen-kauf'          => 'volks_trust',
			'kontakt-cta'             => 'volks_cta',
			'kontakt-abschluss'       => 'volks_cta',
			'kontakt-formular'        => 'volks_contact',
		);
	}

	/**
	 * Map section DOM id → layout key for homepage import.
	 *
	 * @return array<string,string>
	 */
	public static function home_section_id_map() {
		return array(
			'hero'              => 'volks_hero',
			'einleitung'        => 'volks_intro',
			'wertermittlung'    => 'volks_valuation',
			'prozess'           => 'volks_process',
			'regionen'          => 'volks_region',
			'haltung'           => 'volks_weil_slider',
			'vertrauen'         => 'volks_trust',
			'kontakt-cta'       => 'volks_cta',
			'kontakt-formular'  => 'volks_contact',
		);
	}

	/**
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	private static function text( $label ) {
		return array( 'label' => $label, 'type' => 'text' );
	}

	/**
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	private static function textarea( $label ) {
		return array( 'label' => $label, 'type' => 'textarea' );
	}

	/**
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	private static function editor( $label ) {
		return array( 'label' => $label, 'type' => 'classic_editor' );
	}

	/**
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	private static function heading( $label ) {
		return array( 'label' => $label, 'type' => 'heading_html' );
	}

	/**
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	private static function url( $label ) {
		return array( 'label' => $label, 'type' => 'url' );
	}

	/**
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	private static function image( $label ) {
		return array( 'label' => $label, 'type' => 'image' );
	}

	/**
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	private static function video( $label ) {
		return array( 'label' => $label, 'type' => 'video' );
	}

	/**
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	private static function svg_code( $label ) {
		return array(
			'label' => $label,
			'type'  => 'svg_code',
		);
	}

	/**
	 * @param string $label Label.
	 * @param array<string,array<string,mixed>> $fields Sub-fields.
	 * @param string|null $add_text Add button label.
	 * @return array<string,mixed>
	 */
	private static function repeater( $label, array $fields, $add_text = null ) {
		return array(
			'label'    => $label,
			'type'     => 'repeater',
			'fields'   => $fields,
			'add_text' => $add_text ?: 'Eintrag hinzufuegen',
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_hero() {
		return array(
			'label'    => 'Hero',
			'template' => 'volks_hero',
			'fields'   => array(
				'section_id'          => self::text( 'Section-ID (Anker)' ),
				'hero_modifier'       => self::text( 'CSS-Modifikator (z. B. hero--bewerten)' ),
				'background_image'    => self::image( 'Hintergrundbild (statischer Hero)' ),
				'eyebrow'             => self::text( 'Eyebrow' ),
				'title'               => self::heading( 'Hauptueberschrift (mit Highlight-Span)' ),
				'subtitle'            => self::textarea( 'Untertitel' ),
				'video'               => self::video( 'Hintergrund-Video' ),
				'btn_primary_label'   => self::text( 'Button 1 – Text' ),
				'btn_primary_url'     => self::url( 'Button 1 – Link' ),
				'btn_secondary_label' => self::text( 'Button 2 – Text' ),
				'btn_secondary_url'   => self::url( 'Button 2 – Link' ),
				'intent_tiles'        => self::repeater(
					'Intent-Kacheln',
					array(
						'icon'            => self::image( 'Icon' ),
						'label'           => self::text( 'Titel' ),
						'description'     => self::text( 'Beschreibung' ),
						'link_url'        => self::url( 'Link' ),
						'style_modifier'  => self::text( 'CSS-Modifikator (z. B. intent-tile--highlight)' ),
					),
					'Kachel hinzufuegen'
				),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_intro() {
		return array(
			'label'    => 'Einleitung',
			'template' => 'volks_intro',
			'fields'   => array(
				'section_id'          => self::text( 'Section-ID' ),
				'eyebrow'             => self::text( 'Eyebrow' ),
				'title'               => self::text( 'Ueberschrift' ),
				'lead'                => self::textarea( 'Lead-Absatz' ),
				'body'                => self::textarea( 'Zweiter Absatz' ),
				'cta_primary_label'   => self::text( 'CTA 1 – Text' ),
				'cta_primary_url'     => self::url( 'CTA 1 – Link' ),
				'cta_secondary_label' => self::text( 'CTA 2 – Text' ),
				'cta_secondary_url'     => self::url( 'CTA 2 – Link' ),
				'stat_value'          => self::text( 'Statistik – Wert (z. B. 80 %)' ),
				'stat_title'          => self::text( 'Statistik – Titel' ),
				'stat_desc'           => self::textarea( 'Statistik – Beschreibung' ),
				'stat_cta_label'      => self::text( 'Statistik – Button-Text' ),
				'stat_cta_url'        => self::url( 'Statistik – Button-Link' ),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_valuation() {
		return array(
			'label'    => 'Wertermittlung',
			'template' => 'volks_valuation',
			'fields'   => array(
				'section_id' => self::text( 'Section-ID' ),
				'eyebrow'    => self::text( 'Eyebrow' ),
				'title'      => self::text( 'Ueberschrift' ),
				'subtitle'   => self::textarea( 'Untertitel' ),
				'cards'      => self::repeater(
					'Optionen-Karten',
					array(
						'badge'         => self::text( 'Badge' ),
						'icon'          => self::image( 'Icon' ),
						'kicker'        => self::text( 'Kicker' ),
						'price_main'    => self::text( 'Preis (Hauptzeile)' ),
						'price_sub'     => self::text( 'Preis (Unterzeile)' ),
						'price_strike'  => self::text( 'Durchgestrichener Preis (optional)' ),
						'cta_label'     => self::text( 'Button-Text' ),
						'cta_url'       => self::url( 'Button-Link' ),
						'features'      => self::textarea( 'Vorteile (eine Zeile pro Punkt)' ),
						'is_featured'   => array(
							'label' => 'Hervorgehobene Karte',
							'type'  => 'checkbox',
						),
						'promo_amount'  => self::text( 'Promo-Badge – Betrag (optional)' ),
						'promo_title'   => self::text( 'Promo-Badge – Titel (optional)' ),
						'promo_sub'     => self::text( 'Promo-Badge – Unterzeile (optional)' ),
						'promo_url'     => self::url( 'Promo-Badge – Link (optional)' ),
					),
					'Karte hinzufuegen'
				),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_process() {
		return array(
			'label'    => 'Prozess',
			'template' => 'volks_process',
			'fields'   => array(
				'section_id'          => self::text( 'Section-ID' ),
				'eyebrow'             => self::text( 'Eyebrow' ),
				'title'               => self::text( 'Ueberschrift' ),
				'subtitle'            => self::textarea( 'Untertitel' ),
				'steps'               => self::repeater(
					'Schritte',
					array(
						'number' => self::text( 'Nummer' ),
						'title'  => self::text( 'Titel' ),
						'text'   => self::textarea( 'Text' ),
					),
					'Schritt hinzufuegen'
				),
				'cta_primary_label'   => self::text( 'CTA 1 – Text' ),
				'cta_primary_url'     => self::url( 'CTA 1 – Link' ),
				'cta_secondary_label' => self::text( 'CTA 2 – Text' ),
				'cta_secondary_url'   => self::url( 'CTA 2 – Link' ),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_region() {
		return array(
			'label'    => 'Regionen',
			'template' => 'volks_region',
			'fields'   => array(
				'section_id'  => self::text( 'Section-ID' ),
				'eyebrow'     => self::text( 'Eyebrow' ),
				'title'       => self::heading( 'Ueberschrift (Zeilenumbruch mit &lt;br&gt;)' ),
				'paragraph_1' => self::textarea( 'Absatz 1' ),
				'paragraph_2' => self::textarea( 'Absatz 2' ),
				'stats'       => self::repeater(
					'Statistiken',
					array(
						'number' => self::text( 'Zahl' ),
						'label'  => self::text( 'Label' ),
					),
					'Statistik hinzufuegen'
				),
				'map_svg'     => array(
					'label' => 'Karten-SVG',
					'type'  => 'svg_code',
				),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_weil_slider() {
		return array(
			'label'    => 'Weil-Slider',
			'template' => 'volks_weil_slider',
			'fields'   => array(
				'section_id' => self::text( 'Section-ID' ),
				'slides'     => self::repeater(
					'Slides',
					array(
						'image' => self::image( 'Hintergrundbild' ),
						'claim' => self::text( 'Claim (Ueberschrift)' ),
						'text'  => self::textarea( 'Text' ),
					),
					'Slide hinzufuegen'
				),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_trust() {
		return array(
			'label'    => 'Vertrauen',
			'template' => 'volks_trust',
			'fields'   => array(
				'section_id'        => self::text( 'Section-ID' ),
				'eyebrow'           => self::text( 'Eyebrow' ),
				'title'             => self::text( 'Ueberschrift' ),
				'hero_image'        => self::image( 'Hauptbild' ),
				'hero_image_alt'    => self::text( 'Hauptbild – Alt-Text' ),
				'badge_24h_label'   => self::text( 'Badge 24h – Label' ),
				'badge_24h_sub'     => self::text( 'Badge 24h – Unterzeile' ),
				'badge_cert_label'  => self::text( 'Badge Zertifikat – Label' ),
				'badge_stat_number' => self::text( 'Badge Statistik – Zahl' ),
				'badge_stat_label'  => self::text( 'Badge Statistik – Label' ),
				'cards'             => self::repeater(
					'Karten',
					array(
						'title' => self::text( 'Titel' ),
						'text'  => self::textarea( 'Text' ),
					),
					'Karte hinzufuegen'
				),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_cta() {
		return array(
			'label'    => 'Kontakt-CTA',
			'template' => 'volks_cta',
			'fields'   => array(
				'section_id'           => self::text( 'Section-ID' ),
				'eyebrow'              => self::text( 'Eyebrow' ),
				'title'                => self::text( 'Ueberschrift' ),
				'lead'                 => self::textarea( 'Lead' ),
				'btn_main_label'       => self::text( 'Hauptbutton – Text' ),
				'btn_main_url'         => self::url( 'Hauptbutton – Link' ),
				'btn_secondary_label'  => self::text( 'Sekundaerbutton – Text' ),
				'btn_secondary_url'    => self::url( 'Sekundaerbutton – Link' ),
				'btn_tertiary_label'   => self::text( 'Button Bewerten – Text' ),
				'btn_tertiary_url'     => self::url( 'Button Bewerten – Link' ),
				'btn_email_label'      => self::text( 'Button E-Mail – Text' ),
				'btn_email_url'        => self::url( 'Button E-Mail – Link' ),
				'divider_text'         => self::text( 'Trennzeile' ),
				'microcopy'            => self::text( 'Fusstext' ),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function layout_contact() {
		return array(
			'label'    => 'Kontakt & Formular',
			'template' => 'volks_contact',
			'fields'   => array(
				'section_id' => self::text( 'Section-ID' ),
				'eyebrow'    => self::text( 'Eyebrow' ),
				'title'      => self::text( 'Ueberschrift' ),
				'subtitle'   => self::textarea( 'Untertitel' ),
				'info_cards' => self::repeater(
					'Info-Karten',
					array(
						'title'      => self::text( 'Titel' ),
						'icon_html'  => self::svg_code( 'Icon (SVG)' ),
						'text_html'  => self::editor( 'Text (Links erlaubt)' ),
						'note'       => self::text( 'Hinweis' ),
					),
					'Karte hinzufuegen'
				),
				'form_html'  => array(
					'label' => 'Formular-HTML',
					'type'  => 'html',
				),
				'form_hint'  => self::textarea( 'Hinweis unter dem Formular' ),
			),
		);
	}

	/**
	 * Generic section editor: keeps the original HTML shell but exposes common content as fields.
	 *
	 * @return array<string,mixed>
	 */
	private static function layout_editable_html_section() {
		return array(
			'label'    => 'Bearbeitbare HTML-Sektion',
			'template' => 'volks_editable_html_section',
			'fields'   => array(
				'section_id'         => self::text( 'Section-ID (Anker)' ),
				'admin_label'        => self::text( 'Admin-Label' ),
				'headings'           => self::repeater(
					'Ueberschriften',
					array(
						'level' => self::text( 'Ebene (h1/h2/h3)' ),
						'html'  => self::heading( 'Text / Inline-HTML' ),
					),
					'Ueberschrift hinzufuegen'
				),
				'paragraphs'         => self::repeater(
					'Absatztexte',
					array(
						'text' => self::textarea( 'Text' ),
					),
					'Absatz hinzufuegen'
				),
				'links'              => self::repeater(
					'Buttons & Links',
					array(
						'label' => self::text( 'Linktext' ),
						'url'   => self::url( 'URL / Anker' ),
					),
					'Link hinzufuegen'
				),
				'images'             => self::repeater(
					'Bilder',
					array(
						'image' => self::image( 'Bild' ),
						'alt'   => self::text( 'Alt-Text' ),
					),
					'Bild hinzufuegen'
				),
				'background_images'  => array(
					'label'              => 'Hintergrundbilder',
					'type'               => 'repeater',
					'add_button_label'   => 'Hintergrundbild hinzufuegen',
					'item_title_field'   => 'css_class',
					'item_title_labels'  => self::editable_background_css_labels(),
					'fields'             => array(
						'image'     => self::image( 'Bild' ),
						'css_class' => array(
							'label'       => 'Zuordnung',
							'type'        => 'text',
							'description' => 'Leer = inline im HTML. editorial-section--sell-bg oder cta-section--mallorca-bg fuer CSS-Sektionsflaeche.',
						),
					),
				),
				'css_section_background'       => self::image( 'Sektions-Hintergrund (CSS, Vollflaeche)' ),
				'css_section_background_class' => array(
					'label'       => 'CSS-Ziel (automatisch beim Import)',
					'type'        => 'text',
					'description' => 'editorial-section--sell-bg oder cta-section--mallorca-bg',
				),
				'original_html'      => array(
					'label' => 'Original-HTML (Fallback / Struktur)',
					'type'  => 'html',
				),
			),
		);
	}
}
