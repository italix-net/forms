<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\Adapters\GenericColumnAdapter;
use Italix\Forms\FieldMeta;
use Italix\Forms\Rendering\Widgets\TextWidget;
use Italix\Forms\Rendering\Widgets\TextareaWidget;
use Italix\Forms\Rendering\Widgets\SelectWidget;
use Italix\Forms\Rendering\Widgets\CheckboxWidget;
use Italix\Forms\Rendering\Widgets\RadioWidget;
use Italix\Forms\Rendering\Widgets\PasswordWidget;
use Italix\Forms\Rendering\Widgets\NumberWidget;
use Italix\Forms\Rendering\Widgets\DateWidget;
use Italix\Forms\Rendering\Widgets\FileWidget;
use Italix\Forms\Rendering\Widgets\HiddenWidget;
use Italix\Forms\Rendering\Widgets\ReadonlyWidget;

class WidgetTest extends TestCase
{
    /**
     * Create a FieldMeta with given column properties.
     *
     * @param string   $name
     * @param string   $type
     * @param bool     $nullable
     * @param int|null $length
     * @return FieldMeta
     */
    private function make_field(
        string $name = 'test_field',
        string $type = 'VARCHAR',
        bool $nullable = true,
        ?int $length = null
    ): FieldMeta {
        $column = new GenericColumnAdapter($name, $type, $nullable, false, $length);
        return new FieldMeta($column);
    }

    // =========================================================================
    // TextWidget
    // =========================================================================

    public function test_text_widget_renders_input_element(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('username', 'VARCHAR');
        $field->type('text');

        $html = $widget->render_edit($field, 'john');

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="text"', $html);
    }

    public function test_text_widget_renders_name_attribute(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('username');
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('name="username"', $html);
    }

    public function test_text_widget_renders_value(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text');

        $html = $widget->render_edit($field, 'Alice');

        $this->assertStringContainsString('value="Alice"', $html);
    }

    public function test_text_widget_renders_maxlength_from_column_length(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name', 'VARCHAR', true, 100);
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('maxlength="100"', $html);
    }

    public function test_text_widget_no_maxlength_without_column_length(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name', 'VARCHAR', true, null);
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assertStringNotContainsString('maxlength', $html);
    }

    public function test_text_widget_renders_email_type(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('email');
        $field->type('email');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('type="email"', $html);
    }

    public function test_text_widget_renders_url_type(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('website');
        $field->type('url');

        $html = $widget->render_edit($field, 'https://example.com');

        $this->assertStringContainsString('type="url"', $html);
    }

    public function test_text_widget_renders_placeholder(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->placeholder('Enter name');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('placeholder="Enter name"', $html);
    }

    public function test_text_widget_renders_required(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name', 'VARCHAR', false);
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('required', $html);
    }

    public function test_text_widget_renders_id(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('username');
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('id="field-username"', $html);
    }

    public function test_text_widget_escapes_value(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text');

        $html = $widget->render_edit($field, '"<script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_text_widget_view_mode(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');

        $html = $widget->render_view($field, 'Alice');

        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringContainsString('Alice', $html);
        $this->assertStringNotContainsString('<input', $html);
    }

    public function test_text_widget_view_mode_empty_value(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');

        $html = $widget->render_view($field, '');

        $this->assertStringContainsString('form-view-value', $html);
    }

    // =========================================================================
    // TextareaWidget
    // =========================================================================

