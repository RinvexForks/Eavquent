<?php

namespace Devio\Propertier;

use Devio\Propertier\Relations\MorphManyValues;
use Devio\Propertier\Relations\HasManyProperties;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Propertier
{
    /**
     * @var array
     */
    public static $modelColumns = [];

    /**
     * Relationship to the properties table.
     *
     * @return HasManyProperties
     */
    public function properties()
    {
        // We are using a self coded relation as there is no foreign key into
        // the properties table. The entity name will be used as a foreign
        // key to find the properties which belong to this entity item.
        return new HasManyProperties(
            (new Property)->newQuery(), $this, $this->getMorphClass()
        );
    }

    /**
     * Polimorphic relationship to the values table.
     *
     * @return MorphMany
     */
    public function values()
    {
        $this->addHidden('values');

        $instance = new Value;
        $table = $instance->getTable();
        list($type, $id) = $this->getMorphs('entity', null, null);
        // We will add the values relationship to the list of hidden attributes
        // as do not want it to be included into any array or json conversion.
        // We will now just create our custom relation for fetching values.

        return new MorphManyValues(
            $instance->newQuery(), $this, "{$table}.{$type}", "{$table}.{$id}", $this->getKeyName()
        );
    }

    /**
     * Will check if the key exists as registerd property.
     *
     * @param $key
     * @return bool
     */
    public function isProperty($key)
    {
        // Checking if the key corresponds to any comlumn in the main entity
        // table. If there is a match, means the key is an existing model
        // attribute which value will be always taken before property.
        if (in_array($key, $this->getModelColumns())) {
            return false;
        }
        // $key will be property when it does not belong to any relationship
        // name and it also exists into the entity properties collection.
        // This way it won't interfiere with the base model behaviour.
        return is_null($this->getRelationValue($key))
            ? ! is_null($this->getProperty($key))
            : false;
    }

    /**
     * Find a property object by name.
     *
     * @param $key
     * @return mixed
     */
    public function getProperty($key)
    {
        $properties = $this->getPropertiesRelation();
        // We will key our collection by name, this way will be much easier for
        // filtering. Once keyed, just checking if the property has a key of
        // the name passed as argument will mean that a property exists.
        $keyed = $properties->keyBy('name');

        return $keyed->get($key, null);
    }

    /**
     * Will get the values of a property.
     *
     * @param  $key Property name
     * @return mixed
     */
    public function getPropertyValue($key)
    {
        $property = $this->getProperty($key);
        // We will first grab the property object which contains a collection of
        // values linked to it. It will work even when setting elements that
        // are no yet persisted as they will be set into the relationship.
        return $property->values;
    }

    /**
     * Get the properties relation.
     *
     * @return mixed
     */
    public function getPropertiesRelation()
    {
        // This way we can easily swap the name of the relation if needed just
        // in case this relationship name is being used by the parent model
        // as an attribute, relationship or other thing that could crash.
        return $this->getRelationValue('properties');
    }

    /**
     * Get the base model attribute names.
     *
     * @return array
     */
    public function getModelColumns()
    {
        if (empty(static::$modelColumns)) {
            static::$modelColumns = $this->fetchModelColumns();
        }
        // If no attributes are listed into $modelColumns property, we will
        // fetch them from database. This could result into a performance
        // issue so it should be set manually or when booting the model.
        return static::$modelColumns;
    }

    /**
     * Get the model column names.
     *
     * @return mixed
     */
    public function fetchModelColumns()
    {
        return $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable());
    }

    /**
     * Overriding magic method.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->isProperty($key)) {
            return $this->getPropertyValue($key);
        }

        return parent::__get($key);
    }
}
