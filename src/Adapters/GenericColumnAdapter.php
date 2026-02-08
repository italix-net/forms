<?php

declare(strict_types=1);

namespace Italix\Forms\Adapters;

use Italix\Forms\Contracts\ColumnMeta;
use Italix\Forms\Contracts\RelationMeta;

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
 *
 *     // With a foreign key relation:
 *     $column = GenericColumnAdapter::from_array('country_id', [
 *         'type' => 'INTEGER',
 *         'relation' => [
 *             'table' => 'countries',
 *             'key'   => 'id',
 *             'label' => 'name',
 *         ],
 *     ]);
 */
class GenericColumnAdapter implements ColumnMeta
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var bool */
    private $nullable;

    /** @var bool */
    private $primary_key;

    /** @var int|null */
    private $length;

    /** @var mixed */
    private $default;

    /** @var bool */
    private $has_default;

    /** @var RelationMeta|null */
    private $relation;

    /**
     * @param string $name
     * @param string $type
     * @param bool $nullable
     * @param bool $primary_key
     * @param int|null $length
     * @param mixed $default
     * @param bool $has_default
     * @param RelationMeta|null $relation
     */
    public function __construct(
        string $name,
        string $type = 'VARCHAR',
        bool $nullable = true,
        bool $primary_key = false,
        ?int $length = null,
        $default = null,
        bool $has_default = false,
        ?RelationMeta $relation = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->primary_key = $primary_key;
        $this->length = $length;
        $this->default = $default;
        $this->has_default = $has_default;
        $this->relation = $relation;
    }

    /**
     * Create a ColumnMeta from an array definition.
     *
     * Supported keys:
     *   - type: string (default: 'VARCHAR')
     *   - nullable: bool (default: true)
     *   - primary_key: bool (default: false)
     *   - length: int|null (default: null)
     *   - default: mixed (default: null)
     *   - relation: array with 'table', 'key', 'label' keys (optional)
     *
     * @param string $name Column name
     * @param array $definition Column definition array
     * @return self
     */
    public static function from_array(string $name, array $definition): self
    {
        $relation = null;
        if (isset($definition['relation']) && is_array($definition['relation'])) {
            $rel = $definition['relation'];
            $relation = new GenericRelationAdapter(
                $rel['table'] ?? '',
                $rel['key'] ?? 'id',
                $rel['label'] ?? 'name',
                $rel['fetcher'] ?? null
            );
        }

        return new self(
            $name,
            $definition['type'] ?? 'VARCHAR',
            $definition['nullable'] ?? true,
            $definition['primary_key'] ?? false,
            $definition['length'] ?? null,
            $definition['default'] ?? null,
            array_key_exists('default', $definition),
            $relation
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

    /**
     * @return mixed
     */
    public function get_default()
    {
        return $this->default;
    }

    public function has_default(): bool
    {
        return $this->has_default;
    }

    public function get_relation(): ?RelationMeta
    {
        return $this->relation;
    }
}
