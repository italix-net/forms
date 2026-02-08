<?php

declare(strict_types=1);

namespace Italix\Forms\Concerns;

use Italix\Forms\Contracts\ColumnMeta;

/**
 * Trait that provides TableMeta implementation from an array of columns.
 *
 * Use this trait in any class that stores columns as an array to quickly
 * implement the TableMeta interface.
 *
 * Example:
 *
 *     class MyTable implements TableMeta
 *     {
 *         use TableMetaFromArray;
 *
 *         private array $columns = [];
 *
 *         protected function get_columns_for_description(): array
 *         {
 *             return $this->columns;
 *         }
 *     }
 */
trait TableMetaFromArray
{
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
}
