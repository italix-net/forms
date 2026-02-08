<?php

declare(strict_types=1);

namespace Italix\Forms\Tests;

use PHPUnit\Framework\TestCase;
use Italix\Forms\Validation\Rule;

class RuleTest extends TestCase
{
    // =========================================================================
    // Factory Methods - Presence Rules
    // =========================================================================

    public function test_required_creates_rule_with_correct_defaults(): void
    {
        $rule = Rule::required();

        $this->assertSame('required', $rule->get_name());
        $this->assertSame([], $rule->get_params());
        $this->assertSame('This field is required', $rule->get_message());
    }

    public function test_required_with_custom_message(): void
    {
        $rule = Rule::required('Please fill this in');

        $this->assertSame('required', $rule->get_name());
        $this->assertSame('Please fill this in', $rule->get_message());
    }

    public function test_required_if(): void
    {
        $rule = Rule::required_if('role', 'admin');

        $this->assertSame('required_if', $rule->get_name());
        $this->assertSame(['field' => 'role', 'value' => 'admin'], $rule->get_params());
        $this->assertSame('This field is required', $rule->get_message());
    }

    public function test_required_if_with_custom_message(): void
    {
        $rule = Rule::required_if('role', 'admin', 'Required for admins');

        $this->assertSame('Required for admins', $rule->get_message());
    }

    public function test_required_unless(): void
    {
        $rule = Rule::required_unless('status', 'draft');

        $this->assertSame('required_unless', $rule->get_name());
        $this->assertSame(['field' => 'status', 'value' => 'draft'], $rule->get_params());
        $this->assertSame('This field is required', $rule->get_message());
    }

    // =========================================================================
    // Factory Methods - Format Rules
    // =========================================================================

    public function test_email(): void
    {
        $rule = Rule::email();

        $this->assertSame('email', $rule->get_name());
        $this->assertSame([], $rule->get_params());
        $this->assertSame('Please enter a valid email address', $rule->get_message());
    }

    public function test_email_with_custom_message(): void
    {
        $rule = Rule::email('Bad email');

        $this->assertSame('Bad email', $rule->get_message());
    }

    public function test_url(): void
    {
        $rule = Rule::url();

        $this->assertSame('url', $rule->get_name());
        $this->assertSame([], $rule->get_params());
        $this->assertSame('Please enter a valid URL', $rule->get_message());
    }

    public function test_pattern(): void
    {
        $rule = Rule::pattern('/^[A-Z]+$/');

        $this->assertSame('pattern', $rule->get_name());
        $this->assertSame(['regex' => '/^[A-Z]+$/'], $rule->get_params());
        $this->assertSame('Invalid format', $rule->get_message());
    }

    public function test_alpha(): void
    {
        $rule = Rule::alpha();

        $this->assertSame('alpha', $rule->get_name());
        $this->assertSame([], $rule->get_params());
        $this->assertSame('Only letters are allowed', $rule->get_message());
    }

    public function test_alpha_num(): void
    {
        $rule = Rule::alpha_num();

        $this->assertSame('alpha_num', $rule->get_name());
        $this->assertSame('Only letters and numbers are allowed', $rule->get_message());
    }

    public function test_alpha_dash(): void
    {
        $rule = Rule::alpha_dash();

        $this->assertSame('alpha_dash', $rule->get_name());
        $this->assertSame('Only letters, numbers, dashes, and underscores are allowed', $rule->get_message());
    }

    public function test_numeric(): void
    {
        $rule = Rule::numeric();

        $this->assertSame('numeric', $rule->get_name());
        $this->assertSame('Must be a number', $rule->get_message());
    }

    public function test_integer(): void
    {
        $rule = Rule::integer();

        $this->assertSame('integer', $rule->get_name());
        $this->assertSame('Must be a whole number', $rule->get_message());
    }

