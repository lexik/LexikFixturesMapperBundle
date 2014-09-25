<?php

namespace Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=128)
     */
    private $title;

    /**
     * @ORM\Column(length=128)
     */
    private $type;

    /**
     * @ORM\ManyToMany(targetEntity="Comment")
     * @ORM\JoinTable(
     *   name="articles_comments",
     *   joinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="comment_id", referencedColumnName="id", unique=true)}
     * )
     */
    private $comments;

    /**
     * @ORM\ManyToOne(targetEntity="Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\Entity\Category", inversedBy="articles")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    private $category;

    public function __construct()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function addComment(Comment $comment)
    {
        $this->comments[] = $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setCategory(\Lexik\Bundle\FixturesMapperBundle\Tests\Fixture\Entity\Category $category)
    {
        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }
}
