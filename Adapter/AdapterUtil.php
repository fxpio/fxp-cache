<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Cache\Adapter;

/**
 * Adapter utils.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AdapterUtil
{
    /**
     * Set the value of private property.
     *
     * @param object $object   The object
     * @param string $property The property name
     * @param mixed  $value    The value
     *
     * @throws
     */
    public static function setPropertyValue($object, string $property, $value): void
    {
        $ref = new \ReflectionClass($object);
        $prop = static::getPrivateProperty($ref, $property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
        $prop->setAccessible(false);
    }

    /**
     * Get the value of private property.
     *
     * @param object $object   The object
     * @param string $property The property name
     *
     * @throws
     *
     * @return mixed
     */
    public static function getPropertyValue($object, string $property)
    {
        $ref = new \ReflectionClass($object);
        $prop = static::getPrivateProperty($ref, $property);
        $prop->setAccessible(true);
        $value = $prop->getValue($object);
        $prop->setAccessible(false);

        return $value;
    }

    /**
     * Get the private property.
     *
     * @param \ReflectionClass $reflectionClass The reflection class
     * @param string           $property        The property name
     *
     * @throws
     *
     * @return \ReflectionProperty
     */
    public static function getPrivateProperty(\ReflectionClass $reflectionClass, string $property): \ReflectionProperty
    {
        if (!$reflectionClass->hasProperty($property) && $reflectionClass->getParentClass()) {
            return static::getPrivateProperty($reflectionClass->getParentClass(), $property);
        }

        return $reflectionClass->getProperty($property);
    }
}
