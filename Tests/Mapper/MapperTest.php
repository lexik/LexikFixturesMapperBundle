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

    protected $articleMapper;
    protected $commentMapper;
    protected $categoryMapper;

    protected function setUp()
    {
        parent::setUp();

        $adapters = array(
            'doctrine_orm' => array(
                'manager' => 'Doctrine\ORM\EntityManager',
                'adapter' => 'Lexik\Bundle\FixturesMapperBundle\Adapter\DoctrineORMAdapter',
            ),
        );

        $this->validator  = ValidatorFactory::buildDefault(array(), true)->getValidator();

        $this->csvLoader  = new CsvLoader($this->em, $adapters, $this->validator, 'Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper', '|');
        $this->yamlLoader = new YamlLoader($this->em, $adapters, $this->validator, 'Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper', '|');

        $this->articleMapper = $this->yamlLoader->load(__DIR__ . '/../Fixture/Yaml/article.yml');
        $this->commentMapper = $this->yamlLoader->load(__DIR__ . '/../Fixture/Yaml/comment.yml');
        $this->categoryMapper = $this->yamlLoader->load(__DIR__ . '/../Fixture/Yaml/category.yml');
    }

    protected function createLoadData()
    {
        $refRepository = new \Doctrine\Common\DataFixtures\ReferenceRepository($this->em);

        $loadData = new \Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\LoadData();
        $loadData->setReferenceRepository($refRepository);

        return $loadData;
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage You must provide an entity name
     */
    public function testEntityNameException()
    {
        $adapter = new DoctrineORMAdapter($this->em);

        $mapper = new Mapper(array(), $adapter, $this->validator, '|');
        $mapper->persist();
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage You must provide a valid entity name, "InvalidEntity" does not exists
     */
    public function testInvalidEntityNameException()
    {
        $adapter = new DoctrineORMAdapter($this->em);

        $mapper = new Mapper(array(), $adapter, $this->validator, '|');
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
        $this->assertEquals(count($articles), 3);
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
     * @expectedException        Lexik\Bundle\FixturesMapperBundle\Exception\InvalidMethodException
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

    public function testFromYamlSetCollectionAssociationManyToMany()
    {
        $loadData = $this->createLoadData();

        $this->commentMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Comment')
            ->setLoadData($loadData)
            ->mapColumn('reference', function ($ref, $article) use ($loadData) {
                $loadData->addReference($ref, $article);
            })
            ->mapColumn('message')
            ->persist()
        ;

        $this->articleMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')
            ->setLoadData($loadData)
            ->mapColumn('reference', function ($ref, $article) use ($loadData) {
                $loadData->addReference($ref, $article);
            })
            ->mapColumn('type')
            ->mapColumn('title')
            ->mapColumn('comments')
            ->persist()
        ;

        $articles = $this->findAllArticles();

        $this->assertEquals(3, count($articles));

        $this->assertEquals('First article', $articles[0]->getTitle());
        $this->assertEquals(1, count($articles[0]->getComments()));

        $this->assertEquals('Second article', $articles[1]->getTitle());
        $this->assertEquals(2, count($articles[1]->getComments()));

        $this->assertEquals('Third article', $articles[2]->getTitle());
        $this->assertEquals(1, count($articles[2]->getComments()));
    }

    public function testFromYamlSetCollectionAssociationOneToMany()
    {
        $loadData = $this->createLoadData();

        $this->articleMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')
            ->setLoadData($loadData)
            ->mapColumn('reference', function ($ref, $article) use ($loadData) {
                $loadData->addReference($ref, $article);
            })
            ->mapColumn('type')
            ->mapColumn('title')
            ->persist()
        ;

        $this->categoryMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Category')
            ->setLoadData($loadData)
            ->mapColumn('reference', function ($ref, $category) use ($loadData) {
                $loadData->addReference($ref, $category);
            })
            ->mapColumn('label')
            ->mapColumn('articles')
            ->persist()
        ;

        $this->assertArticlesCategory();
    }

    public function testFromCSVSetCollectionAssociationOneToMany()
    {
        $loadData = $this->createLoadData();

        $this->csvLoader->load(__DIR__ . '/../Fixture/Csv/article.csv')
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')
            ->setLoadData($loadData)
            ->mapColumn(0, function ($ref, $article) use ($loadData) {
                $loadData->addReference($ref, $article);
            })
            ->mapColumn(1, 'type')
            ->mapColumn(2, 'title')
            ->persist()
        ;

        $this->csvLoader->load(__DIR__ . '/../Fixture/Csv/category.csv')
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Category')
            ->setLoadData($loadData)
            ->mapColumn(0, function ($ref, $category) use ($loadData) {
                $loadData->addReference($ref, $category);
            })
            ->mapColumn(1, 'label')
            ->mapColumn(2, 'articles')
            ->persist()
        ;

        $this->assertArticlesCategory();
    }

    public function testFromYamlSetSingleAssociation()
    {
        $loadData = $this->createLoadData();

        $this->categoryMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Category')
            ->setLoadData($loadData)
            ->mapColumn('reference', function ($ref, $category) use ($loadData) {
                $loadData->addReference($ref, $category);
            })
            ->mapColumn('label')
            ->persist()
        ;

        $this->articleMapper
            ->setEntityName('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')
            ->setLoadData($loadData)
            ->mapColumn('reference', function ($ref, $article) use ($loadData) {
                $loadData->addReference($ref, $article);
            })
            ->mapColumn('type')
            ->mapColumn('title')
            ->mapColumn('category')
            ->persist()
        ;

        $this->assertArticlesCategory();
    }

    protected function findAllArticles()
    {
        return $this->em->getRepository('Lexik\\Bundle\\FixturesMapperBundle\\Tests\\Fixture\\Entity\\Article')
            ->createQueryBuilder('a')
            ->select('a, cat, com')
            ->leftJoin('a.category', 'cat')
            ->leftJoin('a.category', 'com')
            ->orderBy('a.title', 'asc')
            ->getQuery()
            ->getResult();
    }

    protected function assertArticlesCategory()
    {
        $articles = $this->findAllArticles();

        $this->assertEquals(3, count($articles));

        $this->assertEquals('First article', $articles[0]->getTitle());
        $this->assertEquals('rabbids', $articles[0]->getCategory()->getLabel());

        $this->assertEquals('Second article', $articles[1]->getTitle());
        $this->assertEquals('rainbow', $articles[1]->getCategory()->getLabel());

        $this->assertEquals('Third article', $articles[2]->getTitle());
        $this->assertEquals('rainbow', $articles[2]->getCategory()->getLabel());
    }
}
