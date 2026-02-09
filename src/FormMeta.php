<?php

declare(strict_types=1);

namespace Italix\Forms;

use Italix\Forms\Contracts\DelegatedTableMeta;
use Italix\Forms\Contracts\TableMeta;
use Generator;
use InvalidArgumentException;
use LogicException;

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

    /** @var TableMeta[] Resolved delegate chain (excluding the root source) */
    private $delegate_chain = [];

    /** @var string|null The delegate type name (e.g., 'ComicsBook') or '*' for wildcard */
    private $delegate_type = null;

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
     * Looks up in the source table first, then in delegate tables
     * if delegation is active.
     *
     * @param string $column The column name
     * @return FieldMeta The field metadata (created if not exists)
     * @throws InvalidArgumentException If the column doesn't exist
     */
    public function field(string $column): FieldMeta
    {
        if (!isset($this->field_meta[$column])) {
            $descriptor = $this->source->describe_column($column);

            // Also search delegate chain tables
            if ($descriptor === null && !empty($this->delegate_chain)) {
                foreach ($this->delegate_chain as $delegate) {
                    $descriptor = $delegate->describe_column($column);
                    if ($descriptor !== null) {
                        break;
                    }
                }
            }

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
    // Delegation
    // =========================================================================

    /**
     * Activate delegated type support by specifying the leaf type.
     *
     * The source must implement DelegatedTableMeta. The method resolves the
     * full delegation chain recursively (e.g., 'ComicsBook' resolves to
     * Thing -> Book -> ComicsBook) and merges columns from all levels.
     *
     * Glue columns (type, type_path, foreign keys) are automatically hidden.
     *
     * Pass '*' to enable wildcard mode: shows a type selector and all
     * delegate fields with data-delegate-type attributes for JS toggling.
     *
     * @param string $type The delegate type name or '*' for wildcard
     * @return self
     * @throws LogicException If source doesn't implement DelegatedTableMeta
     * @throws InvalidArgumentException If the delegate type is not found
     */
    public function delegate(string $type): self
    {
        if (!$this->source instanceof DelegatedTableMeta) {
            throw new LogicException(
                'delegate() requires a DelegatedTableMeta source. '
                . 'The provided TableMeta does not support delegated types.'
            );
        }

        $this->delegate_type = $type;

        if ($type === '*') {
            $this->setup_wildcard_delegation($this->source);
        } else {
            $chain = $this->resolve_delegate_chain($this->source, $type);
            if ($chain === null) {
                $available = implode(', ', $this->collect_all_delegate_names($this->source));
                throw new InvalidArgumentException(
                    "Delegate type '{$type}' not found. Available types: {$available}"
                );
            }
            $this->delegate_chain = $chain;
            $this->apply_delegate_chain($type);
        }

        return $this;
    }

    /**
     * Get the resolved delegate chain (excluding root source).
     *
     * @return TableMeta[]
     */
    public function get_delegate_chain(): array
    {
        return $this->delegate_chain;
    }

    /**
     * Get the active delegate type name.
     *
     * @return string|null
     */
    public function get_delegate_type(): ?string
    {
        return $this->delegate_type;
    }

    /**
     * Recursively resolve a delegate chain from root to the target type.
     *
     * Returns an array of TableMeta objects in chain order (excluding root),
     * or null if the target type is not found anywhere in the tree.
     *
     * @param DelegatedTableMeta $table
     * @param string $target_type
     * @return TableMeta[]|null
     */
    private function resolve_delegate_chain(DelegatedTableMeta $table, string $target_type): ?array
    {
        $delegates = $table->get_delegate_tables();

        // Direct child match
        if (isset($delegates[$target_type])) {
            return [$delegates[$target_type]];
        }

        // Recurse into children that are themselves DelegatedTableMeta
        foreach ($delegates as $name => $child_table) {
            if ($child_table instanceof DelegatedTableMeta) {
                $sub_chain = $this->resolve_delegate_chain($child_table, $target_type);
                if ($sub_chain !== null) {
                    return array_merge([$child_table], $sub_chain);
                }
            }
        }

        return null;
    }

    /**
     * Collect all available delegate type names recursively.
     *
     * @param DelegatedTableMeta $table
     * @return string[]
     */
    private function collect_all_delegate_names(DelegatedTableMeta $table): array
    {
        $names = [];
        foreach ($table->get_delegate_tables() as $name => $child_table) {
            $names[] = $name;
            if ($child_table instanceof DelegatedTableMeta) {
                $names = array_merge($names, $this->collect_all_delegate_names($child_table));
            }
        }
        return $names;
    }

    /**
     * Apply a resolved delegate chain: merge columns, hide glue columns.
     *
     * @param string $leaf_type
     */
    private function apply_delegate_chain(string $leaf_type): void
    {
        /** @var DelegatedTableMeta $root */
        $root = $this->source;

        // Hide root glue columns
        $type_col = $root->get_type_column();
        $type_path_col = $root->get_type_path_column();

        if ($type_col) {
            $this->field($type_col)->hidden(true);
        }
        if ($type_path_col) {
            $this->field($type_path_col)->hidden(true);
        }

        // Register all delegate columns and hide FK glue, PKs, and type columns
        $prev_table = $root;
        foreach ($this->delegate_chain as $delegate) {
            $fk = ($prev_table instanceof DelegatedTableMeta) ? $prev_table->get_delegate_foreign_key() : null;

            foreach ($delegate->describe_columns() as $col_name => $column) {
                $is_glue = ($fk !== null && $col_name === $fk);
                $is_pk = $column->is_primary_key();

                // Hide type/type_path columns on intermediate delegates
                $is_type_col = false;
                if ($delegate instanceof DelegatedTableMeta) {
                    $is_type_col = ($col_name === $delegate->get_type_column()
                        || $col_name === $delegate->get_type_path_column());
                }

                $this->register_delegate_column($delegate, $col_name, $is_glue || $is_pk || $is_type_col);
            }

            $prev_table = $delegate;
        }
    }

    /**
     * Register a column from a delegate table into this form.
     *
     * @param TableMeta $delegate
     * @param string $col_name
     * @param bool $hidden Whether to auto-hide this column
     */
    private function register_delegate_column(TableMeta $delegate, string $col_name, bool $hidden): void
    {
        $column = $delegate->describe_column($col_name);
        if ($column === null) {
            return;
        }

        // Store delegate columns in field_meta directly
        $field = new FieldMeta($column);
        if ($hidden) {
            $field->hidden(true);
        }
        $this->field_meta[$col_name] = $field;
    }

    /**
     * Set up wildcard delegation: show all delegate types with conditional visibility.
     *
     * @param DelegatedTableMeta $root
     */
    private function setup_wildcard_delegation(DelegatedTableMeta $root): void
    {
        $type_col = $root->get_type_column();
        $type_path_col = $root->get_type_path_column();

        // Turn type column into a select with all available types
        if ($type_col) {
            $type_names = $this->collect_all_delegate_names($root);
            $type_options = [];
            foreach ($type_names as $name) {
                $type_options[$name] = $name;
            }
            $this->field($type_col)->type('select')->options($type_options);
        }

        // Hide type_path - it's derived from type
        if ($type_path_col) {
            $this->field($type_path_col)->hidden(true);
        }

        // Register all delegate columns with data-delegate-type attributes
        $this->register_wildcard_delegates($root, $root);
    }

    /**
     * Recursively register delegate columns with delegate-type data attribute.
     *
     * @param DelegatedTableMeta $current
     * @param DelegatedTableMeta $root
     */
    private function register_wildcard_delegates(DelegatedTableMeta $current, DelegatedTableMeta $root): void
    {
        foreach ($current->get_delegate_tables() as $type_name => $delegate) {
            $fk = $current->get_delegate_foreign_key();

            foreach ($delegate->describe_columns() as $col_name => $column) {
                if ($column->is_primary_key() || $col_name === $fk) {
                    continue;
                }

                // Skip type/type_path columns on intermediate delegates
                if ($delegate instanceof DelegatedTableMeta) {
                    $dt_col = $delegate->get_type_column();
                    $dtp_col = $delegate->get_type_path_column();
                    if ($col_name === $dt_col || $col_name === $dtp_col) {
                        continue;
                    }
                }

                $field = new FieldMeta($column);
                $field->attrs(['data-delegate-type' => $type_name]);
                $this->field_meta[$col_name] = $field;
            }

            // Recurse into nested delegates
            if ($delegate instanceof DelegatedTableMeta) {
                $this->register_wildcard_delegates($delegate, $root);
            }
        }
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
     * fields are automatically skipped. When delegation is active,
     * delegate columns are included.
     *
     * @return Generator<string, FieldMeta>
     */
    public function each(): Generator
    {
        $fields = $this->collect_all_fields();

        // Filter hidden and excluded
        $visible = [];
        foreach ($fields as $name => $meta) {
            if (in_array($name, $this->excluded, true)) {
                continue;
            }
            if ($meta->is_hidden()) {
                continue;
            }
            $visible[$name] = $meta;
        }

        uasort($visible, function (FieldMeta $a, FieldMeta $b): int {
            $orderA = $a->get_order() ?? PHP_INT_MAX;
            $orderB = $b->get_order() ?? PHP_INT_MAX;
            return $orderA <=> $orderB;
        });

        foreach ($visible as $name => $meta) {
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
        $fields = $this->collect_all_fields();

        // Filter excluded only
        $result = [];
        foreach ($fields as $name => $meta) {
            if (in_array($name, $this->excluded, true)) {
                continue;
            }
            $result[$name] = $meta;
        }

        uasort($result, function (FieldMeta $a, FieldMeta $b): int {
            $orderA = $a->get_order() ?? PHP_INT_MAX;
            $orderB = $b->get_order() ?? PHP_INT_MAX;
            return $orderA <=> $orderB;
        });

        foreach ($result as $name => $meta) {
            yield $name => $meta;
        }
    }

    /**
     * Collect all fields: source columns + delegate columns from field_meta.
     *
     * @return array<string, FieldMeta>
     */
    private function collect_all_fields(): array
    {
        $fields = [];

        // Source table columns
        foreach ($this->source->describe_columns() as $name => $column) {
            $fields[$name] = $this->field_meta[$name] ?? new FieldMeta($column);
        }

        // Delegate columns (stored in field_meta but not in source)
        foreach ($this->field_meta as $name => $meta) {
            if (!isset($fields[$name])) {
                $fields[$name] = $meta;
            }
        }

        return $fields;
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
