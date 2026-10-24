<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — field metadata
 *
 * Written as an xUnit class; runs on the shared runner (`Italix\Testing\Runner`) through XunitCase.
 *
 * Run: php src/Libs/Italix/Forms/tests/FieldMetaTest.php
 */

declare(strict_types=1);

namespace Italix\Forms\Tests;

use Italix\Forms\FieldMeta;
use Italix\Rules\Rule;
use Italix\Forms\Adapters\GenericColumnAdapter;
use InvalidArgumentException;


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

class FieldMetaTest extends XunitCase
{
    /**
     * Helper to create a FieldMeta with a GenericColumnAdapter.
     *
     * @param string $name
     * @param string $type
     * @param bool $nullable
     * @param bool $primary_key
     * @param int|null $length
     * @return FieldMeta
     */
    private function make_field(
        string $name = 'email',
        string $type = 'VARCHAR',
        bool $nullable = false,
        bool $primary_key = false,
        ?int $length = 255
    ): FieldMeta {
        $column = new GenericColumnAdapter($name, $type, $nullable, $primary_key, $length);
        return new FieldMeta($column);
    }

    // =========================================================================
    // Constructor & Column Access
    // =========================================================================

    public function test_column_returns_underlying_column(): void
    {
        $column = new GenericColumnAdapter('name', 'VARCHAR', false, false, 100);
        $field = new FieldMeta($column);

        $this->assert_same($column, $field->column());
    }

    public function test_get_name_returns_column_name(): void
    {
        $field = $this->make_field('username');

        $this->assert_same('username', $field->get_name());
    }

    // =========================================================================
    // Display Setters & Getters (Fluent)
    // =========================================================================

    public function test_label_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->label('Email Address');

