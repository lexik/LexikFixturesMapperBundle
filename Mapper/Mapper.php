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
class Mapper implements MapperInterface
{
    /**
     * Available callbacks.
     */
    const CALLBACK_ON_MAP_COLUMNS_EXCEPTION = 'onMapColumnsException';
    const CALLBACK_ON_VIOLATIONS            = 'onViolations';
    const CALLBACK_PRE_PERSIST              = 'prePersist';
    const CALLBACK_ON_PERSIST_EXCEPTION     = 'onPersistException';

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
     * @var array
     */
    private $callbacks;

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
        $this->callbacks     = array();
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
    public function mapColumn($index, $value = null)
    {
        $this->mapColumns[$index] = (null !== $value) ? $value : $index;

        return $this;
    }

    /**
     * Set a callback.
     *
     * @param string $name
     * @param mixed $callback
     */
    public function setCallback($name, $callback)
    {
        $this->callbacks[$name] = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($validatorStrategy = self::VALIDATOR_EXCEPTION_ON_VIOLATIONS, $batchSize = false)
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
            try {
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
            } catch (\Exception $e) {
                // callback on exception thrown
                $this->executeCallback(self::CALLBACK_ON_MAP_COLUMNS_EXCEPTION, $data, $object, $e);
            }

            // validate object
            if ($validatorStrategy != self::VALIDATOR_BYPASS) {
                $violations = $this->validator->validate($object, $this->validationGroups);

                if (count($violations) > 0) {
                    $this->executeCallback(self::CALLBACK_ON_VIOLATIONS, $data, $object, $violations);

                    if (self::VALIDATOR_EXCEPTION_ON_VIOLATIONS === $validatorStrategy) {
                        throw new \DomainException(sprintf('Violations detected: %s', $violations->__toString()));
                    } else {
                        unset($object);
                        continue;
                    }
                }
            }

            // customize object before persist
            $this->executeCallback(self::CALLBACK_PRE_PERSIST, $data, $object);

            try {
                // persist object
                $this->entityManager->persist($object);
            } catch (\Exception $e) {
                // callback on exception thrown
                $this->executeCallback(self::CALLBACK_ON_PERSIST_EXCEPTION, $data, $object, $e);
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
