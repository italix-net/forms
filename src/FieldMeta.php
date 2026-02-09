<?php

declare(strict_types=1);

namespace Italix\Forms;

use Italix\Contracts\ColumnMeta;
use Italix\Forms\Validation\Rule;
use InvalidArgumentException;

/**
 * Metadata for a single form field.
 *
 * FieldMeta wraps a ColumnMeta and adds form-specific information such as
 * labels, placeholders, validation rules, and layout options.
 *
 * Example:
 *
 *     $field = new FieldMeta($column);
 *     $field
 *         ->label('Email Address')
 *         ->placeholder('you@example.com')
 *         ->help('We will never share your email')
 *         ->rules(Rule::required(), Rule::email());
 */
class FieldMeta
{
    /** @var ColumnMeta */
    private $column;

    // Display properties
    /** @var string|null */
    private $label = null;

    /** @var string|null */
    private $placeholder = null;

    /** @var string|null */
    private $help = null;

    // Input properties
    /** @var string|null */
    private $input_type = null;

    /** @var array */
    private $options = [];

    /** @var array */
    private $attributes = [];

    // State properties
    /** @var bool */
    private $hidden = false;

    /** @var bool */
    private $readonly = false;

    /** @var bool */
    private $disabled = false;

    /** @var bool */
    private $sensitive = false;

    // Layout properties
    /** @var int|null */
    private $order = null;

    /** @var string|null */
    private $group = null;

    /** @var int|null */
    private $colspan = null;

    /** @var string|null */
    private $width = null;

    // CSS classes
    /** @var string|null */
    private $wrapper_class = null;

    /** @var string|null */
    private $input_class = null;

    /** @var string|null */
    private $label_class = null;

    /** @var string|null */
    private $help_class = null;

    // Validation
    /** @var Rule[] */
    private $rules = [];

    /**
     * Known built-in rule names for string parsing validation.
     *
     * @var array<string, bool>
     */
    private static $known_rules = [
        'required' => true, 'email' => true, 'url' => true, 'numeric' => true,
        'integer' => true, 'alpha' => true, 'alpha_num' => true, 'alpha_dash' => true,
        'date' => true, 'file' => true, 'image' => true, 'min' => true, 'max' => true,
        'min_length' => true, 'max_length' => true, 'length' => true, 'between' => true,
        'length_between' => true, 'pattern' => true, 'in' => true, 'not_in' => true,
        'confirmed' => true, 'same' => true, 'different' => true, 'date_format' => true,
        'before' => true, 'after' => true, 'mimes' => true, 'max_file_size' => true,
        'gt' => true, 'gte' => true, 'lt' => true, 'lte' => true,
        'before_or_equal' => true, 'after_or_equal' => true,
        'required_if' => true, 'required_unless' => true,
        'unique' => true, 'exists' => true,
    ];

    public function __construct(ColumnMeta $column)
    {
        $this->column = $column;
    }

    /**
     * Get the underlying column metadata.
     *
     * @return ColumnMeta
     */
    public function column(): ColumnMeta
    {
        return $this->column;
    }

    // =========================================================================
    // Display Setters (Fluent)
    // =========================================================================

    /**
     * Set the field label.
     *
     * @param string $label
     * @return self
     */
    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set the placeholder text.
     *
     * @param string $placeholder
     * @return self
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Set the help text displayed below the field.
     *
     * @param string $help
     * @return self
     */
    public function help(string $help): self
    {
        $this->help = $help;
        return $this;
    }

    // =========================================================================
    // Input Setters (Fluent)
    // =========================================================================

    /**
     * Set the input type (text, email, password, select, textarea, etc.).
     *
     * @param string $type
     * @return self
     */
    public function type(string $type): self
    {
        $this->input_type = $type;
        return $this;
    }