    public function test_date(): void
    {
        $rule = Rule::date();

        $this->assertSame('date', $rule->get_name());
        $this->assertSame('Please enter a valid date', $rule->get_message());
    }

    public function test_date_format(): void
    {
        $rule = Rule::date_format('Y-m-d');

        $this->assertSame('date_format', $rule->get_name());
        $this->assertSame(['format' => 'Y-m-d'], $rule->get_params());
        $this->assertSame('Date must match format: Y-m-d', $rule->get_message());
    }

    // =========================================================================
    // Factory Methods - Size/Length Rules
    // =========================================================================

    public function test_min(): void
    {
        $rule = Rule::min(5);

        $this->assertSame('min', $rule->get_name());
        $this->assertSame(['value' => 5], $rule->get_params());
        $this->assertSame('Minimum value is 5', $rule->get_message());
    }

    public function test_max(): void
    {
        $rule = Rule::max(100);

        $this->assertSame('max', $rule->get_name());
        $this->assertSame(['value' => 100], $rule->get_params());
        $this->assertSame('Maximum value is 100', $rule->get_message());
    }

    public function test_min_length(): void
    {
        $rule = Rule::min_length(3);

        $this->assertSame('min_length', $rule->get_name());
        $this->assertSame(['length' => 3], $rule->get_params());
        $this->assertSame('Minimum 3 characters required', $rule->get_message());
    }

    public function test_max_length(): void
    {
        $rule = Rule::max_length(255);

        $this->assertSame('max_length', $rule->get_name());
        $this->assertSame(['length' => 255], $rule->get_params());
        $this->assertSame('Maximum 255 characters allowed', $rule->get_message());
    }

    public function test_between(): void
    {
        $rule = Rule::between(1, 10);

        $this->assertSame('between', $rule->get_name());
        $this->assertSame(['min' => 1, 'max' => 10], $rule->get_params());
        $this->assertSame('Value must be between 1 and 10', $rule->get_message());
    }

    public function test_length_between(): void
    {
        $rule = Rule::length_between(5, 50);

        $this->assertSame('length_between', $rule->get_name());
        $this->assertSame(['min' => 5, 'max' => 50], $rule->get_params());
        $this->assertSame('Length must be between 5 and 50 characters', $rule->get_message());
    }

    public function test_length(): void
    {
        $rule = Rule::length(10);

        $this->assertSame('length', $rule->get_name());
        $this->assertSame(['length' => 10], $rule->get_params());
        $this->assertSame('Must be exactly 10 characters', $rule->get_message());
    }

    // =========================================================================
    // Factory Methods - Comparison Rules
    // =========================================================================

    public function test_in(): void
    {
        $rule = Rule::in(['a', 'b', 'c']);

        $this->assertSame('in', $rule->get_name());
        $this->assertSame(['values' => ['a', 'b', 'c']], $rule->get_params());
        $this->assertSame('Invalid selection', $rule->get_message());
    }

    public function test_not_in(): void
    {
        $rule = Rule::not_in(['x', 'y']);

        $this->assertSame('not_in', $rule->get_name());
        $this->assertSame(['values' => ['x', 'y']], $rule->get_params());
        $this->assertSame('Invalid selection', $rule->get_message());
    }

    public function test_confirmed_default_field(): void
    {
        $rule = Rule::confirmed();

        $this->assertSame('confirmed', $rule->get_name());
        $this->assertSame(['field' => 'confirmation'], $rule->get_params());
        $this->assertSame('Confirmation does not match', $rule->get_message());
    }

    public function test_confirmed_custom_field(): void
    {
        $rule = Rule::confirmed('password_repeat');

        $this->assertSame(['field' => 'password_repeat'], $rule->get_params());
    }

    public function test_same(): void
    {
        $rule = Rule::same('password');

        $this->assertSame('same', $rule->get_name());
        $this->assertSame(['field' => 'password'], $rule->get_params());
        $this->assertSame('Must match password', $rule->get_message());
    }

