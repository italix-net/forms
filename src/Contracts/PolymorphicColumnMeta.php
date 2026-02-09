<?php

declare(strict_types=1);

namespace Italix\Forms\Contracts;

/**
 * Extended column metadata for polymorphic foreign key ID columns.
 *
 * A polymorphic FK uses two columns: a type discriminator (e.g.,
 * 'commentable_type') and an ID column (e.g., 'commentable_id').
 * This interface is implemented on the ID column to describe the
 * relationship.
 *
 * The rendering layer uses this to produce a dependent field pair:
 * a type selector and an ID selector whose options change based on
 * the selected type.
 *
 * Example:
 *
 *     // commentable_id implements PolymorphicColumnMeta
 *     $column->get_polymorphic_type_column(); // 'commentable_type'
 *     $column->get_polymorphic_targets();
 *     // ['post' => PostTableMeta, 'video' => VideoTableMeta]
 */
interface PolymorphicColumnMeta extends ColumnMeta
{
    /**
     * Get the name of the type discriminator column.
     *
     * @return string e.g., 'commentable_type'
     */
    public function get_polymorphic_type_column(): string;

    /**
     * Get the available polymorphic targets.
     *
     * Returns a map of type value => TableMeta for each possible
     * parent type.
     *
     * @return array<string, TableMeta> e.g., ['post' => $posts_table, 'video' => $videos_table]
     */
    public function get_polymorphic_targets(): array;
}
