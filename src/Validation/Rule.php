<?php

declare(strict_types=1);

namespace Italix\Forms\Validation;

/**
 * Represents a validation rule that can be applied to a form field.
 *
 * Rules can be created using static factory methods for common validations,
 * or custom rules can be created for specific use cases.
 *
 * Example:
 *
 *     $field->rules(
 *         Rule::required(),
 *         Rule::email(),
 *         Rule::max_length(255)
 *     );
 *
 *     // Or with custom messages
 *     $field->rules(
 *         Rule::required('Please enter your email'),
 *         Rule::email('Invalid email format')
 *     );
 */
class Rule
{
    private function __construct(
        public readonly string $name,
        public readonly array $params = [],
        public readonly ?string $message = null,
    ) {}

    // =========================================================================
    // Presence Rules
    // =========================================================================

    /**
     * Field is required (cannot be empty).
     */
    public static function required(?string $message = null): self
    {
        return new self('required', [], $message ?? 'This field is required');
    }

    /**
     * Field is required if another field has a specific value.
     */
    public static function required_if(string $field, mixed $value, ?string $message = null): self
    {
        return new self('required_if', ['field' => $field, 'value' => $value], $message ?? 'This field is required');
    }

    /**
     * Field is required unless another field has a specific value.
     */
    public static function required_unless(string $field, mixed $value, ?string $message = null): self
    {
        return new self('required_unless', ['field' => $field, 'value' => $value], $message ?? 'This field is required');
    }

    // =========================================================================
    // Format Rules
    // =========================================================================

    /**
     * Field must be a valid email address.
     */
    public static function email(?string $message = null): self
    {
        return new self('email', [], $message ?? 'Please enter a valid email address');
    }

    /**
     * Field must be a valid URL.
     */
    public static function url(?string $message = null): self
    {
        return new self('url', [], $message ?? 'Please enter a valid URL');
    }

    /**
     * Field must match a regular expression pattern.
     */
    public static function pattern(string $regex, ?string $message = null): self
    {
        return new self('pattern', ['regex' => $regex], $message ?? 'Invalid format');
    }

    /**
     * Field must contain only alphabetic characters.
     */
    public static function alpha(?string $message = null): self
    {
        return new self('alpha', [], $message ?? 'Only letters are allowed');
    }

    /**
     * Field must contain only alphanumeric characters.
     */
    public static function alpha_num(?string $message = null): self
    {
        return new self('alpha_num', [], $message ?? 'Only letters and numbers are allowed');
    }

    /**
     * Field must contain only alphanumeric characters, dashes, and underscores.
     */
    public static function alpha_dash(?string $message = null): self
    {
        return new self('alpha_dash', [], $message ?? 'Only letters, numbers, dashes, and underscores are allowed');
    }

    /**
     * Field must be numeric.
     */
    public static function numeric(?string $message = null): self
    {
        return new self('numeric', [], $message ?? 'Must be a number');
    }

    /**
     * Field must be an integer.
     */
    public static function integer(?string $message = null): self
    {
        return new self('integer', [], $message ?? 'Must be a whole number');
    }

    /**
     * Field must be a valid date.
     */
    public static function date(?string $message = null): self
    {
        return new self('date', [], $message ?? 'Please enter a valid date');
    }

    /**
     * Field must match a date format.
     */
    public static function date_format(string $format, ?string $message = null): self
    {
        return new self('date_format', ['format' => $format], $message ?? "Date must match format: {$format}");
    }

    // =========================================================================
    // Size/Length Rules
    // =========================================================================

    /**
     * Field must have at least the specified length (for strings) or value (for numbers).
     */
    public static function min(int|float $value, ?string $message = null): self
    {
        return new self('min', ['value' => $value], $message ?? "Minimum value is {$value}");
    }

    /**
     * Field must not exceed the specified length (for strings) or value (for numbers).
     */
    public static function max(int|float $value, ?string $message = null): self
    {
        return new self('max', ['value' => $value], $message ?? "Maximum value is {$value}");
    }

    /**
     * String must have at least the specified number of characters.
     */
    public static function min_length(int $length, ?string $message = null): self
    {
        return new self('min_length', ['length' => $length], $message ?? "Minimum {$length} characters required");
    }

    /**
     * String must not exceed the specified number of characters.
     */
    public static function max_length(int $length, ?string $message = null): self
    {
        return new self('max_length', ['length' => $length], $message ?? "Maximum {$length} characters allowed");
    }

    /**
     * Value must be between min and max (inclusive).
     */
    public static function between(int|float $min, int|float $max, ?string $message = null): self
    {
        return new self('between', ['min' => $min, 'max' => $max], $message ?? "Value must be between {$min} and {$max}");
    }

    /**
     * String length must be between min and max characters (inclusive).
     */
    public static function length_between(int $min, int $max, ?string $message = null): self
    {
        return new self('length_between', ['min' => $min, 'max' => $max], $message ?? "Length must be between {$min} and {$max} characters");
    }

    /**
     * String must have exactly the specified length.
     */
    public static function length(int $length, ?string $message = null): self
    {
        return new self('length', ['length' => $length], $message ?? "Must be exactly {$length} characters");
    }

    // =========================================================================
    // Comparison Rules
    // =========================================================================

