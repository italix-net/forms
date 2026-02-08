<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\Adapters\GenericColumnAdapter;
use Italix\Forms\Adapters\GenericRelationAdapter;
use Italix\Forms\Adapters\GenericTableAdapter;
use Italix\Forms\FieldMeta;
use Italix\Forms\FormMeta;
use Italix\Forms\Rendering\FormHtml;
use Italix\Forms\Rendering\WidgetRegistry;

class FormHtmlTest extends TestCase
{
    /**
     * Create a standard test FormMeta with common columns.
     *
     * @return FormMeta
     */
    private function make_form(): FormMeta
    {
        return new FormMeta(new GenericTableAdapter([
            'name'  => ['type' => 'VARCHAR', 'length' => 100, 'nullable' => false],
            'email' => ['type' => 'VARCHAR', 'length' => 255, 'nullable' => false],
            'bio'   => ['type' => 'TEXT', 'nullable' => true],
        ]));
    }

    /**
     * Create a FormHtml renderer with default settings.
     *
     * @param FormMeta|null $form
     * @return FormHtml
     */
    private function make_html(?FormMeta $form = null): FormHtml
    {
        return new FormHtml($form ?? $this->make_form());
    }

    // =========================================================================
    // render() - Complete Form
    // =========================================================================

    public function test_render_produces_complete_form(): void
    {
        $html = $this->make_html()
            ->action('/users/store')
            ->method('POST')
            ->render();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('</form>', $html);
        $this->assertStringContainsString('action="/users/store"', $html);
    }

    public function test_render_contains_fields(): void
    {
        $html = $this->make_html()->render();

        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="bio"', $html);
    }

    public function test_render_contains_buttons(): void
    {
        $html = $this->make_html()->render();

        $this->assertStringContainsString('<div class="form-buttons">', $html);
        $this->assertStringContainsString('<button type="submit"', $html);
        $this->assertStringContainsString('Save', $html);
    }

    public function test_render_contains_open_and_close(): void
    {
        $html = $this->make_html()->render();

        $this->assertStringStartsWith('<form', $html);
        $this->assertStringEndsWith('</form>', $html);
    }

    public function test_render_in_view_mode_has_no_buttons(): void
    {
        $html = $this->make_html()->mode('view')->render();

        $this->assertStringNotContainsString('<div class="form-buttons">', $html);
        $this->assertStringNotContainsString('<button', $html);
    }

    // =========================================================================
    // open() - Form Opening Tag
    // =========================================================================

    public function test_open_with_action(): void
    {
        $html = $this->make_html()->action('/submit')->open();

        $this->assertStringContainsString('action="/submit"', $html);
    }

    public function test_open_with_post_method(): void
    {
        $html = $this->make_html()->method('POST')->open();

        $this->assertStringContainsString('method="POST"', $html);
    }

    public function test_open_with_get_method(): void
    {
        $html = $this->make_html()->method('GET')->open();

        $this->assertStringContainsString('method="GET"', $html);
    }

    public function test_open_has_default_class(): void
    {
        $html = $this->make_html()->open();

        $this->assertStringContainsString('class="form"', $html);
    }

    public function test_open_with_custom_class(): void
    {
        $html = $this->make_html()->attrs(['class' => 'my-form'])->open();

        $this->assertStringContainsString('class="my-form"', $html);
    }

    public function test_open_with_custom_attrs(): void
    {
        $html = $this->make_html()
            ->attrs(['id' => 'user-form', 'data-ajax' => 'true'])
            ->open();

        $this->assertStringContainsString('id="user-form"', $html);
        $this->assertStringContainsString('data-ajax="true"', $html);
    }

    // =========================================================================
    // Method Spoofing (PUT/PATCH/DELETE)
    // =========================================================================

    public function test_put_method_spoofing(): void
    {
        $html = $this->make_html()->method('PUT')->open();

        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('<input type="hidden" name="_method" value="PUT">', $html);
    }

    public function test_patch_method_spoofing(): void
    {
        $html = $this->make_html()->method('PATCH')->open();

        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('<input type="hidden" name="_method" value="PATCH">', $html);
    }

    public function test_delete_method_spoofing(): void
    {
        $html = $this->make_html()->method('DELETE')->open();

        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('<input type="hidden" name="_method" value="DELETE">', $html);
    }

