<?php

namespace Lexik\Bundle\FixturesMapperBundle\Loader;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Validator;

use Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper;

/**
 * Base loader class for fixtures.
 *
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
abstract class AbstractLoader
{
    /**
     * @var array
     */
    private $adapters;

    /**
     * @var Lexik\Bundle\FixturesMapperBundle\Adapter\EntityManagerAdapterInterface
     */
    private $emAdapter;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * Constructor.
     *
     * @param EntityManager $entityManager
     * @param Validator     $validator
     */
    public function __construct($entityManager, array $adapters, Validator $validator)
    {
        $this->adapters  = $adapters;
        $this->validator = $validator;

        $this->initializeAdapter($entityManager);
    }

    /**
     * Initialize the adapter according to the given entity manager.
     *
     * @param Object $entityManager
     * @throws \Exception
     */
    private function initializeAdapter($entityManager)
    {
        $adapterClass = null;
        $keys = array_keys($this->adapters);
        $i = 0;

        while ($i<count($keys) && null == $adapterClass) {
            $name = $keys[$i];
            if ($entityManager instanceof $this->adapters[$name]['manager']) {
                $adapterClass = $this->adapters[$name]['adapter'];
            }
            $i++;
        }

        if (null == $adapterClass) {
            throw new \Exception(sprintf('Can\'t find any adapter for "%s"', get_class($entityManager)));
        } else {
            $this->emAdapter = new $adapterClass($entityManager);
        }
    }

    /**
     * Load datas from the given path.
     *
     * @param string $path
     * @param array $options
     *
     * @return array
     */
    abstract protected function loadData($path, array $options = array());

    /**
     * Load datas and create a new data mapper.
     *
     * @param string $path
     * @param array $options
     *
     * @return Mapper
     */
    public function load($path, array $options = array())
    {
        $values = $this->loadData($path, $options);

        return new Mapper($values, $this->emAdapter, $this->validator);
    }
}
