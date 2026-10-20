<?php
/**
 * Self-check for the Free/Pro boundary.
 *
 * Every Pro feature is meant to be physically absent from this plugin, not switched off.
 * The easy ways to break that are a rebuild from a stale source tree or a file copied
 * across from the Pro plugin. This asserts that neither has happened.
 *
 *   php bin/test-free-boundary.php
 *
 * @package DevDiggers Order Communication for WooCommerce
 */

$root   = dirname( __DIR__ ) . '/';
$errors = [];

/**
 * Read a file, or return '' when it is absent.
 *
 * @param string $path Absolute path.
 * @return string
 */
function ddwcoc_read( $path ) {
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Every PHP file that belongs to this plugin, excluding the bundled framework and tooling.
 *
 * @param string $root Plugin root.
 * @return string[]
 */
function ddwcoc_plugin_php( $root ) {
	$files    = [];
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $iterator as $file ) {
		$path = $file->getPathname();

		if ( 'php' !== strtolower( $file->getExtension() ) ) {
			continue;
		}

		foreach ( [ '/node_modules/', '/devdiggers-framework/', '/bin/', '/build/' ] as $skip ) {
			if ( false !== strpos( $path, $skip ) ) {
				continue 2;
			}
		}

		$files[ str_replace( $root, '', $path ) ] = ddwcoc_read( $path );
	}

	return $files;
}

$php = ddwcoc_plugin_php( $root );

// 1. Pro-only files are gone, not merely unhooked.
foreach ( [
	'includes/install.php',
	'includes/admin/admin-ajax-functions.php',
	'includes/admin/admin-ajax-hooks.php',
	'templates/admin/transcript.php',
	'templates/admin/templates/templates-template.php',
	'templates/admin/communications/communications-list-template.php',
	'templates/admin/configuration/license-configuration-template.php',
	'templates/admin/configuration/automation-configuration-template.php',
	'templates/admin/configuration/business-hours-configuration-template.php',
	'templates/admin/configuration/limits-configuration-template.php',
] as $file ) {
	if ( file_exists( $root . $file ) ) {
		$errors[] = "Pro file {$file} is shipped.";
	}
}

// 2. No licensing, updater or remote notifications. WordPress.org rejects a free plugin
// that phones home for a key, reachable or not.
foreach ( $php as $relative => $contents ) {
	foreach ( [ 'ddfw_is_license_activated', 'ddfw_register_plugin_for_updates', '_ddwcoc_purchase_code', 'DevDiggers_Notifications', 'DDFW_Plugin_Updater' ] as $needle ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "Licensing reference '{$needle}' is present in {$relative}.";
		}
	}
}

foreach ( [ 'includes/class-ddfw-plugin-updater.php', 'includes/class-devdiggers-notifications.php', 'includes/class-ddfw-plugin-dependency.php', 'templates/layout/license.php', 'assets/js/notifications.js' ] as $file ) {
	if ( file_exists( $root . 'devdiggers-framework/' . $file ) ) {
		$errors[] = "Bundled framework ships {$file}.";
	}
}

// 3. No outbound calls, cron, exports or Pro AJAX. Free never schedules or sends anything itself.
$registrations = [
	'wp_remote_',
	'register_rest_route',
	'wp_schedule_event',
	'wp_schedule_single_event',
	'wp_unschedule_hook',
	'dbDelta',
	'fputcsv',
	'admin_post_ddwcoc_export_csv',
	'admin_post_ddwcoc_transcript',
	"'wp_ajax_ddwcoc_save_template'",
	"'wp_ajax_ddwcoc_delete_template'",
	"'wp_ajax_ddwcoc_update_template_usage'",
	'ddwcoc_send_delayed_auto_reply',
	'woocommerce_order_status_changed',
];

foreach ( $php as $relative => $contents ) {
	foreach ( $registrations as $needle ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "Pro registration {$needle} is present in {$relative}.";
		}
	}
}

// 4. No Pro business logic left behind in shared files: saved replies, private-note writes,
// automation, business hours, limits, response analytics.
$pro_logic = [
	'_ddwcoc_message_templates',
	'message_templates',
	"meta['_ddwcoc_private']",
	"'private'     =>",
	'ddwcoc_send_auto_reply',
	'ddwcoc_post_status_change_message',
	'ddwcoc_is_business_hours',
	'ddwcoc_can_send_message',
	'enable_business_hours',
	'outside_hours',
	'enable_message_limits',
	'daily_message_limit',
	'enable_auto_reply',
	'enable_status_messages',
	'response_times',
	'reply_rate',
	'ddwcoc_get_status_breakdown',
	'ddwcoc_sanitize_status_messages',
	'ddwcoc_csv_safe',
	'ddwcoc_handle_uploads',
	'ddwcoc_get_attachment_groups',
	'media_handle_upload',
	'ddwcoc_attachments',
	'enabled_attachment_sharing',
];

