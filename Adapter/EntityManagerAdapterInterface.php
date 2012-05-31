<?php

namespace Lexik\Bundle\FixturesMapperBundle\Adapter;

/**
 * Define methods to implements for entity manager adapters.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface EntityManagerAdapterInterface
{
    /**
     * Persist an entity.
     *
     * @param Object $object
     */
    public function persist($object);

    /**
     * Remove an entity.
     *
     * @param Object $object
     */
    public function remove($object);

    /**
     * Merge an entity with existings data and returns the merged object
     *
     * @param Object $object
     * @return Object
     */
    public function merge($object);

    /**
     * Detach an entity from the manager.
     *
     * @param Object $object
     */
    public function detach($object);

    /**
     * Refresh an entity with content from the database.
     *
     * @param Object $object
     */
    public function refresh($object);

    /**
     * Flush changes.
     */
    public function flush();

    /**
     * Clear all mapped entity.
     */
    public function clear();

    /**
     * Return true is the property is a single column association
     *
     * @param string $className
     * @param string $fieldName
     * @return boolean
     */
    public function isSingleAssociation($className, $fieldName);

    /**
     * Return true is the property is acollection association
     *
     * @param string $className
     * @param string $fieldName
     * @return boolean
     */
    public function isCollectionAssociation($className, $fieldName);
}