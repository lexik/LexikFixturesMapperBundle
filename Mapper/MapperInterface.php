<?php

namespace Lexik\Bundle\FixturesMapperBundle\Mapper;

/**
 * Fixtures mapper interface.
 *
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
interface MapperInterface
{
    /**
     * Set entity name.
     *
     * @param string $entityName
     *
     * @return Mapper
     */
    public function setEntityName($entityName);

    /**
     * Set validation groups.
     *
     * @param array $validationGroups
     *
     * @return Mapper
     */
    public function setValidationGroups(array $validationGroups);

    /**
     * Set validation strategy.
     *
     * @param integer $validatorStrategy
     *
     * @return Mapper
     */
    public function setValidatorStrategy($validatorStrategy);

    /**
     * Map a column with a property name or a closure.
     *
     * @param string          $index
     * @param string|\Closure $value
     *
     * @return Mapper
     */
    public function mapColumn($index, $value = null);

    /**
     * Map values to entities and persist them.
     *
     * @param integer|boolean $batchSize
     */
    public function persist($batchSize = false);
}