    public function test_different(): void
    {
        $rule = Rule::different('old_password');

        $this->assertSame('different', $rule->get_name());
        $this->assertSame(['field' => 'old_password'], $rule->get_params());
        $this->assertSame('Must be different from old_password', $rule->get_message());
    }

    public function test_gt(): void
    {
        $rule = Rule::gt(10);

        $this->assertSame('gt', $rule->get_name());
        $this->assertSame(['value' => 10], $rule->get_params());
        $this->assertSame('Must be greater than 10', $rule->get_message());
    }

    public function test_gte(): void
    {
        $rule = Rule::gte(0);

        $this->assertSame('gte', $rule->get_name());
        $this->assertSame(['value' => 0], $rule->get_params());
        $this->assertSame('Must be at least 0', $rule->get_message());
    }

    public function test_lt(): void
    {
        $rule = Rule::lt(100);

        $this->assertSame('lt', $rule->get_name());
        $this->assertSame(['value' => 100], $rule->get_params());
        $this->assertSame('Must be less than 100', $rule->get_message());
    }

    public function test_lte(): void
    {
        $rule = Rule::lte(99);

        $this->assertSame('lte', $rule->get_name());
        $this->assertSame(['value' => 99], $rule->get_params());
        $this->assertSame('Must be at most 99', $rule->get_message());
    }

    // =========================================================================
    // Factory Methods - Date Comparison Rules
    // =========================================================================

    public function test_before(): void
    {
        $rule = Rule::before('2025-01-01');

        $this->assertSame('before', $rule->get_name());
        $this->assertSame(['date' => '2025-01-01'], $rule->get_params());
        $this->assertSame('Must be before 2025-01-01', $rule->get_message());
    }

    public function test_before_or_equal(): void
    {
        $rule = Rule::before_or_equal('2025-12-31');

        $this->assertSame('before_or_equal', $rule->get_name());
        $this->assertSame(['date' => '2025-12-31'], $rule->get_params());
        $this->assertSame('Must be on or before 2025-12-31', $rule->get_message());
    }

    public function test_after(): void
    {
        $rule = Rule::after('2020-01-01');

        $this->assertSame('after', $rule->get_name());
        $this->assertSame(['date' => '2020-01-01'], $rule->get_params());
        $this->assertSame('Must be after 2020-01-01', $rule->get_message());
    }

    public function test_after_or_equal(): void
    {
        $rule = Rule::after_or_equal('2020-01-01');

        $this->assertSame('after_or_equal', $rule->get_name());
        $this->assertSame(['date' => '2020-01-01'], $rule->get_params());
        $this->assertSame('Must be on or after 2020-01-01', $rule->get_message());
    }

    // =========================================================================
    // Factory Methods - Database Rules
    // =========================================================================

    public function test_unique(): void
    {
        $rule = Rule::unique('users', 'email');

        $this->assertSame('unique', $rule->get_name());
        $this->assertSame(['table' => 'users', 'column' => 'email'], $rule->get_params());
        $this->assertSame('This value is already taken', $rule->get_message());
    }

    public function test_unique_without_column(): void
    {
        $rule = Rule::unique('users');

        $this->assertSame(['table' => 'users', 'column' => null], $rule->get_params());
    }

    public function test_exists(): void
    {
        $rule = Rule::exists('categories', 'id');

        $this->assertSame('exists', $rule->get_name());
        $this->assertSame(['table' => 'categories', 'column' => 'id'], $rule->get_params());
        $this->assertSame('The selected value is invalid', $rule->get_message());
    }

    // =========================================================================
    // Factory Methods - File Rules
    // =========================================================================

    public function test_file(): void
    {
        $rule = Rule::file();

        $this->assertSame('file', $rule->get_name());
        $this->assertSame([], $rule->get_params());
        $this->assertSame('Must be a file', $rule->get_message());
    }

