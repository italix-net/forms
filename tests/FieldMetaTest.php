<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\FieldMeta;
use Italix\Forms\Validation\Rule;
use Italix\Forms\Adapters\GenericColumnAdapter;
use InvalidArgumentException;

class FieldMetaTest extends TestCase
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

        $this->assertSame($column, $field->column());
    }

    public function test_get_name_returns_column_name(): void
    {
        $field = $this->make_field('username');

        $this->assertSame('username', $field->get_name());
    }

    // =========================================================================
    // Display Setters & Getters (Fluent)
    // =========================================================================

    public function test_label_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->label('Email Address');

        $this->assertSame($field, $result, 'label() should return self for fluency');
        $this->assertSame('Email Address', $field->get_label());
    }

    public function test_label_auto_generated_from_name(): void
    {
        $field = $this->make_field('first_name');

        $this->assertSame('First Name', $field->get_label());
    }

    public function test_label_auto_generated_single_word(): void
    {
        $field = $this->make_field('email');

        $this->assertSame('Email', $field->get_label());
    }

    public function test_placeholder_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->placeholder('you@example.com');

        $this->assertSame($field, $result);
        $this->assertSame('you@example.com', $field->get_placeholder());
    }

    public function test_placeholder_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assertNull($field->get_placeholder());
    }

    public function test_help_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->help('We will never share your email');

        $this->assertSame($field, $result);
        $this->assertSame('We will never share your email', $field->get_help());
    }

    public function test_help_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assertNull($field->get_help());
    }

    // =========================================================================
    // Input Setters & Getters (Fluent)
    // =========================================================================

    public function test_type_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->type('password');

        $this->assertSame($field, $result);
        $this->assertSame('password', $field->get_type());
    }

    public function test_options_setter_and_getter(): void
    {
        $field = $this->make_field();
        $opts = ['m' => 'Male', 'f' => 'Female'];
        $result = $field->options($opts);

        $this->assertSame($field, $result);
        $this->assertSame($opts, $field->get_options());
    }

    public function test_options_default_is_empty_array(): void
    {
        $field = $this->make_field();

        $this->assertSame([], $field->get_options());
    }

    public function test_type_inferred_as_select_when_options_set(): void
    {
        $field = $this->make_field();
        $field->options(['a' => 'A', 'b' => 'B']);

        $this->assertSame('select', $field->get_type());
    }

    public function test_attr_single(): void
    {
        $field = $this->make_field();
        $result = $field->attr('maxlength', 100);

        $this->assertSame($field, $result);
        $this->assertSame(['maxlength' => 100], $field->get_attributes());
    }

    public function test_attrs_multiple(): void
    {
        $field = $this->make_field();
        $result = $field->attrs(['data-id' => '5', 'rows' => 3]);

        $this->assertSame($field, $result);
        $this->assertSame(['data-id' => '5', 'rows' => 3], $field->get_attributes());
    }

    public function test_attrs_merges_with_existing(): void
    {
        $field = $this->make_field();
        $field->attr('foo', 'bar');
        $field->attrs(['baz' => 'qux']);

        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $field->get_attributes());
    }

    public function test_attributes_default_is_empty_array(): void
    {
        $field = $this->make_field();

        $this->assertSame([], $field->get_attributes());
    }

    // =========================================================================
    // State Setters & Getters (Fluent)
    // =========================================================================

    public function test_hidden_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->hidden();

        $this->assertSame($field, $result);
        $this->assertTrue($field->is_hidden());
    }

    public function test_hidden_false(): void
    {
        $field = $this->make_field();
        $field->hidden(true);
        $field->hidden(false);

        $this->assertFalse($field->is_hidden());
    }

    public function test_hidden_default_is_false(): void
    {
        $field = $this->make_field();

        $this->assertFalse($field->is_hidden());
    }

    public function test_readonly_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->readonly();

        $this->assertSame($field, $result);
        $this->assertTrue($field->is_readonly());
    }

    public function test_readonly_default_is_false(): void
    {
        $field = $this->make_field();

        $this->assertFalse($field->is_readonly());
    }

    public function test_disabled_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->disabled();

        $this->assertSame($field, $result);
        $this->assertTrue($field->is_disabled());
    }

    public function test_disabled_default_is_false(): void
    {
        $field = $this->make_field();

        $this->assertFalse($field->is_disabled());
    }

    public function test_sensitive_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->sensitive();

        $this->assertSame($field, $result);
        $this->assertTrue($field->is_sensitive());
    }

    public function test_sensitive_default_is_false(): void
    {
        $field = $this->make_field();

        $this->assertFalse($field->is_sensitive());
    }

    // =========================================================================
    // Layout Setters & Getters (Fluent)
    // =========================================================================

    public function test_order_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->order(5);

        $this->assertSame($field, $result);
        $this->assertSame(5, $field->get_order());
    }

    public function test_order_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assertNull($field->get_order());
    }

    public function test_group_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->group('personal');

        $this->assertSame($field, $result);
        $this->assertSame('personal', $field->get_group());
    }

    public function test_group_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assertNull($field->get_group());
    }

    public function test_colspan_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->colspan(2);

        $this->assertSame($field, $result);
        $this->assertSame(2, $field->get_colspan());
    }

    public function test_colspan_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assertNull($field->get_colspan());
    }

    public function test_width_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->width('50%');

        $this->assertSame($field, $result);
        $this->assertSame('50%', $field->get_width());
    }

    public function test_width_default_is_null(): void
    {
        $field = $this->make_field();

        $this->assertNull($field->get_width());
    }

    // =========================================================================
    // CSS Class Setters & Getters (Fluent)
    // =========================================================================

    public function test_wrapper_class_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->wrapper_class('form-group');

        $this->assertSame($field, $result);
        $this->assertSame('form-group', $field->get_wrapper_class());
    }

    public function test_input_class_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->input_class('form-control');

        $this->assertSame($field, $result);
        $this->assertSame('form-control', $field->get_input_class());
    }

    public function test_label_class_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->label_class('form-label');

        $this->assertSame($field, $result);
        $this->assertSame('form-label', $field->get_label_class());
    }

    public function test_help_class_setter_and_getter(): void
    {
        $field = $this->make_field();
        $result = $field->help_class('form-text');

        $this->assertSame($field, $result);
        $this->assertSame('form-text', $field->get_help_class());
    }

    public function test_css_class_defaults_are_null(): void
    {
        $field = $this->make_field();

        $this->assertNull($field->get_wrapper_class());
        $this->assertNull($field->get_input_class());
        $this->assertNull($field->get_label_class());
        $this->assertNull($field->get_help_class());
    }

    // =========================================================================
    // Validation - Rule Objects
    // =========================================================================

    public function test_rules_accepts_rule_objects(): void
    {
        $field = $this->make_field();
        $result = $field->rules(Rule::required(), Rule::email());

        $this->assertSame($field, $result);

        $rules = $field->get_rules();
        $this->assertCount(2, $rules);
        $this->assertSame('required', $rules[0]->get_name());
        $this->assertSame('email', $rules[1]->get_name());
    }

    public function test_rules_default_is_empty(): void
    {
        $field = $this->make_field();

        $this->assertSame([], $field->get_rules());
    }

    // =========================================================================
    // Validation - String Rule Parsing
    // =========================================================================

    public function test_rules_parses_required_string(): void
    {
        $field = $this->make_field();
        $field->rules('required');

        $rules = $field->get_rules();
        $this->assertCount(1, $rules);
        $this->assertSame('required', $rules[0]->get_name());
    }

    public function test_rules_parses_email_string(): void
    {
        $field = $this->make_field();
        $field->rules('email');

        $rules = $field->get_rules();
        $this->assertSame('email', $rules[0]->get_name());
    }

    public function test_rules_parses_max_length_string(): void
    {
        $field = $this->make_field();
        $field->rules('max_length:255');

        $rules = $field->get_rules();
        $this->assertSame('max_length', $rules[0]->get_name());
        $this->assertSame(['length' => 255], $rules[0]->get_params());
    }

    public function test_rules_parses_between_string(): void
    {
        $field = $this->make_field();
        $field->rules('between:1,10');

        $rules = $field->get_rules();
        $this->assertSame('between', $rules[0]->get_name());
        $this->assertSame(['min' => 1.0, 'max' => 10.0], $rules[0]->get_params());
    }

    public function test_rules_parses_min_string(): void
    {
        $field = $this->make_field();
        $field->rules('min:5');

        $rules = $field->get_rules();
        $this->assertSame('min', $rules[0]->get_name());
        $this->assertSame(['value' => 5.0], $rules[0]->get_params());
    }

    public function test_rules_parses_in_string(): void
    {
        $field = $this->make_field();
        $field->rules('in:a,b,c');

        $rules = $field->get_rules();
        $this->assertSame('in', $rules[0]->get_name());
        $this->assertSame(['values' => ['a', 'b', 'c']], $rules[0]->get_params());
    }

    public function test_rules_parses_pattern_string(): void
    {
        $field = $this->make_field();
        $field->rules('pattern:/^\d+$/');

        $rules = $field->get_rules();
        $this->assertSame('pattern', $rules[0]->get_name());
        $this->assertSame(['regex' => '/^\d+$/'], $rules[0]->get_params());
    }

    public function test_rules_parses_multiple_string_rules(): void
    {
        $field = $this->make_field();
        $field->rules('required', 'email', 'max_length:255');

        $rules = $field->get_rules();
        $this->assertCount(3, $rules);
        $this->assertSame('required', $rules[0]->get_name());
        $this->assertSame('email', $rules[1]->get_name());
        $this->assertSame('max_length', $rules[2]->get_name());
    }

    public function test_rules_mixes_string_and_rule_objects(): void
    {
        $field = $this->make_field();
        $field->rules('required', Rule::email(), 'max_length:255');

        $rules = $field->get_rules();
        $this->assertCount(3, $rules);
        $this->assertSame('required', $rules[0]->get_name());
        $this->assertSame('email', $rules[1]->get_name());
        $this->assertSame('max_length', $rules[2]->get_name());
    }

    public function test_rules_parses_url_string(): void
    {
        $field = $this->make_field();
        $field->rules('url');

        $this->assertSame('url', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_numeric_string(): void
    {
        $field = $this->make_field();
        $field->rules('numeric');

        $this->assertSame('numeric', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_integer_string(): void
    {
        $field = $this->make_field();
        $field->rules('integer');

        $this->assertSame('integer', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_alpha_string(): void
    {
        $field = $this->make_field();
        $field->rules('alpha');

        $this->assertSame('alpha', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_alpha_num_string(): void
    {
        $field = $this->make_field();
        $field->rules('alpha_num');

        $this->assertSame('alpha_num', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_alpha_dash_string(): void
    {
        $field = $this->make_field();
        $field->rules('alpha_dash');

        $this->assertSame('alpha_dash', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_date_string(): void
    {
        $field = $this->make_field();
        $field->rules('date');

        $this->assertSame('date', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_file_string(): void
    {
        $field = $this->make_field();
        $field->rules('file');

        $this->assertSame('file', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_image_string(): void
    {
        $field = $this->make_field();
        $field->rules('image');

        $this->assertSame('image', $field->get_rules()[0]->get_name());
    }

    public function test_rules_parses_confirmed_string_default(): void
    {
        $field = $this->make_field();
        $field->rules('confirmed');

        $rules = $field->get_rules();
        $this->assertSame('confirmed', $rules[0]->get_name());
        $this->assertSame(['field' => 'confirmation'], $rules[0]->get_params());
    }

    public function test_rules_parses_confirmed_string_with_field(): void
    {
        $field = $this->make_field();
        $field->rules('confirmed:password_repeat');

        $rules = $field->get_rules();
        $this->assertSame(['field' => 'password_repeat'], $rules[0]->get_params());
    }

    public function test_rules_parses_same_string(): void
    {
        $field = $this->make_field();
        $field->rules('same:password');

        $rules = $field->get_rules();
        $this->assertSame('same', $rules[0]->get_name());
        $this->assertSame(['field' => 'password'], $rules[0]->get_params());
    }

    public function test_rules_parses_different_string(): void
    {
        $field = $this->make_field();
        $field->rules('different:old_pass');

        $this->assertSame(['field' => 'old_pass'], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_date_format_string(): void
    {
        $field = $this->make_field();
        $field->rules('date_format:Y-m-d');

        $this->assertSame(['format' => 'Y-m-d'], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_before_string(): void
    {
        $field = $this->make_field();
        $field->rules('before:2025-01-01');

        $this->assertSame(['date' => '2025-01-01'], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_after_string(): void
    {
        $field = $this->make_field();
        $field->rules('after:2020-01-01');

        $this->assertSame(['date' => '2020-01-01'], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_gt_string(): void
    {
        $field = $this->make_field();
        $field->rules('gt:10');

        $this->assertSame(['value' => '10'], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_length_between_string(): void
    {
        $field = $this->make_field();
        $field->rules('length_between:5,50');

        $rules = $field->get_rules();
        $this->assertSame('length_between', $rules[0]->get_name());
        $this->assertSame(['min' => 5, 'max' => 50], $rules[0]->get_params());
    }

    public function test_rules_parses_length_string(): void
    {
        $field = $this->make_field();
        $field->rules('length:10');

        $this->assertSame(['length' => 10], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_max_file_size_string(): void
    {
        $field = $this->make_field();
        $field->rules('max_file_size:2048');

        $this->assertSame(['size' => 2048], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_not_in_string(): void
    {
        $field = $this->make_field();
        $field->rules('not_in:x,y,z');

        $this->assertSame(['values' => ['x', 'y', 'z']], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_mimes_string(): void
    {
        $field = $this->make_field();
        $field->rules('mimes:pdf,doc');

        $this->assertSame(['types' => ['pdf', 'doc']], $field->get_rules()[0]->get_params());
    }

    public function test_rules_parses_unique_string(): void
    {
        $field = $this->make_field();
        $field->rules('unique:users,email');

        $rules = $field->get_rules();
        $this->assertSame('unique', $rules[0]->get_name());
        $this->assertSame(['table' => 'users', 'column' => 'email'], $rules[0]->get_params());
    }

    public function test_rules_parses_exists_string(): void
    {
        $field = $this->make_field();
        $field->rules('exists:categories');

        $rules = $field->get_rules();
        $this->assertSame('exists', $rules[0]->get_name());
        $this->assertSame(['table' => 'categories', 'column' => null], $rules[0]->get_params());
    }

    public function test_rules_parses_required_if_string(): void
    {
        $field = $this->make_field();
        $field->rules('required_if:role,admin');

        $rules = $field->get_rules();
        $this->assertSame('required_if', $rules[0]->get_name());
        $this->assertSame(['field' => 'role', 'value' => 'admin'], $rules[0]->get_params());
    }

    public function test_rules_parses_required_unless_string(): void
    {
        $field = $this->make_field();
        $field->rules('required_unless:status,draft');

        $rules = $field->get_rules();
        $this->assertSame('required_unless', $rules[0]->get_name());
        $this->assertSame(['field' => 'status', 'value' => 'draft'], $rules[0]->get_params());
    }

    // =========================================================================
    // Validation - Error Cases
    // =========================================================================

    public function test_rules_throws_on_unknown_rule_name(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown rule 'foobar'");

        $field->rules('foobar');
    }

    public function test_rules_throws_on_unknown_rule_with_params(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown rule 'invalid_rule'");

        $field->rules('invalid_rule:value');
    }

    public function test_rules_throws_on_missing_between_max_param(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Rule 'between' requires 2 parameter(s)");

        $field->rules('between:5');
    }

    public function test_rules_throws_on_missing_min_param(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Rule 'min' requires 1 parameter(s)");

        $field->rules('min');
    }

    public function test_rules_throws_on_missing_max_param(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);

        $field->rules('max');
    }

    public function test_rules_throws_on_missing_max_length_param(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);

        $field->rules('max_length');
    }

    public function test_rules_throws_on_missing_min_length_param(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);

        $field->rules('min_length');
    }

    public function test_rules_throws_on_missing_same_param(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);

        $field->rules('same');
    }

    public function test_rules_throws_on_missing_length_between_params(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Rule 'length_between' requires 2 parameter(s)");

        $field->rules('length_between:5');
    }

    public function test_rules_throws_on_non_rule_non_string(): void
    {
        $field = $this->make_field();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule must be a Rule instance or a string');

        $field->rules(123);
    }

    // =========================================================================
    // Validation - Filters Empty Params
    // =========================================================================

    public function test_rules_filters_empty_params_from_in_rule(): void
    {
        $field = $this->make_field();
        $field->rules('in:a,,b');

        $rules = $field->get_rules();
        $this->assertSame(['values' => ['a', 'b']], $rules[0]->get_params());
    }

    public function test_rules_filters_empty_params_from_not_in_rule(): void
    {
        $field = $this->make_field();
        $field->rules('not_in:x,,y,,z');

        $rules = $field->get_rules();
        $this->assertSame(['values' => ['x', 'y', 'z']], $rules[0]->get_params());
    }

    // =========================================================================
    // is_required() Logic
    // =========================================================================

    public function test_is_required_true_for_non_nullable_column(): void
    {
        $field = $this->make_field('name', 'VARCHAR', false);

        $this->assertTrue($field->is_required());
    }

    public function test_is_required_false_for_nullable_column_without_rule(): void
    {
        $field = $this->make_field('bio', 'TEXT', true);

        $this->assertFalse($field->is_required());
    }

    public function test_is_required_true_for_nullable_column_with_required_rule(): void
    {
        $field = $this->make_field('bio', 'TEXT', true);
        $field->rules(Rule::required());

        $this->assertTrue($field->is_required());
    }

    public function test_is_required_true_for_nullable_with_string_required_rule(): void
    {
        $field = $this->make_field('bio', 'TEXT', true);
        $field->rules('required');

        $this->assertTrue($field->is_required());
    }

    public function test_is_required_false_for_nullable_with_non_required_rules(): void
    {
        $field = $this->make_field('bio', 'TEXT', true);
        $field->rules('email', 'max_length:255');

        $this->assertFalse($field->is_required());
    }

    // =========================================================================
    // Type Inference
    // =========================================================================

    public function test_type_inferred_varchar_to_text(): void
    {
        $field = $this->make_field('name', 'VARCHAR');

        $this->assertSame('text', $field->get_type());
    }

    public function test_type_inferred_boolean_to_checkbox(): void
    {
        $field = $this->make_field('active', 'BOOLEAN');

        $this->assertSame('checkbox', $field->get_type());
    }

    public function test_type_inferred_text_to_textarea(): void
    {
        $field = $this->make_field('bio', 'TEXT');

        $this->assertSame('textarea', $field->get_type());
    }

    public function test_type_inferred_integer_to_number(): void
    {
        $field = $this->make_field('age', 'INTEGER');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_bigint_to_number(): void
    {
        $field = $this->make_field('count', 'BIGINT');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_smallint_to_number(): void
    {
        $field = $this->make_field('rank', 'SMALLINT');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_serial_to_number(): void
    {
        $field = $this->make_field('id', 'SERIAL');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_decimal_to_number(): void
    {
        $field = $this->make_field('price', 'DECIMAL');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_float_to_number(): void
    {
        $field = $this->make_field('score', 'FLOAT');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_real_to_number(): void
    {
        $field = $this->make_field('score', 'REAL');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_numeric_to_number(): void
    {
        $field = $this->make_field('amount', 'NUMERIC');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_double_precision_to_number(): void
    {
        $field = $this->make_field('amount', 'DOUBLE PRECISION');

        $this->assertSame('number', $field->get_type());
    }

    public function test_type_inferred_date_to_date(): void
    {
        $field = $this->make_field('dob', 'DATE');

        $this->assertSame('date', $field->get_type());
    }

    public function test_type_inferred_datetime_to_datetime_local(): void
    {
        $field = $this->make_field('created', 'DATETIME');

        $this->assertSame('datetime-local', $field->get_type());
    }

    public function test_type_inferred_timestamp_to_datetime_local(): void
    {
        $field = $this->make_field('updated', 'TIMESTAMP');

        $this->assertSame('datetime-local', $field->get_type());
    }

    public function test_type_inferred_time_to_time(): void
    {
        $field = $this->make_field('start_time', 'TIME');

        $this->assertSame('time', $field->get_type());
    }

    public function test_type_inferred_json_to_textarea(): void
    {
        $field = $this->make_field('meta', 'JSON');

        $this->assertSame('textarea', $field->get_type());
    }

    public function test_type_inferred_jsonb_to_textarea(): void
    {
        $field = $this->make_field('data', 'JSONB');

        $this->assertSame('textarea', $field->get_type());
    }

    public function test_explicit_type_overrides_inference(): void
    {
        $field = $this->make_field('name', 'VARCHAR');
        $field->type('email');

        $this->assertSame('email', $field->get_type());
    }

    public function test_options_override_type_inference(): void
    {
        $field = $this->make_field('status', 'VARCHAR');
        $field->options(['active' => 'Active', 'inactive' => 'Inactive']);

        $this->assertSame('select', $field->get_type());
    }

    public function test_explicit_type_overrides_options_inference(): void
    {
        $field = $this->make_field('status', 'VARCHAR');
        $field->options(['a' => 'A'])->type('radio');

        $this->assertSame('radio', $field->get_type());
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

        $this->assertSame('email', $arr['name']);
        $this->assertSame('Email Address', $arr['label']);
        $this->assertSame('text', $arr['type']);
        $this->assertSame('you@example.com', $arr['placeholder']);
        $this->assertSame('Your primary email', $arr['help']);
        $this->assertSame([], $arr['options']);
        $this->assertTrue($arr['required']);
        $this->assertFalse($arr['hidden']);
        $this->assertFalse($arr['readonly']);
        $this->assertFalse($arr['disabled']);
        $this->assertSame(1, $arr['order']);
        $this->assertSame('contact', $arr['group']);
        $this->assertSame(2, $arr['colspan']);
        $this->assertSame('50%', $arr['width']);
        $this->assertSame([], $arr['attributes']);
        $this->assertSame('mb-3', $arr['classes']['wrapper']);
        $this->assertSame('form-control', $arr['classes']['input']);
        $this->assertSame('form-label', $arr['classes']['label']);
        $this->assertSame('form-text', $arr['classes']['help']);

        $this->assertCount(2, $arr['rules']);
        $this->assertSame('required', $arr['rules'][0]['rule']);
        $this->assertSame('email', $arr['rules'][1]['rule']);
    }

    public function test_to_array_minimal_field(): void
    {
        $field = $this->make_field('name', 'VARCHAR', true, false, 100);

        $arr = $field->to_array();

        $this->assertSame('name', $arr['name']);
        $this->assertSame('Name', $arr['label']);
        $this->assertSame('text', $arr['type']);
        $this->assertNull($arr['placeholder']);
        $this->assertNull($arr['help']);
        $this->assertSame([], $arr['options']);
        $this->assertSame([], $arr['rules']);
        $this->assertFalse($arr['required']);
        $this->assertFalse($arr['hidden']);
        $this->assertFalse($arr['readonly']);
        $this->assertFalse($arr['disabled']);
        $this->assertNull($arr['order']);
        $this->assertNull($arr['group']);
        $this->assertNull($arr['colspan']);
        $this->assertNull($arr['width']);
        $this->assertSame([], $arr['attributes']);
        $this->assertNull($arr['classes']['wrapper']);
        $this->assertNull($arr['classes']['input']);
        $this->assertNull($arr['classes']['label']);
        $this->assertNull($arr['classes']['help']);
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
        $this->assertSame('password', $arr['name']);
        $this->assertSame('Password', $arr['label']);
        $this->assertSame('text', $arr['type']);
        $this->assertTrue($arr['sensitive']);
        $this->assertTrue($arr['required']);
        $this->assertFalse($arr['hidden']);
        $this->assertSame(5, $arr['order']);
        $this->assertSame('security', $arr['group']);

        // Should NOT include detailed info
        $this->assertArrayNotHasKey('placeholder', $arr);
        $this->assertArrayNotHasKey('help', $arr);
        $this->assertArrayNotHasKey('rules', $arr);
        $this->assertArrayNotHasKey('readonly', $arr);
        $this->assertArrayNotHasKey('disabled', $arr);
        $this->assertArrayNotHasKey('colspan', $arr);
        $this->assertArrayNotHasKey('width', $arr);
        $this->assertArrayNotHasKey('attributes', $arr);
        $this->assertArrayNotHasKey('classes', $arr);
        $this->assertArrayNotHasKey('options', $arr);
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
        $this->assertSame('api_key', $arr['name']);
        $this->assertSame('API Key', $arr['label']);
        $this->assertSame('sk-...', $arr['placeholder']);
        $this->assertArrayHasKey('rules', $arr);
        $this->assertArrayHasKey('classes', $arr);
        $this->assertArrayHasKey('attributes', $arr);

        // should NOT have the 'sensitive' key in full export
        $this->assertArrayNotHasKey('sensitive', $arr);
    }

    public function test_to_array_non_sensitive_field_always_full(): void
    {
        $field = $this->make_field('username', 'VARCHAR', false);
        $field->label('Username');

        $arr = $field->to_array();

        $this->assertArrayHasKey('placeholder', $arr);
        $this->assertArrayHasKey('rules', $arr);
        $this->assertArrayHasKey('classes', $arr);
        $this->assertArrayNotHasKey('sensitive', $arr);
    }
}
