<?php
/**
 * DevDiggers Order Communication for WooCommerce notification email (HTML).
 *
 * The body comes from Configuration > Emails and already includes the greeting.
 *
 * @author DevDiggers
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit();

do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core email hook.

echo wp_kses_post( is_array( $email_message ) ? implode( '', $email_message ) : $email_message );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core email hook.
