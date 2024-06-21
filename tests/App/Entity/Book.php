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

#[ORM\Entity]
#[ORM\Table(name: 'book')]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'book_id')]
    protected $bookId;

    #[ORM\Column(type: 'string', length: 255)]
    protected $title;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'books')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'category_id', nullable: false)]
    protected $category;

    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'books')]
    #[ORM\JoinColumn(name: 'book_id', referencedColumnName: 'book_id')]
    #[ORM\InverseJoinColumn(name: 'author_id', referencedColumnName: 'author_id')]
    protected $authors;

    #[ORM\OneToMany(targetEntity: Sale::class, mappedBy: 'book')]
    protected $sales;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->sales = new ArrayCollection();
    }

    public function setBookId(int $bookId): self
    {
        $this->bookId = $bookId;

        return $this;
    }

    public function getBookId(): int
    {
        return $this->bookId;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCategory(?Category $category = null): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function addAuthor(Author $author): self
    {
        if (!$this->authors->contains($author)) {
            $this->authors[] = $author;
        }

        return $this;
    }

    public function removeAuthor(Author $author): self
    {
        if ($this->authors->contains($author)) {
            $this->authors->removeElement($author);
        }

        return $this;
    }

    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function addSale(Sale $sale): self
    {
        $sale->setBook($this);
        $this->sales[] = $sale;

        return $this;
    }

    public function removeSale(Sale $sale): self
    {
        $this->sales->removeElement($sale);
        $sale->setBook(null);

        return $this;
    }

    public function getSales(): Collection
    {
        return $this->sales;
    }
}
