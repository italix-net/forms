<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix Forms — xUnit-style test classes on the shared runner
 *
 * Eight of this library's suites were written as PHPUnit classes, which
 * `ix test` cannot drive. This is the little of PHPUnit they used, so they
 * run as they are written: every `test_*` method on a fresh instance, one
 * `Italix\Testing\test()` each, stopped at its first failed assertion, as
 * PHPUnit stops it. A method that asserts nothing and expects no exception
 * fails — a port that dropped its assertions must not pass in silence.
 *
 * Not a suite itself: SuiteFinder only runs files named `*Test.php`.
 */

declare(strict_types=1);

namespace Italix\Forms\Tests;

use Italix\Forms\FieldMeta;
use Italix\Forms\Rendering\WidgetInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function Italix\Testing\{section, suite, summary, test};

require_once __DIR__ . '/AssertionFailed.php';

abstract class XunitCase
{
    /** Assertions made across every method of the run — printed on STDERR when IX_XUNIT_COUNT is set ('methods': each one's too). */
    private static int $assertions_n = 0;

    private int $own_assertions_n = 0;

    /** @var class-string<Throwable>|null */
    private ?string $expected_exception_c = null;

    private ?string $expected_message = null;

    private ?string $expected_message_pattern = null;

    /** Final, so run_all()'s `new static()` cannot meet a constructor that wants arguments. */
    final public function __construct()
    {
    }

    /**
     * Run every `test_*` method of the class, in declaration order, and return the process exit code.
     */
    final public static function run_all(string $suite_title): int
    {
        suite($suite_title);
        section(static::class);

        $methods = array_filter(
            (new ReflectionClass(static::class))->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn (ReflectionMethod $m): bool => str_starts_with($m->getName(), 'test_') && !$m->isStatic()
        );

        foreach ($methods as $method) {
            $case = new static();
            [$ok, $details] = $case->run_one($method->getName());

            if (getenv('IX_XUNIT_COUNT') === 'methods') {
                fwrite(STDERR, "{$method->getName()} {$case->own_assertions_n}\n");
            }
            test(str_replace('_', ' ', substr($method->getName(), 5)), $ok, $details);
        }

        if (getenv('IX_XUNIT_COUNT') !== false) {
            fwrite(STDERR, sprintf("%s: %d methods, %d assertions\n", static::class, count($methods), self::$assertions_n));
        }

        return summary();
    }

    /** @return array{0: bool, 1: string} */
    private function run_one(string $method): array
    {
        try {
            $this->{$method}();
        } catch (AssertionFailed $e) {
            return [false, $e->getMessage()];
        } catch (Throwable $e) {
            return $this->judge_thrown($e);
        }

        if ($this->expected_exception_c !== null) {
            return [false, "expected {$this->expected_exception_c}, nothing was thrown"];
        }

        if ($this->own_assertions_n === 0) {
            return [false, 'no assertion was made'];
        }

        return [true, ''];
    }

    /** @return array{0: bool, 1: string} */
    private function judge_thrown(Throwable $e): array
    {
        $thrown = get_class($e) . ': ' . $e->getMessage();

        if ($this->expected_exception_c === null || !$e instanceof $this->expected_exception_c) {
            return [false, "unexpected {$thrown}"];
        }

        if ($this->expected_message !== null && !str_contains($e->getMessage(), $this->expected_message)) {
            return [false, "message should contain '{$this->expected_message}', was {$thrown}"];
        }

        if ($this->expected_message_pattern !== null && preg_match($this->expected_message_pattern, $e->getMessage()) !== 1) {
            return [false, "message should match {$this->expected_message_pattern}, was {$thrown}"];
        }

        return [true, ''];
    }

    // -------------------------------------------------------------------------
    // Expectations

    /** @param class-string<Throwable> $exception_c */
    protected function expect_exception(string $exception_c): void
    {
        $this->expected_exception_c = $exception_c;
    }

    protected function expect_exception_message(string $message): void
    {
        $this->expected_message = $message;
    }

    protected function expect_exception_message_matches(string $pattern): void
    {
        $this->expected_message_pattern = $pattern;
    }

    // -------------------------------------------------------------------------
    // Assertions — PHPUnit's semantics for the ones these suites use

    protected function assert_same(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->check($expected === $actual, $message, fn (): string => 'expected ' . self::show($expected) . ', got ' . self::show($actual));
    }

    protected function assert_true(mixed $actual, string $message = ''): void
    {
        $this->check($actual === true, $message, fn (): string => 'expected true, got ' . self::show($actual));
    }