        $this->assert_same($field, $result, 'label() should return self for fluency');
        $this->assert_same('Email Address', $field->get_label());
    }

    public function test_label_auto_generated_from_name(): void
    {
        $field = $this->make_field('first_name');

        $this->assert_same('First Name', $field->get_label());
    }

    public function test_label_auto_generated_single_word(): void
    {
        $field = $this->make_field('email');

        $this->assert_same('Email', $field->get_label());
    }

    public function test_placeholder_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->placeholder('you@example.com');

        $this->assert_same($field, $result);
        $this->assert_same('you@example.com', $field->get_placeholder());
    }

    public function test_placeholder_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assert_null($field->get_placeholder());
    }

    public function test_help_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->help('We will never share your email');

        $this->assert_same($field, $result);
        $this->assert_same('We will never share your email', $field->get_help());
    }

    public function test_help_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assert_null($field->get_help());
    }

    // =========================================================================
    // Input Setters & Getters (Fluent)
    // =========================================================================

    public function test_type_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->type('password');

        $this->assert_same($field, $result);
        $this->assert_same('password', $field->get_type());
    }

    public function test_options_setter_and_getter(): void
    {
        $field = $this->make_field();
        $opts = ['m' => 'Male', 'f' => 'Female'];
        $result = $field->options($opts);

        $this->assert_same($field, $result);
        $this->assert_same($opts, $field->get_options());
    }

    public function test_options_default_is_empty_array(): void
    {
        $field = $this->make_field();

        $this->assert_same([], $field->get_options());
    }

    public function test_type_inferred_as_select_when_options_set(): void
    {
        $field = $this->make_field();
        $field->options(['a' => 'A', 'b' => 'B']);

        $this->assert_same('select', $field->get_type());
    }

    public function test_attr_single(): void
    {
        $field = $this->make_field();
        $result = $field->attr('maxlength', 100);

        $this->assert_same($field, $result);
        $this->assert_same(['maxlength' => 100], $field->get_attributes());
    }

    public function test_attrs_multiple(): void
    {
        $field = $this->make_field();
        $result = $field->attrs(['data-id' => '5', 'rows' => 3]);

        $this->assert_same($field, $result);
        $this->assert_same(['data-id' => '5', 'rows' => 3], $field->get_attributes());
    }

    public function test_attrs_merges_with_existing(): void
    {
        $field = $this->make_field();
        $field->attr('foo', 'bar');
        $field->attrs(['baz' => 'qux']);

        $this->assert_same(['foo' => 'bar', 'baz' => 'qux'], $field->get_attributes());
    }

    public function test_attributes_default_is_empty_array(): void
    {
        $field = $this->make_field();

        $this->assert_same([], $field->get_attributes());
    }

    // =========================================================================
    // State Setters & Getters (Fluent)
    // =========================================================================

    public function test_hidden_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->hidden();

        $this->assert_same($field, $result);
        $this->assert_true($field->is_hidden());
    }

    public function test_hidden_false(): void
    {
        $field = $this->make_field();
        $field->hidden(true);
        $field->hidden(false);

        $this->assert_false($field->is_hidden());
    }

    public function test_hidden_default_is_false(): void
    {
        $field = $this->make_field();

        $this->assert_false($field->is_hidden());
    }

    public function test_readonly_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->readonly();

        $this->assert_same($field, $result);
        $this->assert_true($field->is_readonly());
    }

    public function test_readonly_default_is_false(): void
    {
        $field = $this->make_field();

        $this->assert_false($field->is_readonly());
    }

    public function test_disabled_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->disabled();

        $this->assert_same($field, $result);
        $this->assert_true($field->is_disabled());
    }

    public function test_disabled_default_is_false(): void
    {
        $field = $this->make_field();

        $this->assert_false($field->is_disabled());
    }

    public function test_sensitive_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->sensitive();

        $this->assert_same($field, $result);
        $this->assert_true($field->is_sensitive());
    }

    public function test_sensitive_default_is_false(): void
    {
        $field = $this->make_field();

        $this->assert_false($field->is_sensitive());
    }

    // =========================================================================
    // Layout Setters & Getters (Fluent)
    // =========================================================================

    public function test_order_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->order(5);

        $this->assert_same($field, $result);
        $this->assert_same(5, $field->get_order());
    }

    public function test_order_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assert_null($field->get_order());
    }

    public function test_group_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->group('personal');

        $this->assert_same($field, $result);
        $this->assert_same('personal', $field->get_group());
    }

    public function test_group_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assert_null($field->get_group());
    }

    public function test_colspan_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->colspan(2);

        $this->assert_same($field, $result);
        $this->assert_same(2, $field->get_colspan());
    }

    public function test_colspan_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assert_null($field->get_colspan());
    }

    public function test_width_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->width('50%');

        $this->assert_same($field, $result);
        $this->assert_same('50%', $field->get_width());
    }

    public function test_width_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assert_null($field->get_width());
    }

    // =========================================================================
    // CSS Class Setters & Getters (Fluent)
    // =========================================================================

    public function test_wrapper_class_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->wrapper_class('form-group');

        $this->assert_same($field, $result);
        $this->assert_same('form-group', $field->get_wrapper_class());
    }

    public function test_input_class_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->input_class('form-control');

        $this->assert_same($field, $result);
        $this->assert_same('form-control', $field->get_input_class());
    }

    public function test_label_class_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->label_class('form-label');

        $this->assert_same($field, $result);
        $this->assert_same('form-label', $field->get_label_class());
    }

    public function test_help_class_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->help_class('form-text');

        $this->assert_same($field, $result);
        $this->assert_same('form-text', $field->get_help_class());
    }

    public function test_css_class_defaults_are_null(): void
    {
        $field = $this->make_field();

        $this->assert_null($field->get_wrapper_class());
        $this->assert_null($field->get_input_class());
        $this->assert_null($field->get_label_class());
        $this->assert_null($field->get_help_class());
    }

    // =========================================================================
    // Validation - Rule Objects
    // =========================================================================

    public function test_rules_accepts_rule_objects(): void
    {
        $field = $this->make_field();
        $result = $field->rules(Rule::required(), Rule::email());

        $this->assert_same($field, $result);

        $rules = $field->get_rules();
        $this->assert_count(2, $rules);
        $this->assert_same('required', $rules[0]->get_name());
        $this->assert_same('email', $rules[1]->get_name());
    }

    public function test_rules_default_is_empty(): void
    {
        $field = $this->make_field();

        $this->assert_same([], $field->get_rules());
    }

    // =========================================================================
    // Rule shorthand strings are rejected — the vocabulary moved to italix/rules
    // =========================================================================

    public function test_rules_throws_on_shorthand_string(): void
    {
        $field = $this->make_field();

        $this->expect_exception(InvalidArgumentException::class);
        $this->expect_exception_message_matches('/italix\/rules/');

        $field->rules('required');
    }

    public function test_rules_throws_on_non_rule_non_string(): void
    {
        $field = $this->make_field();

        $this->expect_exception(InvalidArgumentException::class);

        $field->rules(123);
    }

    // =========================================================================
    // is_required() Logic
    // =========================================================================

    public function test_is_required_true_for_non_nullable_column(): void
    {
        $field = $this->make_field('name', 'VARCHAR', false);

        $this->assert_true($field->is_required());
    }

    public function test_is_required_false_for_nullable_column_without_rule(): void
    {
        $field = $this->make_field('bio', 'TEXT', true);

        $this->assert_false($field->is_required());
    }

    public function test_is_required_true_for_nullable_column_with_required_rule(): void
    {
        $field = $this->make_field('bio', 'TEXT', true);
        $field->rules(Rule::required());

        $this->assert_true($field->is_required());
    }

    public function test_is_required_false_for_nullable_with_non_required_rules(): void
    {
        $field = $this->make_field('bio', 'TEXT', true);
        $field->rules(Rule::email(), Rule::max_length(255));

        $this->assert_false($field->is_required());
    }

    // =========================================================================
    // Type Inference
    // =========================================================================

    public function test_type_inferred_varchar_to_text(): void
    {
        $field = $this->make_field('name', 'VARCHAR');

        $this->assert_same('text', $field->get_type());
    }

    public function test_type_inferred_boolean_to_checkbox(): void
    {
        $field = $this->make_field('active', 'BOOLEAN');

        $this->assert_same('checkbox', $field->get_type());
    }

    public function test_type_inferred_text_to_textarea(): void
    {
        $field = $this->make_field('bio', 'TEXT');

        $this->assert_same('textarea', $field->get_type());
    }

    public function test_type_inferred_integer_to_number(): void
    {
        $field = $this->make_field('age', 'INTEGER');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_bigint_to_number(): void
    {
        $field = $this->make_field('count', 'BIGINT');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_smallint_to_number(): void
    {
        $field = $this->make_field('rank', 'SMALLINT');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_serial_to_number(): void
    {
        $field = $this->make_field('id', 'SERIAL');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_decimal_to_number(): void
    {
        $field = $this->make_field('price', 'DECIMAL');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_float_to_number(): void
    {
        $field = $this->make_field('score', 'FLOAT');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_real_to_number(): void
    {
        $field = $this->make_field('score', 'REAL');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_numeric_to_number(): void
    {
        $field = $this->make_field('amount', 'NUMERIC');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_double_precision_to_number(): void
    {
        $field = $this->make_field('amount', 'DOUBLE PRECISION');

        $this->assert_same('number', $field->get_type());
    }

    public function test_type_inferred_date_to_date(): void
    {
        $field = $this->make_field('dob', 'DATE');

        $this->assert_same('date', $field->get_type());
    }

    public function test_type_inferred_datetime_to_datetime_local(): void
    {
        $field = $this->make_field('created', 'DATETIME');

        $this->assert_same('datetime-local', $field->get_type());
    }

    public function test_type_inferred_timestamp_to_datetime_local(): void
    {
        $field = $this->make_field('updated', 'TIMESTAMP');

        $this->assert_same('datetime-local', $field->get_type());
    }

    public function test_type_inferred_time_to_time(): void
    {
        $field = $this->make_field('start_time', 'TIME');

        $this->assert_same('time', $field->get_type());
    }

    public function test_type_inferred_json_to_textarea(): void
    {
        $field = $this->make_field('meta', 'JSON');

        $this->assert_same('textarea', $field->get_type());
    }

    public function test_type_inferred_jsonb_to_textarea(): void
    {
        $field = $this->make_field('data', 'JSONB');

        $this->assert_same('textarea', $field->get_type());
    }

    public function test_explicit_type_overrides_inference(): void
    {
        $field = $this->make_field('name', 'VARCHAR');
        $field->type('email');

        $this->assert_same('email', $field->get_type());
    }

    public function test_options_override_type_inference(): void
    {
        $field = $this->make_field('status', 'VARCHAR');
        $field->options(['active' => 'Active', 'inactive' => 'Inactive']);

        $this->assert_same('select', $field->get_type());
    }

    public function test_explicit_type_overrides_options_inference(): void
    {
        $field = $this->make_field('status', 'VARCHAR');
        $field->options(['a' => 'A'])->type('radio');

        $this->assert_same('radio', $field->get_type());
    }

    // =========================================================================
    // to_array() - Normal Field
    // =========================================================================

    public function test_to_array_normal_field(): void
    {
        $field = $this->make_field('email', 'VARCHAR', false, false, 255);
        $field->label('Email Address')
            ->placeholder('you@example.com')
            ->help('Your primary email')
            ->order(1)
            ->group('contact')
            ->colspan(2)
            ->width('50%')
            ->wrapper_class('mb-3')
            ->input_class('form-control')
            ->label_class('form-label')
            ->help_class('form-text')
            ->rules(Rule::required(), Rule::email());

        $arr = $field->to_array();

        $this->assert_same('email', $arr['name']);
        $this->assert_same('Email Address', $arr['label']);
        $this->assert_same('text', $arr['type']);
        $this->assert_same('you@example.com', $arr['placeholder']);
        $this->assert_same('Your primary email', $arr['help']);
        $this->assert_same([], $arr['options']);
        $this->assert_true($arr['required']);
        $this->assert_false($arr['hidden']);
        $this->assert_false($arr['readonly']);
        $this->assert_false($arr['disabled']);
        $this->assert_same(1, $arr['order']);
        $this->assert_same('contact', $arr['group']);
        $this->assert_same(2, $arr['colspan']);
        $this->assert_same('50%', $arr['width']);
        $this->assert_same([], $arr['attributes']);
        $this->assert_same('mb-3', $arr['classes']['wrapper']);
        $this->assert_same('form-control', $arr['classes']['input']);
        $this->assert_same('form-label', $arr['classes']['label']);
        $this->assert_same('form-text', $arr['classes']['help']);

        $this->assert_count(2, $arr['rules']);
        $this->assert_same('required', $arr['rules'][0]['rule']);
        $this->assert_same('email', $arr['rules'][1]['rule']);
    }

    public function test_to_array_minimal_field(): void
    {
        $field = $this->make_field('name', 'VARCHAR', true, false, 100);

        $arr = $field->to_array();

        $this->assert_same('name', $arr['name']);
        $this->assert_same('Name', $arr['label']);
        $this->assert_same('text', $arr['type']);
        $this->assert_null($arr['placeholder']);
        $this->assert_null($arr['help']);
        $this->assert_same([], $arr['options']);
        $this->assert_same([], $arr['rules']);
        $this->assert_false($arr['required']);
        $this->assert_false($arr['hidden']);
        $this->assert_false($arr['readonly']);
        $this->assert_false($arr['disabled']);
        $this->assert_null($arr['order']);
        $this->assert_null($arr['group']);
        $this->assert_null($arr['colspan']);
        $this->assert_null($arr['width']);
        $this->assert_same([], $arr['attributes']);
        $this->assert_null($arr['classes']['wrapper']);
        $this->assert_null($arr['classes']['input']);
        $this->assert_null($arr['classes']['label']);
        $this->assert_null($arr['classes']['help']);
    }

    // =========================================================================
    // to_array() - Sensitive Fields
    // =========================================================================

    public function test_to_array_sensitive_field_redacted_by_default(): void
    {
        $field = $this->make_field('password', 'VARCHAR', false);
        $field->sensitive()
            ->label('Password')
            ->placeholder('Enter password')
            ->help('Must be 8+ chars')
            ->order(5)
            ->group('security');

        $arr = $field->to_array();

        // Redacted output should have limited keys
        $this->assert_same('password', $arr['name']);
        $this->assert_same('Password', $arr['label']);
        $this->assert_same('text', $arr['type']);
        $this->assert_true($arr['sensitive']);
        $this->assert_true($arr['required']);
        $this->assert_false($arr['hidden']);
        $this->assert_same(5, $arr['order']);
        $this->assert_same('security', $arr['group']);

        // Should NOT include detailed info
        $this->assert_not_has_key('placeholder', $arr);
        $this->assert_not_has_key('help', $arr);
        $this->assert_not_has_key('rules', $arr);
        $this->assert_not_has_key('readonly', $arr);
        $this->assert_not_has_key('disabled', $arr);
        $this->assert_not_has_key('colspan', $arr);
        $this->assert_not_has_key('width', $arr);
        $this->assert_not_has_key('attributes', $arr);
        $this->assert_not_has_key('classes', $arr);
        $this->assert_not_has_key('options', $arr);
    }

    public function test_to_array_sensitive_field_included_when_requested(): void
    {
        $field = $this->make_field('api_key', 'VARCHAR', false);
        $field->sensitive()
            ->label('API Key')
            ->placeholder('sk-...')
            ->rules(Rule::required());

        $arr = $field->to_array(true);

        // Should include all details when include_sensitive = true
        $this->assert_same('api_key', $arr['name']);
        $this->assert_same('API Key', $arr['label']);
        $this->assert_same('sk-...', $arr['placeholder']);
        $this->assert_has_key('rules', $arr);
        $this->assert_has_key('classes', $arr);
        $this->assert_has_key('attributes', $arr);

        // should NOT have the 'sensitive' key in full export
        $this->assert_not_has_key('sensitive', $arr);
    }

    public function test_to_array_non_sensitive_field_always_full(): void
    {
        $field = $this->make_field('username', 'VARCHAR', false);
        $field->label('Username');

        $arr = $field->to_array();

        $this->assert_has_key('placeholder', $arr);
        $this->assert_has_key('rules', $arr);
        $this->assert_has_key('classes', $arr);
        $this->assert_not_has_key('sensitive', $arr);
    }
}

exit(FieldMetaTest::run_all('Italix Forms — field metadata'));
