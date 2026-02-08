<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\FormMeta;
use Italix\Forms\FieldMeta;
use Italix\Forms\FormSection;
use Italix\Forms\Validation\Rule;
use Italix\Forms\Adapters\GenericTableAdapter;
use InvalidArgumentException;

use function Italix\Forms\form_meta;

class FormMetaTest extends TestCase
{
    /**
     * Create a standard test FormMeta with common columns.
     *
     * @return FormMeta
     */
    private function make_form(): FormMeta
    {
        return form_meta([
            'id' => ['type' => 'INTEGER', 'primary_key' => true, 'nullable' => false],
            'name' => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
            'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
            'bio' => ['type' => 'TEXT', 'nullable' => true],
            'age' => ['type' => 'INTEGER', 'nullable' => true],
        ]);
    }

    // =========================================================================
    // field() - Create & Retrieve
    // =========================================================================

    public function test_field_creates_and_returns_field_meta(): void
    {
        $form = $this->make_form();
        $field = $form->field('name');

        $this->assertInstanceOf(FieldMeta::class, $field);
        $this->assertSame('name', $field->get_name());
    }

    public function test_field_returns_same_instance_on_second_call(): void
    {
        $form = $this->make_form();
        $first = $form->field('email');
        $second = $form->field('email');

        $this->assertSame($first, $second);
    }

    public function test_field_throws_on_unknown_column(): void
    {
        $form = $this->make_form();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'nonexistent' not found");

        $form->field('nonexistent');
    }

    // =========================================================================
    // has_field()
    // =========================================================================

    public function test_has_field_false_before_access(): void
    {
        $form = $this->make_form();

        $this->assertFalse($form->has_field('name'));
    }

    public function test_has_field_true_after_access(): void
    {
        $form = $this->make_form();
        $form->field('name');

        $this->assertTrue($form->has_field('name'));
    }

    public function test_has_field_false_for_unknown_column(): void
    {
        $form = $this->make_form();

        $this->assertFalse($form->has_field('nonexistent'));
    }

    // =========================================================================
    // fields() - Bulk Configuration
    // =========================================================================

    public function test_fields_bulk_config_works(): void
    {
        $form = $this->make_form();
        $result = $form->fields([
            'name' => ['label' => 'Full Name', 'order' => 1],
            'email' => ['label' => 'Email Address', 'placeholder' => 'you@example.com'],
        ]);

        $this->assertSame($form, $result, 'fields() should return self for fluency');
        $this->assertSame('Full Name', $form->field('name')->get_label());
        $this->assertSame(1, $form->field('name')->get_order());
        $this->assertSame('Email Address', $form->field('email')->get_label());
        $this->assertSame('you@example.com', $form->field('email')->get_placeholder());
    }

    public function test_fields_bulk_config_throws_on_invalid_method(): void
    {
        $form = $this->make_form();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid field configuration method 'nonexistent_method'");

        $form->fields([
            'name' => ['nonexistent_method' => 'value'],
        ]);
    }

    public function test_fields_bulk_config_handles_options_as_array(): void
    {
        $form = $this->make_form();
        $form->fields([
            'name' => ['options' => ['a' => 'Alpha', 'b' => 'Beta']],
        ]);

        $this->assertSame(['a' => 'Alpha', 'b' => 'Beta'], $form->field('name')->get_options());
    }

    public function test_fields_bulk_config_handles_attrs_as_array(): void
    {
        $form = $this->make_form();
        $form->fields([
            'name' => ['attrs' => ['data-x' => '1', 'rows' => 5]],
        ]);

        $this->assertSame(['data-x' => '1', 'rows' => 5], $form->field('name')->get_attributes());
    }

    public function test_fields_bulk_config_spreads_array_values_for_non_special_methods(): void
    {
        $form = $this->make_form();
        $form->fields([
            'age' => ['rules' => [Rule::required(), Rule::integer()]],
        ]);

        $rules = $form->field('age')->get_rules();
        $this->assertCount(2, $rules);
        $this->assertSame('required', $rules[0]->get_name());
        $this->assertSame('integer', $rules[1]->get_name());
    }

