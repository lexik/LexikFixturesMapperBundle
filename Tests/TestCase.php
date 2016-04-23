<?php

namespace Lexik\Bundle\FixturesMapperBundle\Tests;

use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as AnnotationDriverORM;
use Doctrine\Common\EventManager;

use Lexik\Bundle\FixturesMapperBundle\DependencyInjection\LexikFixturesMapperExtension;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    protected function setUp()
    {
        parent::setUp();

        $this->containerBuilder = new ContainerBuilder();
        $loader = new LexikFixturesMapperExtension();
        $loader->load(array(), $this->containerBuilder);

        $this->em = $this->getMockSqliteEntityManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     *
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager()
    {
        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $reader = new AnnotationReader();
        $mappingDriver = new AnnotationDriverORM($reader, array(
            __DIR__.'/Fixture/Entity',
        ));

        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(__DIR__.'/temp'));

        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('EntityProxy'));

        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true));

        $config->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'));

        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver));

        $config->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\ORM\\EntityRepository'));

        $config->expects($this->any())
            ->method('getQuoteStrategy')
            ->will($this->returnValue(  new Mapping\DefaultQuoteStrategy()));


        $config->expects($this->any())
            ->method('getRepositoryFactory')
            ->will($this->returnValue(  new DefaultRepositoryFactory()));

        $evm = $this->getMock('Doctrine\Common\EventManager');
        $em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        return $em;
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->containerBuilder);
    }
}
