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
#[ORM\Table(name: 'author')]
class Author
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'author_id')]
    protected $authorId;

    #[ORM\Column(type: 'string', length: 255)]
    protected $firstName;

    #[ORM\Column(type: 'string', length: 255)]
    protected $lastName;

    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'authors')]
    protected $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function setAuthorId(int $authorId): self
    {
        $this->authorId = $authorId;

        return $this;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function addBook(Book $book): self
    {
        $book->addAuthor($this);
        $this->books[] = $book;

        return $this;
    }

    public function removeBook(Book $book): self
    {
        $this->books->removeElement($book);
        $book->removeAuthor($this);

        return $this;
    }

    public function getBooks(): Collection
    {
        return $this->books;
    }
}
