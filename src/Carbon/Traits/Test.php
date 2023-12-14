<?php

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Carbon\Traits;

use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use Carbon\Factory;
use Carbon\FactoryImmutable;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;

trait Test
{
    ///////////////////////////////////////////////////////////////////
    ///////////////////////// TESTING AIDS ////////////////////////////
    ///////////////////////////////////////////////////////////////////

    /**
     * Set a Carbon instance (real or mock) to be returned when a "now"
     * instance is created.  The provided instance will be returned
     * specifically under the following conditions:
     *   - A call to the static now() method, ex. Carbon::now()
     *   - When a null (or blank string) is passed to the constructor or parse(), ex. new Carbon(null)
     *   - When the string "now" is passed to the constructor or parse(), ex. new Carbon('now')
     *   - When a string containing the desired time is passed to Carbon::parse().
     *
     * Note the timezone parameter was left out of the examples above and
     * has no affect as the mock value will be returned regardless of its value.
     *
     * Only the moment is mocked with setTestNow(), the timezone will still be the one passed
     * as parameter of date_default_timezone_get() as a fallback (see setTestNowAndTimezone()).
     *
     * To clear the test instance call this method using the default
     * parameter of null.
     *
     * /!\ Use this method for unit tests only.
     *
     * @param DateTimeInterface|Closure|static|string|false|null $testNow real or mock Carbon instance
     */
    public static function setTestNow(mixed $testNow = null): void
    {
        FactoryImmutable::getDefaultInstance()->setTestNow($testNow);
    }

    /**
     * Set a Carbon instance (real or mock) to be returned when a "now"
     * instance is created.  The provided instance will be returned
     * specifically under the following conditions:
     *   - A call to the static now() method, ex. Carbon::now()
     *   - When a null (or blank string) is passed to the constructor or parse(), ex. new Carbon(null)
     *   - When the string "now" is passed to the constructor or parse(), ex. new Carbon('now')
     *   - When a string containing the desired time is passed to Carbon::parse().
     *
     * It will also align default timezone (e.g. call date_default_timezone_set()) with
     * the second argument or if null, with the timezone of the given date object.
     *
     * To clear the test instance call this method using the default
     * parameter of null.
     *
     * /!\ Use this method for unit tests only.
     *
     * @param DateTimeInterface|Closure|static|string|false|null $testNow real or mock Carbon instance
     */
    public static function setTestNowAndTimezone($testNow = null, $tz = null): void
    {
        FactoryImmutable::getDefaultInstance()->setTestNowAndTimezone($testNow, $tz);
    }

    /**
     * Temporarily sets a static date to be used within the callback.
     * Using setTestNow to set the date, executing the callback, then
     * clearing the test instance.
     *
     * /!\ Use this method for unit tests only.
     *
     * @template T
     *
     * @param DateTimeInterface|Closure|static|string|false|null $testNow  real or mock Carbon instance
     * @param Closure(): T                                       $callback
     *
     * @return T
     */
    public static function withTestNow(mixed $testNow, callable $callback): mixed
    {
        return FactoryImmutable::getDefaultInstance()->withTestNow($testNow, $callback);
    }

    /**
     * Get the Carbon instance (real or mock) to be returned when a "now"
     * instance is created.
     *
     * @return Closure|CarbonInterface|null the current instance used for testing
     */
    public static function getTestNow(): Closure|CarbonInterface|null
    {
        return FactoryImmutable::getDefaultInstance()->getTestNow();
    }

    /**
     * Determine if there is a valid test instance set. A valid test instance
     * is anything that is not null.
     *
     * @return bool true if there is a test instance, otherwise false
     */
    public static function hasTestNow(): bool
    {
        return FactoryImmutable::getDefaultInstance()->hasTestNow();
    }

    /**
     * Get the mocked date passed in setTestNow() and if it's a Closure, execute it.
     *
     * @param string|\DateTimeZone $tz
     *
     * @return \Carbon\CarbonImmutable|\Carbon\Carbon|null
     */
    protected static function getMockedTestNow($tz): CarbonInterface|self|null
    {
        $testNow = static::getTestNow();

        if ($testNow instanceof Closure) {
            $realNow = new DateTimeImmutable('now');
            $testNow = $testNow(static::parse(
                $realNow->format('Y-m-d H:i:s.u'),
                $tz ?: $realNow->getTimezone(),
            ));
        }
        /* @var \Carbon\CarbonImmutable|\Carbon\Carbon|null $testNow */

        return $testNow instanceof CarbonInterface
            ? $testNow->avoidMutation()->tz($tz)
            : $testNow;
    }

    private function mockConstructorParameters(&$time, ?CarbonTimeZone $tz): void
    {
        $now = $this->clock instanceof Factory
            ? $this->clock->getTestNow()
            : $this->nowFromClock($tz);
        $testInstance = $now ?? clone static::getMockedTestNow($tz);

        if (static::hasRelativeKeywords($time)) {
            $testInstance = $testInstance->modify($time);
        }

        $time = $testInstance instanceof self
            ? $testInstance->rawFormat(static::MOCK_DATETIME_FORMAT)
            : $testInstance->format(static::MOCK_DATETIME_FORMAT);
    }

    private function nowFromClock(?CarbonTimeZone $tz): ?DateTimeImmutable
    {
        $now = $this->clock?->now();

        return $now && $tz ? $now->setTimezone($tz) : null;
    }
}
