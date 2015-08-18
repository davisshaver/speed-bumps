<?php
namespace Speed_Bumps\Utils;

class Text {

	private static $_cache = array();

	private static function _cache_check( $function, $key ) {
		if ( ! array_key_exists( $function, static::$_cache ) ) {
			static::$_cache[ $function ] = array();
			return false;
		}

		if ( array_key_exists( $key, static::$_cache[ $function ] ) ) {
			return static::$_cache[ $function ][ $key ];
		}

		return false;
	}

	private static function _cache_set( $function, $key, $value ) {
		static::$_cache[ $function ][ $key ] = $value;

		//error_log( print_r( static::$_cache, true ) );
	}

	public static function split_paragraphs( $content ) {
		if ( is_array( $content ) ) {
			$content = implode( "\n\n", $content );
		}

		$cache_key = hash( 'md4', $content );
		$result = static::_cache_check( 'split_paragraphs', $cache_key );
		if ( false === $result ) {
			$result = array_filter( preg_split( '/\n\s*\n/', $content ) );
			static::_cache_set( 'split_paragraphs', $cache_key, $result );
		}

		return $result;
	}


	public static function split_words( $content ) {
		if ( is_array( $content ) ) {
			$content = implode( ' ', $content );
		}

		$cache_key = hash( 'md4', $content );
		$result = static::_cache_check( 'split_words', $cache_key );
		if ( false === $result ) {
			$result = array_filter( explode( ' ', strip_tags( $content ) ) );
			static::_cache_set( 'split_words', $cache_key, $result );
		}
	}


	public static function split_characters( $content ) {
		if ( is_array( $content ) ) {
			$content = implode( '', $content );
		}
		return str_split( $content );
	}

	/**
	 * Get the content between two paragraph indexes.
	 *
	 * Given two indexes, return an array of paragraphs between those two indexes.
	 *
	 * @param array Parts; array of all paragraphs in content
	 * @param int Start (or end) index
	 * @param int End (or start) index
	 * @return array Array of paragraphs between these two points.
	 */
	public static function content_between_points( $parts, $index_1, $index_2 ) {
		$start_point = max( 0, min( $index_1, $index_2 ) );
		$end_point = min( count( $parts ), max( $index_1, $index_2 ) );
		$length = absint( $end_point - $start_point );

		return array_slice( $parts, $start_point, $length );
	}

	/**
	 * Get the content within a certain distance of a given index
	 *
	 * Given two indexes, return an array of paragraphs between those two indexes.
	 *
	 * @param array Parts; array of all paragraphs in content
	 * @param int Current index
	 * @param string Unit to measure distance from (characters/words/paragraphs)
	 * @param int Number of units to count away from initial index in either direction
	 * @return array Array of paragraphs between these two points, inclusive.
	 */
	public static function content_within_distance_of( $parts, $index, $unit, $measure ) {

		if ( 'paragraphs' === $unit ) {
			// Since the insertion point we're counting from is *after* the paragraph whose index we're using:
			return self::content_between_points( $parts, $index + 1 - $measure, $index + 1 + $measure );
		}

		$cache_key = hash( 'md4', var_export( $parts, true ) ) . $index . $unit . $measure;

		$result = static::_cache_check( 'content_within_distance_of', $cache_key );

		if ( false === $result ) {
			$paragraphs = array();

			$p = $index; $count_backward = 0;
			while ( $count_backward < $measure && $p >= 0 ) {
				array_unshift( $paragraphs, $parts[ $p ] );
				$count_backward += self::count_units( $parts[ $p ], $unit );
				$p--;
			}

			$p = $index; $count_forward = 0;
			while ( $count_forward <= $measure && $p < count( $parts ) - 1 ) {
				$p++;
				array_push( $paragraphs, $parts[ $p ] );
				$count_forward += self::count_units( $parts[ $p ], $unit );
			}

			$result = $paragraphs;
			static::_cache_set( 'content_within_distance_of', $cache_key, $result );
		}

		return $result;
	}

	public static function count_units( $text, $unit ) {
		switch ( $unit ) {
			case 'words':
				return self::word_count( $text );
			case 'characters':
				return strlen( $text );
			default:
				return false;
		}
	}

	/**
	 * Abstracted helper function for counting the words in a chunk of text.
	 *
	 * Given either a string of text or an array of strings, will split it into words and return the word count
	 *
	 * @param string|array Text to count words in
	 * @return int Word count
	 */
	public static function word_count( $text ) {
		if ( is_array( $text ) ) {
			$text = implode( ' ', $text );
		}

		$words = Text::split_words( $text );

		return count( $words );
	}
}
