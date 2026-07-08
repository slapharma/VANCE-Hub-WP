<?php
/**
 * Transient-based rate limiter for VHH Annotations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Rate_Limiter {

	/**
	 * Count a hit against a bucket and report whether the caller is over limit.
	 *
	 * @param string $bucket e.g. 'write', 'review_send'.
	 * @param string $id     Caller identity (user ID, token hash, IP).
	 * @param int    $limit  Max hits per window.
	 * @param int    $window Window length in seconds.
	 * @return true|WP_Error True if allowed; WP_Error(429) if over limit.
	 */
	public static function check( $bucket, $id, $limit = 30, $window = 60 ) {
		$key  = 'vhh_rl_' . sanitize_key( $bucket ) . '_' . md5( (string) $id );
		$data = get_transient( $key );

		if ( ! is_array( $data ) || empty( $data['reset'] ) || $data['reset'] <= time() ) {
			$data = array(
				'count' => 0,
				'reset' => time() + $window,
			);
		}

		$data['count']++;
		set_transient( $key, $data, max( 1, $data['reset'] - time() ) );

		if ( $data['count'] > $limit ) {
			return new WP_Error(
				'vhh_rate_limited',
				'Too many requests — slow down.',
				array(
					'status'      => 429,
					'retry_after' => max( 1, $data['reset'] - time() ),
				)
			);
		}
		return true;
	}
}