foreach ( $php as $relative => $contents ) {
	foreach ( $pro_logic as $needle ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "Pro logic '{$needle}' is present in {$relative}.";
		}
	}
}

// 5. No Pro-only setting is registered, so a crafted options.php or wizard POST has nothing to write.
$admin_functions = ddwcoc_read( $root . 'includes/admin/admin-functions.php' );

foreach ( [ '_ddwcoc_enabled_attachment_sharing', '_ddwcoc_attachment_types', '_ddwcoc_attachment_max_size', '_ddwcoc_attachment_max_files', '_ddwcoc_enable_auto_reply', '_ddwcoc_auto_reply_message', '_ddwcoc_auto_reply_delay', '_ddwcoc_enable_status_messages', '_ddwcoc_status_messages', '_ddwcoc_enable_business_hours', '_ddwcoc_business_days', '_ddwcoc_outside_hours_action', '_ddwcoc_enable_message_limits', '_ddwcoc_daily_message_limit', '_ddwcoc_limit_exceeded_message' ] as $needle ) {
	if ( false !== strpos( $admin_functions, "'{$needle}'" ) ) {
		$errors[] = "Pro setting '{$needle}' is registered in includes/admin/admin-functions.php.";
	}
}

// 6. The built bundles carry no Pro flow, and still do the Free job.
$admin_bundle = ddwcoc_read( $root . 'assets/js/admin.js' );
$front_bundle = ddwcoc_read( $root . 'assets/js/front.js' );

if ( '' === $admin_bundle || '' === $front_bundle ) {
	$errors[] = 'Built bundles are missing. Run `npm run build`.';
}

foreach ( [ 'ddwcoc_save_template', 'ddwcoc_delete_template', 'ddwcoc_update_template_usage', 'quick-repl', 'check-license', 'ddwcoc_attachments', 'dataTransfer', 'devdiggers.com', 'console.log', 'document.cookie', 'localStorage' ] as $needle ) {
	foreach ( [ 'assets/js/admin.js' => $admin_bundle, 'assets/js/front.js' => $front_bundle ] as $bundle => $contents ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "Pro identifier '{$needle}' is present in {$bundle}.";
		}
	}
}

foreach ( [ 'ddwcoc_send_message', 'ddwcoc_fetch_messages', 'ddwcoc_set_conversation_status' ] as $needle ) {
	if ( '' !== $front_bundle && false === strpos( $front_bundle, $needle ) ) {
		$errors[] = "Free behaviour '{$needle}' is missing from assets/js/front.js.";
	}
}

// 7. Every built asset maps to a webpack entry.
$webpack = ddwcoc_read( $root . 'webpack.config.js' );

foreach ( [ 'assets/js', 'assets/css' ] as $dir ) {
	foreach ( (array) glob( $root . $dir . '/*' ) as $path ) {
		$name = basename( $path );

		if ( 'index.php' === $name ) {
			continue;
		}

		if ( false === strpos( $webpack, '"' . pathinfo( $name, PATHINFO_FILENAME ) . '"' ) ) {
			$errors[] = "{$dir}/{$name} has no webpack entry.";
		}
	}
}

// 8. The Free bootstrap yields to Pro and loads the framework on its own pages.
$bootstrap = ddwcoc_read( $root . 'functions.php' );

foreach ( [ "class_exists( 'DDWCOC_Init' )", "'ddwcoc' !== \$prefix", 'DDWCOC_Free_Init' ] as $needle ) {
	if ( false === strpos( $bootstrap, $needle ) ) {
		$errors[] = "Bootstrap guard '{$needle}' is missing from functions.php.";
	}
}

// 9. No constants or upgrade routine a first release has no use for. Asset versions come
// from filemtime(), and the Pro link is written where it is used.
foreach ( $php as $relative => $contents ) {
	foreach ( [ 'DDWCOC_VERSION', 'DDWCOC_PRO_URL', 'ddwcoc_maybe_upgrade', 'ddwcoc_version', 'utm_source' ] as $needle ) {
		if ( false !== strpos( $contents, $needle ) ) {
			$errors[] = "Unneeded '{$needle}' is present in {$relative}.";
		}
	}
}

if ( $errors ) {
	echo "Free/Pro boundary check FAILED:\n";

	foreach ( $errors as $error ) {
		echo ' - ' . $error . "\n";
	}

	exit( 1 );
}

echo "Free/Pro boundary check passed.\n";
