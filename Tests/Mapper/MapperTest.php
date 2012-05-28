<?php

namespace Lexik\Bundle\FixturesMapperBundle\Tests\Loader;

use Lexik\Bundle\FixturesMapperBundle\Adapter\DoctrineORMAdapter;
use Lexik\Bundle\FixturesMapperBundle\Tests\TestCase;
use Lexik\Bundle\FixturesMapperBundle\Loader\CsvLoader;
use Lexik\Bundle\FixturesMapperBundle\Loader\YamlLoader;
use Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper;

use Symfony\Component\Validator\ValidatorFactory;

class MapperTest extends TestCase
{
    protected $validator;
    protected $csvLoader;
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

        $this->validator  = ValidatorFactory::buildDefault()->getValidator();

        $this->csvLoader  = new CsvLoader($this->em, $adapters, $this->validator, 'Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper');
        $this->yamlLoader = new YamlLoader($this->em, $adapters, $this->validator, 'Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper');

        $this->articleMapper = $this->yamlLoader->load(__DIR__ . '/../Fixture/Yaml/article.yml');
        $this->commentMapper = $this->yamlLoader->load(__DIR__ . '/../Fixture/Yaml/comment.yml');
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage You must provide an entity name
     */
    public function testEntityNameException()
    {
        $adapter = new DoctrineORMAdapter($this->em);

        $mapper = new Mapper(array(), $adapter, $this->validator);
        $mapper->persist();
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage You must provide a valid entity name, "InvalidEntity" does not exists
     */
    public function testInvalidEntityNameException()
    {
        $adapter = new DoctrineORMAdapter($this->em);

        $mapper = new Mapper(array(), $adapter, $this->validator);
        $mapper
            ->setEntityName('InvalidEntity')
            ->persist()
        ;
    }

    public function testFromYaml()
    {
        $this->articleMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')
            ->mapColumn('type')
            ->mapColumn('title')
            ->persist()
        ;

        $articles = $this->em->getRepository('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')->findAll();
        $this->assertEquals(count($articles), 2);
    }

    /**
     * @expectedException        PDOException
     * @expectedExceptionMessage Article.title may not be NULL
     */
    public function testFromYamlIncompleteMapping()
    {
        $this->articleMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')
            ->persist()
        ;
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage The method "setTest" does not exists
     */
    public function testFromYamlInvalidSetter()
    {
        $this->articleMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')
            ->mapColumn('title', 'test')
            ->persist()
        ;
    }
}
