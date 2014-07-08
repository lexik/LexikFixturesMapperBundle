<?php

namespace Lexik\Bundle\FixturesMapperBundle\Mapper;

use Doctrine\Common\DataFixtures\AbstractFixture;

use Lexik\Bundle\FixturesMapperBundle\Adapter\EntityManagerAdapterInterface;
use Lexik\Bundle\FixturesMapperBundle\Exception\InvalidMethodException;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Fixtures mapper.
 *
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class Mapper implements MapperInterface
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
     * Available callbacks.
     */
    const CALLBACK_ON_EXCEPTION  = 'onException';
    const CALLBACK_ON_VIOLATIONS = 'onViolations';
    const CALLBACK_PRE_PERSIST   = 'prePersist';

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
     * @var string
     */
    private $validatorStrategy;

    /**
     * @var array
     */
    private $callbacks;

    /**
     * @var Doctrine\Common\DataFixtures\AbstractFixture
     */
    private $fixtures;

    /**
     * @var string
     */
    private $collectionDelimiter;

    /**
     * Constructor.
     *
     * @param array                         $values
     * @param EntityManagerAdapterInterface $entityManager
     * @param ValidatorInterface                     $validator
     */
    public function __construct(array $values, EntityManagerAdapterInterface $entityManager, ValidatorInterface $validator, $collectionDelimiter)
    {
        $this->values            = $values;
        $this->entityManager     = $entityManager;
        $this->validator         = $validator;
        $this->callbacks         = array();
        $this->validatorStrategy = self::VALIDATOR_EXCEPTION_ON_VIOLATIONS;
        $this->collectionDelimiter = $collectionDelimiter;
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
     * {@inheritdoc}
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Set the reference repository.
     *
     * @param ReferenceRepository $repository
     */
    public function setLoadData(AbstractFixture $fixtures)
    {
        $this->fixtures = $fixtures;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setValidationGroups(array $validationGroups)
    {
        $this->validationGroups = $validationGroups;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setValidatorStrategy($validatorStrategy)
    {
        $this->validatorStrategy = $validatorStrategy;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mapColumn($index, $value = null)
    {
        $this->mapColumns[$index] = (null !== $value) ? $value : $index;

        return $this;
    }

    /**
     * Set a callback.
     *
     * @param string $name
     * @param mixed  $callback
     */
    public function setCallback($name, $callback)
    {
        $this->callbacks[$name] = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($batchSize = false)
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

            try {
                $object = $this->doPersist($data);
            } catch (InvalidMethodException $e) {
                throw $e;
            } catch (\Exception $e) {
                if (self::VALIDATOR_EXCEPTION_ON_VIOLATIONS === $this->validatorStrategy) {
                    throw $e;
                } else {
                    // callback on exception thrown
                    $this->executeCallback(self::CALLBACK_ON_EXCEPTION, $this->entityName, $data, $e);
                    continue;
                }
            }

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
     * Persist one object.
     *
     * @param  array            $data
     * @throws \DomainException
     */
    protected function doPersist($data)
    {
        $object = new $this->entityName;

        // map columns
        foreach ($this->mapColumns as $index => $value) {
            if (isset($data[$index])) {
                if ($value instanceof \Closure) {
                    $value($data[$index], $object, $data);
                } elseif (is_array($value) && is_callable($value)) { // array check to prevent functions call whose names would be same as columns (like mail, sort, ...)
                    call_user_func($value, $data[$index], $object, $data);
                } else {
                    $this->setPropertyValue($object, $value, $data[$index]);
                }
            }
        }

        // validate object
        if ($this->validatorStrategy !== self::VALIDATOR_BYPASS) {
            $violations = $this->validator->validate($object, $this->validationGroups);

            if (count($violations) > 0) {
                $this->executeCallback(self::CALLBACK_ON_VIOLATIONS, $data, $object, $violations);

                if (self::VALIDATOR_EXCEPTION_ON_VIOLATIONS === $this->validatorStrategy) {
                    throw new \DomainException(sprintf('Violations detected: %s', $violations->__toString()));
                } else {
                    unset($object);
                    continue;
                }
            }
        }

        // customize object before persist
        $this->executeCallback(self::CALLBACK_PRE_PERSIST, $data, $object);

        // persist object
        $this->entityManager->persist($object);

        return $object;
    }

    /**
     * Execute a callback with given arguments.
     */
    protected function executeCallback()
    {
        $arguments = func_get_args();
        $callbackName = $arguments[0];

        if (isset($this->callbacks[$callbackName])) {
            $callback = $this->callbacks[$callbackName];
            array_shift($arguments); // shift callback name
            call_user_func_array($callback, $arguments);
        }
    }

    /**
     * Set the value for the given property.
     *
     * @param object $object
     * @param string $property
     * @param mixed  $value
     */
    protected function setPropertyValue($object, $property, $value)
    {
        if ($this->entityManager->isSingleAssociation(get_class($object), $property)) {
            $relatedObject = $this->entityManager->merge($this->fixtures->getReference($value));
            $method = $this->getPropertyMethod($property, $object);
            $object->$method($relatedObject);

        } elseif ($this->entityManager->isCollectionAssociation(get_class($object), $property)) {
            if (is_string($value)) {
                $value = explode($this->collectionDelimiter, $value);
            }

            $method = $this->getPropertyMethod($property, $object, false);
            foreach ($value as $elmt) {
                $relatedObject = $this->entityManager->merge($this->fixtures->getReference($elmt));
                $object->$method($relatedObject);
            }

        } else {
            $method = $this->getPropertyMethod($property, $object);
            $object->$method($value);
        }
    }

    /**
     * Get the method name of a given property.
     *
     * @param string  $name
     * @param object  $object
     * @param boolean $single
     *
     * @throws InvalidMethodException
     *
     * @return string
     */
    private function getPropertyMethod($property, $object, $single = true)
    {
        $methodPattern = $single ? 'set%s' : 'add%s';
        $method = sprintf($methodPattern, Container::camelize($property));

        if ( ! method_exists($object, $method)) {
            throw new InvalidMethodException(sprintf('The method "%s" does not exists', $method));
        }

        return $method;
    }
}
