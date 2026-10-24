<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — widgets
 *
 * Written as an xUnit class; runs on the shared runner (`Italix\Testing\Runner`) through XunitCase.
 *
 * Run: php src/Libs/Italix/Forms/tests/WidgetTest.php
 */

declare(strict_types=1);

namespace Italix\Forms\Tests;

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

class WidgetTest extends XunitCase
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

        $this->assert_contains_string('<input', $html);
        $this->assert_contains_string('type="text"', $html);
    }

    public function test_text_widget_renders_name_attribute(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('username');
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('name="username"', $html);
    }

    public function test_text_widget_renders_value(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text');

        $html = $widget->render_edit($field, 'Alice');

        $this->assert_contains_string('value="Alice"', $html);
    }

    public function test_text_widget_renders_maxlength_from_column_length(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name', 'VARCHAR', true, 100);
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('maxlength="100"', $html);
    }

    public function test_text_widget_no_maxlength_without_column_length(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name', 'VARCHAR', true, null);
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assert_not_contains_string('maxlength', $html);
    }

    public function test_text_widget_renders_email_type(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('email');
        $field->type('email');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('type="email"', $html);
    }

    public function test_text_widget_renders_url_type(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('website');
        $field->type('url');

        $html = $widget->render_edit($field, 'https://example.com');

        $this->assert_contains_string('type="url"', $html);
    }

    public function test_text_widget_renders_placeholder(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->placeholder('Enter name');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('placeholder="Enter name"', $html);
    }

    public function test_text_widget_renders_required(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name', 'VARCHAR', false);
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('required', $html);
    }

    public function test_text_widget_renders_id(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('username');
        $field->type('text');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('id="field-username"', $html);
    }

    public function test_text_widget_escapes_value(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text');

        $html = $widget->render_edit($field, '"<script>');

        $this->assert_not_contains_string('<script>', $html);
        $this->assert_contains_string('&lt;script&gt;', $html);
    }

    public function test_text_widget_view_mode(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');

        $html = $widget->render_view($field, 'Alice');

        $this->assert_contains_string('form-view-value', $html);
        $this->assert_contains_string('Alice', $html);
        $this->assert_not_contains_string('<input', $html);
    }

    public function test_text_widget_view_mode_empty_value(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');

        $html = $widget->render_view($field, '');

        $this->assert_contains_string('form-view-value', $html);
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

        $this->assert_contains_string('<textarea', $html);
        $this->assert_contains_string('</textarea>', $html);
    }

    public function test_textarea_widget_value_inside_tags(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, 'My bio content');

        $this->assert_contains_string('>My bio content</textarea>', $html);
    }

    public function test_textarea_widget_default_rows_five(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('rows="5"', $html);
    }

    public function test_textarea_widget_custom_rows_via_attrs(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, '', ['rows' => '10']);

        $this->assert_contains_string('rows="10"', $html);
    }

    public function test_textarea_widget_renders_name(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('description', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('name="description"', $html);
    }

    public function test_textarea_widget_escapes_value(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea');

        $html = $widget->render_edit($field, '<b>Bold</b>');

        $this->assert_contains_string('&lt;b&gt;Bold&lt;/b&gt;', $html);
    }

    public function test_textarea_widget_placeholder(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');
        $field->type('textarea')->placeholder('Write something...');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('placeholder="Write something..."', $html);
    }

    public function test_textarea_widget_view_mode(): void
    {
        $widget = new TextareaWidget();
        $field = $this->make_field('bio', 'TEXT');

        $html = $widget->render_view($field, 'Some bio text');

        $this->assert_contains_string('form-view-value', $html);
        $this->assert_contains_string('Some bio text', $html);
        $this->assert_not_contains_string('<textarea', $html);
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

        $this->assert_contains_string('<select', $html);
        $this->assert_contains_string('</select>', $html);
    }

    public function test_select_widget_renders_options(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('status');
        $field->type('select')->options(['active' => 'Active', 'inactive' => 'Inactive']);

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('value="active"', $html);
        $this->assert_contains_string('>Active</option>', $html);
        $this->assert_contains_string('value="inactive"', $html);
        $this->assert_contains_string('>Inactive</option>', $html);
    }

    public function test_select_widget_selected_value(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('color');
        $field->type('select')->options(['r' => 'Red', 'g' => 'Green', 'b' => 'Blue']);

        $html = $widget->render_edit($field, 'g');

        $this->assert_contains_string('value="g" selected', $html);
    }

    public function test_select_widget_placeholder_option(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('country');
        $field->type('select')->options(['us' => 'USA', 'ca' => 'Canada']);

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('-- Select --', $html);
    }

    public function test_select_widget_custom_placeholder(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('country');
        $field->type('select')
            ->options(['us' => 'USA', 'ca' => 'Canada'])
            ->placeholder('Choose a country');

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('Choose a country', $html);
    }

    public function test_select_widget_renders_name(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('role');
        $field->type('select')->options(['admin' => 'Admin']);

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('name="role"', $html);
    }

    public function test_select_widget_view_mode_shows_label(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('status');
        $field->type('select')->options(['active' => 'Active', 'inactive' => 'Inactive']);

        $html = $widget->render_view($field, 'active');

        $this->assert_contains_string('Active', $html);
        $this->assert_contains_string('form-view-value', $html);
        $this->assert_not_contains_string('<select', $html);
    }

    public function test_select_widget_view_mode_unknown_value_shows_raw(): void
    {
        $widget = new SelectWidget();
        $field = $this->make_field('status');
        $field->type('select')->options(['active' => 'Active']);

        $html = $widget->render_view($field, 'unknown');

        $this->assert_contains_string('unknown', $html);
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

        $this->assert_contains_string('type="hidden"', $html);
        $this->assert_contains_string('value="0"', $html);
        $this->assert_contains_string('type="checkbox"', $html);
        $this->assert_contains_string('value="1"', $html);
    }

    public function test_checkbox_widget_single_checked_state(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_edit($field, true);

        $this->assert_contains_string('checked', $html);
    }

    public function test_checkbox_widget_single_unchecked_state(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_edit($field, false);

        $this->assert_not_contains_string(' checked', $html);
    }

    public function test_checkbox_widget_multiple_from_options(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('tags');
        $field->type('checkbox')->options(['php' => 'PHP', 'js' => 'JavaScript', 'py' => 'Python']);

        $html = $widget->render_edit($field, ['php', 'py']);

        $this->assert_contains_string('checkbox-group', $html);
        $this->assert_contains_string('PHP', $html);
        $this->assert_contains_string('JavaScript', $html);
        $this->assert_contains_string('Python', $html);
    }

    public function test_checkbox_widget_multiple_checked_state(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('tags');
        $field->type('checkbox')->options(['a' => 'Alpha', 'b' => 'Beta']);

        $html = $widget->render_edit($field, ['a']);

        // The option 'a' should have checked
        $this->assert_contains_string('value="a" checked', $html);
    }

    public function test_checkbox_widget_multiple_uses_array_name(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('tags');
        $field->type('checkbox')->options(['a' => 'Alpha']);

        $html = $widget->render_edit($field, []);

        $this->assert_contains_string('name="tags[]"', $html);
    }

    public function test_checkbox_widget_view_mode_boolean_yes(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_view($field, true);

        $this->assert_contains_string('Yes', $html);
        $this->assert_contains_string('form-view-value', $html);
    }

    public function test_checkbox_widget_view_mode_boolean_no(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('active', 'BOOLEAN');
        $field->type('checkbox');

        $html = $widget->render_view($field, false);

        $this->assert_contains_string('No', $html);
    }

    public function test_checkbox_widget_view_mode_multiple_shows_labels(): void
    {
        $widget = new CheckboxWidget();
        $field = $this->make_field('tags');
        $field->type('checkbox')->options(['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma']);

        $html = $widget->render_view($field, ['a', 'c']);

        $this->assert_contains_string('Alpha', $html);
        $this->assert_contains_string('Gamma', $html);
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

        $this->assert_contains_string('radio-group', $html);
        $this->assert_contains_string('type="radio"', $html);
        $this->assert_contains_string('Low', $html);
        $this->assert_contains_string('Medium', $html);
        $this->assert_contains_string('High', $html);
    }

    public function test_radio_widget_checked_state(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('priority');
        $field->type('radio')->options(['low' => 'Low', 'high' => 'High']);

        $html = $widget->render_edit($field, 'high');

        $this->assert_contains_string('value="high" checked', $html);
    }

    public function test_radio_widget_renders_name(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('size');
        $field->type('radio')->options(['s' => 'Small']);

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('name="size"', $html);
    }

    public function test_radio_widget_renders_unique_ids(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('color');
        $field->type('radio')->options(['r' => 'Red', 'g' => 'Green']);

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('id="field-color-r"', $html);
        $this->assert_contains_string('id="field-color-g"', $html);
    }

    public function test_radio_widget_disabled(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('level');
        $field->type('radio')->options(['a' => 'A'])->disabled();

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('disabled', $html);
    }

    public function test_radio_widget_view_mode_shows_label(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('priority');
        $field->type('radio')->options(['low' => 'Low', 'high' => 'High']);

        $html = $widget->render_view($field, 'low');

        $this->assert_contains_string('Low', $html);
        $this->assert_contains_string('form-view-value', $html);
        $this->assert_not_contains_string('type="radio"', $html);
    }

    public function test_radio_widget_view_mode_unknown_value(): void
    {
        $widget = new RadioWidget();
        $field = $this->make_field('priority');
        $field->type('radio')->options(['low' => 'Low']);

        $html = $widget->render_view($field, 'unknown');

        $this->assert_contains_string('unknown', $html);
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

        $this->assert_contains_string('type="password"', $html);
        $this->assert_contains_string('value=""', $html);
        $this->assert_not_contains_string('secret123', $html);
    }

    public function test_password_widget_autocomplete_attribute(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('password');
        $field->type('password');

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('autocomplete="new-password"', $html);
    }

    public function test_password_widget_renders_name(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('pass');
        $field->type('password');

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('name="pass"', $html);
    }

    public function test_password_widget_view_mode_shows_asterisks(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('password');

        $html = $widget->render_view($field, 'secret');

        $this->assert_contains_string('********', $html);
        $this->assert_not_contains_string('secret', $html);
        $this->assert_contains_string('form-view-value', $html);
    }

    public function test_password_widget_view_mode_shows_asterisks_even_null(): void
    {
        $widget = new PasswordWidget();
        $field = $this->make_field('password');

        $html = $widget->render_view($field, null);

        $this->assert_contains_string('********', $html);
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

        $this->assert_contains_string('<input', $html);
        $this->assert_contains_string('type="number"', $html);
        $this->assert_contains_string('value="25"', $html);
    }

    public function test_number_widget_auto_step_any_for_decimal(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('price', 'DECIMAL');
        $field->type('number');

        $html = $widget->render_edit($field, 9.99);

        $this->assert_contains_string('step="any"', $html);
    }

    public function test_number_widget_auto_step_any_for_float(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('rate', 'FLOAT');
        $field->type('number');

        $html = $widget->render_edit($field, 3.14);

        $this->assert_contains_string('step="any"', $html);
    }

    public function test_number_widget_auto_step_any_for_real(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('value', 'REAL');
        $field->type('number');

        $html = $widget->render_edit($field, 1.5);

        $this->assert_contains_string('step="any"', $html);
    }

    public function test_number_widget_auto_step_any_for_numeric(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('amount', 'NUMERIC');
        $field->type('number');

        $html = $widget->render_edit($field, 10.5);

        $this->assert_contains_string('step="any"', $html);
    }

    public function test_number_widget_auto_step_any_for_double_precision(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('amount', 'DOUBLE PRECISION');
        $field->type('number');

        $html = $widget->render_edit($field, 1.23);

        $this->assert_contains_string('step="any"', $html);
    }

    public function test_number_widget_no_step_for_integer(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('count', 'INTEGER');
        $field->type('number');

        $html = $widget->render_edit($field, 5);

        $this->assert_not_contains_string('step=', $html);
    }

    public function test_number_widget_manual_step_not_overridden(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('price', 'DECIMAL');
        $field->type('number');

        $html = $widget->render_edit($field, 10, ['step' => '0.01']);

        $this->assert_contains_string('step="0.01"', $html);
        $this->assert_not_contains_string('step="any"', $html);
    }

    public function test_number_widget_null_value_empty_string(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('age', 'INTEGER');
        $field->type('number');

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('value=""', $html);
    }

    public function test_number_widget_view_mode(): void
    {
        $widget = new NumberWidget();
        $field = $this->make_field('age', 'INTEGER');

        $html = $widget->render_view($field, 42);

        $this->assert_contains_string('form-view-value', $html);
        $this->assert_contains_string('42', $html);
        $this->assert_not_contains_string('<input', $html);
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

        $this->assert_contains_string('<input', $html);
        $this->assert_contains_string('type="date"', $html);
        $this->assert_contains_string('value="2000-01-15"', $html);
    }

    public function test_date_widget_renders_datetime_local_type(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('created_at', 'DATETIME');
        $field->type('datetime-local');

        $html = $widget->render_edit($field, '2024-01-01T10:00');

        $this->assert_contains_string('type="datetime-local"', $html);
    }

    public function test_date_widget_renders_time_type(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('start_time', 'TIME');
        $field->type('time');

        $html = $widget->render_edit($field, '14:30');

        $this->assert_contains_string('type="time"', $html);
        $this->assert_contains_string('value="14:30"', $html);
    }

    public function test_date_widget_null_value_empty_string(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('birthday', 'DATE');
        $field->type('date');

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('value=""', $html);
    }

    public function test_date_widget_renders_name(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('start_date', 'DATE');
        $field->type('date');

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('name="start_date"', $html);
    }

    public function test_date_widget_view_mode(): void
    {
        $widget = new DateWidget();
        $field = $this->make_field('birthday', 'DATE');

        $html = $widget->render_view($field, '2000-06-15');

        $this->assert_contains_string('form-view-value', $html);
        $this->assert_contains_string('2000-06-15', $html);
        $this->assert_not_contains_string('<input', $html);
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

        $this->assert_contains_string('<input', $html);
        $this->assert_contains_string('type="file"', $html);
    }

    public function test_file_widget_does_not_have_value_attribute(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');
        $field->type('file');

        $html = $widget->render_edit($field, '/uploads/photo.jpg');

        // File inputs should not include a value attribute for security
        $this->assert_not_contains_string('value=', $html);
    }

    public function test_file_widget_renders_name(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('document');
        $field->type('file');

        $html = $widget->render_edit($field, null);

        $this->assert_contains_string('name="document"', $html);
    }

    public function test_file_widget_view_mode_no_file(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');

        $html = $widget->render_view($field, null);

        $this->assert_contains_string('No file', $html);
        $this->assert_contains_string('form-view-empty', $html);
    }

    public function test_file_widget_view_mode_with_file(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');

        $html = $widget->render_view($field, 'photo.jpg');

        $this->assert_contains_string('photo.jpg', $html);
        $this->assert_contains_string('form-view-value', $html);
        $this->assert_not_contains_string('No file', $html);
    }

    public function test_file_widget_view_mode_empty_string_shows_no_file(): void
    {
        $widget = new FileWidget();
        $field = $this->make_field('avatar');

        $html = $widget->render_view($field, '');

        $this->assert_contains_string('No file', $html);
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

        $this->assert_contains_string('type="hidden"', $html);
        $this->assert_contains_string('value="abc123"', $html);
        $this->assert_contains_string('name="csrf_token"', $html);
    }

    public function test_hidden_widget_no_wrapper(): void
    {
        $widget = new HiddenWidget();
        $field = $this->make_field('token');
        $field->type('hidden');

        $html = $widget->render_edit($field, 'xyz');

        $this->assert_not_contains_string('<div', $html);
        $this->assert_not_contains_string('<label', $html);
    }

    public function test_hidden_widget_renders_id(): void
    {
        $widget = new HiddenWidget();
        $field = $this->make_field('session_id');
        $field->type('hidden');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('id="field-session_id"', $html);
    }

    public function test_hidden_widget_view_mode_empty_string(): void
    {
        $widget = new HiddenWidget();
        $field = $this->make_field('token');

        $html = $widget->render_view($field, 'secret');

        $this->assert_same('', $html);
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

        $this->assert_contains_string('type="hidden"', $html);
        $this->assert_contains_string('name="created_at"', $html);
        $this->assert_contains_string('value="2024-01-01"', $html);
        $this->assert_contains_string('<span class="form-readonly-value">', $html);
        $this->assert_contains_string('2024-01-01</span>', $html);
    }

    public function test_readonly_widget_edit_escapes_value(): void
    {
        $widget = new ReadonlyWidget();
        $field = $this->make_field('data');
        $field->type('readonly');

        $html = $widget->render_edit($field, '<script>');

        $this->assert_not_contains_string('<script>', $html);
        $this->assert_contains_string('&lt;script&gt;', $html);
    }

    public function test_readonly_widget_view_mode(): void
    {
        $widget = new ReadonlyWidget();
        $field = $this->make_field('created_at');

        $html = $widget->render_view($field, '2024-06-01');

        $this->assert_contains_string('form-view-value', $html);
        $this->assert_contains_string('2024-06-01', $html);
        $this->assert_not_contains_string('type="hidden"', $html);
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

        $this->assert_contains_string('disabled', $html);
    }

    public function test_widget_renders_readonly(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->readonly();

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('readonly', $html);
    }

    public function test_widget_renders_custom_attributes(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->attr('data-validate', 'true');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('data-validate="true"', $html);
    }

    public function test_widget_renders_input_class(): void
    {
        $widget = new TextWidget();
        $field = $this->make_field('name');
        $field->type('text')->input_class('form-control');

        $html = $widget->render_edit($field, '');

        $this->assert_contains_string('class="form-control"', $html);
    }
}

exit(WidgetTest::run_all('Italix Forms — widgets'));
