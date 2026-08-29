<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Italix\Forms\Adapters;

use Italix\Contracts\ColumnMeta;
use Italix\Contracts\TableMeta;

/**
 * Generic adapter that wraps an array of column definitions as TableMeta.
 *
 * Use this when you don't have an ORM and want to define forms from
 * simple array definitions.
 *
 * Example:
 *
 *     $table = new GenericTableAdapter([
 *         'name' => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
 *         'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
 *         'age' => ['type' => 'INTEGER', 'nullable' => true],
 *     ]);
 *
 *     $form = new FormMeta($table);
 */
class GenericTableAdapter implements TableMeta
{
    /** @var array<string, ColumnMeta> */
    private $columns = [];

    /**
     * @param array<string, array|ColumnMeta> $columns Column definitions
     */
    public function __construct(array $columns)
    {
        foreach ($columns as $name => $definition) {
            $this->columns[$name] = $definition instanceof ColumnMeta
                ? $definition
                : GenericColumnAdapter::from_array($name, $definition);
        }
    }

    /**
     * @return iterable<string, ColumnMeta>
     */
    public function describe_columns(): iterable
    {
        return $this->columns;
    }

    public function describe_column(string $name): ?ColumnMeta
    {
        return $this->columns[$name] ?? null;
    }
}
