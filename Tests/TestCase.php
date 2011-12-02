<?php

namespace Lexik\Bundle\FixturesMapperBundle\Tests;

use Symfony\Bundle\AsseticBundle\DependencyInjection\AsseticExtension;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as AnnotationDriverORM;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;

use Lexik\Bundle\FixturesMapperBundle\DependencyInjection\LexikFixturesMapperExtension;
use Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\Entity\Article;
use Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\Entity\Comment;

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
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article'),
            $this->em->getClassMetadata('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Comment'),
        ));
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

        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        $mappingDriver = new AnnotationDriverORM($reader);

        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver));

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
