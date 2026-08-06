<?php
/**
 * Safe administration controls for the Propstack integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Volks_Propstack_Admin {
	const PAGE_SLUG = 'volks-propstack';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_post_volks_propstack_save', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_volks_propstack_sync', array( __CLASS__, 'run_sync' ) );
	}

	public static function admin_menu() {
		add_management_page(
			'Propstack-Synchronisierung',
			'Propstack Sync',
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function save_settings() {
		self::authorize( 'volks_propstack_save' );

		$allowed = isset( $_POST['allowed_statuses'] )
			? sanitize_text_field( wp_unslash( (string) $_POST['allowed_statuses'] ) )
			: 'Vermarktung';
		$allowed = implode( ', ', array_filter( array_map( 'trim', explode( ',', $allowed ) ) ) );
		if ( '' === $allowed ) {
			$allowed = 'Vermarktung';
		}
		$sold = isset( $_POST['sold_statuses'] )
			? sanitize_text_field( wp_unslash( (string) $_POST['sold_statuses'] ) )
			: 'Verkauft';
		$sold = implode( ', ', array_filter( array_map( 'trim', explode( ',', $sold ) ) ) );
		if ( '' === $sold ) {
			$sold = 'Verkauft';
		}

		$key_file = isset( $_POST['key_file'] )
			? sanitize_text_field( wp_unslash( (string) $_POST['key_file'] ) )
			: '';
		$key_file = wp_normalize_path( trim( $key_file ) );
		if ( '' !== $key_file && ! str_starts_with( $key_file, '/' ) ) {
			self::redirect( 'error', 'Der Key-Dateipfad muss ein absoluter Pfad sein.' );
		}

		update_option( 'volks_propstack_allowed_statuses', $allowed, false );
		update_option( 'volks_propstack_sold_statuses', $sold, false );
		update_option( 'volks_propstack_key_file', $key_file, false );

		$requested_enabled = ! empty( $_POST['enabled'] );
		$api               = new Volks_Propstack_API();
		$enabled           = $requested_enabled && $api->has_key();
		update_option( 'volks_propstack_enabled', $enabled ? 1 : 0, false );
		Volks_Propstack_Sync::ensure_schedule();

		if ( $requested_enabled && ! $enabled ) {
			self::redirect( 'warning', 'Die Einstellungen wurden gespeichert. Die Automatik bleibt aus, weil der API-Key nicht gelesen werden konnte.' );
		}
		self::redirect( 'success', 'Die Propstack-Einstellungen wurden gespeichert.' );
	}

	public static function run_sync() {
		self::authorize( 'volks_propstack_sync' );
		$dry_run = ! isset( $_POST['sync_mode'] ) || 'live' !== sanitize_key( wp_unslash( (string) $_POST['sync_mode'] ) );
		$result  = Volks_Propstack_Sync::run( $dry_run );

		if ( is_wp_error( $result ) ) {
			self::redirect( 'error', $result->get_error_message() );
		}

		$message = sprintf(
			'%s: %d externe Objekte; %d neu, %d aktualisiert, %d unverändert, %d deaktiviert, %d übersprungen.',
			$dry_run ? 'Testlauf abgeschlossen' : 'Synchronisierung abgeschlossen',
			absint( $result['remote'] ?? 0 ),
			absint( $result['created'] ?? 0 ),
			absint( $result['updated'] ?? 0 ),
			absint( $result['unchanged'] ?? 0 ),
			absint( $result['deactivated'] ?? 0 ),
			absint( $result['skipped'] ?? 0 )
		);
		self::redirect( 'success', $message );
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie dürfen diese Seite nicht aufrufen.' ) );
		}

		$api         = new Volks_Propstack_API();
		$has_key     = $api->has_key();
		$key_file    = (string) get_option( 'volks_propstack_key_file', '' );
		$enabled     = (bool) get_option( 'volks_propstack_enabled', false );
		$last_sync   = (string) get_option( 'volks_propstack_last_sync', '' );
		$last_result = get_option( 'volks_propstack_last_result', array() );
		$last_error  = get_option( 'volks_propstack_last_error', array() );
		$notice_type = isset( $_GET['vps_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['vps_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice_text = isset( $_GET['vps_message'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['vps_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1>Propstack-Synchronisierung</h1>
			<p>Diese Integration liest ausschließlich freigegebene Immobiliendaten. Sie kann in Propstack keine Objekte schreiben, löschen oder umstellen.</p>

			<?php if ( $notice_text ) : ?>
				<div class="notice notice-<?php echo esc_attr( in_array( $notice_type, array( 'success', 'warning', 'error' ), true ) ? $notice_type : 'info' ); ?> is-dismissible"><p><?php echo esc_html( $notice_text ); ?></p></div>
			<?php endif; ?>

			<table class="widefat striped" style="max-width:900px;margin:20px 0">
				<tbody>
					<tr><th style="width:260px">API-Key</th><td><strong><?php echo $has_key ? 'Vorhanden und lesbar' : 'Fehlt oder nicht lesbar'; ?></strong></td></tr>
					<tr><th>Automatische Synchronisierung</th><td><?php echo $enabled ? 'Aktiv (stündlich)' : 'Inaktiv'; ?></td></tr>
					<tr><th>Letzte erfolgreiche Synchronisierung</th><td><?php echo $last_sync ? esc_html( get_date_from_gmt( $last_sync, 'd.m.Y H:i:s' ) ) : 'Noch nie'; ?></td></tr>
					<?php if ( is_array( $last_result ) && $last_result ) : ?><tr><th>Letztes Ergebnis</th><td><?php echo esc_html( sprintf( '%d Objekte, %d neu, %d aktualisiert, %d deaktiviert', absint( $last_result['remote'] ?? 0 ), absint( $last_result['created'] ?? 0 ), absint( $last_result['updated'] ?? 0 ), absint( $last_result['deactivated'] ?? 0 ) ) ); ?></td></tr><?php endif; ?>
					<?php if ( is_array( $last_error ) && ! empty( $last_error['message'] ) ) : ?><tr><th>Letzter Fehler</th><td><?php echo esc_html( (string) $last_error['message'] ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>

			<h2>Einstellungen</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:900px">
				<input type="hidden" name="action" value="volks_propstack_save">
				<?php wp_nonce_field( 'volks_propstack_save' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="vps-key-file">Geschützte Key-Datei</label></th><td><input class="regular-text code" id="vps-key-file" name="key_file" type="text" value="<?php echo esc_attr( $key_file ); ?>" placeholder="/absoluter/pfad/propstack.env"><p class="description">Die Datei sollte außerhalb des Webroots liegen und <code>PROPSTACK_API_KEY=...</code> enthalten. Der Key wird nicht in der WordPress-Datenbank gespeichert.</p></td></tr>
					<tr><th scope="row"><label for="vps-statuses">Aktive Immobilien</label></th><td><input class="regular-text" id="vps-statuses" name="allowed_statuses" type="text" value="<?php echo esc_attr( (string) get_option( 'volks_propstack_allowed_statuses', 'Vermarktung' ) ); ?>"><p class="description">Kommagetrennte, exakte Statusnamen für das Immobilienarchiv. Für dieses Konto: <code>Vermarktung</code>.</p></td></tr>
					<tr><th scope="row"><label for="vps-sold-statuses">Verkauft-Galerie</label></th><td><input class="regular-text" id="vps-sold-statuses" name="sold_statuses" type="text" value="<?php echo esc_attr( (string) get_option( 'volks_propstack_sold_statuses', 'Verkauft' ) ); ?>"><p class="description">Kommagetrennte, exakte Statusnamen für die Referenzgalerie. Für dieses Konto: <code>Verkauft</code>.</p></td></tr>
					<tr><th scope="row">Automatik</th><td><label><input name="enabled" type="checkbox" value="1" <?php checked( $enabled ); ?>> Stündliche, nur lesende Synchronisierung aktivieren</label><p class="description">Ohne lesbaren API-Key kann die Automatik nicht aktiviert werden.</p></td></tr>
				</table>
				<?php submit_button( 'Einstellungen speichern' ); ?>
			</form>

			<h2>Manuelle Kontrolle</h2>
			<p>Der Testlauf validiert zuerst die externen Daten und ändert keine WordPress-Immobilien.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="volks_propstack_sync">
				<?php wp_nonce_field( 'volks_propstack_sync' ); ?>
				<button class="button button-secondary" type="submit" name="sync_mode" value="dry" <?php disabled( ! $has_key ); ?>>Testlauf</button>
				<button class="button button-primary" type="submit" name="sync_mode" value="live" <?php disabled( ! $has_key ); ?>>Jetzt synchronisieren</button>
			</form>
		</div>
		<?php
	}

	private static function authorize( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie dürfen diese Aktion nicht ausführen.' ), 403 );
		}
		check_admin_referer( $action );
	}

	private static function redirect( $type, $message ) {
		$url = add_query_arg(
			array(
				'page'        => self::PAGE_SLUG,
				'vps_notice'  => sanitize_key( $type ),
				'vps_message' => $message,
			),
			admin_url( 'tools.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
