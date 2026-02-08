<?php

declare(strict_types=1);

namespace Italix\Forms\Adapters;

use Italix\Forms\Contracts\ColumnMeta;

/**
 * Generic adapter that wraps any array or data as a ColumnMeta.
 *
 * Use this when you don't have an ORM but want to define columns
 * from arrays or other data sources.
 *
 * Example:
 *
 *     $column = GenericColumnAdapter::from_array('email', [
 *         'type' => 'VARCHAR',
 *         'length' => 255,
 *         'nullable' => false,
 *     ]);
 */
class GenericColumnAdapter implements ColumnMeta
{
    public function __construct(
        private string $name,
        private string $type = 'VARCHAR',
        private bool $nullable = true,
        private bool $primary_key = false,
        private ?int $length = null,
        private mixed $default = null,
        private bool $has_default = false,
    ) {}

    /**
     * Create a ColumnMeta from an array definition.
     *
     * Supported keys:
     *   - type: string (default: 'VARCHAR')
     *   - nullable: bool (default: true)
     *   - primary_key: bool (default: false)
     *   - length: int|null (default: null)
     *   - default: mixed (default: null)
     *
     * @param string $name Column name
     * @param array $definition Column definition array
     */
    public static function from_array(string $name, array $definition): self
    {
        return new self(
            name: $name,
            type: $definition['type'] ?? 'VARCHAR',
            nullable: $definition['nullable'] ?? true,
            primary_key: $definition['primary_key'] ?? false,
            length: $definition['length'] ?? null,
            default: $definition['default'] ?? null,
            has_default: array_key_exists('default', $definition),
        );
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_type(): string
    {
        return $this->type;
    }

    public function is_nullable(): bool
    {
        return $this->nullable;
    }

    public function is_primary_key(): bool
    {
        return $this->primary_key;
    }

    public function get_length(): ?int
    {
        return $this->length;
    }

    public function get_default(): mixed
    {
        return $this->default;
    }

    public function has_default(): bool
    {
        return $this->has_default;
    }
}
