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
     * Throw an exception on violations detection.
     */
    const VALIDATOR_EXCEPTION_ON_VIOLATIONS = 1;

    /**
     * Ignore object and continue the loop on violations detection.
     */
    const VALIDATOR_CONTINUE_ON_VIOLATIONS = 2;

    /**
     * Bypass the entity validation.
     */
    const VALIDATOR_BYPASS = 3;

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
     * @param integer         $validatorStrategy
     * @param integer|boolean $batchSize
     */
    public function persist($validatorStrategy = self::VALIDATOR_EXCEPTION_ON_VIOLATIONS, $batchSize = false);
}
