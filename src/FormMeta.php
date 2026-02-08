<?php

declare(strict_types=1);

namespace Italix\Forms;

use Italix\Forms\Contracts\TableMeta;
use Generator;
use InvalidArgumentException;

/**
 * Form metadata wrapper for a table/entity.
 *
 * FormMeta wraps any object implementing TableMeta and provides form-specific
 * metadata like labels, placeholders, validation rules, and layout organization.
 *
 * Example:
 *
 *     // Create from any TableMeta implementation
 *     $form = new FormMeta($usersTable);
 *
 *     // Or use the helper function
 *     $form = form_meta($usersTable);
 *
 *     // Configure fields
 *     $form->field('email')
 *         ->label('Email Address')
 *         ->placeholder('you@example.com')
 *         ->rules(Rule::required(), Rule::email());
 *
 *     // Define sections
 *     $form->section('basic')
 *         ->title('Basic Information')
 *         ->columns(2);
 *
 *     // Iterate over fields
 *     foreach ($form->each() as $name => $field) {
 *         echo $field->get_label();
 *     }
 */
class FormMeta
{
    /** @var TableMeta */
    private $source;

    /** @var array<string, FieldMeta> */
    private $field_meta = [];

    /** @var array<string, FormSection> */
    private $sections = [];

    /** @var string[] */
    private $excluded = [];

    /** @var string|null */
    private $default_section = null;

    public function __construct(TableMeta $source)
    {
        $this->source = $source;
    }

    /**
     * Get the underlying TableMeta source.
     *
     * @return TableMeta
     */
    public function source(): TableMeta
    {
        return $this->source;
    }

    // =========================================================================
    // Field Configuration
    // =========================================================================

    /**
     * Get or create field metadata for a column.
     *
     * @param string $column The column name
     * @return FieldMeta The field metadata (created if not exists)
     * @throws InvalidArgumentException If the column doesn't exist
     */
    public function field(string $column): FieldMeta
    {
        if (!isset($this->field_meta[$column])) {
            $descriptor = $this->source->describe_column($column);

            if ($descriptor === null) {
                throw new InvalidArgumentException("Column '{$column}' not found in source");
            }

            $this->field_meta[$column] = new FieldMeta($descriptor);
        }

        return $this->field_meta[$column];
    }

    /**
     * Check if a field has been configured.
     *
     * @param string $column
     * @return bool
     */
    public function has_field(string $column): bool
    {
        return isset($this->field_meta[$column]);
    }

    /**
     * Configure multiple fields at once using an array.
     *
     * Example:
     *
     *     $form->fields([
     *         'email' => ['label' => 'Email', 'placeholder' => 'you@example.com'],
     *         'name' => ['label' => 'Full Name', 'order' => 1],
     *         'bio' => ['type' => 'textarea', 'group' => 'profile'],
     *     ]);
     *
     * @param array<string, array> $config Field configurations
     * @return self
     * @throws InvalidArgumentException If a method name is invalid
     */
    public function fields(array $config): self
    {
        foreach ($config as $column => $settings) {
            $field = $this->field($column);

            foreach ($settings as $method => $value) {
                if (!method_exists($field, $method)) {
                    throw new InvalidArgumentException(
                        "Invalid field configuration method '{$method}' for column '{$column}'. "
                        . "Check available methods on FieldMeta."
                    );
                }

                // Handle array values that should be spread as arguments
                if (is_array($value) && !in_array($method, ['options', 'attrs'], true)) {
                    $field->$method(...$value);
                } else {
                    $field->$method($value);
                }
            }
        }

        return $this;
    }

    /**
     * Exclude columns from the form.
     *
     * Excluded columns won't appear in iteration.
     *
     * @param string ...$columns Column names to exclude
     * @return self
     */
    public function exclude(string ...$columns): self
    {
        $this->excluded = array_merge($this->excluded, $columns);
        return $this;
    }

    /**
     * Get the list of excluded column names.
     *
     * @return string[]
     */
    public function get_excluded(): array
    {
        return $this->excluded;
    }

    // =========================================================================
    // Section Configuration
    // =========================================================================

    /**
     * Get or create a form section.
     *
     * @param string $name Section identifier
     * @return FormSection The section (created if not exists)
     */
    public function section(string $name): FormSection
    {
        if (!isset($this->sections[$name])) {
            $this->sections[$name] = new FormSection($name);
        }

        return $this->sections[$name];
    }

