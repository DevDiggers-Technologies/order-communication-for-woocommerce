<?php
/**
 * Dashboard data: conversation volume and backlog.
 *
 * @author DevDiggers
 * @version 1.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Helper\Dashboard;

use DDWCOrderCommunication\Helper\DDWCOC_Communication_Helper;
use DevDiggers\Framework\Includes\DDFW_Dashboard_Data;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Dashboard_Helper' ) ) {
	/**
	 * Dashboard helper class
	 */
	class DDWCOC_Dashboard_Helper {
		/**
		 * Messages in a date range, ordered by order then ID.
		 *
		 * @param string $from Y-m-d.
		 * @param string $to   Y-m-d.
		 * @return array
		 */
		protected function ddwcoc_get_rows( $from, $to ) {
			global $wpdb;

			// ponytail: loads every message in the range into PHP; fine for years of a typical store, move to SQL aggregates for very large shops.
			return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Live message counts across comment meta; caching would show stale counts.
				$wpdb->prepare(
					"SELECT c.comment_post_ID AS order_id, c.comment_date AS date, s.meta_value AS sender, p.meta_value AS private
				FROM {$wpdb->comments} c
				LEFT JOIN {$wpdb->commentmeta} s ON s.comment_id = c.comment_ID AND s.meta_key = '_ddwcoc_sender'
				LEFT JOIN {$wpdb->commentmeta} p ON p.comment_id = c.comment_ID AND p.meta_key = '_ddwcoc_private'
				WHERE c.comment_type = %s AND c.comment_date BETWEEN %s AND %s
				ORDER BY c.comment_post_ID, c.comment_ID",
					DDWCOC_Communication_Helper::TYPE,
					$from . ' 00:00:00',
					$to . ' 23:59:59'
				)
			);
		}

		/**
		 * Aggregate metrics for a range.
		 *
		 * @param string $from Y-m-d.
		 * @param string $to   Y-m-d.
		 * @return array
		 */
		public function ddwcoc_get_metrics( $from, $to ) {
			$metrics = [
				'customer'      => 0,
				'staff'         => 0,
				'system'        => 0,
				'conversations' => [],
				'by_day'        => [
					'customer' => [],
					'staff'    => [],
				],
			];

			foreach ( $this->ddwcoc_get_rows( $from, $to ) as $row ) {
				$sender = $row->sender ? $row->sender : 'staff';

				if ( $row->private ) {
					continue;
				}

				++$metrics[ $sender ];

				if ( 'system' === $sender ) {
					continue;
				}

				$day                                        = substr( $row->date, 0, 10 );
				$metrics['by_day'][ $sender ][ $day ]       = ( $metrics['by_day'][ $sender ][ $day ] ?? 0 ) + 1;
				$metrics['conversations'][ $row->order_id ] = true;
			}

			return $metrics;
		}

		/**
		 * All dashboard data for the range plus the previous period of the same length.
		 *
		 * @param array $range DDFW date range.
		 * @return array
		 */
		public function ddwcoc_get_dashboard_data( $range ) {
			$days     = max( 1, (int) round( ( strtotime( $range['to'] ) - strtotime( $range['from'] ) ) / DAY_IN_SECONDS ) + 1 );
			$current  = $this->ddwcoc_get_metrics( $range['from'], $range['to'] );
			$previous = $this->ddwcoc_get_metrics( gmdate( 'Y-m-d', strtotime( $range['from'] . " -{$days} days" ) ), gmdate( 'Y-m-d', strtotime( $range['from'] . ' -1 day' ) ) );

			$activity = DDFW_Dashboard_Data::build_time_series( $range['from'], $range['to'], $current['by_day'] );

			return [
				'current'  => $current,
				'changes'  => [
					'customer'      => $this->ddwcoc_change( $current['customer'], $previous['customer'] ),
					'staff'         => $this->ddwcoc_change( $current['staff'], $previous['staff'] ),
					'conversations' => $this->ddwcoc_change( count( $current['conversations'] ), count( $previous['conversations'] ) ),
				],
				'activity' => array_sum( array_map( 'array_sum', $current['by_day'] ) ) ? $activity : [],
			];
		}

		/**
		 * Percent change.
		 *
		 * @param float $current  Current.
		 * @param float $previous Previous.
		 * @return float
		 */
		protected function ddwcoc_change( $current, $previous ) {
			if ( ! $previous ) {
				return $current ? 100 : 0;
			}

			return round( ( $current - $previous ) / $previous * 100, 1 );
		}

		/**
		 * Orders with unread customer messages, oldest wait first.
		 *
		 * @param int  $limit      Limit, 0 for all.
		 * @param bool $with_dates Return rows with waiting_since + unread instead of IDs.
		 * @return array Order IDs (or rows with waiting_since when $with_dates).
		 */
		public function ddwcoc_get_awaiting_order_ids( $limit = 0, $with_dates = false ) {
			global $wpdb;

			$sql = $wpdb->prepare(
				"SELECT c.comment_post_ID AS order_id, MIN( c.comment_date_gmt ) AS waiting_since, COUNT(*) AS unread
				FROM {$wpdb->comments} c
				INNER JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = '_ddwcoc_message_status' AND m.meta_value = 'unread'
				WHERE c.comment_type = %s
				GROUP BY c.comment_post_ID
				ORDER BY waiting_since ASC",
				DDWCOC_Communication_Helper::TYPE
			);

			if ( $limit ) {
				$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
			}

			$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- Prepared above; bulk meta update has no API equivalent.

			return $with_dates ? $rows : array_map( 'absint', wp_list_pluck( $rows, 'order_id' ) );
		}

		/**
		 * Latest customer messages.
		 *
		 * @param int $limit Limit.
		 * @return \WP_Comment[]
		 */
		public function ddwcoc_get_recent_customer_messages( $limit = 6 ) {
			return get_comments(
				[
					'type'     => DDWCOC_Communication_Helper::TYPE,
					'number'   => $limit,
					'orderby'  => 'comment_ID',
					'order'    => 'DESC',
					'meta_key' => '_ddwcoc_sender', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'   => 'customer', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				]
			);
		}

		/**
		 * Human duration, e.g. 45m, 3h 20m, 2d 4h.
		 *
		 * @param int $seconds Seconds.
		 * @return string
		 */
		public static function ddwcoc_format_duration( $seconds ) {
			$seconds = (int) $seconds;

			if ( $seconds <= 0 ) {
				return '-';
			}

			$minutes = (int) round( $seconds / MINUTE_IN_SECONDS );

			if ( $minutes < 60 ) {
				/* translators: %d: minutes. */
				return sprintf( esc_html__( '%dm', 'devdiggers-order-communication-for-woocommerce' ), max( 1, $minutes ) );
			}

			$hours = intdiv( $minutes, 60 );

			if ( $hours < 48 ) {
				/* translators: 1: hours, 2: minutes. */
				return sprintf( esc_html__( '%1$dh %2$dm', 'devdiggers-order-communication-for-woocommerce' ), $hours, $minutes % 60 );
			}

			/* translators: 1: days, 2: hours. */
			return sprintf( esc_html__( '%1$dd %2$dh', 'devdiggers-order-communication-for-woocommerce' ), intdiv( $hours, 24 ), $hours % 24 );
		}
	}
}
