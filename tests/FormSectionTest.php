<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — form sections
 *
 * Written as an xUnit class; runs on the shared runner (`Italix\Testing\Runner`) through XunitCase.
 *
 * Run: php src/Libs/Italix/Forms/tests/FormSectionTest.php
 */

declare(strict_types=1);

namespace Italix\Forms\Tests;

use Italix\Forms\FormSection;


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

class FormSectionTest extends XunitCase
{
    // =========================================================================
    // Constructor & Name
    // =========================================================================

    public function test_get_name(): void
    {
        $section = new FormSection('personal_info');

        $this->assert_same('personal_info', $section->get_name());
    }

    // =========================================================================
    // Title
    // =========================================================================

    public function test_title_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->title('Personal Information');

        $this->assert_same($section, $result, 'title() should return self for fluency');
        $this->assert_same('Personal Information', $section->get_title());
    }

    public function test_title_auto_generated_from_snake_case_name(): void
    {
        $section = new FormSection('personal_info');

        $this->assert_same('Personal Info', $section->get_title());
    }

    public function test_title_auto_generated_single_word(): void
    {
        $section = new FormSection('settings');

        $this->assert_same('Settings', $section->get_title());
    }

    public function test_title_auto_generated_multi_word(): void
    {
        $section = new FormSection('account_security_settings');

        $this->assert_same('Account Security Settings', $section->get_title());
    }

    // =========================================================================
    // Description
    // =========================================================================

    public function test_description_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->description('Enter your details');

        $this->assert_same($section, $result);
        $this->assert_same('Enter your details', $section->get_description());
    }

    public function test_description_default_is_null(): void
    {
        $section = new FormSection('personal');

        $this->assert_null($section->get_description());
    }

    // =========================================================================
    // Icon
    // =========================================================================

    public function test_icon_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->icon('fa-user');

        $this->assert_same($section, $result);
        $this->assert_same('fa-user', $section->get_icon());
    }

    public function test_icon_default_is_null(): void
    {
        $section = new FormSection('personal');

        $this->assert_null($section->get_icon());
    }

    // =========================================================================
    // Order
    // =========================================================================

    public function test_order_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->order(5);

        $this->assert_same($section, $result);
        $this->assert_same(5, $section->get_order());
    }

    public function test_order_default_is_zero(): void
    {
        $section = new FormSection('personal');

        $this->assert_same(0, $section->get_order());
    }

    // =========================================================================
    // Columns
    // =========================================================================

    public function test_columns_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->columns(3);

        $this->assert_same($section, $result);
        $this->assert_same(3, $section->get_columns());
    }

    public function test_columns_default_is_one(): void
    {
        $section = new FormSection('personal');

        $this->assert_same(1, $section->get_columns());
    }

    // =========================================================================
    // CSS Class
    // =========================================================================

    public function test_class_setter_and_getter(): void
    {
        $section = new FormSection('personal');
        $result = $section->class('card mb-4');

        $this->assert_same($section, $result);
        $this->assert_same('card mb-4', $section->get_class());
    }

    public function test_class_default_is_null(): void
    {
        $section = new FormSection('personal');

        $this->assert_null($section->get_class());
    }

    // =========================================================================
    // Collapsible / Collapsed / Expanded
    // =========================================================================

    public function test_collapsible_default_not_collapsed(): void
    {
        $section = new FormSection('personal');
        $result = $section->collapsible();

        $this->assert_same($section, $result);
        $this->assert_true($section->is_collapsible());
        $this->assert_false($section->is_collapsed());
    }

    public function test_collapsible_with_collapsed_true(): void
    {
        $section = new FormSection('personal');
        $section->collapsible(true);

        $this->assert_true($section->is_collapsible());
        $this->assert_true($section->is_collapsed());
    }

    public function test_collapsible_with_collapsed_false(): void
    {
        $section = new FormSection('personal');
        $section->collapsible(false);

        $this->assert_true($section->is_collapsible());
        $this->assert_false($section->is_collapsed());
    }

    public function test_collapsed_method(): void
    {
        $section = new FormSection('personal');
        $result = $section->collapsed();

        $this->assert_same($section, $result);
        $this->assert_true($section->is_collapsible());
        $this->assert_true($section->is_collapsed());
    }

    public function test_expanded_method(): void
    {
        $section = new FormSection('personal');
        $section->collapsed();
        $result = $section->expanded();

        $this->assert_same($section, $result);
        $this->assert_false($section->is_collapsed());
    }

    public function test_collapsible_default_is_false(): void
    {
        $section = new FormSection('personal');

        $this->assert_false($section->is_collapsible());
    }

    public function test_collapsed_default_is_false(): void
    {
        $section = new FormSection('personal');

        $this->assert_false($section->is_collapsed());
    }

    public function test_collapsed_then_expanded_preserves_collapsible(): void
    {
        $section = new FormSection('personal');
        $section->collapsed();
        $section->expanded();

        // collapsible was set by collapsed(), expanded() only changes collapsed state
        $this->assert_true($section->is_collapsible());
        $this->assert_false($section->is_collapsed());
    }

    // =========================================================================
    // to_array()
    // =========================================================================

    public function test_to_array_defaults(): void
    {
        $section = new FormSection('personal');

        $arr = $section->to_array();

        $this->assert_same([
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

        $this->assert_same([
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

        $this->assert_same('Shipping Address', $arr['title']);
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

        $this->assert_same($section, $result);
        $this->assert_same('Test', $section->get_title());
        $this->assert_same('Desc', $section->get_description());
        $this->assert_same('icon', $section->get_icon());
        $this->assert_same(1, $section->get_order());
        $this->assert_same(2, $section->get_columns());
        $this->assert_same('cls', $section->get_class());
        $this->assert_true($section->is_collapsible());
    }
}

exit(FormSectionTest::run_all('Italix Forms — form sections'));
