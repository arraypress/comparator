<?php
/**
 * Comparator Functions
 *
 * @package     ArrayPress/Comparator
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace {

	use ArrayPress\Utils\Comparator;

	if ( ! function_exists( 'comparator' ) ) {
		/**
		 * Compares two values using the Comparator class with support for custom comparison logic and error handling.
		 *
		 * @param mixed         $value1         The first value to compare.
		 * @param mixed         $value2         The second value to compare.
		 * @param string        $operator       The comparison operator. Defaults to '='.
		 * @param ?string       $type           The type of comparison to perform. Defaults to null for auto-detection.
		 * @param bool          $caseSensitive  Whether the string comparison should be case-sensitive. Defaults to true.
		 * @param bool          $useEpsilon     Indicates whether to use epsilon for floating-point comparison.
		 * @param callable|null $customFunction Optional. A custom function to use for comparison if 'custom' operator is selected.
		 * @param float         $epsilon        Optional. The tolerance level for floating-point comparisons. Defaults to 1.0e-10.
		 * @param callable|null $errorCallback  Optional. A callback function for error handling.
		 *
		 * @return bool|null The result of the comparison or null on error.
		 */
		function comparator(
			$value1,
			$value2,
			string $operator = '=',
			?string $type = null,
			bool $caseSensitive = true,
			bool $useEpsilon = false,
			?callable $customFunction = null,
			float $epsilon = 1.0e-10,
			?callable $errorCallback = null
		): ?bool {
			try {
				// Instantiate the Comparator with the specified type and case sensitivity
				$comparator = new Comparator( $type, $caseSensitive, $epsilon );

				// Perform the comparison
				return $comparator->compare( $value1, $value2, $operator, $customFunction, $useEpsilon );
			} catch ( Exception $e ) {
				if ( $errorCallback && is_callable( $errorCallback ) ) {
					call_user_func( $errorCallback, $e );
				}

				return null; // Indicate error
			}
		}
	}

}