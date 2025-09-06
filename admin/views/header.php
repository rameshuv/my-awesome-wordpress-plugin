<?php
/**
 * Common admin header for Bonus Hunt Guesser pages.
 *
 * Displays plugin navigation and status notices.
 *
 * @package Bonus_Hunt_Guesser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Display status messages.
if ( isset( $_GET['message'] ) ) {
    $message_type = sanitize_text_field( wp_unslash( $_GET['message'] ) );
    switch ( $message_type ) {
        case 'success':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__( 'Action completed successfully.', 'bonus-hunt-guesser' ) .
                '</p></div>';
            break;
        case 'updated':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__( 'Updated successfully.', 'bonus-hunt-guesser' ) .
                '</p></div>';
            break;
        case 'deleted':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__( 'Deleted successfully.', 'bonus-hunt-guesser' ) .
                '</p></div>';
            break;
        case 'error':
            echo '<div class="notice notice-error is-dismissible"><p>' .
                esc_html__( 'An error occurred. Please try again.', 'bonus-hunt-guesser' ) .
                '</p></div>';
            break;
    }
}

$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
$pages        = array(
    'bhg'                    => __( 'Dashboard', 'bonus-hunt-guesser' ),
    'bhg-bonus-hunts'        => __( 'Bonus Hunts', 'bonus-hunt-guesser' ),
    'bhg-users'              => __( 'Users', 'bonus-hunt-guesser' ),
    'bhg-affiliate-websites' => __( 'Affiliate Websites', 'bonus-hunt-guesser' ),
    'bhg-tournaments'        => __( 'Tournaments', 'bonus-hunt-guesser' ),
    'bhg-translations'       => __( 'Translations', 'bonus-hunt-guesser' ),
    'bhg-settings'           => __( 'Settings', 'bonus-hunt-guesser' ),
    'bhg-database'           => __( 'Database', 'bonus-hunt-guesser' ),
    'bhg-tools'              => __( 'Tools', 'bonus-hunt-guesser' ),
);
?>
<div class="wrap bhg-admin">
    <h1><?php esc_html_e( 'Bonus Hunt Guesser', 'bonus-hunt-guesser' ); ?></h1>
    <h2 class="nav-tab-wrapper">
        <?php foreach ( $pages as $slug => $label ) : ?>
            <?php $url = admin_url( 'admin.php?page=' . $slug ); ?>
            <a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo ( $current_page === $slug ) ? ' nav-tab-active' : ''; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </h2>
</div>
