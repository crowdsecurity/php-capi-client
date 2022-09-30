<?php
/**
 * Some helpers for Unit test.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */

namespace CrowdSec\CapiClient\Tests;

use ReflectionClass;

class PHPUnitUtil
{
    public static function callMethod($obj, $name, array $args)
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    public static function getPHPUnitVersion(): string
    {
        if (class_exists('\PHPUnit\Runner\Version')) {
            return \PHPUnit\Runner\Version::id();
        } else {
            return \PHPUnit_Runner_Version::id();
        }
    }

    public static function assertRegExp($testCase, $pattern, $string, $message = '')
    {
        if (version_compare(self::getPHPUnitVersion(), '9.0', '>=')) {
            $testCase->assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            $testCase->assertRegExp($pattern, $string, $message);
        }
    }
}
