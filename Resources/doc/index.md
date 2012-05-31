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

This is the full configuration tree with default values (there is no requied option so you don't need to define these options in your `config.yml` except if you need to change one of it):

```yaml
# app/config/config.yml
lexik_fixtures_mapper:
    loader:
        base_class: 'Lexik\Bundle\FixturesMapperBundle\Loader\AbstractLoader'
        csv_class:  'Lexik\Bundle\FixturesMapperBundle\Loader\CsvLoader'
        yaml_class: 'Lexik\Bundle\FixturesMapperBundle\Loader\YamlLoader'
    adapter:
        doctrine_orm: { manager: 'Doctrine\ORM\EntityManager', adapter: 'Lexik\Bundle\FixturesMapperBundle\Adapter\DoctrineORMAdapter' }
```

Usage
=====

Load and map fixtures from a CSV file
-------------------------------------

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
    
    public function getOrder()
    {
        return 1;
    }   
}
```

Load and map fixtures from a YAML file
--------------------------------------

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

        $this->container->get('lexik_fixtures_mapper.loader.yml')
            ->load(sprintf('%s/data/csv/MyEntity.yml', __DIR__))
            ->setEntityName('\\Acme\\DemoBundle\\Entity\\MyEntity')

            // now you have a Mapper instance, you can map columns from your YAML with entity properties:

            ->mapColumn('reference', function($data, $object) use ($self) {
                $self->addReference($data, $object);
            })
            ->mapColumn('title')

            // when mapping's done, you can persist your entities:

            ->persist()
        ;
    }
    
    public function getOrder()
    {
        return 1;
    }   
}
```

Entites validation
------------------

By default, `LexikFixturesMapperBundle` validate entities, you can configure the validation strategy by using the `setValidatorStrategy()` method on a mapper instance.
You can also pass some validation groups to use with entities validation.
Available validation strategy:

* Throw an exception on violations detection (default strategy)
* Ignore object and continue the loop on violations detection.
* Bypass the entity validation.

Example:

```php
<?php
    $this->container->get('lexik_fixtures_mapper.loader.yml')
        ->load(sprintf('%s/data/csv/MyEntity.yml', __DIR__))
        ->setEntityName('\\Acme\\DemoBundle\\Entity\\MyEntity')
        
        // change the validation strategy
        ->setValidatorStrategy(\Lexik\Bundle\FixturesMapperBundle\Mapper::VALIDATOR_CONTINUE_ON_VIOLATIONS)
        
        // set validations groups
        ->setValidationGroups(array('my_validation_group'))
        
        ...   
        ->persist()
    ;
```  

Callbacks
---------

The bundle allow you to define some callbacks on the mapper instance. The following callbacks are available:

* `CALLBACK_ON_EXCEPTION`: called when an exception is rised during persist.
* `CALLBACK_ON_VIOLATIONS`: called on validation violations.
* `CALLBACK_PRE_PERSIST`: called before an entity is persisted.

Example:

```php
<?php
    $this->container->get('lexik_fixtures_mapper.loader.yml')
        ->load(sprintf('%s/data/csv/MyEntity.yml', __DIR__))
        ->setEntityName('\\Acme\\DemoBundle\\Entity\\MyEntity')
        
        // define a callback on prePersist
        ->setCallback(\Lexik\Bundle\FixturesMapperBundle\Mapper::CALLBACK_PRE_PERSIST, function ($data, $object) {
            // ... do something on pre persist
        })
        
        ->persist()
    ;
```

Auto map relation field
-----------------------

##### This will work only by using YAML file.

To make the bundle automaticaly maps field that are relations you will have to add all entities to the reference repositories.

Here we suppose the MyEntity entity is related to one RelatedEntity (so a RelatedEntity can be related to many MyEntity).

```yaml
# /data/csv/MyEntity.yml
-
    reference: ent_1
    title:     My entity 1
    related:   rel_1
-
    reference: ent_2
    title:     My entity 2
    related:   rel_2

# /data/csv/RelatedEntity.yml
-
    reference: rel_1
    name:      Related entity 1
-
    reference: rel_2
    name:      Related entity 2
```

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

        // load RelatedEntity
        $this->container->get('lexik_fixtures_mapper.loader.yml')
            ->load(sprintf('%s/data/csv/RelatedEntity.yml', __DIR__))
            ->setEntityName('\\Acme\\DemoBundle\\Entity\\RelatedEntity')
            ->mapColumn('reference', function($data, $object) use ($self) {
                $self->addReference($data, $object);
            })
            ->mapColumn('name')
            ->persist()
        ;

        // load MyEntity
        $this->container->get('lexik_fixtures_mapper.loader.yml')
            ->load(sprintf('%s/data/csv/MyEntity.yml', __DIR__))
            ->setEntityName('\\Acme\\DemoBundle\\Entity\\MyEntity')
            
            ->setLoadData($this)
            ->mapColumn('reference', function($data, $object) use ($self) {
                $self->addReference($data, $object);
            })
            ->mapColumn('title')
            ->mapColumn('related')
            
            ->persist()
        ;
    }
    
    public function getOrder()
    {
        return 1;
    }   
}
```