    protected function assert_false(mixed $actual, string $message = ''): void
    {
        $this->check($actual === false, $message, fn (): string => 'expected false, got ' . self::show($actual));
    }

    protected function assert_null(mixed $actual, string $message = ''): void
    {
        $this->check($actual === null, $message, fn (): string => 'expected null, got ' . self::show($actual));
    }

    protected function assert_not_null(mixed $actual, string $message = ''): void
    {
        $this->check($actual !== null, $message, fn (): string => 'expected anything but null');
    }

    protected function assert_contains_string(string $needle, string $haystack, string $message = ''): void
    {
        $this->check(str_contains($haystack, $needle), $message, fn (): string => self::show($needle) . ' not found in ' . self::show($haystack));
    }

    protected function assert_not_contains_string(string $needle, string $haystack, string $message = ''): void
    {
        $this->check(!str_contains($haystack, $needle), $message, fn (): string => self::show($needle) . ' found in ' . self::show($haystack));
    }

    protected function assert_instance_of(string $class_c, mixed $actual, string $message = ''): void
    {
        $this->check($actual instanceof $class_c, $message, fn (): string => "expected an instance of {$class_c}, got " . get_debug_type($actual));
    }

    protected function assert_not_instance_of(string $class_c, mixed $actual, string $message = ''): void
    {
        $this->check(!$actual instanceof $class_c, $message, fn (): string => "expected anything but an instance of {$class_c}");
    }

    protected function assert_has_key(int|string $key, array $array, string $message = ''): void
    {
        $this->check(array_key_exists($key, $array), $message, fn (): string => 'no key ' . self::show($key) . ' in ' . self::show(array_keys($array)));
    }

    protected function assert_not_has_key(int|string $key, array $array, string $message = ''): void
    {
        $this->check(!array_key_exists($key, $array), $message, fn (): string => 'unexpected key ' . self::show($key));
    }

    /** Strict, as PHPUnit's assertContains() is for an iterable since 9. */
    protected function assert_in(mixed $needle, iterable $haystack, string $message = ''): void
    {
        $this->check(in_array($needle, self::as_array($haystack), true), $message, fn (): string => self::show($needle) . ' not in ' . self::show(self::as_array($haystack)));
    }

    protected function assert_not_in(mixed $needle, iterable $haystack, string $message = ''): void
    {
        $this->check(!in_array($needle, self::as_array($haystack), true), $message, fn (): string => self::show($needle) . ' is in ' . self::show(self::as_array($haystack)));
    }

    protected function assert_count(int $expected_n, \Countable|iterable $actual, string $message = ''): void
    {
        $actual_n = is_countable($actual) ? count($actual) : count(self::as_array($actual));
        $this->check($actual_n === $expected_n, $message, fn (): string => "expected {$expected_n} elements, got {$actual_n}");
    }

    protected function assert_is_array(mixed $actual, string $message = ''): void
    {
        $this->check(is_array($actual), $message, fn (): string => 'expected an array, got ' . get_debug_type($actual));
    }

    protected function assert_empty(mixed $actual, string $message = ''): void
    {
        $this->check(empty($actual), $message, fn (): string => 'expected empty, got ' . self::show($actual));
    }

    protected function assert_at_least(int|float $minimum, int|float $actual, string $message = ''): void
    {
        $this->check($actual >= $minimum, $message, fn (): string => "expected at least {$minimum}, got {$actual}");
    }

    // -------------------------------------------------------------------------
    // Doubles

    /** A widget with no behaviour, for the cases that only need one by identity. */
    protected function stub_widget(): WidgetInterface
    {
        return new class () implements WidgetInterface {
            public function render_edit(FieldMeta $field, $value, array $attrs = []): string
            {
                return '';
            }

            public function render_view(FieldMeta $field, $value, array $attrs = []): string
            {
                return '';
            }
        };
    }

    // -------------------------------------------------------------------------

    /** @param callable(): string $describe */
    private function check(bool $passed, string $message, callable $describe): void
    {
        self::$assertions_n++;
        $this->own_assertions_n++;

        if (!$passed) {
            throw new AssertionFailed(($message !== '' ? $message . ' — ' : '') . $describe());
        }
    }

    private static function as_array(iterable $values): array
    {
        return is_array($values) ? $values : iterator_to_array($values, false);
    }

    private static function show(mixed $value): string
    {
        $shown = match (true) {
            is_string($value) => '"' . $value . '"',
            is_object($value) => get_class($value) . '#' . spl_object_id($value),
            is_array($value)  => (string) json_encode($value, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            default           => var_export($value, true),
        };

        return strlen($shown) > 200 ? substr($shown, 0, 200) . '…' : $shown;
    }
}
