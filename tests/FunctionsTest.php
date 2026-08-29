<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\FormMeta;
use Italix\Forms\Adapters\GenericTableAdapter;
use Italix\Contracts\TableMeta;

use function Italix\Forms\form_meta;

class FunctionsTest extends TestCase
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

        $this->assertInstanceOf(FormMeta::class, $form);

        // Should be able to access fields
        $name_field = $form->field('name');
        $this->assertSame('name', $name_field->get_name());
        $this->assertSame('VARCHAR', $name_field->column()->get_type());
        $this->assertSame(100, $name_field->column()->get_length());
        $this->assertFalse($name_field->column()->is_nullable());
    }

    public function test_form_meta_from_array_creates_generic_table_adapter(): void
    {
        $form = form_meta([
            'id' => ['type' => 'INTEGER', 'primary_key' => true],
        ]);

        $this->assertInstanceOf(GenericTableAdapter::class, $form->source());
    }

    public function test_form_meta_from_array_iterates_correctly(): void
    {
        $form = form_meta([
            'name' => ['type' => 'VARCHAR'],
            'bio' => ['type' => 'TEXT'],
        ]);

        $names = array_keys(iterator_to_array($form->each()));

        $this->assertCount(2, $names);
        $this->assertContains('name', $names);
        $this->assertContains('bio', $names);
    }

    public function test_form_meta_from_empty_array(): void
    {
        $form = form_meta([]);

        $this->assertInstanceOf(FormMeta::class, $form);
        $this->assertSame(0, $form->count());
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

        $this->assertInstanceOf(FormMeta::class, $form);
        $this->assertSame($table, $form->source());
    }

    public function test_form_meta_from_table_meta_preserves_source(): void
    {
        $table = new GenericTableAdapter([
            'id' => ['type' => 'INTEGER', 'primary_key' => true, 'nullable' => false],
        ]);

        $form = form_meta($table);

        // The source should be the exact same instance
        $this->assertSame($table, $form->source());
    }

    public function test_form_meta_from_table_meta_allows_field_access(): void
    {
        $table = new GenericTableAdapter([
            'title' => ['type' => 'VARCHAR', 'length' => 200, 'nullable' => false],
        ]);

        $form = form_meta($table);
        $field = $form->field('title');

        $this->assertSame('title', $field->get_name());
        $this->assertSame('VARCHAR', $field->column()->get_type());
        $this->assertSame(200, $field->column()->get_length());
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

        $this->assertInstanceOf(FormMeta::class, $form);
        $this->assertSame($custom, $form->source());

        $field = $form->field('foo');
        $this->assertSame('foo', $field->get_name());
        $this->assertSame(50, $field->column()->get_length());
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

        $this->assertCount(2, $fields);
        $this->assertArrayNotHasKey('id', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('email', $fields);

        $names = array_keys($fields);
        $this->assertSame('name', $names[0]);
        $this->assertSame('email', $names[1]);

        $this->assertSame('Full Name', $fields['name']->get_label());
        $this->assertSame('Email Address', $fields['email']->get_label());
    }
}
