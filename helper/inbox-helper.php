<?php
/**
 * Conversation queries for the Inbox sidebar.
 *
 * @author DevDiggers
 * @version 1.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Inbox_Helper' ) ) {
	/**
	 * Inbox Helper class
	 */
	class DDWCOC_Inbox_Helper {
		/**
		 * Search term from the request.
		 *
		 * @var string
		 */
		protected $search;

		/**
		 * Construct
		 *
		 * @param string $search Search term.
		 */
		public function __construct( $search = '' ) {
			$this->search = $search;
		}

		/**
		 * SQL pieces shared by the row and count queries.
		 *
		 * @param string $status '', 'unread' or 'closed'.
		 * @return array [ join, where ]
		 */
		protected function ddwcoc_get_query_parts( $status ) {
			global $wpdb;

			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$join       = 'INNER JOIN ' . \Automattic\WooCommerce\Utilities\OrderUtil::get_table_for_orders() . ' o ON c.comment_post_ID = o.id';
				$where      = "o.type = 'shop_order'";
				$meta_table = \Automattic\WooCommerce\Utilities\OrderUtil::get_table_for_order_meta();
				$meta_id    = 'order_id';
			} else {
				$join       = "INNER JOIN {$wpdb->posts} o ON c.comment_post_ID = o.ID";
				$where      = "o.post_type = 'shop_order'";
				$meta_table = $wpdb->postmeta;
				$meta_id    = 'post_id';
			}

			$where .= $wpdb->prepare( ' AND c.comment_type = %s', DDWCOC_Communication_Helper::TYPE );

			if ( '' !== $this->search ) {
				$like   = '%' . $wpdb->esc_like( $this->search ) . '%';
				$where .= $wpdb->prepare( ' AND ( c.comment_content LIKE %s OR c.comment_author LIKE %s OR c.comment_author_email LIKE %s OR CAST( c.comment_post_ID AS CHAR ) LIKE %s )', $like, $like, $like, $like );
			}

			$closed = "EXISTS ( SELECT 1 FROM {$meta_table} om WHERE om.{$meta_id} = c.comment_post_ID AND om.meta_key = '_ddwcoc_conversation_status' AND om.meta_value = 'closed' )";

			if ( 'unread' === $status ) {
				$where .= " AND NOT {$closed} AND " . $wpdb->prepare(
					"EXISTS ( SELECT 1 FROM {$wpdb->comments} sc INNER JOIN {$wpdb->commentmeta} sm ON sm.comment_id = sc.comment_ID AND sm.meta_key = '_ddwcoc_message_status' AND sm.meta_value = 'unread' WHERE sc.comment_post_ID = c.comment_post_ID AND sc.comment_type = %s )",
					DDWCOC_Communication_Helper::TYPE
				);
			} elseif ( 'closed' === $status ) {
				$where .= " AND {$closed}";
			}

			return [ $join, $where ];
		}

		/**
		 * Conversation totals per Inbox filter.
		 *
		 * @param string[] $statuses Filters.
		 * @return int[]
		 */
		public function ddwcoc_get_status_counts( $statuses ) {
			global $wpdb;

			$counts = [];

			foreach ( $statuses as $status ) {
				list( $join, $where ) = $this->ddwcoc_get_query_parts( $status );
				$counts[ $status ]    = (int) $wpdb->get_var( "SELECT COUNT( DISTINCT c.comment_post_ID ) FROM {$wpdb->comments} c {$join} WHERE {$where}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $join is built from core table names and $where is prepared in ddwcoc_get_query_parts(); live counts, so no caching.
			}

			return $counts;
		}

		/**
		 * Latest conversations for the Inbox sidebar.
		 *
		 * @param string $status Filter.
		 * @param int    $limit  Rows.
		 * @return object[] order_id, recent_date_gmt, last_id, unread.
		 */
		public function ddwcoc_get_rows( $status, $limit = 50 ) {
			global $wpdb;

			list( $join, $where ) = $this->ddwcoc_get_query_parts( $status );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Parts are prepared in ddwcoc_get_query_parts().
			return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $join is built from core table names and $where is prepared; live counts, so no caching.
				$wpdb->prepare(
					"SELECT c.comment_post_ID AS order_id, MAX( c.comment_date_gmt ) AS recent_date_gmt, MAX( c.comment_ID ) AS last_id, SUM( um.meta_value = 'unread' ) AS unread
				FROM {$wpdb->comments} c {$join}
				LEFT JOIN {$wpdb->commentmeta} um ON um.comment_id = c.comment_ID AND um.meta_key = '_ddwcoc_message_status'
				WHERE {$where}
				GROUP BY c.comment_post_ID
				ORDER BY recent_date_gmt DESC
				LIMIT %d",
					$limit
				)
			);
			// phpcs:enable
		}
	}
}