    public function test_post_method_no_spoofing(): void
    {
        $html = $this->make_html()->method('POST')->open();

        $this->assertStringNotContainsString('_method', $html);
    }

    public function test_get_method_no_spoofing(): void
    {
        $html = $this->make_html()->method('GET')->open();

        $this->assertStringNotContainsString('_method', $html);
    }

    public function test_method_case_insensitive(): void
    {
        $html = $this->make_html()->method('put')->open();

        $this->assertStringContainsString('value="PUT"', $html);
    }

    // =========================================================================
    // close() - Form Closing Tag
    // =========================================================================

    public function test_close_produces_closing_form_tag(): void
    {
        $html = $this->make_html()->close();

        $this->assertSame('</form>', $html);
    }

    public function test_close_view_mode_produces_closing_div(): void
    {
        $html = $this->make_html()->mode('view')->close();

        $this->assertSame('</div>', $html);
    }

    // =========================================================================
    // View Mode
    // =========================================================================

    public function test_view_mode_open_produces_div(): void
    {
        $html = $this->make_html()->mode('view')->open();

        $this->assertSame('<div class="form-view">', $html);
    }

    public function test_view_mode_open_does_not_contain_form_tag(): void
    {
        $html = $this->make_html()->mode('view')->open();

        $this->assertStringNotContainsString('<form', $html);
    }

    public function test_view_mode_close_produces_closing_div(): void
    {
        $html = $this->make_html()->mode('view')->close();

        $this->assertSame('</div>', $html);
    }

    public function test_view_mode_fields_render_view_widgets(): void
    {
        $html = $this->make_html()
            ->mode('view')
            ->values(['name' => 'John'])
            ->field('name');

        $this->assertStringContainsString('form-view-value', $html);
        $this->assertStringContainsString('John', $html);
    }

    public function test_view_mode_full_render(): void
    {
        $html = $this->make_html()
            ->mode('view')
            ->values(['name' => 'Alice', 'email' => 'alice@example.com'])
            ->render();

        $this->assertStringStartsWith('<div class="form-view">', $html);
        $this->assertStringEndsWith('</div>', $html);
        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('alice@example.com', $html);
    }

    // =========================================================================
    // values() - Pre-filling Fields
    // =========================================================================

    public function test_values_prefills_text_field(): void
    {
        $html = $this->make_html()
            ->values(['name' => 'Bob'])
            ->field('name');

        $this->assertStringContainsString('value="Bob"', $html);
    }

    public function test_values_prefills_multiple_fields(): void
    {
        $renderer = $this->make_html()
            ->values(['name' => 'Alice', 'email' => 'alice@test.com']);

        $name_html = $renderer->field('name');
        $email_html = $renderer->field('email');

        $this->assertStringContainsString('value="Alice"', $name_html);
        $this->assertStringContainsString('value="alice@test.com"', $email_html);
    }

    public function test_values_null_for_missing_field(): void
    {
        $html = $this->make_html()
            ->values(['name' => 'Test'])
            ->field('email');

        // email has no value set, so value should be empty
        $this->assertStringContainsString('value=""', $html);
    }

