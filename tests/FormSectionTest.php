<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\FormSection;

class FormSectionTest extends TestCase
{
    // =========================================================================
    // Constructor & Name
    // =========================================================================

    public function test_get_name(): void
    {
        $section = new FormSection('personal_info');

        $this->assertSame('personal_info', $section->get_name());
    }

    // =========================================================================
    // Title
    // =========================================================================

    public function test_title_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->title('Personal Information');

        $this->assertSame($section, $result, 'title() should return self for fluency');
        $this->assertSame('Personal Information', $section->get_title());
    }

    public function test_title_auto_generated_from_snake_case_name(): void
    {
        $section = new FormSection('personal_info');

        $this->assertSame('Personal Info', $section->get_title());
    }

    public function test_title_auto_generated_single_word(): void
    {
        $section = new FormSection('settings');

        $this->assertSame('Settings', $section->get_title());
    }

    public function test_title_auto_generated_multi_word(): void
    {
        $section = new FormSection('account_security_settings');

        $this->assertSame('Account Security Settings', $section->get_title());
    }

    // =========================================================================
    // Description
    // =========================================================================

    public function test_description_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->description('Enter your details');

        $this->assertSame($section, $result);
        $this->assertSame('Enter your details', $section->get_description());
    }

    public function test_description_default_is_null(): void
    {
        $section = new FormSection('personal');

        $this->assertNull($section->get_description());
    }

    // =========================================================================
    // Icon
    // =========================================================================

    public function test_icon_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->icon('fa-user');

        $this->assertSame($section, $result);
        $this->assertSame('fa-user', $section->get_icon());
    }

    public function test_icon_default_is_null(): void
    {
        $section = new FormSection('personal');

        $this->assertNull($section->get_icon());
    }

    // =========================================================================
    // Order
    // =========================================================================

    public function test_order_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->order(5);

        $this->assertSame($section, $result);
        $this->assertSame(5, $section->get_order());
    }

    public function test_order_default_is_zero(): void
    {
        $section = new FormSection('personal');

        $this->assertSame(0, $section->get_order());
    }

    // =========================================================================
    // Columns
    // =========================================================================

    public function test_columns_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->columns(3);

        $this->assertSame($section, $result);
        $this->assertSame(3, $section->get_columns());
    }

    public function test_columns_default_is_one(): void
    {
        $section = new FormSection('personal');

        $this->assertSame(1, $section->get_columns());
    }

    // =========================================================================
    // CSS Class
    // =========================================================================

    public function test_class_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->class('card mb-4');

        $this->assertSame($section, $result);
        $this->assertSame('card mb-4', $section->get_class());
    }

    public function test_class_default_is_null(): void
    {
        $section = new FormSection('personal');

        $this->assertNull($section->get_class());
    }

    // =========================================================================
    // Collapsible / Collapsed / Expanded
    // =========================================================================

    public function test_collapsible_default_not_collapsed(): void
    {
        $section = new FormSection('personal');
        $result = $section->collapsible();

        $this->assertSame($section, $result);
        $this->assertTrue($section->is_collapsible());
        $this->assertFalse($section->is_collapsed());
    }

    public function test_collapsible_with_collapsed_true(): void
    {
        $section = new FormSection('personal');
        $section->collapsible(true);

        $this->assertTrue($section->is_collapsible());
        $this->assertTrue($section->is_collapsed());
    }

    public function test_collapsible_with_collapsed_false(): void
    {
        $section = new FormSection('personal');
        $section->collapsible(false);

        $this->assertTrue($section->is_collapsible());
        $this->assertFalse($section->is_collapsed());
    }

    public function test_collapsed_method(): void
    {
        $section = new FormSection('personal');
        $result = $section->collapsed();

        $this->assertSame($section, $result);
        $this->assertTrue($section->is_collapsible());
        $this->assertTrue($section->is_collapsed());
    }

    public function test_expanded_method(): void
    {
        $section = new FormSection('personal');
        $section->collapsed();
        $result = $section->expanded();

        $this->assertSame($section, $result);
        $this->assertFalse($section->is_collapsed());
    }

    public function test_collapsible_default_is_false(): void
    {
        $section = new FormSection('personal');

        $this->assertFalse($section->is_collapsible());
    }

    public function test_collapsed_default_is_false(): void
    {
        $section = new FormSection('personal');

        $this->assertFalse($section->is_collapsed());
    }

    public function test_collapsed_then_expanded_preserves_collapsible(): void
    {
        $section = new FormSection('personal');
        $section->collapsed();
        $section->expanded();

        // collapsible was set by collapsed(), expanded() only changes collapsed state
        $this->assertTrue($section->is_collapsible());
        $this->assertFalse($section->is_collapsed());
    }

    // =========================================================================
    // to_array()
    // =========================================================================

    public function test_to_array_defaults(): void
    {
        $section = new FormSection('personal');

        $arr = $section->to_array();

        $this->assertSame([
            'name' => 'personal',
            'title' => 'Personal',
            'description' => null,
            'icon' => null,
            'order' => 0,
            'columns' => 1,
            'class' => null,
            'collapsible' => false,
            'collapsed' => false,
        ], $arr);
    }

    public function test_to_array_fully_configured(): void
    {
        $section = new FormSection('account_settings');
        $section
            ->title('Account Settings')
            ->description('Manage your account')
            ->icon('fa-cog')
            ->order(3)
            ->columns(2)
            ->class('settings-section')
            ->collapsible(true);

        $arr = $section->to_array();

        $this->assertSame([
            'name' => 'account_settings',
            'title' => 'Account Settings',
            'description' => 'Manage your account',
            'icon' => 'fa-cog',
            'order' => 3,
            'columns' => 2,
            'class' => 'settings-section',
            'collapsible' => true,
            'collapsed' => true,
        ], $arr);
    }

    public function test_to_array_auto_generated_title(): void
    {
        $section = new FormSection('shipping_address');

        $arr = $section->to_array();

        $this->assertSame('Shipping Address', $arr['title']);
    }

    // =========================================================================
    // Fluent Chaining
    // =========================================================================

    public function test_fluent_chaining(): void
    {
        $section = new FormSection('test');

        $result = $section
            ->title('Test')
            ->description('Desc')
            ->icon('icon')
            ->order(1)
            ->columns(2)
            ->class('cls')
            ->collapsible();

        $this->assertSame($section, $result);
        $this->assertSame('Test', $section->get_title());
        $this->assertSame('Desc', $section->get_description());
        $this->assertSame('icon', $section->get_icon());
        $this->assertSame(1, $section->get_order());
        $this->assertSame(2, $section->get_columns());
        $this->assertSame('cls', $section->get_class());
        $this->assertTrue($section->is_collapsible());
    }
}
