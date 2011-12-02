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
     * Merge an entity with existings data.
     *
     * @param Object $object
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
}