    public function test_values_escapes_html(): void
    {
        $html = $this->make_html()
            ->values(['name' => '<script>alert("xss")</script>'])
            ->field('name');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // =========================================================================
    // errors() - Validation Error Display
    // =========================================================================

    public function test_errors_shows_error_message(): void
    {
        $html = $this->make_html()
            ->errors(['name' => 'Name is required'])
            ->field('name');

        $this->assertStringContainsString('Name is required', $html);
        $this->assertStringContainsString('field-error', $html);
    }

    public function test_errors_adds_has_error_class_to_wrapper(): void
    {
        $html = $this->make_html()
            ->errors(['name' => 'Name is required'])
            ->field('name');

        $this->assertStringContainsString('has-error', $html);
    }

    public function test_no_error_no_has_error_class(): void
    {
        $html = $this->make_html()->field('name');

        $this->assertStringNotContainsString('has-error', $html);
        $this->assertStringNotContainsString('field-error', $html);
    }

    public function test_errors_escapes_html(): void
    {
        $html = $this->make_html()
            ->errors(['name' => '<b>Bad</b>'])
            ->field('name');

        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }

    // =========================================================================
    // Auto-detect enctype
    // =========================================================================

    public function test_auto_detect_enctype_for_file_field(): void
    {
        $form = new FormMeta(new GenericTableAdapter([
            'name'   => ['type' => 'VARCHAR'],
            'avatar' => ['type' => 'VARCHAR'],
        ]));
        $form->field('avatar')->type('file');

        $html = (new FormHtml($form))->open();

        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
    }

    public function test_no_enctype_without_file_field(): void
    {
        $html = $this->make_html()->open();

        $this->assertStringNotContainsString('enctype', $html);
    }

    public function test_manual_enctype_overrides_detection(): void
    {
        $html = $this->make_html()
            ->enctype('application/x-www-form-urlencoded')
            ->open();

        $this->assertStringContainsString('enctype="application/x-www-form-urlencoded"', $html);
    }

    // =========================================================================
    // buttons() - Submit and Cancel
    // =========================================================================

    public function test_default_buttons_submit_only(): void
    {
        $html = $this->make_html()->render_buttons();

        $this->assertStringContainsString('Save', $html);
        $this->assertStringNotContainsString('Cancel', $html);
        $this->assertStringNotContainsString('<a', $html);
    }

    public function test_custom_submit_label(): void
    {
        $html = $this->make_html()
            ->buttons(['submit' => 'Create User'])
            ->render_buttons();

        $this->assertStringContainsString('Create User', $html);
        $this->assertStringNotContainsString('Save', $html);
    }

    public function test_cancel_link(): void
    {
        $html = $this->make_html()
            ->buttons(['submit' => 'Save', 'cancel' => '/users'])
            ->render_buttons();

        $this->assertStringContainsString('href="/users"', $html);
        $this->assertStringContainsString('Cancel', $html);
        $this->assertStringContainsString('btn-cancel', $html);
    }

    public function test_cancel_custom_label(): void
    {
        $html = $this->make_html()
            ->buttons(['submit' => 'Save', 'cancel' => '/back', 'cancel_label' => 'Go Back'])
            ->render_buttons();

        $this->assertStringContainsString('Go Back', $html);
        $this->assertStringNotContainsString('Cancel', $html);
    }

    public function test_no_submit_button_when_false(): void
    {
        $html = $this->make_html()
            ->buttons(['submit' => false, 'cancel' => '/back'])
            ->render_buttons();

        $this->assertStringNotContainsString('<button', $html);
        $this->assertStringContainsString('<a', $html);
    }

    public function test_buttons_container_class(): void
    {
        $html = $this->make_html()->render_buttons();

        $this->assertStringContainsString('class="form-buttons"', $html);
    }

    public function test_submit_button_has_primary_class(): void
    {
        $html = $this->make_html()->render_buttons();

        $this->assertStringContainsString('class="btn btn-primary"', $html);
    }

    // =========================================================================
    // Sections / Fieldsets Rendering
    // =========================================================================

    public function test_sections_render_fieldset(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('personal');
        $form->field('email')->group('personal');
        $form->section('personal')->title('Personal Info');

        $html = (new FormHtml($form))->render();

        $this->assertStringContainsString('<fieldset', $html);
        $this->assertStringContainsString('<legend>Personal Info</legend>', $html);
        $this->assertStringContainsString('</fieldset>', $html);
    }

    public function test_sections_class_attribute(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('details');
        $form->section('details')->title('Details')->class('extra-class');

        $html = (new FormHtml($form))->render();

        $this->assertStringContainsString('form-section extra-class', $html);
    }

    public function test_sections_with_description(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('basic');
        $form->section('basic')
            ->title('Basic')
            ->description('Fill in your basic details');

        $html = (new FormHtml($form))->render();

        $this->assertStringContainsString('section-description', $html);
        $this->assertStringContainsString('Fill in your basic details', $html);
    }

    public function test_sections_with_columns_grid(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('info');
        $form->field('email')->group('info');
        $form->section('info')->title('Info')->columns(2);

        $html = (new FormHtml($form))->render();

        $this->assertStringContainsString('form-grid', $html);
        $this->assertStringContainsString('grid-template-columns:repeat(2,1fr)', $html);
    }

    public function test_sections_collapsible(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('sec');
        $form->section('sec')->title('Section')->collapsible();

        $html = (new FormHtml($form))->render();

        $this->assertStringContainsString('data-collapsible="true"', $html);
    }

    public function test_sections_collapsed(): void
    {
        $form = $this->make_form();
        $form->field('name')->group('sec');
        $form->section('sec')->title('Section')->collapsed();

        $html = (new FormHtml($form))->render();

        $this->assertStringContainsString('data-collapsed="true"', $html);
    }

    public function test_no_fieldset_without_sections(): void
    {
        $html = $this->make_html()->render();

        $this->assertStringNotContainsString('<fieldset', $html);
        $this->assertStringNotContainsString('<legend', $html);
    }

    // =========================================================================
    // field() - Individual Field Rendering
    // =========================================================================

    public function test_field_renders_single_field(): void
    {
        $html = $this->make_html()
            ->values(['name' => 'Test'])
            ->field('name');

        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('value="Test"', $html);
        $this->assertStringContainsString('form-field', $html);
    }

    public function test_field_renders_label(): void
    {
        $form = $this->make_form();
        $form->field('name')->label('Full Name');

        $html = (new FormHtml($form))->field('name');

        $this->assertStringContainsString('Full Name', $html);
        $this->assertStringContainsString('<label', $html);
    }

    public function test_field_renders_required_indicator(): void
    {
        // name is non-nullable so it's required
        $html = $this->make_html()->field('name');

        $this->assertStringContainsString('required', $html);
    }

    public function test_field_renders_help_text(): void
    {
        $form = $this->make_form();
        $form->field('name')->help('Enter your full name');

        $html = (new FormHtml($form))->field('name');

        $this->assertStringContainsString('form-help', $html);
        $this->assertStringContainsString('Enter your full name', $html);
    }

    // =========================================================================
    // fields() - All Visible Fields
    // =========================================================================

    public function test_fields_renders_all_visible(): void
    {
        $html = $this->make_html()->fields();

        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="bio"', $html);
    }

    public function test_fields_skips_hidden(): void
    {
        $form = $this->make_form();
        $form->field('bio')->hidden();

        $html = (new FormHtml($form))->fields();

        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringNotContainsString('name="bio"', $html);
    }

    public function test_fields_respects_order(): void
    {
        $form = $this->make_form();
        $form->field('email')->order(1);
        $form->field('name')->order(2);
        $form->field('bio')->order(3);

        $html = (new FormHtml($form))->fields();

        $pos_email = strpos($html, 'name="email"');
        $pos_name = strpos($html, 'name="name"');
        $pos_bio = strpos($html, 'name="bio"');

        $this->assertLessThan($pos_name, $pos_email);
        $this->assertLessThan($pos_bio, $pos_name);
    }

    // =========================================================================
    // auto_resolve_relations
    // =========================================================================

    public function test_auto_resolve_relations_populates_options(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) {
                return [1 => 'USA', 2 => 'Canada', 3 => 'Mexico'];
            }
        );

