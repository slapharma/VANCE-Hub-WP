<?php
/**
 * Selector validation for VHH Annotations.
 *
 * Two selector shapes (W3C Web Annotation-style):
 *
 *   TextQuoteSelector  { type, exact (1..1000), prefix (0..64), suffix (0..64) }
 *   ImageRegionSelector{ type, attachmentId, src?, region {x,y,w,h} 0..1, wholeImage? }
 *
 * IMPORTANT: exact/prefix/suffix must preserve internal whitespace exactly as
 * selected — sanitize_textarea_field() collapses whitespace and would break
 * quote re-anchoring. We only strip tags and invalid UTF-8.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Selector {

	const MAX_JSON_BYTES = 4096;
	const MAX_EXACT      = 1000;
	const MAX_CONTEXT    = 64;

	/**
	 * Validate and sanitize a selector payload.
	 *
	 * @param array|string $selector    Decoded array or raw JSON string.
	 * @param string       $target_type 'text' | 'image'.
	 * @return array|WP_Error Sanitized selector array.
	 */
	public static function validate( $selector, $target_type ) {
		if ( is_string( $selector ) ) {
			if ( strlen( $selector ) > self::MAX_JSON_BYTES ) {
				return new WP_Error( 'vhh_selector_too_large', 'Selector payload too large.', array( 'status' => 400 ) );
			}
			$selector = json_decode( $selector, true );
		}
		if ( ! is_array( $selector ) ) {
			return new WP_Error( 'vhh_selector_invalid', 'Selector must be a JSON object.', array( 'status' => 400 ) );
		}

		if ( 'text' === $target_type ) {
			return self::validate_text( $selector );
		}
		if ( 'image' === $target_type ) {
			return self::validate_image( $selector );
		}
		return new WP_Error( 'vhh_target_invalid', 'Unknown target type.', array( 'status' => 400 ) );
	}

	private static function validate_text( array $sel ) {
		$allowed = array( 'type', 'exact', 'prefix', 'suffix' );
		if ( array_diff( array_keys( $sel ), $allowed ) ) {
			return new WP_Error( 'vhh_selector_invalid', 'Unknown selector keys.', array( 'status' => 400 ) );
		}
		if ( ! isset( $sel['type'] ) || 'TextQuoteSelector' !== $sel['type'] ) {
			return new WP_Error( 'vhh_selector_invalid', 'Expected TextQuoteSelector.', array( 'status' => 400 ) );
		}

		$exact = isset( $sel['exact'] ) ? self::clean_quote_string( $sel['exact'] ) : '';
		if ( '' === $exact || mb_strlen( $exact ) > self::MAX_EXACT ) {
			return new WP_Error( 'vhh_selector_invalid', 'Selector "exact" must be 1-1000 characters.', array( 'status' => 400 ) );
		}

		$out = array(
			'type'  => 'TextQuoteSelector',
			'exact' => $exact,
		);
		foreach ( array( 'prefix', 'suffix' ) as $key ) {
			if ( isset( $sel[ $key ] ) && '' !== $sel[ $key ] ) {
				$val = self::clean_quote_string( $sel[ $key ] );
				if ( mb_strlen( $val ) > self::MAX_CONTEXT ) {
					$val = mb_substr( $val, 0, self::MAX_CONTEXT );
				}
				$out[ $key ] = $val;
			}
		}
		return $out;
	}

	private static function validate_image( array $sel ) {
		$allowed = array( 'type', 'attachmentId', 'src', 'region', 'wholeImage' );
		if ( array_diff( array_keys( $sel ), $allowed ) ) {
			return new WP_Error( 'vhh_selector_invalid', 'Unknown selector keys.', array( 'status' => 400 ) );
		}
		if ( ! isset( $sel['type'] ) || 'ImageRegionSelector' !== $sel['type'] ) {
			return new WP_Error( 'vhh_selector_invalid', 'Expected ImageRegionSelector.', array( 'status' => 400 ) );
		}
		if ( ! isset( $sel['region'] ) || ! is_array( $sel['region'] ) ) {
			return new WP_Error( 'vhh_selector_invalid', 'Missing image region.', array( 'status' => 400 ) );
		}

		$region = array();
		foreach ( array( 'x', 'y', 'w', 'h' ) as $key ) {
			if ( ! isset( $sel['region'][ $key ] ) || ! is_numeric( $sel['region'][ $key ] ) ) {
				return new WP_Error( 'vhh_selector_invalid', 'Region must contain numeric x, y, w, h.', array( 'status' => 400 ) );
			}
			$region[ $key ] = max( 0.0, min( 1.0, (float) $sel['region'][ $key ] ) );
		}
		if ( $region['w'] <= 0 || $region['h'] <= 0 ) {
			return new WP_Error( 'vhh_selector_invalid', 'Region must have positive width and height.', array( 'status' => 400 ) );
		}

		$out = array(
			'type'         => 'ImageRegionSelector',
			'attachmentId' => isset( $sel['attachmentId'] ) ? absint( $sel['attachmentId'] ) : 0,
			'region'       => $region,
			'wholeImage'   => ! empty( $sel['wholeImage'] ),
		);
		if ( ! empty( $sel['src'] ) ) {
			$src = esc_url_raw( (string) $sel['src'] );
			if ( $src ) {
				$out['src'] = $src;
			}
		}
		if ( ! $out['attachmentId'] && empty( $out['src'] ) ) {
			return new WP_Error( 'vhh_selector_invalid', 'Image selector needs attachmentId or src.', array( 'status' => 400 ) );
		}
		return $out;
	}

	/** Strip tags + invalid UTF-8 but PRESERVE whitespace (anchoring depends on it). */
	private static function clean_quote_string( $value ) {
		$value = (string) $value;
		$value = wp_check_invalid_utf8( $value );
		$value = wp_strip_all_tags( $value, false );
		return $value;
	}
}
