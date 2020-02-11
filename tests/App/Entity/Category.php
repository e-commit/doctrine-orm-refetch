<?php

declare(strict_types=1);

/*
 * This file is part of the doctrine-orm-refetch package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\DoctrineOrmRefetch\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="category")
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="category_id")
     */
    protected $categoryId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Book", mappedBy="category")
     */
    protected $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function setCategoryId(int $categoryId): self
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addBook(Book $book): self
    {
        $book->setCategory($this);
        $this->books[] = $book;

        return $this;
    }

    public function removeBook(Book $book): self
    {
        $this->books->removeElement($book);
        $book->setCategory(null);

        return $this;
    }

    public function getBooks(): Collection
    {
        return $this->books;
    }
}