    /**
     * Field must be one of the specified values.
     */
    public static function in(array $values, ?string $message = null): self
    {
        return new self('in', ['values' => $values], $message ?? 'Invalid selection');
    }

    /**
     * Field must not be one of the specified values.
     */
    public static function not_in(array $values, ?string $message = null): self
    {
        return new self('not_in', ['values' => $values], $message ?? 'Invalid selection');
    }

    /**
     * Field must match another field's value (e.g., password confirmation).
     */
    public static function confirmed(string $field = 'confirmation', ?string $message = null): self
    {
        return new self('confirmed', ['field' => $field], $message ?? 'Confirmation does not match');
    }

    /**
     * Field must match another field's value.
     */
    public static function same(string $field, ?string $message = null): self
    {
        return new self('same', ['field' => $field], $message ?? "Must match {$field}");
    }

    /**
     * Field must be different from another field's value.
     */
    public static function different(string $field, ?string $message = null): self
    {
        return new self('different', ['field' => $field], $message ?? "Must be different from {$field}");
    }

    /**
     * Field must be greater than a value or another field.
     */
    public static function gt(int|float|string $value, ?string $message = null): self
    {
        return new self('gt', ['value' => $value], $message ?? "Must be greater than {$value}");
    }

    /**
     * Field must be greater than or equal to a value or another field.
     */
    public static function gte(int|float|string $value, ?string $message = null): self
    {
        return new self('gte', ['value' => $value], $message ?? "Must be at least {$value}");
    }

    /**
     * Field must be less than a value or another field.
     */
    public static function lt(int|float|string $value, ?string $message = null): self
    {
        return new self('lt', ['value' => $value], $message ?? "Must be less than {$value}");
    }

    /**
     * Field must be less than or equal to a value or another field.
     */
    public static function lte(int|float|string $value, ?string $message = null): self
    {
        return new self('lte', ['value' => $value], $message ?? "Must be at most {$value}");
    }

    // =========================================================================
    // Date Comparison Rules
    // =========================================================================

    /**
     * Date must be before another date.
     */
    public static function before(string $date, ?string $message = null): self
    {
        return new self('before', ['date' => $date], $message ?? "Must be before {$date}");
    }

    /**
     * Date must be before or equal to another date.
     */
    public static function before_or_equal(string $date, ?string $message = null): self
    {
        return new self('before_or_equal', ['date' => $date], $message ?? "Must be on or before {$date}");
    }

    /**
     * Date must be after another date.
     */
    public static function after(string $date, ?string $message = null): self
    {
        return new self('after', ['date' => $date], $message ?? "Must be after {$date}");
    }

    /**
     * Date must be after or equal to another date.
     */
    public static function after_or_equal(string $date, ?string $message = null): self
    {
        return new self('after_or_equal', ['date' => $date], $message ?? "Must be on or after {$date}");
    }

    // =========================================================================
    // Database Rules
    // =========================================================================

    /**
     * Value must be unique in the database table.
     */
    public static function unique(string $table, ?string $column = null, ?string $message = null): self
    {
        return new self('unique', ['table' => $table, 'column' => $column], $message ?? 'This value is already taken');
    }

    /**
     * Value must exist in the database table.
     */
    public static function exists(string $table, ?string $column = null, ?string $message = null): self
    {
        return new self('exists', ['table' => $table, 'column' => $column], $message ?? 'The selected value is invalid');
    }

    // =========================================================================
    // File Rules
    // =========================================================================

    /**
     * Field must be an uploaded file.
     */
    public static function file(?string $message = null): self
    {
        return new self('file', [], $message ?? 'Must be a file');
    }

    /**
     * File must be an image.
     */
    public static function image(?string $message = null): self
    {
        return new self('image', [], $message ?? 'Must be an image');
    }

    /**
     * File must have one of the specified MIME types.
     */
    public static function mimes(array $types, ?string $message = null): self
    {
        $typeList = implode(', ', $types);
        return new self('mimes', ['types' => $types], $message ?? "File must be of type: {$typeList}");
    }

    /**
     * File must not exceed the specified size in kilobytes.
     */
    public static function max_file_size(int $kilobytes, ?string $message = null): self
    {
        return new self('max_file_size', ['size' => $kilobytes], $message ?? "File must not exceed {$kilobytes}KB");
    }

    // =========================================================================
    // Custom Rules
    // =========================================================================

    /**
     * Create a custom validation rule.
     *
     * @param string $name Rule name (for identification)
     * @param array $params Rule parameters
     * @param string|null $message Error message
     */
    public static function custom(string $name, array $params = [], ?string $message = null): self
    {
        return new self($name, $params, $message);
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Export the rule as an array.
     *
     * Useful for serializing to JSON for JavaScript validation libraries.
     */
    public function to_array(): array
    {
        return [
            'rule' => $this->name,
            'params' => $this->params,
            'message' => $this->message,
        ];
    }

    /**
     * Get a string representation of the rule.
     */
    public function __toString(): string
    {
        if (empty($this->params)) {
            return $this->name;
        }

        $paramStr = implode(',', array_map(
            fn($v) => is_array($v) ? implode(',', $v) : (string)$v,
            array_values($this->params)
        ));

        return "{$this->name}:{$paramStr}";
    }
}
