<?php

namespace Lexik\Bundle\FixturesMapperBundle\Tests\Loader;

use Lexik\Bundle\FixturesMapperBundle\Tests\TestCase;
use Lexik\Bundle\FixturesMapperBundle\Loader\CsvLoader;
use Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ValidatorFactory;

class CsvLoaderTest extends TestCase
{
    protected $csvLoader;

    protected function setUp()
    {
        parent::setUp();

        $adapters = array(
            'doctrine_orm' => array(
                'manager' => 'Doctrine\ORM\EntityManager',
                'adapter' => 'Lexik\Bundle\FixturesMapperBundle\Adapter\DoctrineORMAdapter',
            ),
        );

        $validator = Validation::createValidatorBuilder()
            ->getValidator();
        $this->csvLoader = new CsvLoader($this->getMockSqliteEntityManager(), $adapters, $validator, 'Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper', '|');
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage You must provide a valid CSV file.
     */
    public function testExceptionLoader()
    {
        $this->csvLoader->load('not_a_file.csv');
    }

    public function testLoader()
    {
        $articleMapper = $this->csvLoader->load(__DIR__ . '/../Fixture/Csv/article.csv');
        $this->assertTrue($articleMapper instanceof Mapper);
        $this->assertEquals(count($articleMapper->getValues()), 3);

        $commentMapper = $this->csvLoader->load(__DIR__ . '/../Fixture/Csv/comment.csv');
        $this->assertTrue($commentMapper instanceof Mapper);
        $this->assertEquals(count($commentMapper->getValues()), 4);
    }

    public function testLoaderIgnoreLine()
    {
        $articleMapper = $this->csvLoader->load(__DIR__ . '/../Fixture/Csv/article.csv', array(
            'ignored_lines' => 1,
        ));
        $this->assertTrue($articleMapper instanceof Mapper);
        $this->assertEquals(count($articleMapper->getValues()), 2);

        $articleMapper = $this->csvLoader->load(__DIR__ . '/../Fixture/Csv/article.csv', array(
            'ignored_lines' => array(1, 2, 3),
        ));
        $this->assertTrue($articleMapper instanceof Mapper);
        $this->assertEquals(count($articleMapper->getValues()), 0);
    }
}
