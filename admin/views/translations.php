<?php
/**
 * Translations management view.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
}

global $wpdb;
$table = $wpdb->prefix . 'bhg_translations';

if ( function_exists( 'bhg_seed_default_translations_if_empty' ) ) {
	bhg_seed_default_translations_if_empty();
}

$default_translations = function_exists( 'bhg_get_default_translations' ) ? bhg_get_default_translations() : array();
$default_keys         = array_keys( $default_translations );

// Handle form submission.
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bhg_save_translation'] ) ) {
	// Verify nonce.
	if ( ! isset( $_POST['bhg_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhg_nonce'] ) ), 'bhg_save_translation_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'bonus-hunt-guesser' ) );
	}

	// Sanitize input.
	$tkey   = isset( $_POST['tkey'] ) ? sanitize_text_field( wp_unslash( $_POST['tkey'] ) ) : '';
	$tvalue = isset( $_POST['tvalue'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tvalue'] ) ) : '';

	// Validate input.
	if ( '' === $tkey ) {
		$bhg_error = __( 'Key field is required.', 'bonus-hunt-guesser' );
	} else {
		$wpdb->replace(
			$table,
			array(
				'tkey'   => $tkey,
				'tvalue' => $tvalue,
			),
			array( '%s', '%s' )
		);
		$bhg_notice = __( 'Translation saved.', 'bonus-hunt-guesser' );
	}
}

// Fetch rows.
$rows = $wpdb->get_results( "SELECT tkey, tvalue FROM {$wpdb->prefix}bhg_translations ORDER BY tkey ASC" );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Translations', 'bonus-hunt-guesser' ); ?></h1>

	<style>
	.bhg-default-row td { background-color: #fffbcc; }
	</style>

	<?php if ( ! empty( $bhg_notice ) ) : ?>
	<div class="notice notice-success"><p><?php echo esc_html( $bhg_notice ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $bhg_error ) ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( $bhg_error ); ?></p></div>
	<?php endif; ?>

	<form method="post">
	<?php wp_nonce_field( 'bhg_save_translation_action', 'bhg_nonce' ); ?>
	<table class="form-table" role="presentation">
	<tbody>
	<tr>
	<th scope="row"><label for="tkey"><?php esc_html_e( 'Key', 'bonus-hunt-guesser' ); ?></label></th>
	<td><input name="tkey" id="tkey" type="text" class="regular-text" required></td>
	</tr>
	<tr>
	<th scope="row"><label for="tvalue"><?php esc_html_e( 'Value', 'bonus-hunt-guesser' ); ?></label></th>
	<td><textarea name="tvalue" id="tvalue" class="large-text" rows="4"></textarea></td>
	</tr>
	</tbody>
	</table>
	<p class="submit"><button type="submit" name="bhg_save_translation" id="bhg_save_translation" class="button button-primary"><?php esc_html_e( 'Save', 'bonus-hunt-guesser' ); ?></button></p>
	</form>

	<h2><?php esc_html_e( 'Existing keys', 'bonus-hunt-guesser' ); ?></h2>
	<table class="widefat striped">
	<thead><tr><th><?php esc_html_e( 'Key', 'bonus-hunt-guesser' ); ?></th><th><?php esc_html_e( 'Value', 'bonus-hunt-guesser' ); ?></th><th><?php esc_html_e( 'Actions', 'bonus-hunt-guesser' ); ?></th></tr></thead>
	<tbody>
	<?php
	if ( $rows ) :
		foreach ( $rows as $r ) :
			?>
	<tr<?php echo in_array( $r->tkey, $default_keys, true ) ? ' class="bhg-default-row"' : ''; ?>>
	<td><code><?php echo esc_html( $r->tkey ); ?></code></td>
	<td><?php echo esc_html( $r->tvalue ); ?></td>
	<td><a href="#" class="bhg-edit-translation" onclick="bhgEditTranslation('<?php echo esc_js( $r->tkey ); ?>','<?php echo esc_js( $r->tvalue ); ?>');return false;"><?php esc_html_e( 'Edit', 'bonus-hunt-guesser' ); ?></a></td>
	</tr>
			<?php endforeach; else : ?>
	<tr><td colspan="3"><?php esc_html_e( 'No translations yet.', 'bonus-hunt-guesser' ); ?></td></tr>
	<?php endif; ?>
	</tbody>
	</table>
	<script>
	function bhgEditTranslation( key, value ) {
	document.getElementById( 'tkey' ).value = key;
	document.getElementById( 'tvalue' ).value = value;
	document.getElementById( 'bhg_save_translation' ).textContent = '<?php echo esc_js( __( 'Update', 'bonus-hunt-guesser' ) ); ?>';
	}
	document.getElementById( 'tkey' ).addEventListener( 'input', function() {
	document.getElementById( 'bhg_save_translation' ).textContent = '<?php echo esc_js( __( 'Save', 'bonus-hunt-guesser' ) ); ?>';
	} );
	</script>
</div>