    public function test_image(): void
    {
        $rule = Rule::image();

        $this->assertSame('image', $rule->get_name());
        $this->assertSame('Must be an image', $rule->get_message());
    }

    public function test_mimes(): void
    {
        $rule = Rule::mimes(['pdf', 'doc', 'docx']);

        $this->assertSame('mimes', $rule->get_name());
        $this->assertSame(['types' => ['pdf', 'doc', 'docx']], $rule->get_params());
        $this->assertSame('File must be of type: pdf, doc, docx', $rule->get_message());
    }

    public function test_max_file_size(): void
    {
        $rule = Rule::max_file_size(2048);

        $this->assertSame('max_file_size', $rule->get_name());
        $this->assertSame(['size' => 2048], $rule->get_params());
        $this->assertSame('File must not exceed 2048KB', $rule->get_message());
    }

    // =========================================================================
    // Custom Rule
    // =========================================================================

    public function test_custom(): void
    {
        $rule = Rule::custom('phone_number', ['format' => 'US'], 'Invalid US phone number');

        $this->assertSame('phone_number', $rule->get_name());
        $this->assertSame(['format' => 'US'], $rule->get_params());
        $this->assertSame('Invalid US phone number', $rule->get_message());
    }

    public function test_custom_with_no_params_and_no_message(): void
    {
        $rule = Rule::custom('my_rule');

        $this->assertSame('my_rule', $rule->get_name());
        $this->assertSame([], $rule->get_params());
        $this->assertNull($rule->get_message());
    }

    // =========================================================================
    // Public Properties
    // =========================================================================

    public function test_public_name_property(): void
    {
        $rule = Rule::email();

        $this->assertSame('email', $rule->name);
    }

    public function test_public_params_property(): void
    {
        $rule = Rule::max_length(100);

        $this->assertSame(['length' => 100], $rule->params);
    }

    public function test_public_message_property(): void
    {
        $rule = Rule::required('Must provide');

        $this->assertSame('Must provide', $rule->message);
    }

    // =========================================================================
    // to_array()
    // =========================================================================

    public function test_to_array_no_params(): void
    {
        $rule = Rule::required();
        $arr = $rule->to_array();

        $this->assertSame([
            'rule' => 'required',
            'params' => [],
            'message' => 'This field is required',
        ], $arr);
    }

    public function test_to_array_with_params(): void
    {
        $rule = Rule::between(1, 100);
        $arr = $rule->to_array();

        $this->assertSame([
            'rule' => 'between',
            'params' => ['min' => 1, 'max' => 100],
            'message' => 'Value must be between 1 and 100',
        ], $arr);
    }

    public function test_to_array_custom_no_message(): void
    {
        $rule = Rule::custom('foo');
        $arr = $rule->to_array();

        $this->assertSame([
            'rule' => 'foo',
            'params' => [],
            'message' => null,
        ], $arr);
    }

    // =========================================================================
    // __toString()
    // =========================================================================

    public function test_to_string_no_params(): void
    {
        $rule = Rule::required();

        $this->assertSame('required', (string)$rule);
    }

    public function test_to_string_single_param(): void
    {
        $rule = Rule::max_length(255);

        $this->assertSame('max_length:255', (string)$rule);
    }

    public function test_to_string_multiple_params(): void
    {
        $rule = Rule::between(1, 10);

        $this->assertSame('between:1,10', (string)$rule);
    }

    public function test_to_string_array_param(): void
    {
        $rule = Rule::in(['a', 'b', 'c']);

        $this->assertSame('in:a,b,c', (string)$rule);
    }

    public function test_to_string_email(): void
    {
        $rule = Rule::email();

        $this->assertSame('email', (string)$rule);
    }

    public function test_to_string_pattern(): void
    {
        $rule = Rule::pattern('/^\d+$/');

        $this->assertSame('pattern:/^\d+$/', (string)$rule);
    }
}
