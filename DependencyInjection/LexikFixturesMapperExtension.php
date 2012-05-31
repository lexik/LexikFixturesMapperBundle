<?php

namespace Lexik\Bundle\FixturesMapperBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class LexikFixturesMapperExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('lexik_fixtures_mapper.mapper.base.class', $config['mapper']['base_class']);
        $container->setParameter('lexik_fixtures_mapper.mapper.collection_delimiter', $config['mapper']['collection_delimiter']);
        $container->setParameter('lexik_fixtures_mapper.loader.base.class', $config['loader']['base_class']);
        $container->setParameter('lexik_fixtures_mapper.loader.csv.class', $config['loader']['csv_class']);
        $container->setParameter('lexik_fixtures_mapper.loader.yaml.class', $config['loader']['yaml_class']);
        $container->setParameter('lexik_fixtures_mapper.adapters', $config['adapter']);
    }
}