    // =========================================================================
    // exclude()
    // =========================================================================

    public function test_exclude_returns_self(): void
    {
        $form = $this->make_form();
        $result = $form->exclude('id');

        $this->assertSame($form, $result);
    }

    public function test_exclude_removes_columns_from_iteration(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'age');

        $names = array_keys(iterator_to_array($form->each()));

        $this->assertContains('name', $names);
        $this->assertContains('email', $names);
        $this->assertContains('bio', $names);
        $this->assertNotContains('id', $names);
        $this->assertNotContains('age', $names);
    }

    public function test_get_excluded(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'age');

        $this->assertSame(['id', 'age'], $form->get_excluded());
    }

    public function test_exclude_accumulates(): void
    {
        $form = $this->make_form();
        $form->exclude('id');
        $form->exclude('age');

        $this->assertSame(['id', 'age'], $form->get_excluded());
    }

    // =========================================================================
    // section()
    // =========================================================================

    public function test_section_creates_form_section(): void
    {
        $form = $this->make_form();
        $section = $form->section('personal');

        $this->assertInstanceOf(FormSection::class, $section);
        $this->assertSame('personal', $section->get_name());
    }

    public function test_section_returns_same_instance(): void
    {
        $form = $this->make_form();
        $first = $form->section('personal');
        $second = $form->section('personal');

        $this->assertSame($first, $second);
    }

    public function test_has_section(): void
    {
        $form = $this->make_form();

        $this->assertFalse($form->has_section('personal'));

        $form->section('personal');

        $this->assertTrue($form->has_section('personal'));
    }

    public function test_sections_returns_all_defined_sections(): void
    {
        $form = $this->make_form();
        $form->section('personal')->title('Personal');
        $form->section('settings')->title('Settings');

        $sections = $form->sections();

        $this->assertCount(2, $sections);
        $this->assertArrayHasKey('personal', $sections);
        $this->assertArrayHasKey('settings', $sections);
    }

    public function test_default_section(): void
    {
        $form = $this->make_form();
        $result = $form->default_section('general');

        $this->assertSame($form, $result);
    }

    // =========================================================================
    // source()
    // =========================================================================

    public function test_source_returns_table_meta(): void
    {
        $table = new GenericTableAdapter([
            'name' => ['type' => 'VARCHAR'],
        ]);
        $form = new FormMeta($table);

        $this->assertSame($table, $form->source());
    }

    // =========================================================================
    // each() - Iteration
    // =========================================================================

    public function test_each_yields_all_visible_fields(): void
    {
        $form = $this->make_form();

        $names = array_keys(iterator_to_array($form->each()));

        $this->assertCount(5, $names);
        $this->assertContains('id', $names);
        $this->assertContains('name', $names);
        $this->assertContains('email', $names);
        $this->assertContains('bio', $names);
        $this->assertContains('age', $names);
    }

    public function test_each_skips_hidden_fields(): void
    {
        $form = $this->make_form();
        $form->field('id')->hidden();

        $names = array_keys(iterator_to_array($form->each()));

        $this->assertNotContains('id', $names);
        $this->assertCount(4, $names);
    }

    public function test_each_skips_excluded_fields(): void
    {
        $form = $this->make_form();
        $form->exclude('id');

        $names = array_keys(iterator_to_array($form->each()));

        $this->assertNotContains('id', $names);
        $this->assertCount(4, $names);
    }

    public function test_each_sorts_by_order(): void
    {
        $form = $this->make_form();
        $form->field('bio')->order(1);
        $form->field('name')->order(2);
        $form->field('email')->order(3);

        $names = array_keys(iterator_to_array($form->each()));

        // bio(1), name(2), email(3) should come first; id and age have null order => PHP_INT_MAX
        $this->assertSame('bio', $names[0]);
        $this->assertSame('name', $names[1]);
        $this->assertSame('email', $names[2]);
    }

    public function test_each_yields_field_meta_instances(): void
    {
        $form = $this->make_form();

        foreach ($form->each() as $name => $field) {
            $this->assertInstanceOf(FieldMeta::class, $field);
            $this->assertIsString($name);
        }
    }

    public function test_each_creates_field_meta_for_unconfigured_columns(): void
    {
        $form = $this->make_form();
        // Don't configure any fields, just iterate

        $fields = iterator_to_array($form->each());

        $this->assertCount(5, $fields);
        foreach ($fields as $field) {
            $this->assertInstanceOf(FieldMeta::class, $field);
        }
    }

    // =========================================================================
    // all() - Including Hidden
    // =========================================================================

    public function test_all_includes_hidden_fields(): void
    {
        $form = $this->make_form();
        $form->field('id')->hidden();

        $names = array_keys(iterator_to_array($form->all()));

        $this->assertContains('id', $names);
        $this->assertCount(5, $names);
    }

    public function test_all_excludes_excluded_fields(): void
    {
        $form = $this->make_form();
        $form->exclude('id');

        $names = array_keys(iterator_to_array($form->all()));

        $this->assertNotContains('id', $names);
        $this->assertCount(4, $names);
    }

    public function test_all_sorts_by_order(): void
    {
        $form = $this->make_form();
        $form->field('age')->order(1);
        $form->field('id')->order(2)->hidden();

        $names = array_keys(iterator_to_array($form->all()));

        $this->assertSame('age', $names[0]);
        $this->assertSame('id', $names[1]);
    }

    // =========================================================================
    // by_section()
    // =========================================================================

    public function test_by_section_groups_fields(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('personal');
        $form->field('email')->group('contact');
        $form->field('bio')->group('personal');

        $form->section('personal')->title('Personal Info')->order(1);
        $form->section('contact')->title('Contact')->order(2);

        $grouped = $form->by_section();

        // Ungrouped fields should be in '_default'
        $this->assertArrayHasKey('_default', $grouped);
        $this->assertArrayHasKey('personal', $grouped);
        $this->assertArrayHasKey('contact', $grouped);

        $this->assertArrayHasKey('name', $grouped['personal']['fields']);
        $this->assertArrayHasKey('bio', $grouped['personal']['fields']);
        $this->assertArrayHasKey('email', $grouped['contact']['fields']);

        $this->assertInstanceOf(FormSection::class, $grouped['personal']['section']);
        $this->assertSame('Personal Info', $grouped['personal']['section']->get_title());
    }

    public function test_by_section_ungrouped_first(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('personal');

        $grouped = $form->by_section();
        $keys = array_keys($grouped);

        $this->assertSame('_default', $keys[0]);
    }

    public function test_by_section_null_section_for_ungrouped(): void
    {
        $form = $this->make_form();

        $grouped = $form->by_section();

        $this->assertArrayHasKey('_default', $grouped);
        $this->assertNull($grouped['_default']['section']);
    }

    public function test_by_section_auto_creates_section_for_unknown_group(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('misc');

        $grouped = $form->by_section();

        $this->assertArrayHasKey('misc', $grouped);
        $this->assertInstanceOf(FormSection::class, $grouped['misc']['section']);
        $this->assertSame('Misc', $grouped['misc']['section']->get_title());
    }

    public function test_by_section_with_default_section(): void
    {
        $form = $this->make_form();
        $form->default_section('general');
        $form->section('general')->title('General');

        $grouped = $form->by_section();

        // All fields should be in 'general' since default_section is set
        $this->assertArrayHasKey('general', $grouped);
        $this->assertArrayNotHasKey('_default', $grouped);
    }

    public function test_by_section_sections_sorted_by_order(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'bio', 'age');

        $form->field('name')->group('section_b');
        $form->field('email')->group('section_a');

        $form->section('section_a')->order(1);
        $form->section('section_b')->order(2);

        $grouped = $form->by_section();
        $keys = array_keys($grouped);

        $this->assertSame('section_a', $keys[0]);
        $this->assertSame('section_b', $keys[1]);
    }

    public function test_by_section_skips_hidden_fields(): void
    {
        $form = $this->make_form();
        $form->field('id')->hidden();

        $grouped = $form->by_section();
        $all_field_names = [];
        foreach ($grouped as $group) {
            foreach ($group['fields'] as $name => $field) {
                $all_field_names[] = $name;
            }
        }

        $this->assertNotContains('id', $all_field_names);
    }

    // =========================================================================
    // count()
    // =========================================================================

    public function test_count_all_visible(): void
    {
        $form = $this->make_form();

        $this->assertSame(5, $form->count());
    }

    public function test_count_excludes_hidden(): void
    {
        $form = $this->make_form();
        $form->field('id')->hidden();

        $this->assertSame(4, $form->count());
    }

    public function test_count_excludes_excluded(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'age');

        $this->assertSame(3, $form->count());
    }

    // =========================================================================
    // to_array()
    // =========================================================================

    public function test_to_array_structure(): void
    {
        $form = $this->make_form();
        $form->exclude('id');
        $form->section('personal')->title('Personal');
        $form->field('name')->label('Full Name')->order(1);

        $arr = $form->to_array();

        $this->assertArrayHasKey('fields', $arr);
        $this->assertArrayHasKey('sections', $arr);
        $this->assertArrayHasKey('excluded', $arr);
        $this->assertArrayHasKey('default_section', $arr);

        $this->assertSame(['id'], $arr['excluded']);
        $this->assertNull($arr['default_section']);

        // Fields should not include excluded 'id'
        $this->assertArrayNotHasKey('id', $arr['fields']);
        $this->assertArrayHasKey('name', $arr['fields']);
        $this->assertSame('Full Name', $arr['fields']['name']['label']);

        // Sections
        $this->assertArrayHasKey('personal', $arr['sections']);
        $this->assertSame('Personal', $arr['sections']['personal']['title']);
    }

    public function test_to_array_sensitive_field_redacted(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'bio', 'age');
        $form->field('email')->sensitive()->label('Secret Email');

        $arr = $form->to_array();

        // email should be redacted
        $this->assertTrue($arr['fields']['email']['sensitive']);
        $this->assertArrayNotHasKey('placeholder', $arr['fields']['email']);
        $this->assertArrayNotHasKey('rules', $arr['fields']['email']);

        // name should be full
        $this->assertArrayHasKey('placeholder', $arr['fields']['name']);
    }

    public function test_to_array_with_include_sensitive(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'bio', 'age');
        $form->field('email')->sensitive()->label('Email');

        $arr = $form->to_array(true);

        // email should now be full
        $this->assertArrayHasKey('placeholder', $arr['fields']['email']);
        $this->assertArrayHasKey('rules', $arr['fields']['email']);
    }

    public function test_to_array_skips_hidden_fields(): void
    {
        $form = $this->make_form();
        $form->field('id')->hidden();

        $arr = $form->to_array();

        $this->assertArrayNotHasKey('id', $arr['fields']);
    }

    // =========================================================================
    // to_json()
    // =========================================================================

    public function test_to_json_returns_valid_json(): void
    {
        $form = $this->make_form();
        $form->exclude('id');
        $form->field('name')->label('Full Name');

        $json = $form->to_json();

        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertArrayHasKey('fields', $decoded);
        $this->assertArrayHasKey('name', $decoded['fields']);
        $this->assertSame('Full Name', $decoded['fields']['name']['label']);
    }

    public function test_to_json_with_flags(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'email', 'bio', 'age');
        $form->field('name')->label('Name');

        $json = $form->to_json(JSON_PRETTY_PRINT);

        $this->assertStringContainsString("\n", $json);
    }

    public function test_to_json_sensitive_redacted_by_default(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'bio', 'age');
        $form->field('email')->sensitive();

        $json = $form->to_json();
        $decoded = json_decode($json, true);

        $this->assertTrue($decoded['fields']['email']['sensitive']);
        $this->assertArrayNotHasKey('placeholder', $decoded['fields']['email']);
    }

    public function test_to_json_sensitive_included_when_requested(): void
    {
        $form = $this->make_form();
        $form->exclude('id', 'bio', 'age');
        $form->field('email')->sensitive();

        $json = $form->to_json(0, true);
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('placeholder', $decoded['fields']['email']);
    }
}
