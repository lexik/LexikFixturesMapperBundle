<?php

namespace Lexik\Bundle\FixturesMapperBundle\Mapper;

use Doctrine\Common\DataFixtures\AbstractFixture;

use Lexik\Bundle\FixturesMapperBundle\Adapter\EntityManagerAdapterInterface;
use Lexik\Bundle\FixturesMapperBundle\Exception\InvalidMethodException;

use Symfony\Component\Validator\Validator\RecursiveValidator;
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
     * @param Validator                     $validator
     */
    public function __construct(array $values, EntityManagerAdapterInterface $entityManager, RecursiveValidator $validator, $collectionDelimiter)
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
     * Add a callback.
     *
     * @param string $name
     * @param mixed  $callback
     */
    public function addCallback($name, $callback)
    {
        if (!isset($this->callbacks[$name])) {
            $this->callbacks[$name] = array();
        }

        $this->callbacks[$name][] = $callback;

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
                $this->doPersist($data);
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
                } else if (is_array($value) && is_callable($value)) { // array check to prevent functions call whose names would be same as columns (like mail, sort, ...)
                    call_user_func($value, $data[$index], $object, $data);
                } else {
                    $this->setPropertyValue($object, $value, $data[$index]);
                }
            }
        }

        // customize object before persist
        $this->executeCallback(self::CALLBACK_PRE_PERSIST, $data, $object);

        // validate object
        if ($this->validatorStrategy !== self::VALIDATOR_BYPASS) {
            $violations = $this->validator->validate($object, $this->validationGroups);

            if (count($violations) > 0) {
                $this->executeCallback(self::CALLBACK_ON_VIOLATIONS, $data, $object, $violations);

                if (self::VALIDATOR_EXCEPTION_ON_VIOLATIONS === $this->validatorStrategy) {
                    throw new \DomainException(sprintf('Violations detected: %s', $violations->__toString()));
                } else {
                    unset($object);
                    return;
                }
            }
        }

        // persist object
        $this->entityManager->persist($object);
    }

    /**
     * Execute a callback with given arguments.
     */
    protected function executeCallback()
    {
        $arguments = func_get_args();
        $callbackName = $arguments[0];

        if (isset($this->callbacks[$callbackName])) {
            array_shift($arguments); // shift callback name

            foreach ($this->callbacks[$callbackName] as $callback) {
                call_user_func_array($callback, $arguments);
            }
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
            $relatedObject = $this->entityManager->merge($this->fixtures->getReference(trim($value)));
            $method = $this->getPropertyMethod($property, $object);
            $object->$method($relatedObject);

        } elseif ($this->entityManager->isCollectionAssociation(get_class($object), $property)) {
            if (is_string($value)) {
                $value = explode($this->collectionDelimiter, $value);
            }

            $method = $this->getPropertyMethod($property, $object, false);
            foreach ($value as $elmt) {
                $relatedObject = $this->entityManager->merge($this->fixtures->getReference(trim($elmt)));
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
        if ($single) {
            $method = sprintf('set%s', Container::camelize($property));
        } else {
            $method = sprintf('add%s', Container::camelize($property));

            if (substr($method, -1) == "s") {
                $method = substr($method, 0, -1);
            }
        }

        if ( ! method_exists($object, $method)) {
            throw new InvalidMethodException(sprintf('The method "%s" does not exists in class "%s".', $method, get_class($object)));
        }

        return $method;
    }
}
