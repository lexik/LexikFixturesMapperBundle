<?php

namespace Lexik\Bundle\FixturesMapperBundle\Mapper;

use Lexik\Bundle\FixturesMapperBundle\Adapter\EntityManagerAdapterInterface;

use Symfony\Component\Validator\Validator;
use Symfony\Component\DependencyInjection\Container;

/**
 * Fixtures mapper.
 *
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class Mapper
{
    /**
     * Throw an exception on violations detection.
     */
    const EXCEPTION_ON_VALIDATOR_VIOLATIONS = 1;

    /**
     * Ignore object and continue the loop on violations detection.
     */
    const CONTINUE_ON_VALIDATOR_VIOLATIONS  = 2;

    /**
     * @var array
     */
    private $values;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var array
     */
    private $mapColumns = array();

    /**
     * @var EntityManagerAdapterInterface
     */
    private $entityManager;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var null|array
     */
    private $validationGroups;

    /**
     * Constructor.
     *
     * @param array                         $values
     * @param EntityManagerAdapterInterface $entityManager
     * @param Validator                     $validator
     */
    public function __construct(array $values, EntityManagerAdapterInterface $entityManager, Validator $validator)
    {
        $this->values        = $values;
        $this->entityManager = $entityManager;
        $this->validator     = $validator;
    }

    /**
     * Get values.
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Set entity name.
     *
     * @param string $entityName
     *
     * @return Mapper
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Set validation groups.
     *
     * @param array $validationGroups
     *
     * @return Mapper
     */
    public function setValidationGroups(array $validationGroups)
    {
        $this->validationGroups = $validationGroups;

        return $this;
    }

    /**
     * Map a column with a property name or a closure.
     *
     * @param string          $index
     * @param string|\Closure $value
     *
     * @return Mapper
     */
    public function mapColumn($index, $value = null)
    {
        $this->mapColumns[$index] = (null !== $value) ? $value : $index;

        return $this;
    }

    /**
     * Map values to entities and persist them.
     *
     * @param integer         $validatorStrategy
     * @param integer|boolean $batchSize
     * @param \Closure        $callback
     */
    public function persist($validatorStrategy = self::EXCEPTION_ON_VALIDATOR_VIOLATIONS, $batchSize = false, \Closure $callback = null)
    {
        if (null === $this->entityName) {
            throw new \InvalidArgumentException('You must provide an entity name');
        }

        if ( ! class_exists($this->entityName)) {
            throw new \InvalidArgumentException(sprintf('You must provide a valid entity name, "%s" does not exists', $this->entityName));
        }

        $row = 0;
        foreach ($this->values as $reference => $data) {
            $row++;

            $object = new $this->entityName;

            // map columns
            foreach ($this->mapColumns as $index => $value) {
                if (isset($data[$index])) {
                    if ($value instanceof \Closure) {
                        $value($data[$index], $object, $data);
                    } else {
                        $setter = $this->getPropertySetter($value, $object);
                        $object->$setter($data[$index]);
                    }
                }
            }

            // validate object
            $violations = $this->validator->validate($object, $this->validationGroups);
            if (count($violations) > 0) {
                if (self::EXCEPTION_ON_VALIDATOR_VIOLATIONS === $validatorStrategy) {
                    throw new \DomainException(sprintf('Violations detected: %s', $violations->__toString()));
                } else {
                    unset($object);
                    continue;
                }
            }

            // customize object before persist
            if ($callback instanceof \Closure) {
                $callback($data, $object);
            }

            // persist object
            $this->entityManager->persist($object);

            // batch processing
            if (false !== $batchSize && $row % $batchSize == 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Detaches all objects from Doctrine!
            }

            unset($object);
        }

        $this->entityManager->flush();
        $this->entityManager->clear(); // Detaches all objects from Doctrine!
    }

    /**
     * Get setter name of a given property.
     *
     * @param string $name
     * @param object $object
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function getPropertySetter($property, $object)
    {
        $setter = sprintf('set%s', Container::camelize($property));
        if ( ! method_exists($object, $setter)) {
            throw new \InvalidArgumentException(sprintf('The method "%s" does not exists', $setter));
        }

        return $setter;
    }
}
