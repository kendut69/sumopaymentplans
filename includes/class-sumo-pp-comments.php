<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Comments
 *
 * Handle comments (payment notes).
 */
class SUMO_PP_Comments {

	/**
	 * Init SUMO_PP_Comments.
	 */
	public static function init() {
		// Secure payment notes.
		add_filter( 'comments_clauses' , __CLASS__ . '::exclude_payment_comments' , 10 , 1 ) ;
		add_filter( 'comment_feed_where' , __CLASS__ . '::exclude_payment_comments_from_feed_where' ) ;

		// Prevent comments counts.
		add_filter( 'wp_count_comments' , __CLASS__ . '::prevent_payment_comments_count' , 11 , 2 ) ;

		// Delete comments count cache whenever there is a new comment or a comment status changes.
		add_action( 'wp_insert_comment' , __CLASS__ . '::delete_comments_count_cache' ) ;
		add_action( 'wp_set_comment_status' , __CLASS__ . '::delete_comments_count_cache' ) ;
	}

	/**
	 * Exclude payment comments from queries and RSS.
	 * 
	 * @param  array $clauses A compacted array of comment query clauses.
	 * @return array
	 */
	public static function exclude_payment_comments( $clauses ) {
		$clauses[ 'where' ] .= ( $clauses[ 'where' ] ? ' AND ' : '' ) . " comment_type != 'payment_note' " ;
		return $clauses ;
	}

	/**
	 * Exclude payment comments from queries and RSS.
	 *
	 * @param  string $where The WHERE clause of the query.
	 * @return string
	 */
	public static function exclude_payment_comments_from_feed_where( $where ) {
		return $where . ( $where ? ' AND ' : '' ) . " comment_type != 'payment_note' " ;
	}

	/**
	 * Delete comments count cache whenever there is
	 * new comment or the status of a comment changes. Cache
	 * will be regenerated next time self::prevent_payment_comments_count()
	 * is called.
	 */
	public static function delete_comments_count_cache() {
		delete_transient( '_sumo_pp_count_comments' ) ;
	}

	/**
	 * Prevent payment notes from wp_count_comments().
	 * 
	 * @global object $wpdb
	 * @param object $stats
	 * @param int $post_id The post ID.
	 */
	public static function prevent_payment_comments_count( $stats, $post_id ) {
		global $wpdb ;

		if ( 0 === $post_id ) {
			$stats = get_transient( '_sumo_pp_count_comments' ) ;

			if ( ! is_object( $stats ) ) {
				self::delete_comments_count_cache() ;
				$stats = get_transient( '_sumo_pp_count_comments' ) ;
			}

			if ( ! $stats ) {
				$stats = array(
					'total_comments' => 0,
					'all'            => 0,
						) ;

				$count = $wpdb->get_results(
						"
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE comment_type NOT IN ('payment_note', 'subscription_note', 'order_note', 'webhook_delivery')
					GROUP BY comment_approved
				" , ARRAY_A
						) ;

				$approved = array(
					'0'            => 'moderated',
					'1'            => 'approved',
					'spam'         => 'spam',
					'trash'        => 'trash',
					'post-trashed' => 'post-trashed',
						) ;

				foreach ( ( array ) $count as $row ) {
					// Don't count post-trashed toward totals.
					if ( ! in_array( $row[ 'comment_approved' ] , array( 'post-trashed', 'trash', 'spam' ) , true ) ) {
						$stats[ 'all' ] += $row[ 'num_comments' ] ;
						$stats[ 'total_comments' ] += $row[ 'num_comments' ] ;
					} elseif ( ! in_array( $row[ 'comment_approved' ] , array( 'post-trashed', 'trash' ) , true ) ) {
						$stats[ 'total_comments' ] += $row[ 'num_comments' ] ;
					}
					if ( isset( $approved[ $row[ 'comment_approved' ] ] ) ) {
						$stats[ $approved[ $row[ 'comment_approved' ] ] ] = $row[ 'num_comments' ] ;
					}
				}

				foreach ( $approved as $key ) {
					if ( empty( $stats[ $key ] ) ) {
						$stats[ $key ] = 0 ;
					}
				}

				$stats = ( object ) $stats ;
				set_transient( '_sumo_pp_count_comments' , $stats ) ;
			}
		}
		return $stats ;
	}
}

SUMO_PP_Comments::init() ;
