<?php

declare(strict_types=1);

namespace Italix\Forms\Contracts;

/**
 * Extended column metadata for columns that are foreign keys.
 *
 * Implement this interface (instead of plain ColumnMeta) when a column
 * references another table. The rendering layer uses the relation
 * to automatically populate select options or configure autocomplete.
 *
 * Example:
 *
 *     class OrmColumn implements RelationalColumnMeta
 *     {
 *         // ... all ColumnMeta methods ...
 *
 *         public function get_relation(): ?RelationMeta
 *         {
 *             if ($this->has_foreign_key()) {
 *                 return new MyRelationAdapter($this->foreign_key);
 *             }
 *             return null;
 *         }
 *     }
 */
interface RelationalColumnMeta extends ColumnMeta
{
    /**
     * Get foreign key relation metadata, if this column is a foreign key.
     *
     * Return null if this column is not a foreign key.
     *
     * @return RelationMeta|null
     */
    public function get_relation(): ?RelationMeta;
}
