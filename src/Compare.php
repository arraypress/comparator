<?php
/**
 * Compare Helper Class
 *
 * @package     ArrayPress/Comparator
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Utils;

use Exception;

/**
 * Compare Class
 *
 * Provides functionality for versatile comparison operations across different data types,
 * including support for custom comparison functions, improved type detection, regular expression comparisons,
 * and handling of complex types like JSON strings.
 */
if ( ! class_exists( 'Compare' ) ) :

	/**
	 * Compare helper class
	 */
	class Compare {

		/**
		 * Compares two values with support for custom comparison logic and error handling.
		 *
		 * @param mixed         $value1         The first value to compare.
		 * @param mixed         $value2         The second value to compare.
		 * @param string        $operator       The comparison operator. Defaults to '='.
		 * @param ?string       $type           The type of comparison to perform. Defaults to null for auto-detection.
		 * @param bool          $caseSensitive  Whether the string comparison should be case-sensitive. Defaults to true.
		 * @param bool          $useEpsilon     Indicates whether to use epsilon for floating-point comparison.
		 * @param mixed|null    $value3         Optional. The upper bound for BETWEEN comparisons.
		 * @param callable|null $customFunction Optional. A custom function to use for comparison if 'custom' operator is
		 *                                      selected.
		 * @param float         $epsilon        Optional. The tolerance level for floating-point comparisons. Defaults to
		 *                                      1.0e-10.
		 * @param callable|null $errorCallback  Optional. A callback function for error handling.
		 *
		 * @return bool|null The result of the comparison or null on error.
		 */
		public static function values(
			$value1,
			$value2,
			string $operator = '=',
			?string $type = null,
			bool $caseSensitive = true,
			bool $useEpsilon = false,
			$value3 = null,
			?callable $customFunction = null,
			float $epsilon = 1.0e-10,
			?callable $errorCallback = null
		): ?bool {
			try {
				$comparator = new Comparator( $type, $caseSensitive, $epsilon );

				return $comparator->compare( $value1, $value2, $operator, $customFunction, $useEpsilon, $value3 );
			} catch ( Exception $e ) {
				if ( $errorCallback && is_callable( $errorCallback ) ) {
					call_user_func( $errorCallback, $e );
				}

				return null; // Indicate error
			}
		}

		/**
		 * Performs a BETWEEN comparison to determine if a value is within a specified range.
		 *
		 * @param mixed   $value      The value to compare.
		 * @param mixed   $lower      The lower bound of the comparison range.
		 * @param mixed   $upper      The upper bound of the comparison range.
		 * @param ?string $type       Optional. The type of comparison to perform. Defaults to null for auto-detection.
		 * @param bool    $useEpsilon Indicates whether to use epsilon for floating-point comparison. Defaults to false.
		 * @param float   $epsilon    The tolerance level for floating-point comparisons. Defaults to 1.0e-10.
		 *
		 * @return bool|null  True if the value is within the range, false if not, or null on error.
		 */
		public static function between( $value, $lower, $upper, ?string $type = null, bool $useEpsilon = false, float $epsilon = 1.0e-10 ): ?bool {
			return self::values( $value, $lower, 'between', $type, true, $useEpsilon, $upper, null, $epsilon );
		}

		/**
		 * Performs a NOT BETWEEN comparison to determine if a value is outside a specified range.
		 *
		 * @param mixed   $value      The value to compare.
		 * @param mixed   $lower      The lower bound of the comparison range.
		 * @param mixed   $upper      The upper bound of the comparison range.
		 * @param ?string $type       Optional. The type of comparison to perform. Defaults to null for auto-detection.
		 * @param bool    $useEpsilon Indicates whether to use epsilon for floating-point comparison. Defaults to false.
		 * @param float   $epsilon    The tolerance level for floating-point comparisons. Defaults to 1.0e-10.
		 *
		 * @return bool|null  True if the value is outside the range, false if not, or null on error.
		 */
		public static function notBetween( $value, $lower, $upper, ?string $type = null, bool $useEpsilon = false, float $epsilon = 1.0e-10 ): ?bool {
			return self::values( $value, $lower, 'between', $type, true, $useEpsilon, $upper, null, $epsilon );
		}

		/**
		 * Compares the hash values of two inputs.
		 *
		 * @param mixed         $value1        The first value to hash and compare.
		 * @param mixed         $value2        The second value to hash and compare.
		 * @param string        $algorithm     The hash algorithm to use (e.g., 'sha256').
		 * @param callable|null $errorCallback Optional. A callback function for error handling.
		 *
		 * @return bool|null Returns true if the hash values match, false otherwise.
		 */
		public static function hashes(
			$value1,
			$value2,
			string $algorithm = 'sha256',
			?callable $errorCallback = null
		): ?bool {
			try {
				$comparator = new Comparator();

				return $comparator->hashComparison( $value1, $value2, $algorithm );
			} catch ( Exception $e ) {
				if ( $errorCallback && is_callable( $errorCallback ) ) {
					call_user_func( $errorCallback, $e );
				}

				return null; // Indicate error
			}
		}

	}

endif;