    /**
     * Check if a section has been defined.
     *
     * @param string $name
     * @return bool
     */
    public function has_section(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    /**
     * Set the default section for fields that don't specify one.
     *
     * @param string $name
     * @return self
     */
    public function default_section(string $name): self
    {
        $this->default_section = $name;
        return $this;
    }

    /**
     * Get all defined sections.
     *
     * @return array<string, FormSection>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    // =========================================================================
    // Iteration
    // =========================================================================

    /**
     * Iterate over all visible fields, sorted by order.
     *
     * This is the primary way to render form fields. Hidden and excluded
     * fields are automatically skipped.
     *
     * @return Generator<string, FieldMeta>
     */
    public function each(): Generator
    {
        $fields = [];

        foreach ($this->source->describe_columns() as $name => $column) {
            if (in_array($name, $this->excluded, true)) {
                continue;
            }

            $meta = $this->field_meta[$name] ?? new FieldMeta($column);

            if ($meta->is_hidden()) {
                continue;
            }

            $fields[$name] = $meta;
        }

        uasort($fields, function (FieldMeta $a, FieldMeta $b): int {
            $orderA = $a->get_order() ?? PHP_INT_MAX;
            $orderB = $b->get_order() ?? PHP_INT_MAX;
            return $orderA <=> $orderB;
        });

        foreach ($fields as $name => $meta) {
            yield $name => $meta;
        }
    }

    /**
     * Get all fields including hidden ones, sorted by order.
     *
     * @return Generator<string, FieldMeta>
     */
    public function all(): Generator
    {
        $fields = [];

        foreach ($this->source->describe_columns() as $name => $column) {
            if (in_array($name, $this->excluded, true)) {
                continue;
            }

            $fields[$name] = $this->field_meta[$name] ?? new FieldMeta($column);
        }

        uasort($fields, function (FieldMeta $a, FieldMeta $b): int {
            $orderA = $a->get_order() ?? PHP_INT_MAX;
            $orderB = $b->get_order() ?? PHP_INT_MAX;
            return $orderA <=> $orderB;
        });

        foreach ($fields as $name => $meta) {
            yield $name => $meta;
        }
    }

    /**
     * Get fields organized by section.
     *
     * @return array<string, array{section: FormSection|null, fields: array<string, FieldMeta>}>
     */
    public function by_section(): array
    {
        $result = [];
        $ungrouped = [];

        foreach ($this->each() as $name => $field) {
            $sectionName = $field->get_group() ?? $this->default_section;

            if ($sectionName !== null) {
                if (!isset($result[$sectionName])) {
                    $result[$sectionName] = [
                        'section' => $this->sections[$sectionName] ?? new FormSection($sectionName),
                        'fields' => [],
                    ];
                }
                $result[$sectionName]['fields'][$name] = $field;
            } else {
                $ungrouped[$name] = $field;
            }
        }

        uasort($result, function (array $a, array $b): int {
            return $a['section']->get_order() <=> $b['section']->get_order();
        });

        if (!empty($ungrouped)) {
            $result = ['_default' => ['section' => null, 'fields' => $ungrouped]] + $result;
        }

        return $result;
    }

    /**
     * Count the total number of visible fields.
     *
     * @return int
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->each() as $_) {
            $count++;
        }
        return $count;
    }

    // =========================================================================
    // Export
    // =========================================================================

    /**
     * Export the entire form configuration as an array.
     *
     * Useful for serialization, caching, or passing to template engines.
     * Sensitive fields are automatically redacted unless $include_sensitive is true.
     *
     * @param bool $include_sensitive Whether to include sensitive field data
     * @return array
     */
    public function to_array(bool $include_sensitive = false): array
    {
        $fields = [];
        foreach ($this->each() as $name => $field) {
            $fields[$name] = $field->to_array($include_sensitive);
        }

        $sections = [];
        foreach ($this->sections as $name => $section) {
            $sections[$name] = $section->to_array();
        }

        return [
            'fields' => $fields,
            'sections' => $sections,
            'excluded' => $this->excluded,
            'default_section' => $this->default_section,
        ];
    }

    /**
     * Export the form configuration as JSON.
     *
     * Useful for JavaScript form builders like React, Vue, etc.
     * Sensitive fields are automatically redacted unless $include_sensitive is true.
     *
     * @param int $flags JSON encoding flags
     * @param bool $include_sensitive Whether to include sensitive field data
     * @return string
     */
    public function to_json(int $flags = 0, bool $include_sensitive = false): string
    {
        $json = json_encode($this->to_array($include_sensitive), $flags);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode form metadata to JSON: ' . json_last_error_msg());
        }
        return $json;
    }
}
