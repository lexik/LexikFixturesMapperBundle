Installation
============

Update your `deps` and `deps.lock` files:

```
// deps
...
[LexikFixturesMapperBundle]
    git=https://github.com/lexik/LexikFixturesMapperBundle.git
    target=/bundles/Lexik/Bundle/FixturesMapperBundle

// deps.lock
...
LexikFixturesMapperBundle <commit>
```

Register the namespaces with the autoloader:

```php
<?php
// app/autoload.php
$loader->registerNamespaces(array(
    // ...
    'Lexik' => __DIR__.'/../vendor/bundles',
    // ...
));
```

Register the bundle with your kernel:

```php
<?php
// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Lexik\Bundle\FixturesMapperBundle\LexikFixturesMapperBundle(),
    // ...
);
```

___________________

Configuration
=============

This is the full configuration tree with default values:

```yaml
# app/config/config.yml
lexik_fixtures_mapper:
    loader:
        base_class: "Lexik\Bundle\FixturesMapperBundle\Loader\AbstractLoader"
        csv_class:  "Lexik\Bundle\FixturesMapperBundle\Loader\CsvLoader"
        yaml_class: "Lexik\Bundle\FixturesMapperBundle\Loader\YamlLoader"
    adapter:
        doctrine_orm: { manager: "Doctrine\ORM\EntityManager", adapter: "Lexik\Bundle\FixturesMapperBundle\Adapter\DoctrineORMAdapter" }
```

Usage
=====

Example:

```php
<?php
# Acme/DemoBundle/DataFixtures/ORM/LoadData.php

namespace Acme\DemoBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $self = $this; // beurk

        $this->container->get('lexik_fixtures_mapper.loader.csv')
            ->load(sprintf('%s/data/csv/MyEntity.csv', __DIR__))
            ->setEntityName('\\Acme\\DemoBundle\\Entity\\MyEntity')

            // now you have a Mapper instance, you can map columns from your CSV with entity properties:

            ->mapColumn(0, function($data, &$object) use ($self) {
                $self->addReference($data, $object);
            })
            ->mapColumn(1, 'title')

            // when mapping's done, you can persist your entities:

            ->persist()
        ;
    }
}
```

By default, `LexikFixturesMapperBundle` validate entities, you can configure the strategy on violations detection, 2 solutions:

* throw an Exception (default configuration)

      ->persist(Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper::EXCEPTION_ON_VALIDATOR_VIOLATIONS)

* ignore invalid entity and continue

      ->persist(Lexik\Bundle\FixturesMapperBundle\Mapper\Mapper::CONTINUE_ON_VALIDATOR_VIOLATIONS)
