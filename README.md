# Comparator Library

The Comparator library provides a flexible and powerful way to compare values in PHP, supporting various data types and
comparison operators. It is designed to be easy to use while offering extensive functionality for complex comparison
scenarios.

### Features

- Supports multiple data types: string, float, int, bool, array, object, and JSON.
- Customizable comparison behavior, including case sensitivity and epsilon for floating-point comparisons.
- Extensive set of comparison operators, including mathematical comparisons, regex matching, string containment, and
  custom comparison functions.
- Allows for both strict and loose comparisons.
- Configurable match type for array comparisons to require all or any elements to match.

### Installation

Ensure you have the package installed in your project. If not, you can typically include it using Composer:

```bash
composer require arraypress/comparator
```

## Global Comparison Function

The library provides a convenient global `comparator` function to facilitate easy comparisons between two values with
extensive customization options. This function leverages the `Comparator` class internally while offering additional
flexibility for custom comparison logic and error handling.

### Function Signature

```php
comparator(
    mixed $value1,
    mixed $value2,
    string $operator,
    ?string $type = null,
    bool $caseSensitive = true,
    bool $useEpsilon = false,
    string $matchType = 'all',
    ?callable $customFunction = null,
    ?callable $errorCallback = null
): ?bool
```

### Parameters

- `$value1`, `$value2`: The values to be compared.
- `$operator`: Defines how the values should be compared. See the operator table for supported operators.
- `$type`: Specifies the type of comparison. If `null`, the type is auto-detected.
- `$caseSensitive`: Determines if string comparisons are case-sensitive. Defaults to `true`.
- `$useEpsilon`: Utilize epsilon for floating-point comparisons to handle precision issues.
- `$matchType`: The match type for array comparisons - 'all' or 'any'. Defaults to `all`.
- `$customFunction`: A custom function for comparison, used when the operator is set to 'custom'.
- `$errorCallback`: A callback for error handling, invoked if an exception occurs during comparison.

### Return Value

Returns a boolean indicating the result of the comparison, or `null` in case of an error.

### Usage Example

```php
$result = comparator( 'hello', 'Hello', '=', null, false ); // Case-insensitive string comparison
if ( $result ) {
    // Values are considered equal
}
```

This function simplifies complex comparisons by abstracting the instantiation and setup of the `Comparator` class,
allowing developers to perform comparisons with minimal setup and handling errors gracefully.

## Supported Comparison Operators and Their Aliases

The `Comparator` class provides a flexible way to compare different types of values by supporting a wide range of
comparison operators. To make the class more intuitive and user-friendly, it allows the use of human-readable aliases
for these operators. Below is a table that maps these aliases to their corresponding standard operator symbols,
providing an easy reference for users to construct their comparison expressions.

| Operator Symbol    | Human-Readable Aliases                               | Description                                                                    |
|--------------------|------------------------------------------------------|--------------------------------------------------------------------------------|
| `==`               | equal_to, equals, =                                  | Checks if two values are equal                                                 |
| `===`              | strict_equal_to                                      | Checks if two values are strictly equal (identical)                            |
| `!=`               | not_equal_to, not_equals                             | Checks if two values are not equal                                             |
| `!==`              | strict_not_equal_to                                  | Checks if two values are strictly not equal                                    |
| `>`                | more_than, more, greater_than, greater               | Checks if value on the left is greater than the value on the right             |
| `<`                | less_than, less                                      | Checks if value on the left is less than the value on the right                |
| `>=`               | at_least, greater_than_or_equal_to, greater_or_equal | Checks if value on the left is greater than or equal to the value on the right |
| `<=`               | at_most, less_than_or_equal_to, less_or_equal        | Checks if value on the left is less than or equal to the value on the right    |
| `starts`           | startswith, starts_with, begins                      | Checks if a string starts with a specified substring                           |
| `contains`         | (no aliases)                                         | Checks if a string contains a specified substring                              |
| `does_not_contain` | not_contains                                         | Checks if a string does not contain a specified substring                      |
| `ends`             | endswith, ends_with                                  | Checks if a string ends with a specified substring                             |

This feature enhances the readability and expressiveness of your code, making it easier to understand and maintain.

## Automatic Type Detection

