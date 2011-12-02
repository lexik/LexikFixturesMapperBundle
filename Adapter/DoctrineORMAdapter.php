<?php

namespace Lexik\Bundle\FixturesMapperBundle\Adapter;

use Doctrine\ORM\EntityManager;

/**
 * Adapter for Doctrine ORM entity manager.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineORMAdapter implements EntityManagerAdapterInterface
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Construct.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($object)
    {
        $this->em->persist($object);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($object)
    {
        $this->em->remove($object);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($object)
    {
        $this->em->merge($object);
    }

    /**
     * {@inheritdoc}
     */
    public function detach($object)
    {
        $this->em->detach($object);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($object)
    {
        $this->em->refresh($object);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->em->clear();
    }
}