        $column = new GenericColumnAdapter('country_id', 'INTEGER', true, false, null, null, false, $relation);
        $table = new GenericTableAdapter(['country_id' => $column]);
        $form = new FormMeta($table);

        $html = (new FormHtml($form))
            ->auto_resolve_relations(true)
            ->values(['country_id' => 2])
            ->field('country_id');

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('USA', $html);
        $this->assertStringContainsString('Canada', $html);
        $this->assertStringContainsString('Mexico', $html);
    }

    public function test_auto_resolve_relations_disabled(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) {
                return [1 => 'USA', 2 => 'Canada'];
            }
        );

        $column = new GenericColumnAdapter('country_id', 'INTEGER', true, false, null, null, false, $relation);
        $table = new GenericTableAdapter(['country_id' => $column]);
        $form = new FormMeta($table);

        $html = (new FormHtml($form))
            ->auto_resolve_relations(false)
            ->field('country_id');

        // Without auto-resolve, should render as number (inferred from INTEGER)
        $this->assertStringNotContainsString('<select', $html);
        $this->assertStringContainsString('type="number"', $html);
    }

    public function test_auto_resolve_relations_does_not_override_manual_options(): void
    {
        $relation = new GenericRelationAdapter('countries', 'id', 'name',
            function ($table, $key, $label, $limit) {
                return [1 => 'USA', 2 => 'Canada'];
            }
        );

        $column = new GenericColumnAdapter('country_id', 'INTEGER', true, false, null, null, false, $relation);
        $table = new GenericTableAdapter(['country_id' => $column]);
        $form = new FormMeta($table);
        $form->field('country_id')->options(['a' => 'Option A', 'b' => 'Option B']);

        $html = (new FormHtml($form))
            ->auto_resolve_relations(true)
            ->field('country_id');

        $this->assertStringContainsString('Option A', $html);
        $this->assertStringContainsString('Option B', $html);
        $this->assertStringNotContainsString('USA', $html);
    }

    public function test_auto_resolve_too_many_rows_sets_autocomplete(): void
    {
        $relation = new GenericRelationAdapter('big_table', 'id', 'name',
            function ($table, $key, $label, $limit) {
                // Return more than $limit rows to trigger "too many"
                $options = [];
                for ($i = 1; $i <= $limit; $i++) {
                    $options[$i] = "Row {$i}";
                }
                return $options;
            }
        );

        $column = new GenericColumnAdapter('big_id', 'INTEGER', true, false, null, null, false, $relation);
        $table = new GenericTableAdapter(['big_id' => $column]);
        $form = new FormMeta($table);

        $html = (new FormHtml($form))
            ->auto_resolve_relations(true)
            ->field('big_id');

        $this->assertStringContainsString('data-autocomplete="true"', $html);
        $this->assertStringContainsString('data-source-table="big_table"', $html);
    }

    // =========================================================================
    // Fluent Interface Returns Self
    // =========================================================================

    public function test_fluent_action_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->action('/test'));
    }

    public function test_fluent_method_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->method('POST'));
    }

    public function test_fluent_mode_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->mode('edit'));
    }

    public function test_fluent_values_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->values([]));
    }

    public function test_fluent_errors_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->errors([]));
    }

    public function test_fluent_attrs_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->attrs([]));
    }

    public function test_fluent_buttons_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->buttons([]));
    }

    public function test_fluent_enctype_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->enctype('multipart/form-data'));
    }

    public function test_fluent_auto_resolve_relations_returns_self(): void
    {
        $html = $this->make_html();
        $this->assertSame($html, $html->auto_resolve_relations(true));
    }

    // =========================================================================
    // Getters
    // =========================================================================

    public function test_get_form_returns_form_meta(): void
    {
        $form = $this->make_form();
        $html = new FormHtml($form);

        $this->assertSame($form, $html->get_form());
    }

    public function test_get_registry_returns_widget_registry(): void
    {
        $html = $this->make_html();

        $this->assertInstanceOf(WidgetRegistry::class, $html->get_registry());
    }

    public function test_custom_registry_is_used(): void
    {
        $registry = new WidgetRegistry();
        $form = $this->make_form();
        $html = new FormHtml($form, $registry);

        $this->assertSame($registry, $html->get_registry());
    }

    // =========================================================================
    // Hidden Fields Bare Rendering
    // =========================================================================

    public function test_hidden_field_rendered_bare(): void
    {
        $form = new FormMeta(new GenericTableAdapter([
            'token' => ['type' => 'VARCHAR'],
        ]));
        $form->field('token')->type('hidden');

        $html = (new FormHtml($form))
            ->values(['token' => 'abc123'])
            ->field('token');

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('value="abc123"', $html);
        // Hidden fields should not have wrapper/label
        $this->assertStringNotContainsString('form-field', $html);
        $this->assertStringNotContainsString('<label', $html);
    }

    // =========================================================================
    // Wrapper CSS Classes
    // =========================================================================

    public function test_wrapper_class_on_field(): void
    {
        $form = $this->make_form();
        $form->field('name')->wrapper_class('custom-wrapper');

        $html = (new FormHtml($form))->field('name');

        $this->assertStringContainsString('form-field custom-wrapper', $html);
    }

    public function test_label_class_on_field(): void
    {
        $form = $this->make_form();
        $form->field('name')->label_class('bold-label');

        $html = (new FormHtml($form))->field('name');

        $this->assertStringContainsString('form-label bold-label', $html);
    }

    public function test_help_class_on_field(): void
    {
        $form = $this->make_form();
        $form->field('name')->help('Some help')->help_class('small-help');

        $html = (new FormHtml($form))->field('name');

        $this->assertStringContainsString('form-help small-help', $html);
    }
}
