<?php
/**
 * DevDiggers Order Communication for WooCommerce notification email (plain text).
 *
 * @author DevDiggers
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit();

echo '= ' . esc_html( $email_heading ) . " =\n\n";

$ddwcoc_body = is_array( $email_message ) ? implode( "\n\n", $email_message ) : $email_message;

// Keep link targets readable once the HTML is stripped.
$ddwcoc_body = preg_replace( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i', '$2: $1', $ddwcoc_body );

echo esc_html( trim( wp_strip_all_tags( $ddwcoc_body ) ) ) . "\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core email filter.
