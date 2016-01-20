<?php

namespace Devio\Propertier;

use RuntimeException;

class Resolver
{
    /**
     * All of the registered properties.
     *
     * @var array
     */
    protected static $properties = [];

    /**
     * Register the properties.
     *
     * @param $properties
     */
    public static function register($properties)
    {
        static::$properties = $properties;
    }

    /**
     * Get the right value type class based on the property provided.
     *
     * @param       $property
     * @param array $attributes
     * @param bool $exists
     * @return PropertyValue
     */
    public function value($property, $attributes = [], $exists = false)
    {
        $class = $this->getClassName($property);

        if (! class_exists($class)) {
            throw new RuntimeException("Property class {$class} not found");
        }

        return $class::createInstanceFrom($property, $attributes, $exists);
    }

    /**
     * Resolves the property classpath.
     *
     * @param $property
     * @return PropertyAbstract
     * @throws UnresolvedPropertyException
     */
    protected function getClassName($property)
    {
        $type = $this->getPropertyType($property);

        if (is_null($type) || ! isset(static::$properties[$type])) {
            throw new RuntimeException('Error when resolving unregisterd property type');
        }

        return static::$properties[$type];
    }

    /**
     * Get the type of a property.
     *
     * @param $property
     * @return mixed
     * @throws UnresolvedPropertyException
     */
    protected function getPropertyType($property)
    {
        if (is_string($property)) {
            return $property;
        } elseif ($property instanceof Property) {
            return $property->getAttribute('type');
        }

        return null;
    }
}
