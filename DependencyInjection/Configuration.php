<?php

namespace Lexik\Bundle\FixturesMapperBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('lexik_fixtures_mapper');

        $defaultAdapters = array(
            'doctrine_orm' => array(
                'manager' => 'Doctrine\ORM\EntityManager',
                'adapter' => 'Lexik\Bundle\FixturesMapperBundle\Adapter\DoctrineORMAdapter',
            ),
        );

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()

                ->arrayNode('mapper')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_class')
                            ->cannotBeEmpty()
                            ->defaultValue('Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper')
                         ->end()
                    ->end()
                ->end()

                ->arrayNode('loader')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_class')
                            ->cannotBeEmpty()
                            ->defaultValue('Lexik\Bundle\FixturesMapperBundle\Loader\AbstractLoader')
                         ->end()
                         ->scalarNode('csv_class')
                             ->cannotBeEmpty()
                             ->defaultValue('Lexik\Bundle\FixturesMapperBundle\Loader\CsvLoader')
                         ->end()
                         ->scalarNode('yaml_class')
                             ->cannotBeEmpty()
                             ->defaultValue('Lexik\Bundle\FixturesMapperBundle\Loader\YamlLoader')
                         ->end()
                    ->end()
                ->end()

                ->arrayNode('adapter')
                    ->addDefaultsIfNotSet()
                    ->defaultValue($defaultAdapters)
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('manager')->defaultNull()->isRequired()->end()
                            ->scalarNode('adapter')->defaultNull()->isRequired()->end()
                        ->end()
                    ->end()
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function($values) use($defaultAdapters) {
                            return array_merge($defaultAdapters, $values);
                        })
                    ->end()
                ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}