    public function test_textarea_widget_renders_textarea_element(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, 'Hello world');

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('</textarea>', $html);
    }

    public function test_textarea_widget_value_inside_tags(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, 'My bio content');

        $this->assertStringContainsString('>My bio content</textarea>', $html);
    }

    public function test_textarea_widget_default_rows_five(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('rows="5"', $html);
    }

    public function test_textarea_widget_custom_rows_via_attrs(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, '', ['rows' => '10']);

        $this->assertStringContainsString('rows="10"', $html);
    }

    public function test_textarea_widget_renders_name(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('description', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('name="description"', $html);
    }

    public function test_textarea_widget_escapes_value(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, '<b>Bold</b>');

        $this->assertStringContainsString('&lt;b&gt;Bold&lt;/b&gt;', $html);
    }

    public function test_textarea_widget_placeholder(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea')->placeholder('Write something...');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('placeholder="Write something..."', $html);
    }

    public function test_textarea_widget_view_mode(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');

        $html = $widget->render_view($field, 'Some bio text');

        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringContainsString('Some bio text', $html);
        $this->assertStringNotContainsString('<textarea', $html);
    }

    // =========================================================================
    // SelectWidget
    // =========================================================================

    public function test_select_widget_renders_select_element(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('status');
        $field->type('select')->options(['active' => 'Active', 'inactive' => 'Inactive']);

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('</select>', $html);
    }

    public function test_select_widget_renders_options(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('status');
        $field->type('select')->options(['active' => 'Active', 'inactive' => 'Inactive']);

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('value="active"', $html);
        $this->assertStringContainsString('>Active</option>', $html);
        $this->assertStringContainsString('value="inactive"', $html);
        $this->assertStringContainsString('>Inactive</option>', $html);
    }

    public function test_select_widget_selected_value(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('color');
        $field->type('select')->options(['r' => 'Red', 'g' => 'Green', 'b' => 'Blue']);

        $html = $widget->render_edit($field, 'g');

        $this->assertStringContainsString('value="g" selected', $html);
    }

    public function test_select_widget_placeholder_option(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('country');
        $field->type('select')->options(['us' => 'USA', 'ca' => 'Canada']);

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('-- Select --', $html);
    }

    public function test_select_widget_custom_placeholder(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('country');
        $field->type('select')
            ->options(['us' => 'USA', 'ca' => 'Canada'])
            ->placeholder('Choose a country');

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('Choose a country', $html);
    }

    public function test_select_widget_renders_name(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('role');
        $field->type('select')->options(['admin' => 'Admin']);

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('name="role"', $html);
    }

    public function test_select_widget_view_mode_shows_label(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('status');
        $field->type('select')->options(['active' => 'Active', 'inactive' => 'Inactive']);

        $html = $widget->render_view($field, 'active');

        $this->assertStringContainsString('Active', $html);
        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringNotContainsString('<select', $html);
    }

    public function test_select_widget_view_mode_unknown_value_shows_raw(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('status');
        $field->type('select')->options(['active' => 'Active']);

        $html = $widget->render_view($field, 'unknown');

        $this->assertStringContainsString('unknown', $html);
    }

    // =========================================================================
    // CheckboxWidget
    // =========================================================================

    public function test_checkbox_widget_single_boolean_hidden_plus_checkbox(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_edit($field, false);

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('value="1"', $html);
    }

    public function test_checkbox_widget_single_checked_state(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_edit($field, true);

        $this->assertStringContainsString('checked', $html);
    }

    public function test_checkbox_widget_single_unchecked_state(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_edit($field, false);

        $this->assertStringNotContainsString(' checked', $html);
    }

    public function test_checkbox_widget_multiple_from_options(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('tags');
        $field->type('checkbox')->options(['php' => 'PHP', 'js' => 'JavaScript', 'py' => 'Python']);

        $html = $widget->render_edit($field, ['php', 'py']);

        $this->assertStringContainsString('checkbox-group', $html);
        $this->assertStringContainsString('PHP', $html);
        $this->assertStringContainsString('JavaScript', $html);
        $this->assertStringContainsString('Python', $html);
    }

    public function test_checkbox_widget_multiple_checked_state(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('tags');
        $field->type('checkbox')->options(['a' => 'Alpha', 'b' => 'Beta']);

        $html = $widget->render_edit($field, ['a']);

        // The option 'a' should have checked
        $this->assertStringContainsString('value="a" checked', $html);
    }

    public function test_checkbox_widget_multiple_uses_array_name(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('tags');
        $field->type('checkbox')->options(['a' => 'Alpha']);

        $html = $widget->render_edit($field, []);

        $this->assertStringContainsString('name="tags[]"', $html);
    }

    public function test_checkbox_widget_view_mode_boolean_yes(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_view($field, true);

        $this->assertStringContainsString('Yes', $html);
        $this->assertStringContainsString('form-view-value', $html);
    }

    public function test_checkbox_widget_view_mode_boolean_no(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_view($field, false);

        $this->assertStringContainsString('No', $html);
    }

    public function test_checkbox_widget_view_mode_multiple_shows_labels(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('tags');
        $field->type('checkbox')->options(['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma']);

        $html = $widget->render_view($field, ['a', 'c']);

        $this->assertStringContainsString('Alpha', $html);
        $this->assertStringContainsString('Gamma', $html);
    }

    // =========================================================================
    // RadioWidget
    // =========================================================================

    public function test_radio_widget_renders_radio_group(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('priority');
        $field->type('radio')->options(['low' => 'Low', 'med' => 'Medium', 'high' => 'High']);

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('radio-group', $html);
        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('Low', $html);
        $this->assertStringContainsString('Medium', $html);
        $this->assertStringContainsString('High', $html);
    }

    public function test_radio_widget_checked_state(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('priority');
        $field->type('radio')->options(['low' => 'Low', 'high' => 'High']);

        $html = $widget->render_edit($field, 'high');

        $this->assertStringContainsString('value="high" checked', $html);
    }

    public function test_radio_widget_renders_name(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('size');
        $field->type('radio')->options(['s' => 'Small']);

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('name="size"', $html);
    }

    public function test_radio_widget_renders_unique_ids(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('color');
        $field->type('radio')->options(['r' => 'Red', 'g' => 'Green']);

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('id="field-color-r"', $html);
        $this->assertStringContainsString('id="field-color-g"', $html);
    }

    public function test_radio_widget_disabled(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('level');
        $field->type('radio')->options(['a' => 'A'])->disabled();

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('disabled', $html);
    }

    public function test_radio_widget_view_mode_shows_label(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('priority');
        $field->type('radio')->options(['low' => 'Low', 'high' => 'High']);

        $html = $widget->render_view($field, 'low');

        $this->assertStringContainsString('Low', $html);
        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringNotContainsString('type="radio"', $html);
    }

    public function test_radio_widget_view_mode_unknown_value(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('priority');
        $field->type('radio')->options(['low' => 'Low']);

        $html = $widget->render_view($field, 'unknown');

        $this->assertStringContainsString('unknown', $html);
    }

    // =========================================================================
    // PasswordWidget
    // =========================================================================

    public function test_password_widget_never_prefills_value(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('password');
        $field->type('password');

        $html = $widget->render_edit($field, 'secret123');

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString('value=""', $html);
        $this->assertStringNotContainsString('secret123', $html);
    }

    public function test_password_widget_autocomplete_attribute(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('password');
        $field->type('password');

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('autocomplete="new-password"', $html);
    }

    public function test_password_widget_renders_name(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('pass');
        $field->type('password');

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('name="pass"', $html);
    }

    public function test_password_widget_view_mode_shows_asterisks(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('password');

        $html = $widget->render_view($field, 'secret');

        $this->assertStringContainsString('********', $html);
        $this->assertStringNotContainsString('secret', $html);
        $this->assertStringContainsString('form-view-value', $html);
    }

    public function test_password_widget_view_mode_shows_asterisks_even_null(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('password');

        $html = $widget->render_view($field, null);

        $this->assertStringContainsString('********', $html);
    }

    // =========================================================================
    // NumberWidget
    // =========================================================================

    public function test_number_widget_renders_number_input(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('age', 'INTEGER');
        $field->type('number');

        $html = $widget->render_edit($field, 25);

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('value="25"', $html);
    }

    public function test_number_widget_auto_step_any_for_decimal(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('price', 'DECIMAL');
        $field->type('number');

        $html = $widget->render_edit($field, 9.99);

        $this->assertStringContainsString('step="any"', $html);
    }

    public function test_number_widget_auto_step_any_for_float(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('rate', 'FLOAT');
        $field->type('number');

        $html = $widget->render_edit($field, 3.14);

        $this->assertStringContainsString('step="any"', $html);
    }

    public function test_number_widget_auto_step_any_for_real(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('value', 'REAL');
        $field->type('number');

        $html = $widget->render_edit($field, 1.5);

        $this->assertStringContainsString('step="any"', $html);
    }

    public function test_number_widget_auto_step_any_for_numeric(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('amount', 'NUMERIC');
        $field->type('number');

        $html = $widget->render_edit($field, 10.5);

        $this->assertStringContainsString('step="any"', $html);
    }

    public function test_number_widget_auto_step_any_for_double_precision(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('amount', 'DOUBLE PRECISION');
        $field->type('number');

        $html = $widget->render_edit($field, 1.23);

        $this->assertStringContainsString('step="any"', $html);
    }

    public function test_number_widget_no_step_for_integer(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('count', 'INTEGER');
        $field->type('number');

        $html = $widget->render_edit($field, 5);

        $this->assertStringNotContainsString('step=', $html);
    }

    public function test_number_widget_manual_step_not_overridden(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('price', 'DECIMAL');
        $field->type('number');

        $html = $widget->render_edit($field, 10, ['step' => '0.01']);

        $this->assertStringContainsString('step="0.01"', $html);
        $this->assertStringNotContainsString('step="any"', $html);
    }

    public function test_number_widget_null_value_empty_string(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('age', 'INTEGER');
        $field->type('number');

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('value=""', $html);
    }

    public function test_number_widget_view_mode(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('age', 'INTEGER');

        $html = $widget->render_view($field, 42);

        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringContainsString('42', $html);
        $this->assertStringNotContainsString('<input', $html);
    }

    // =========================================================================
    // DateWidget
    // =========================================================================

    public function test_date_widget_renders_date_input(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('birthday', 'DATE');
        $field->type('date');

        $html = $widget->render_edit($field, '2000-01-15');

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('value="2000-01-15"', $html);
    }

    public function test_date_widget_renders_datetime_local_type(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('created_at', 'DATETIME');
        $field->type('datetime-local');

        $html = $widget->render_edit($field, '2024-01-01T10:00');

        $this->assertStringContainsString('type="datetime-local"', $html);
    }

    public function test_date_widget_renders_time_type(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('start_time', 'TIME');
        $field->type('time');

        $html = $widget->render_edit($field, '14:30');

        $this->assertStringContainsString('type="time"', $html);
        $this->assertStringContainsString('value="14:30"', $html);
    }

    public function test_date_widget_null_value_empty_string(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('birthday', 'DATE');
        $field->type('date');

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('value=""', $html);
    }

    public function test_date_widget_renders_name(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('start_date', 'DATE');
        $field->type('date');

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('name="start_date"', $html);
    }

    public function test_date_widget_view_mode(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('birthday', 'DATE');

        $html = $widget->render_view($field, '2000-06-15');

        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringContainsString('2000-06-15', $html);
        $this->assertStringNotContainsString('<input', $html);
    }

    // =========================================================================
    // FileWidget
    // =========================================================================

    public function test_file_widget_renders_file_input(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');
        $field->type('file');

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="file"', $html);
    }

    public function test_file_widget_does_not_have_value_attribute(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');
        $field->type('file');

        $html = $widget->render_edit($field, '/uploads/photo.jpg');

        // File inputs should not include a value attribute for security
        $this->assertStringNotContainsString('value=', $html);
    }

    public function test_file_widget_renders_name(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('document');
        $field->type('file');

        $html = $widget->render_edit($field, null);

        $this->assertStringContainsString('name="document"', $html);
    }

    public function test_file_widget_view_mode_no_file(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');

        $html = $widget->render_view($field, null);

        $this->assertStringContainsString('No file', $html);
        $this->assertStringContainsString('form-view-empty', $html);
    }

    public function test_file_widget_view_mode_with_file(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');

        $html = $widget->render_view($field, 'photo.jpg');

        $this->assertStringContainsString('photo.jpg', $html);
        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringNotContainsString('No file', $html);
    }

    public function test_file_widget_view_mode_empty_string_shows_no_file(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');

        $html = $widget->render_view($field, '');

        $this->assertStringContainsString('No file', $html);
    }

    // =========================================================================
    // HiddenWidget
    // =========================================================================

    public function test_hidden_widget_bare_input(): void
    {
        $widget = new HiddenWidget();
        $field = $this->make_field('csrf_token');
        $field->type('hidden');

        $html = $widget->render_edit($field, 'abc123');

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('value="abc123"', $html);
        $this->assertStringContainsString('name="csrf_token"', $html);
    }

    public function test_hidden_widget_no_wrapper(): void
    {
        $widget = new HiddenWidget();
        $field = $this->make_field('token');
        $field->type('hidden');

        $html = $widget->render_edit($field, 'xyz');

        $this->assertStringNotContainsString('<div', $html);
        $this->assertStringNotContainsString('<label', $html);
    }

    public function test_hidden_widget_renders_id(): void
    {
        $widget = new HiddenWidget();
        $field = $this->make_field('session_id');
        $field->type('hidden');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('id="field-session_id"', $html);
    }

    public function test_hidden_widget_view_mode_empty_string(): void
    {
        $widget = new HiddenWidget();
        $field = $this->make_field('token');

        $html = $widget->render_view($field, 'secret');

        $this->assertSame('', $html);
    }

    // =========================================================================
    // ReadonlyWidget
    // =========================================================================

    public function test_readonly_widget_edit_renders_hidden_plus_span(): void
    {
        $widget = new ReadonlyWidget();
        $field = $this->make_field('created_at');
        $field->type('readonly');

        $html = $widget->render_edit($field, '2024-01-01');

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="created_at"', $html);
        $this->assertStringContainsString('value="2024-01-01"', $html);
        $this->assertStringContainsString('<span class="form-readonly-value">', $html);
        $this->assertStringContainsString('2024-01-01</span>', $html);
    }

    public function test_readonly_widget_edit_escapes_value(): void
    {
        $widget = new ReadonlyWidget();
        $field = $this->make_field('data');
        $field->type('readonly');

        $html = $widget->render_edit($field, '<script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_readonly_widget_view_mode(): void
    {
        $widget = new ReadonlyWidget();
        $field = $this->make_field('created_at');

        $html = $widget->render_view($field, '2024-06-01');

        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringContainsString('2024-06-01', $html);
        $this->assertStringNotContainsString('type="hidden"', $html);
    }

    // =========================================================================
    // Common Widget Attributes
    // =========================================================================

    public function test_widget_renders_disabled(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->disabled();

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('disabled', $html);
    }

    public function test_widget_renders_readonly(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->readonly();

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('readonly', $html);
    }

    public function test_widget_renders_custom_attributes(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->attr('data-validate', 'true');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('data-validate="true"', $html);
    }

    public function test_widget_renders_input_class(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->input_class('form-control');

        $html = $widget->render_edit($field, '');

        $this->assertStringContainsString('class="form-control"', $html);
    }
}