    /**
     * Set options for select, radio, or checkbox inputs.
     *
     * @param array $options Associative array of value => label pairs
     * @return self
     */
    public function options(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Set a single HTML attribute.
     *
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function attr(string $name, $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Set multiple HTML attributes at once.
     *
     * @param array $attributes
     * @return self
     */
    public function attrs(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    // =========================================================================
    // State Setters (Fluent)
    // =========================================================================

    /**
     * Mark the field as hidden (won't be rendered in forms).
     *
     * @param bool $hidden
     * @return self
     */
    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Mark the field as readonly.
     *
     * @param bool $readonly
     * @return self
     */
    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Mark the field as disabled.
     *
     * @param bool $disabled
     * @return self
     */
    public function disabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * Mark the field as sensitive (excluded from JSON/array exports by default).
     *
     * Use this for fields like passwords, API keys, or tokens that should
     * not be serialized to the frontend.
     *
     * @param bool $sensitive
     * @return self
     */
    public function sensitive(bool $sensitive = true): self
    {
        $this->sensitive = $sensitive;
        return $this;
    }

    // =========================================================================
    // Layout Setters (Fluent)
    // =========================================================================

    /**
     * Set the display order (lower numbers first).
     *
     * @param int $order
     * @return self
     */
    public function order(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Assign the field to a section/group.
     *
     * @param string $group
     * @return self
     */
    public function group(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Set the column span in a grid layout.
     *
     * @param int $span
     * @return self
     */
    public function colspan(int $span): self
    {
        $this->colspan = $span;
        return $this;
    }

    /**
     * Set a custom width (CSS value).
     *
     * @param string $width
     * @return self
     */
    public function width(string $width): self
    {
        $this->width = $width;
        return $this;
    }

    // =========================================================================
    // CSS Class Setters (Fluent)
    // =========================================================================

    /**
     * Set the wrapper element CSS class.
     *
     * @param string $class
     * @return self
     */
    public function wrapper_class(string $class): self
    {
        $this->wrapper_class = $class;
        return $this;
    }

    /**
     * Set the input element CSS class.
     *
     * @param string $class
     * @return self
     */
    public function input_class(string $class): self
    {
        $this->input_class = $class;
        return $this;
    }

    /**
     * Set the label element CSS class.
     *
     * @param string $class
     * @return self
     */
    public function label_class(string $class): self
    {
        $this->label_class = $class;
        return $this;
    }

    /**
     * Set the help text CSS class.
     *
     * @param string $class
     * @return self
     */
    public function help_class(string $class): self
    {
        $this->help_class = $class;
        return $this;
    }

    // =========================================================================
    // Validation Setters (Fluent)
    // =========================================================================

    /**
     * Add validation rules to the field.
     *
     * Accepts Rule objects or shorthand strings like 'required', 'email',
     * 'max_length:255', etc.
     *
     * Example:
     *
     *     $field->rules(Rule::required(), Rule::email());
     *     $field->rules('required', 'email', 'max_length:255');
     *
     * @param Rule|string ...$rules
     * @return self
     * @throws InvalidArgumentException If a string rule is unknown or has invalid parameters
     */
    public function rules(...$rules): self
    {
        foreach ($rules as $rule) {
            if ($rule instanceof Rule) {
                $this->rules[] = $rule;
            } elseif (is_string($rule)) {
                $this->rules[] = $this->parse_rule_string($rule);
            } else {
                throw new InvalidArgumentException(
                    'Rule must be a Rule instance or a string, got ' . gettype($rule)
                );
            }
        }
        return $this;
    }

    /**
     * Parse a rule string like 'max_length:255' into a Rule object.
     *
     * @param string $rule
     * @return Rule
     * @throws InvalidArgumentException If the rule name is unknown or parameters are invalid
     */
    private function parse_rule_string(string $rule): Rule
    {
        if (strpos($rule, ':') !== false) {
            [$name, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
            // Filter out empty strings from params
            $params = array_values(array_filter($params, fn($p) => $p !== ''));
        } else {
            $name = $rule;
            $params = [];
        }

        if (!isset(self::$known_rules[$name])) {
            throw new InvalidArgumentException(
                "Unknown rule '{$name}'. Use Rule::custom() for custom validation rules."
            );
        }

        switch ($name) {
            // No-param rules
            case 'required':
                return Rule::required();
            case 'email':
                return Rule::email();
            case 'url':
                return Rule::url();
            case 'numeric':
                return Rule::numeric();
            case 'integer':
                return Rule::integer();
            case 'alpha':
                return Rule::alpha();
            case 'alpha_num':
                return Rule::alpha_num();
            case 'alpha_dash':
                return Rule::alpha_dash();
            case 'date':
                return Rule::date();
            case 'file':
                return Rule::file();
            case 'image':
                return Rule::image();

            // Single numeric param
            case 'min':
                $this->assert_param_count($name, $params, 1);
                return Rule::min((float)$params[0]);
            case 'max':
                $this->assert_param_count($name, $params, 1);
                return Rule::max((float)$params[0]);
            case 'min_length':
                $this->assert_param_count($name, $params, 1);
                return Rule::min_length((int)$params[0]);
            case 'max_length':
                $this->assert_param_count($name, $params, 1);
                return Rule::max_length((int)$params[0]);
            case 'length':
                $this->assert_param_count($name, $params, 1);
                return Rule::length((int)$params[0]);
            case 'max_file_size':
                $this->assert_param_count($name, $params, 1);
                return Rule::max_file_size((int)$params[0]);

            // Two numeric params
            case 'between':
                $this->assert_param_count($name, $params, 2);
                return Rule::between((float)$params[0], (float)$params[1]);
            case 'length_between':
                $this->assert_param_count($name, $params, 2);
                return Rule::length_between((int)$params[0], (int)$params[1]);

            // Single string param
            case 'pattern':
                $this->assert_param_count($name, $params, 1);
                return Rule::pattern($params[0]);
            case 'confirmed':
                return Rule::confirmed($params[0] ?? 'confirmation');
            case 'same':
                $this->assert_param_count($name, $params, 1);
                return Rule::same($params[0]);
            case 'different':
                $this->assert_param_count($name, $params, 1);
                return Rule::different($params[0]);
            case 'date_format':
                $this->assert_param_count($name, $params, 1);
                return Rule::date_format($params[0]);
            case 'before':
                $this->assert_param_count($name, $params, 1);
                return Rule::before($params[0]);
            case 'after':
                $this->assert_param_count($name, $params, 1);
                return Rule::after($params[0]);
            case 'before_or_equal':
                $this->assert_param_count($name, $params, 1);
                return Rule::before_or_equal($params[0]);
            case 'after_or_equal':
                $this->assert_param_count($name, $params, 1);
                return Rule::after_or_equal($params[0]);

            // Comparison rules (single param)
            case 'gt':
                $this->assert_param_count($name, $params, 1);
                return Rule::gt($params[0]);
            case 'gte':
                $this->assert_param_count($name, $params, 1);
                return Rule::gte($params[0]);
            case 'lt':
                $this->assert_param_count($name, $params, 1);
                return Rule::lt($params[0]);
            case 'lte':
                $this->assert_param_count($name, $params, 1);
                return Rule::lte($params[0]);

            // Array-param rules
            case 'in':
                $this->assert_param_count($name, $params, 1, true);
                return Rule::in($params);
            case 'not_in':
                $this->assert_param_count($name, $params, 1, true);
                return Rule::not_in($params);
            case 'mimes':
                $this->assert_param_count($name, $params, 1, true);
                return Rule::mimes($params);

            // Two-param string rules (require_if, required_unless, unique, exists)
            case 'required_if':
                $this->assert_param_count($name, $params, 2);
                return Rule::required_if($params[0], $params[1]);
            case 'required_unless':
                $this->assert_param_count($name, $params, 2);
                return Rule::required_unless($params[0], $params[1]);
            case 'unique':
                $this->assert_param_count($name, $params, 1);
                return Rule::unique($params[0], $params[1] ?? null);
            case 'exists':
                $this->assert_param_count($name, $params, 1);
                return Rule::exists($params[0], $params[1] ?? null);

            default:
                // This should never be reached due to the known_rules check above
                throw new InvalidArgumentException("Unknown rule '{$name}'.");
        }
    }

    /**
     * Assert that a rule string has the required number of parameters.
     *
     * @param string $rule_name
     * @param array $params
     * @param int $min_count Minimum number of parameters required
     * @param bool $at_least If true, requires at least $min_count params (for array rules)
     * @throws InvalidArgumentException
     */
    private function assert_param_count(string $rule_name, array $params, int $min_count, bool $at_least = false): void
    {
        if ($at_least) {
            if (count($params) < $min_count) {
                throw new InvalidArgumentException(
                    "Rule '{$rule_name}' requires at least {$min_count} parameter(s), got " . count($params) . "."
                );
            }
        } else {
            if (count($params) < $min_count) {
                throw new InvalidArgumentException(
                    "Rule '{$rule_name}' requires {$min_count} parameter(s), got " . count($params)
                    . ". Use format: '{$rule_name}:" . implode(',', array_fill(0, $min_count, 'value')) . "'."
                );
            }
        }
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /**
     * Get the field name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->column->get_name();
    }

    /**
     * Get the field label (auto-generated from name if not set).
     *
     * @return string
     */
    public function get_label(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        return ucwords(str_replace('_', ' ', $this->column->get_name()));
    }

    /**
     * Get the placeholder text.
     *
     * @return string|null
     */
    public function get_placeholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * Get the help text.
     *
     * @return string|null
     */
    public function get_help(): ?string
    {
        return $this->help;
    }

    /**
     * Get the input type (auto-inferred from column type if not set).
     *
     * @return string
     */
    public function get_type(): string
    {
        if ($this->input_type !== null) {
            return $this->input_type;
        }

        if (!empty($this->options)) {
            return 'select';
        }

        return $this->infer_input_type();
    }

    /**
     * Get the options for select/radio/checkbox inputs.
     *
     * @return array
     */
    public function get_options(): array
    {
        return $this->options;
    }

    /**
     * Get HTML attributes.
     *
     * @return array
     */
    public function get_attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get the validation rules.
     *
     * @return Rule[]
     */
    public function get_rules(): array
    {
        return $this->rules;
    }

    /**
     * Get the display order.
     *
     * @return int|null
     */
    public function get_order(): ?int
    {
        return $this->order;
    }

    /**
     * Get the section/group name.
     *
     * @return string|null
     */
    public function get_group(): ?string
    {
        return $this->group;
    }

    /**
     * Get the column span.
     *
     * @return int|null
     */
    public function get_colspan(): ?int
    {
        return $this->colspan;
    }

    /**
     * Get the custom width.
     *
     * @return string|null
     */
    public function get_width(): ?string
    {
        return $this->width;
    }

    /**
     * Get the wrapper CSS class.
     *
     * @return string|null
     */
    public function get_wrapper_class(): ?string
    {
        return $this->wrapper_class;
    }

    /**
     * Get the input CSS class.
     *
     * @return string|null
     */
    public function get_input_class(): ?string
    {
        return $this->input_class;
    }

    /**
     * Get the label CSS class.
     *
     * @return string|null
     */
    public function get_label_class(): ?string
    {
        return $this->label_class;
    }

    /**
     * Get the help text CSS class.
     *
     * @return string|null
     */
    public function get_help_class(): ?string
    {
        return $this->help_class;
    }

    /**
     * Check if the field is hidden.
     *
     * @return bool
     */
    public function is_hidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Check if the field is readonly.
     *
     * @return bool
     */
    public function is_readonly(): bool
    {
        return $this->readonly;
    }

    /**
     * Check if the field is disabled.
     *
     * @return bool
     */
    public function is_disabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Check if the field is marked as sensitive.
     *
     * @return bool
     */
    public function is_sensitive(): bool
    {
        return $this->sensitive;
    }

    /**
     * Check if the field is required.
     *
     * A field is required if:
     * - The underlying column is not nullable, OR
     * - There's an explicit 'required' validation rule
     *
     * @return bool
     */
    public function is_required(): bool
    {
        if (!$this->column->is_nullable()) {
            return true;
        }

        foreach ($this->rules as $rule) {
            if ($rule->name === 'required') {
                return true;
            }
        }

        return false;
    }

    /**
     * Infer the HTML input type from the column data type.
     *
     * @return string
     */
    private function infer_input_type(): string
    {
        $col_type = strtoupper($this->column->get_type());

        if ($col_type === 'BOOLEAN') {
            return 'checkbox';
        }
        if ($col_type === 'TEXT') {
            return 'textarea';
        }
        if ($col_type === 'DATE') {
            return 'date';
        }
        if (in_array($col_type, ['DATETIME', 'TIMESTAMP'], true)) {
            return 'datetime-local';
        }
        if ($col_type === 'TIME') {
            return 'time';
        }
        if (in_array($col_type, ['INTEGER', 'BIGINT', 'SMALLINT', 'SERIAL', 'BIGSERIAL'], true)) {
            return 'number';
        }
        if (in_array($col_type, ['DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION', 'FLOAT'], true)) {
            return 'number';
        }
        if (in_array($col_type, ['JSON', 'JSONB'], true)) {
            return 'textarea';
        }

        return 'text';
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Export all metadata as an array.
     *
     * Useful for JSON serialization, template engines, or JavaScript form builders.
     * Sensitive fields are excluded from the export by default.
     *
     * @param bool $include_sensitive Whether to include sensitive fields
     * @return array
     */
    public function to_array(bool $include_sensitive = false): array
    {
        if ($this->sensitive && !$include_sensitive) {
            return [
                'name' => $this->get_name(),
                'label' => $this->get_label(),
                'type' => $this->get_type(),
                'sensitive' => true,
                'required' => $this->is_required(),
                'hidden' => $this->is_hidden(),
                'order' => $this->get_order(),
                'group' => $this->get_group(),
            ];
        }

        return [
            'name' => $this->get_name(),
            'label' => $this->get_label(),
            'type' => $this->get_type(),
            'placeholder' => $this->get_placeholder(),
            'help' => $this->get_help(),
            'options' => $this->get_options(),
            'rules' => array_map(fn(Rule $r) => $r->to_array(), $this->rules),
            'required' => $this->is_required(),
            'hidden' => $this->is_hidden(),
            'readonly' => $this->is_readonly(),
            'disabled' => $this->is_disabled(),
            'order' => $this->get_order(),
            'group' => $this->get_group(),
            'colspan' => $this->get_colspan(),
            'width' => $this->get_width(),
            'attributes' => $this->get_attributes(),
            'classes' => [
                'wrapper' => $this->get_wrapper_class(),
                'input' => $this->get_input_class(),
                'label' => $this->get_label_class(),
                'help' => $this->get_help_class(),
            ],
        ];
    }
}
