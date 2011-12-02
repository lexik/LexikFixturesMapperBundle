<?php

namespace Lexik\Bundle\FixturesMapperBundle\Tests\Loader;

use Lexik\Bundle\FixturesMapperBundle\Tests\TestCase;
use Lexik\Bundle\FixturesMapperBundle\Loader\YamlLoader;
use Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper;

use Symfony\Component\Validator\ValidatorFactory;

class YamlLoaderTest extends TestCase
{
    protected $yamlLoader;

    protected function setUp()
    {
        parent::setUp();

        $adapters = array(
            'doctrine_orm' => array(
                'manager' => 'Doctrine\ORM\EntityManager',
                'adapter' => 'Lexik\Bundle\FixturesMapperBundle\Adapter\DoctrineORMAdapter',
            ),
        );

        $validator = ValidatorFactory::buildDefault()->getValidator();
        $this->yamlLoader = new YamlLoader($this->getMockSqliteEntityManager(), $adapters, $validator);
    }

    public function testLoader()
    {
        $articleMapper = $this->yamlLoader->load(__DIR__ . '/../Fixture/Yaml/article.yml');
        $this->assertTrue($articleMapper instanceof Mapper);
        $this->assertEquals(count($articleMapper->getValues()), 2);

        $commentMapper = $this->yamlLoader->load(__DIR__ . '/../Fixture/Yaml/comment.yml');
        $this->assertTrue($commentMapper instanceof Mapper);
        $this->assertEquals(count($commentMapper->getValues()), 4);
    }
}
