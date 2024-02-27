<?php
/**
 * Enhanced Comparator Class
 *
 * Extends functionality to support custom comparison functions, improved type detection,
 * regular expression comparisons for strings, and enhanced documentation. Ideal for
 * versatile comparison operations across different data types with added support for
 * complex types like JSON strings.
 *
 * Usage:
 * - Custom comparison: `$slurp->compare($value1, $value2, 'custom', $customFunction);`
 * - Regular expression comparison: `$slurp->compare($value1, $value2, 'regex');`
 * - Improved type detection for complex types: Automatically detects and compares JSON strings.
 *
 * @package     ArrayPress/Comparator
 * @copyright   Copyright 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.1.0
 * @author      David Sherlock
 */

namespace ArrayPress\Utils;

use InvalidArgumentException;
use function json_decode;
use function json_last_error;

/**
 * Enhanced Comparator Class
 *
 * Provides functionality for versatile comparison operations across different data types,
 * including support for custom comparison functions, improved type detection, regular expression comparisons,
 * and handling of complex types like JSON strings.
 */
if ( ! class_exists( 'Comparator' ) ) :

	class Comparator {

		/**
		 * @var ?string The type of comparison to perform.
		 */
		private ?string $type;

		/**
		 * @var bool Whether the string comparison should be case-sensitive.
		 */
		private bool $caseSensitive;

		/**
		 * @var array List of valid comparison operators including basic mathematical comparisons,
		 * regular expression checks, and string-specific checks such as starts with, contains, and ends with.
		 */
		private array $validOperators = [
			'==',
			'===',
			'!=',
			'!==',
			'>',
			'<',
			'>=',
			'<=',
			'starts',
			'ends',
			'all',
			'any'
		];

		/**
		 * @var float The tolerance level for floating-point comparisons.
		 */
		private float $epsilon; // Default value can be adjusted as needed

		/**
		 * Constructor.
		 *
		 * @param ?string $type          Optional. The type of comparison to perform. Defaults to null for auto-detection.
		 * @param bool    $caseSensitive Optional. Whether the string comparison should be case-sensitive. Defaults to true.
		 * @param float   $epsilon       Optional. The tolerance level for floating-point comparisons. Defaults to 1.0e-10.
		 */
		public function __construct( ?string $type = null, bool $caseSensitive = true, float $epsilon = 1.0e-10 ) {
			$this->type          = $type;
			$this->caseSensitive = $caseSensitive;
			$this->epsilon       = $epsilon;
		}

		/** Setters *******************************************************************/

		/**
		 * Sets the type of comparison.
		 *
		 * @param ?string $type The type of comparison to perform.
		 *
		 * @return self For method chaining.
		 */
		public function setType( ?string $type ): self {
			$this->type = $type;

			return $this;
		}

		/**
		 * Sets whether the string comparison should be case-sensitive.
		 *
		 * @param bool $caseSensitive Whether the comparison should be case-sensitive.
		 *
		 * @return self For method chaining.
		 */
		public function setCaseSensitive( bool $caseSensitive ): self {
			$this->caseSensitive = $caseSensitive;

			return $this;
		}

		/**
		 * Sets the epsilon value for floating-point comparisons.
		 *
		 * @param float $epsilon The tolerance level for the comparison.
		 *
		 * @return self For method chaining.
		 */
		public function setEpsilon( float $epsilon ): self {
			$this->epsilon = $epsilon;

			return $this;
		}

		/** Comparison ****************************************************************/

		/**
		 * Performs the comparison between two values based on the specified operator.
		 *
		 * @param mixed         $value1         The first value to compare.
		 * @param mixed         $value2         The second value to compare.
		 * @param string        $operator       The comparison operator or 'custom' for using a custom function. Defaults to '='.
		 * @param callable|null $customFunction Optional. The custom function to use for comparison if 'custom' operator is used.
		 *
		 * @return bool The result of the comparison.
		 * @throws InvalidArgumentException If an invalid operator or custom function is provided.
		 */
		public function compare( $value1, $value2, string $operator = '=', callable $customFunction = null, bool $useEpsilon = false ): bool {
			$operator = strtolower( trim( $operator ) );

			if ( $operator === 'custom' && is_callable( $customFunction ) ) {
				return $customFunction( $value1, $value2 );
			}

			if ( in_array( $operator, [ 'regex', 'reg', 'rx' ] ) ) {
				return $this->regexComparison( $value1, $value2 );
			}

			$this->type = $this->type ?? $this->detectType( $value1, $value2 );
			$operator   = $this->translateOperator( $operator );

			if ( ! $this->validateOperator( $operator, $customFunction ) ) {
				throw new InvalidArgumentException( "Invalid comparison operator or custom function." );
			}

			switch ( $this->type ) {
				case 'string':
					return $this->stringComparison( (string) $value1, $value2, $operator );
				case 'float':
					return $this->numericComparison( (float) $value1, (float) $value2, $operator, $useEpsilon );
				case 'int':
					return $this->numericComparison( (int) $value1, (int) $value2, $operator );
				case 'date':
					return $this->dateComparison( $value1, $value2, $operator );
				case 'array':
					$value1 = $this->toArray( $value1 );
					$value2 = $this->toArray( $value2 );

					return $this->arrayComparison( $value1, $value2, $operator );
				case 'bool':
					return $this->booleanComparison( (bool) $value1, (bool) $value2, $operator );
				case 'object':
					$value1 = $this->toObject( $value1 );
					$value2 = $this->toObject( $value2 );

					return $this->objectComparison( $value1, $value2, $operator );
				case 'json':
					return $this->jsonComparison( (string) $value1, (string) $value2, $operator );
				default:
					return false;
			}

		}

		/** Validation ****************************************************************/

		/**
		 * Translates a human-readable operator into its corresponding symbol.
		 *
		 * @param string $operator The human-readable operator.
		 *
		 * @return string The operator symbol or the original operator if not found.
		 */
		private function translateOperator( string $operator ): string {

			// Mapping of operator aliases to their canonical form
			$operatorAliases = [
				'=='     => [ 'equal_to', 'equals', '=' ],
				'==='    => [ 'strict_equal_to' ],
				'!='     => [ 'not_equal_to', 'not_equals' ],
				'!=='    => [ 'strict_not_equal_to' ],
				'>'      => [ 'more_than', 'greater_than' ],
				'<'      => [ 'less_than' ],
				'>='     => [ 'at_least', 'greater_than_or_equal_to' ],
				'<='     => [ 'at_most', 'less_than_or_equal_to' ],
				'starts' => [ 'startswith', 'starts_with', 'begins_with' ],
				'ends'   => [ 'endswith', 'ends_with' ],
				'all'    => [ 'includes_all', 'contains_all', 'has_all', 'match_all' ],
				'any'    => [ 'includes_any', 'contains_any', 'contains', 'has', 'match_any' ],
			];

			// Normalize the input operator to lowercase
			$operatorKey = strtolower( $operator );

			// Search each alias list for the operator key and return the canonical form
			foreach ( $operatorAliases as $canonical => $aliases ) {
				if ( $operatorKey === $canonical || in_array( $operatorKey, $aliases ) ) {
					return $canonical;
				}
			}

			// Return the original operator if no alias is found
			return $operator;
		}

		/**
		 * Validates the comparison operator.
		 *
		 * @param string        $operator       The comparison operator.
		 * @param callable|null $customFunction The custom function, if 'custom' operator is used.
		 *
		 * @return bool True if the operator is valid, false otherwise.
		 */
		private function validateOperator( string $operator, ?callable $customFunction ): bool {
			if ( $operator === 'custom' ) {
				return is_callable( $customFunction );
			}

			return in_array( $operator, $this->validOperators );
		}

		/** Comparison ****************************************************************/

		/**
		 * Performs a regular expression comparison.
		 *
		 * @param string $pattern The regex pattern.
		 * @param string $subject The string to test against the pattern.
		 *
		 * @return bool True if the pattern matches the subject, false otherwise.
		 * @throws InvalidArgumentException If the regex pattern is invalid.
		 */
		private function regexComparison( string $subject, string $pattern ): bool {

			// Ensure the pattern is properly delimited
			$pattern = '/' . str_replace( '/', '\/', $pattern ) . '/u';

			if ( @preg_match( $pattern, '' ) === false ) {
				throw new InvalidArgumentException( "Invalid regular expression: $pattern" );
			}

			return preg_match( $pattern, $subject ) === 1;
		}

		/**
		 * Compares two JSON strings.
		 *
		 * @param string $json1    The first JSON string.
		 * @param string $json2    The second JSON string.
		 * @param string $operator The comparison operator.
		 *
		 * @return bool The result of the comparison.
		 */
		private function jsonComparison( string $json1, string $json2, string $operator ): bool {
			$data1 = json_decode( $json1, true );
			$data2 = json_decode( $json2, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}

			return $this->arrayComparison( $data1, $data2, $operator );
		}

		/**
		 * Performs numeric comparison between two values based on the specified operator.
		 * Supports epsilon-based comparison for floating-point numbers.
		 *
		 * @param mixed  $value1     The first value to compare.
		 * @param mixed  $value2     The second value to compare.
		 * @param string $operator   The comparison operator to use.
		 * @param bool   $useEpsilon Indicates whether to use epsilon for floating-point comparison.
		 *
		 * @return bool The result of the comparison. Returns false if an unsupported operator is used.
		 */
		private function numericComparison( $value1, $value2, string $operator, bool $useEpsilon = false ): bool {

			// Apply epsilon logic for floating point equality comparisons
			if ( $useEpsilon && is_float( $value1 ) && is_float( $value2 ) ) {
				switch ( $operator ) {
					case '==':
						return abs( $value1 - $value2 ) < $this->epsilon;
					case '!=':
						return abs( $value1 - $value2 ) >= $this->epsilon;
				}
			}

			// Standard comparison for other cases and operators
			switch ( $operator ) {
				case '<':
					return $value1 < $value2;
				case '<=':
					return $value1 <= $value2;
				case '>':
					return $value1 > $value2;
				case '>=':
					return $value1 >= $value2;
				case '==':
					return $value1 == $value2;
				case '!=':
					return $value1 != $value2;
				default:
					return false;
			}
		}

		/**
		 * Performs string comparison between two values based on the specified operator.
		 * Supports basic comparison ('==', '!=') and pattern matching ('starts', 'contains', 'ends').
		 * Additionally, supports checking if a string contains all elements of an array.
		 *
		 * @param string $value1   The first string to compare.
		 * @param mixed  $value2   The second string or an array of strings to compare.
		 * @param string $operator The comparison operator to use.
		 *
		 * @return bool The result of the comparison. Returns false if an unsupported operator is used or
		 *              if the caseSensitive property affects the comparison outcome.
		 */
		private function stringComparison( string $value1, $value2, string $operator ): bool {
			if ( ! $this->caseSensitive ) {
				$value1 = strtolower( $value1 );
				if ( is_string( $value2 ) ) {
					$value2 = strtolower( $value2 );
				} elseif ( is_array( $value2 ) ) {
					$value2 = array_map( 'strtolower', $value2 );
				}
			}

			switch ( $operator ) {
				case '==':
					return $value1 == $value2;
				case '!=':
					return $value1 != $value2;
				case 'starts':
					if ( is_array( $value2 ) ) {
						return strpos( $value1, reset( $value2 ) ) === 0;
					} else {
						return strpos( $value1, $value2 ) === 0;
					}
				case 'all':
					if ( is_array( $value2 ) ) {
						foreach ( $value2 as $element ) {
							if ( strpos( $value1, $element ) === false ) {
								return false;
							}
						}

						return true;
					} else {
						return strpos( $value1, $value2 ) !== false;
					}
				case 'any':
					if ( is_array( $value2 ) ) {
						foreach ( $value2 as $element ) {
							if ( strpos( $value1, $element ) !== false ) {
								return true;
							}
						}

						return false;
					} else {
						return strpos( $value1, $value2 ) !== false;
					}
				case 'ends':
					if ( is_array( $value2 ) ) {
						$value2 = end( $value2 );
					}

					return substr( $value1, - strlen( $value2 ) ) === $value2;
				default:
					return false;
			}
		}

		/**
		 * Compares two dates based on the specified operator.
		 *
		 * Converts date strings into timestamps and performs a numeric comparison between them.
		 *
		 * @param string $date1    The first date string.
		 * @param string $date2    The second date string.
		 * @param string $operator The comparison operator to use.
		 *
		 * @return bool The result of the comparison.
		 */
		private function dateComparison( string $date1, string $date2, string $operator ): bool {
			$timestamp1 = strtotime( $date1 );
			$timestamp2 = strtotime( $date2 );

			return $this->numericComparison( $timestamp1, $timestamp2, $operator );
		}

		/**
		 * Compares two arrays based on the specified operator or a custom function.
		 *
		 * @param array  $array1   The first array.
		 * @param array  $array2   The second array.
		 * @param string $operator The comparison operator to use.
		 *
		 * @return bool The result of the comparison.
		 */
		private function arrayComparison( array $array1, array $array2, string $operator ): bool {

			// Adjust for case sensitivity
			if ( ! $this->caseSensitive ) {
				$array1 = array_map( 'strtolower', $array1 );
				$array2 = array_map( 'strtolower', $array2 );
			}

			switch ( $operator ) {
				case '==':
					return $array1 == $array2;
				case '!=':
					return $array1 != $array2;
				case 'all':
					foreach ( $array2 as $item ) {
						if ( ! in_array( $item, $array1, true ) ) {
							return false;
						}
					}

					return true;
				case 'any':
					foreach ( $array2 as $item ) {
						if ( in_array( $item, $array1, true ) ) {
							return true;
						}
					}

					return false;
				case 'starts':
					return reset( $array1 ) === reset( $array2 );
				case 'ends':
					return end( $array1 ) === end( $array2 );
				default:
					return false;
			}
		}

		/**
		 * Compares two boolean values based on the specified operator.
		 *
		 * @param bool   $bool1    The first boolean value.
		 * @param bool   $bool2    The second boolean value.
		 * @param string $operator The comparison operator to use.
		 *
		 * @return bool The result of the comparison.
		 */
		private function booleanComparison( bool $bool1, bool $bool2, string $operator ): bool {
			switch ( $operator ) {
				case '==':
					return $bool1 == $bool2;
				case '!=':
					return $bool1 != $bool2;
				default:
					return false;
			}
		}

		/**
		 * Compares two objects based on the specified operator or a custom function.
		 *
		 * @note Additional logic
		 *
		 * @param object $obj1     The first object.
		 * @param object $obj2     The second object.
		 * @param string $operator The comparison operator to use.
		 *
		 * @return bool The result of the comparison.
		 */
		private function objectComparison( object $obj1, object $obj2, string $operator ): bool {
			switch ( $operator ) {
				case '==':
					return $obj1 == $obj2; // Structural equality
				case '===':
					return $obj1 === $obj2; // Referential equality
				case '!=':
					return $obj1 != $obj2;
				case '!==':
					return $obj1 !== $obj2;
				default:
					return false;
			}
		}

		/**
		 * Compares the hash values of two inputs.
		 *
		 * @param mixed  $value1    The first value to hash and compare.
		 * @param mixed  $value2    The second value to hash and compare.
		 * @param string $algorithm The hash algorithm to use (e.g., 'sha256').
		 *
		 * @return bool Returns true if the hash values match, false otherwise.
		 */
		public function hashComparison( $value1, $value2, string $algorithm = 'sha256' ): bool {
			if ( ! in_array( $algorithm, hash_algos() ) ) {
				throw new InvalidArgumentException( "Unsupported hash algorithm: $algorithm" );
			}

			$hash1 = hash( $algorithm, $this->normalizeValueForHashing( $value1 ) );
			$hash2 = hash( $algorithm, $this->normalizeValueForHashing( $value2 ) );

			return $hash1 === $hash2;
		}

		/**
		 * Normalizes a value for hashing. This primarily involves converting complex structures like arrays and objects
		 * to a JSON string to ensure consistent hashing. Simpler types are converted to strings directly.
		 *
		 * @param mixed $value The value to normalize.
		 *
		 * @return string The normalized value.
		 */
		protected function normalizeValueForHashing( $value ): string {
			if ( is_array( $value ) || is_object( $value ) ) {
				return json_encode( $value );
			} elseif ( is_bool( $value ) ) {
				return $value ? 'true' : 'false';
			} elseif ( is_null( $value ) ) {
				return 'null'; // Lowercase to match JSON representation of null.
			} else {
				return (string) $value;
			}
		}

		/** Helpers ****************************************************************/

		/**
		 * Detects the type of the values to be compared.
		 *
		 * @param mixed $value1 The first value.
		 * @param mixed $value2 The second value.
		 *
		 * @return string The detected type.
		 */
		private function detectType( $value1, $value2 ): string {
			if ( is_float( $value1 ) || is_float( $value2 ) ) {
				return 'float';
			} elseif ( is_int( $value1 ) || is_int( $value2 ) ) {
				return 'int';
			} elseif ( is_string( $value1 ) && is_string( $value2 ) ) {
				if ( $this->isJsonString( $value1 ) && $this->isJsonString( $value2 ) ) {
					return 'json';
				}

				return 'string';
			} elseif ( is_string( $value1 ) && is_array( $value2 ) ) {
				return 'string';
			} elseif ( is_bool( $value1 ) || is_bool( $value2 ) ) {
				return 'bool';
			} elseif ( is_array( $value1 ) && is_array( $value2 ) ) {
				return 'array';
			} elseif ( is_object( $value1 ) && is_object( $value2 ) ) {
				return 'object';
			} elseif ( strtotime( $value1 ) !== false && strtotime( $value2 ) !== false ) {
				return 'date';
			} else {
				return 'unknown';
			}
		}

		/**
		 * Converts a value to an array. It handles JSON strings and objects by converting them to arrays.
		 *
		 * @param mixed $value The value to be converted to an array.
		 *
		 * @return array The value converted to an array.
		 */
		private function toArray( $value ): array {
			if ( is_array( $value ) ) {
				return $value;
			}
			if ( is_string( $value ) ) {
				$decoded = json_decode( $value, true );

				return is_null( $decoded ) ? (array) $value : $decoded;
			}
			if ( is_object( $value ) ) {
				return (array) $value;
			}

			return (array) $value;
		}

		/**
		 * Converts a value to an object. It handles JSON strings and arrays by converting them to objects.
		 *
		 * @param mixed $value The value to be converted to an object.
		 *
		 * @return object The value converted to an object.
		 */
		private function toObject( $value ): object {
			if ( is_object( $value ) ) {
				return $value;
			}
			if ( is_array( $value ) ) {
				return (object) $value;
			}
			if ( is_string( $value ) ) {
				$decoded = json_decode( $value );

				return is_null( $decoded ) ? (object) $value : $decoded;
			}

			return (object) $value;
		}

		/**
		 * Checks if a string is a valid JSON.
		 *
		 * @param string $string The string to check.
		 *
		 * @return bool True if the string is a valid JSON, false otherwise.
		 */
		private function isJsonString( string $string ): bool {
			json_decode( $string );

			return json_last_error() === JSON_ERROR_NONE;
		}

	}

endif;