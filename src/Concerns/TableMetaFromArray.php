<?php

declare(strict_types=1);

namespace Italix\Forms\Concerns;

use Italix\Forms\Adapters\GenericRelationAdapter;
use Italix\Forms\Contracts\ColumnMeta;

/**
 * Trait that provides TableMeta implementation from an array of columns.
 *
 * Use this trait in any class that stores columns as an array to quickly
 * implement the TableMeta interface.
 *
 * Optionally, set a relation fetcher callable to enable automatic option
 * loading for foreign key columns.
 *
 * Example:
 *
 *     class MyTable implements TableMeta
 *     {
 *         use TableMetaFromArray;
 *
 *         private $columns = [];
 *
 *         protected function get_columns_for_description(): array
 *         {
 *             return $this->columns;
 *         }
 *     }
 */
trait TableMetaFromArray
{
    /** @var callable|null */
    private $relation_fetcher = null;

    /**
     * Return the columns array for TableMeta implementation.
     *
     * Override this method to provide your columns array.
     * The array should be keyed by column name with ColumnMeta values.
     *
     * @return array<string, ColumnMeta>
     */
    abstract protected function get_columns_for_description(): array;

    /**
     * @return iterable<string, ColumnMeta>
     */
    public function describe_columns(): iterable
    {
        return $this->get_columns_for_description();
    }

    public function describe_column(string $name): ?ColumnMeta
    {
        $columns = $this->get_columns_for_description();
        return $columns[$name] ?? null;
    }

    /**
     * Set a callable that fetches rows from related tables.
     *
     * This is the bridge between the metadata layer and your database.
     * The callable receives: ($table, $key, $label, $limit) and must
     * return an associative array of [key => label] pairs.
     *
     * When set, all GenericRelationAdapter instances on columns of this
     * table that don't already have a fetcher will use this one.
     *
     * @param callable $fetcher fn(string $table, string $key, string $label, int $limit): array
     * @return self
     */
    public function set_relation_fetcher($fetcher): self
    {
        $this->relation_fetcher = $fetcher;

        foreach ($this->get_columns_for_description() as $column) {
            $relation = $column->get_relation();
            if ($relation instanceof GenericRelationAdapter) {
                $relation->set_fetcher($fetcher);
            }
        }

        return $this;
    }

    /**
     * Get the relation fetcher callable.
     *
     * @return callable|null
     */
    public function get_relation_fetcher()
    {
        return $this->relation_fetcher;
    }
}
