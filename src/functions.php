<?php

declare(strict_types=1);

namespace Italix\Forms;

use Italix\Forms\Adapters\GenericTableAdapter;
use Italix\Forms\Contracts\TableMeta;

/**
 * Create a FormMeta instance from various sources.
 *
 * This is the primary entry point for creating form metadata. It accepts:
 *
 * - Any object implementing TableMeta (from an ORM, etc.)
 * - An array of column definitions (for standalone use)
 *
 * Examples:
 *
 *     // From an ORM Table
 *     $form = form_meta($usersTable);
 *
 *     // From an array (no ORM needed)
 *     $form = form_meta([
 *         'name' => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
 *         'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
 *         'age' => ['type' => 'INTEGER', 'nullable' => true],
 *     ]);
 *
 * @param TableMeta|array $source The table metadata source
 * @return FormMeta The form metadata wrapper
 */
function form_meta(TableMeta|array $source): FormMeta
{
    if (is_array($source)) {
        $source = new GenericTableAdapter($source);
    }

    return new FormMeta($source);
}