The `Comparator` class is equipped with an intelligent automatic type detection system, designed to simplify the
comparison process between different types of values. This feature automatically identifies the type of the provided
values and applies the most appropriate comparison logic based on their types. The table below explains the types that
can be automatically detected and the conditions under which each type is identified.

| Detected Type | Conditions                                                                                         |
|---------------|----------------------------------------------------------------------------------------------------|
| `float`       | At least one of the values is a floating-point number.                                             |
| `int`         | Both values are integers, or one is an integer (if no floating-point numbers are detected).        |
| `string`      | Both values are strings. Special case: If either value is a valid JSON string, `json` is returned. |
| `json`        | Both values are strings formatted as valid JSON.                                                   |
| `bool`        | At least one of the values is a boolean.                                                           |
| `array`       | Both values are arrays.                                                                            |
| `object`      | Both values are objects.                                                                           |
| `date`        | Both values are strings that can be converted to valid dates using `strtotime`.                    |
| `unknown`     | The type cannot be determined or does not match any of the above criteria.                         |

This automatic type detection facilitates a more dynamic and error-resistant comparison process, allowing users to
compare different types of data without the need for explicit type casting or manual type checks. It ensures that the
comparison logic is always aligned with the nature of the data being compared, enhancing the robustness and versatility
of the `Comparator` class.

### Example Test 1: Integer Comparison

Compare two integers to check if the first is less than the second.

```php
$result = comparator( 5, 10, '<' ) ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 2: Float Comparison with Epsilon

Compare two floats for equality, considering a small margin (epsilon).

```php
$result = comparator( 0.1 + 0.2, 0.3, '=', 'float', true, true ) ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 3: String Case-Sensitive Comparison

Compare two strings with case sensitivity.

```php
$result = comparator( 'Hello', 'hello', '=', 'string', true ) ? 'Pass' : 'Fail';
// Expected output: Fail (due to case sensitivity)
```

### Example Test 4: Custom Function Comparison

Use a custom function for comparison. In this example, checking if the first number is double the second.

```php
$customFunction = function ( $a, $b ) {
    return $a == ( $b * 2 );
};
$result = comparator( 10, 5, 'custom', null, true, false, 'all', $customFunction ) ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 5: JSON String Comparison

Compare two JSON strings for equality.

```php
$json1 = '{"name": "John", "age": 30}';
$json2 = '{"name": "John", "age": 30}';
$result = comparator( $json1, $json2, '=', 'json' ) ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 6: Regular Expression Comparison

Use a regular expression to compare if a string matches the pattern.

```php
$result = comparator( 'test123', '^test', 'regex' ) ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 7: Array Comparison

Compare two arrays for equality.

```php
$array1 = [1, 2, 3];
$array2 = [1, 2, 3];
$result = comparator( $array1, $array2, '=', 'array' ) ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 8: Boolean Comparison

Compare two booleans for equality.

```php
$result = comparator( true, true, '=', 'bool' ) ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 9: Object Comparison

Compare two objects for equality.

```php
$obj1 = (object) [ 'key' => 'value' ];
$obj2 = (object) [ 'key' => 'value' ];
$result = comparator( $obj1, $obj2, '=', 'object' ) ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 10: Date Comparison

Compare two dates to check if the first is less than the second.

```php
$result = comparator('2023-01-01', '2024-01-01', 'less_than', 'date') ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 11: String Contains

Check if a string contains another string.

```php
$result = comparator('Hello world', 'world', 'contains', 'string') ? 'Pass' : 'Fail';
// Expected output: Fail
```

### Example Test 12: String Starts With

Check if a string starts with another string.

```php
$result = comparator('Hello world', 'Hello', 'startswith', 'string') ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 13: String Ends With

Check if a string ends with another string.

```php
$result = comparator('Hello world', 'world', 'endswith', 'string') ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 14: Not Equal Comparison

Compare two integers to check if they are not equal.

```php
$result = comparator(5, 10, 'not_equal_to') ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 15: Greater Than Comparison

Compare two integers to check if the first is greater than the second.

```php
$result = comparator(10, 5, 'more_than') ? 'Pass' : 'Fail';
// Expected output: Pass
```

### Example Test 16: Less Than or Equal To Comparison

Compare two integers to check if the first is less than or equal to the second.

## Contributions

Contributions to this library are highly appreciated. Raise issues on GitHub or submit pull requests for bug
fixes or new features. Share feedback and suggestions for improvements.

## License: GPLv2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.