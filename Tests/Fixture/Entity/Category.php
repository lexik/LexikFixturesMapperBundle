<?php

namespace Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\OneToMany(targetEntity="Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\Entity\Category", mappedBy="category")
     */
    private $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function addArticles(\Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\Entity\Article $article)
    {
        $article->setCategory($this);

        $this->articles[] = $article;
    }

    public function getArticles()
    {
        return $this->articles;
    }
}
