<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — helper functions
 *
 * Written as an xUnit class; runs on the shared runner (`Italix\Testing\Runner`) through XunitCase.
 *
 * Run: php src/Libs/Italix/Forms/tests/FunctionsTest.php
 */

declare(strict_types=1);

namespace Italix\Forms\Tests;

use Italix\Forms\FormMeta;
use Italix\Forms\Adapters\GenericTableAdapter;
use Italix\Contracts\TableMeta;

use function Italix\Forms\form_meta;


(static function (): void {
    foreach ([
        __DIR__ . '/../../../../../vendor/autoload.php',
        __DIR__ . '/../../../../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
        __DIR__ . '/../vendor/autoload.php',
    ] as $autoload) {
        if (is_file($autoload)) {
            require_once $autoload;

            return;
        }
    }

    fwrite(STDERR, "Could not find an autoloader. Run composer install.
");
    exit(2);
})();

require_once __DIR__ . '/XunitCase.php';

class FunctionsTest extends XunitCase
{
    // =========================================================================
    // form_meta() from array
    // =========================================================================

    public function test_form_meta_from_array(): void
    {
        $form = form_meta([
            'name' => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
            'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
            'age' => ['type' => 'INTEGER', 'nullable' => true],
        ]);

        $this->assert_instance_of(FormMeta::class, $form);

        // Should be able to access fields
        $name_field = $form->field('name');
        $this->assert_same('name', $name_field->get_name());
        $this->assert_same('VARCHAR', $name_field->column()->get_type());
        $this->assert_same(100, $name_field->column()->get_length());
        $this->assert_false($name_field->column()->is_nullable());
    }

    public function test_form_meta_from_array_creates_generic_table_adapter(): void
    {
        $form = form_meta([
            'id' => ['type' => 'INTEGER', 'primary_key' => true],
        ]);

        $this->assert_instance_of(GenericTableAdapter::class, $form->source());
    }

    public function test_form_meta_from_array_iterates_correctly(): void
    {
        $form = form_meta([
            'name' => ['type' => 'VARCHAR'],
            'bio' => ['type' => 'TEXT'],
        ]);

        $names = array_keys(iterator_to_array($form->each()));

        $this->assert_count(2, $names);
        $this->assert_in('name', $names);
        $this->assert_in('bio', $names);
    }

    public function test_form_meta_from_empty_array(): void
    {
        $form = form_meta([]);

        $this->assert_instance_of(FormMeta::class, $form);
        $this->assert_same(0, $form->count());
    }

    // =========================================================================
    // form_meta() from TableMeta instance
    // =========================================================================

    public function test_form_meta_from_table_meta_instance(): void
    {
        $table = new GenericTableAdapter([
            'name' => ['type' => 'VARCHAR', 'length' => 100],
            'email' => ['type' => 'VARCHAR', 'length' => 255],
        ]);

        $form = form_meta($table);

        $this->assert_instance_of(FormMeta::class, $form);
        $this->assert_same($table, $form->source());
    }

    public function test_form_meta_from_table_meta_preserves_source(): void
    {
        $table = new GenericTableAdapter([
            'id' => ['type' => 'INTEGER', 'primary_key' => true, 'nullable' => false],
        ]);

        $form = form_meta($table);

        // The source should be the exact same instance
        $this->assert_same($table, $form->source());
    }

    public function test_form_meta_from_table_meta_allows_field_access(): void
    {
        $table = new GenericTableAdapter([
            'title' => ['type' => 'VARCHAR', 'length' => 200, 'nullable' => false],
        ]);

        $form = form_meta($table);
        $field = $form->field('title');

        $this->assert_same('title', $field->get_name());
        $this->assert_same('VARCHAR', $field->column()->get_type());
        $this->assert_same(200, $field->column()->get_length());
    }

    public function test_form_meta_from_custom_table_meta(): void
    {
        // Create an anonymous class implementing TableMeta
        $custom = new class implements TableMeta {
            /** @var array */
            private $cols;

            public function __construct()
            {
                $this->cols = [
                    'foo' => new \Italix\Forms\Adapters\GenericColumnAdapter('foo', 'VARCHAR', true, false, 50),
                ];
            }

            public function describe_columns(): iterable
            {
                return $this->cols;
            }

            public function describe_column(string $name): ?\Italix\Contracts\ColumnMeta
            {
                return $this->cols[$name] ?? null;
            }
        };

        $form = form_meta($custom);

        $this->assert_instance_of(FormMeta::class, $form);
        $this->assert_same($custom, $form->source());

        $field = $form->field('foo');
        $this->assert_same('foo', $field->get_name());
        $this->assert_same(50, $field->column()->get_length());
    }

    // =========================================================================
    // form_meta() - Full workflow
    // =========================================================================

    public function test_form_meta_full_workflow(): void
    {
        $form = form_meta([
            'id' => ['type' => 'INTEGER', 'primary_key' => true, 'nullable' => false],
            'name' => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
            'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
        ]);

        $form->exclude('id');
        $form->field('name')->label('Full Name')->order(1);
        $form->field('email')->label('Email Address')->order(2);

        $fields = iterator_to_array($form->each());

        $this->assert_count(2, $fields);
        $this->assert_not_has_key('id', $fields);
        $this->assert_has_key('name', $fields);
        $this->assert_has_key('email', $fields);

        $names = array_keys($fields);
        $this->assert_same('name', $names[0]);
        $this->assert_same('email', $names[1]);

        $this->assert_same('Full Name', $fields['name']->get_label());
        $this->assert_same('Email Address', $fields['email']->get_label());
    }
}

exit(FunctionsTest::run_all('Italix Forms — helper functions'));
