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

namespace Ecommit\DoctrineOrmRefetch\Tests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaValidator;
use Ecommit\DoctrineOrmRefetch\Exception\EntityNotFoundException;
use Ecommit\DoctrineOrmRefetch\Exception\ExceptionInterface;
use Ecommit\DoctrineOrmRefetch\RefetchManager;
use Ecommit\DoctrineOrmRefetch\Tests\App\Doctrine;
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Author;
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Book;
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Category;
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Sale;

class RefetchManagerTest extends AbstractTest
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var RefetchManager
     */
    protected $refetchManager;

    protected function setUp(): void
    {
        $this->em = Doctrine::getEntityManager();
        $this->em->getConnection()->beginTransaction();
        $this->refetchManager = RefetchManager::create($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        $this->em->clear();
    }

    public function testSchema(): void
    {
        $validator = new SchemaValidator(Doctrine::getEntityManager());
        $this->assertCount(0, $validator->validateMapping());
    }

    public function testCountObjectsInUnitOfWork(): void
    {
        $this->assertEquals(0, $this->countObjectsInUnitOfWork());

        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $this->assertNotNull($book);
        $this->assertEquals(2, $this->countObjectsInUnitOfWork()); // Book + category (lazy association)

        $categoryName = $book->getCategory()->getName();
        $this->assertEquals(2, $this->countObjectsInUnitOfWork()); // Nothing new

        $authors = $book->getAuthors();
        $this->assertEquals(2, $this->countObjectsInUnitOfWork()); // Nothing new

        $firstLastName = $authors->first()->getFirstName();
        $this->assertEquals(5, $this->countObjectsInUnitOfWork()); // + 3 authors (lazy association collection - collection is populated the first time its accessed)

        $this->em->clear();
        $this->assertEquals(0, $this->countObjectsInUnitOfWork());

        /** @var Author $author */
        $author = $this->em->getRepository(Author::class)->find(4);
        $this->assertNotNull($author);
        $this->assertEquals(1, $this->countObjectsInUnitOfWork()); // Author

        $books = $author->getBooks();
        $this->assertEquals(1, $this->countObjectsInUnitOfWork()); // Nothing new

        $firstTitle = $books->first()->getTitle();
        $this->assertEquals(7, $this->countObjectsInUnitOfWork()); // + 4 books (lazy association collection) + 2 categories

        $firstCategoryName = $book->getCategory()->getName();
        $this->assertEquals(7, $this->countObjectsInUnitOfWork()); // Nothing new

        $this->em->clear();
        $this->assertEquals(0, $this->countObjectsInUnitOfWork());

        $book1Sales = $this->em->getRepository(Sale::class)->findBy(['book' => 1]);
        $this->assertCount(2, $book1Sales);
        $this->assertEquals(3, $this->countObjectsInUnitOfWork()); // 2 sales + 1 book

        $book = $book1Sales[0]->getBook();
        $this->assertEquals(3, $this->countObjectsInUnitOfWork()); // Nothing new

        $category = $book->getCategory();
        $this->assertEquals(4, $this->countObjectsInUnitOfWork()); // + category
    }

    public function testCreate(): void
    {
        $refetchManager = RefetchManager::create($this->em);
        $this->assertEquals(RefetchManager::class, \get_class($refetchManager));
    }

    public function testGetEntityManager(): void
    {
        $this->assertSame($this->em, $this->refetchManager->getEntityManager());
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testOneObjectWithoutClear($useRefetchObjectMethod): void
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $this->assertNotNull($book);

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($book);
        } else {
            $book = $this->refetchManager->getObject($book);
        }

        $this->checkUnitOfWork(2, [$book, $book->getCategory()]);
        $this->assertEquals(6, $book->getBookId());
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testOneObjectWithClear($useRefetchObjectMethod): void
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $this->assertNotNull($book);
        $this->em->clear();

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($book);
        } else {
            $book = $this->refetchManager->getObject($book);
        }

        $this->checkUnitOfWork(2, [$book, $book->getCategory()]);
        $this->assertEquals(6, $book->getBookId());
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testOneObjectWithClearAndLazyAssociation($useRefetchObjectMethod): void
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $category = $book->getCategory();
        $this->assertNotNull($book);
        $this->em->clear();

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($book);
        } else {
            $book = $this->refetchManager->getObject($book);
        }

        $this->checkUnitOfWork(2, [$book, $book->getCategory()]);
        $this->assertEquals(6, $book->getBookId());
        $this->assertFalse($this->unitOfWorkContainsObjects([$category]));

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($category);
        } else {
            $category = $this->refetchManager->getObject($category);
        }

        $this->checkUnitOfWork(2, [$book, $category]);
        $this->assertEquals($category->getCategoryId(), $book->getCategory()->getCategoryId());
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testOneObjectWithClearAndCollection($useRefetchObjectMethod): void
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(9);
        $this->assertNotNull($book);

        $this->em->clear();

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($book);
        } else {
            $book = $this->refetchManager->getObject($book);
        }

        $authors = $book->getAuthors()->getIterator();
        $this->checkUnitOfWork(4, [$book, $book->getCategory()]);
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testChangesBeforeClearNotFlushed($useRefetchObjectMethod): void
    {
        $book = $this->createObjectInTestRefetchChangesNotFlushed();
        $oldTitle = $book->getTitle();

        $this->updateObjectInTestRefetchChangesNotFlushed($book, $oldTitle);

        $this->em->clear();

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($book);
        } else {
            $book = $this->refetchManager->getObject($book);
        }

        $this->assertEquals(6, $book->getBookId());
        $this->checkObjectInTestRefetchChangesNotFlushed($book, $oldTitle, 5);
    }

    protected function createObjectInTestRefetchChangesNotFlushed(): Book
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $this->assertNotNull($book);

        return $book;
    }

    protected function updateObjectInTestRefetchChangesNotFlushed(Book $book, string $oldTitle): void
    {
        $book->setTitle($oldTitle.'2');
        $this->assertEquals(1, $book->getCategory()->getCategoryId());
        /** @var Category $newCategory */
        $newCategory = $this->em->getRepository(Category::class)->find(2);
        $this->assertNotNull($newCategory);
        $book->setCategory($newCategory);
        $this->assertEquals(2, $book->getCategory()->getCategoryId());
        $this->assertCount(3, $book->getAuthors());
        /** @var Author $newAuthor */
        $newAuthor = $this->em->getRepository(Author::class)->find(4);
        $this->assertNotNull($newAuthor);
        $book->addAuthor($newAuthor);
        $this->assertCount(4, $book->getAuthors());
    }

    protected function checkObjectInTestRefetchChangesNotFlushed(Book $book, string $oldTitle, int $expectedInUnitOfWork): void
    {
        $this->assertEquals($oldTitle, $book->getTitle());
        $this->assertEquals(1, $book->getCategory()->getCategoryId());
        $this->assertCount(3, $book->getAuthors());
        $this->checkUnitOfWork($expectedInUnitOfWork, [$book, $book->getCategory()]);
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testChangesAfterClearNotFlushed($useRefetchObjectMethod): void
    {
        $book = $this->createObjectInTestRefetchChangesNotFlushed();
        $oldTitle = $book->getTitle();

        $this->em->clear();

        $this->updateObjectInTestRefetchChangesNotFlushed($book, $oldTitle);

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($book);
        } else {
            $book = $this->refetchManager->getObject($book);
        }

        $this->assertEquals(6, $book->getBookId());
        $this->checkObjectInTestRefetchChangesNotFlushed($book, $oldTitle, 7);
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testInIterate($useRefetchObjectMethod): void
    {
        $entityManager = $this->em;

        // Example in README.md - Beginning

        $refetchManager = RefetchManager::create($entityManager);

        $author = $entityManager->getRepository(Author::class)->find(1);
        $this->assertNotNull($author); // Remove this line in Example

        $queryBuilder = $entityManager->getRepository(Book::class)->createQueryBuilder('b');
        $queryBuilder->select('b')
            ->andWhere('b.bookId != :bookId')
            ->setParameter('bookId', 7);
        $iterableResult = $queryBuilder->getQuery()->iterate();

        $i = 0;
        foreach ($iterableResult as $row) {
            ++$i;
            /** @var Book $book */
            $book = current($row);

            if (!$book->getAuthors()->contains($author)) {
                $book->addAuthor($author);
            }

            if (0 === $i % 2) {
                // $author is managed
                $entityManager->flush();
                $entityManager->clear();
                // $author is not managed
                $this->assertEquals(0, $this->countObjectsInUnitOfWork()); // Remove this line in Example
                if ($useRefetchObjectMethod) { // Remove this line in Example
                    $refetchManager->refetchObject($author); // Remove this line in Example
                } else { // Remove this line in Example
                    $author = $refetchManager->getObject($author);
                } // Remove this line in Example
                // $author is managed
                $this->assertEquals(1, $this->countObjectsInUnitOfWork()); // Remove this line in Example
            }
        }

        $entityManager->flush();
        $entityManager->clear();

        // Example in README.md - End

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->em->getRepository(Book::class)->createQueryBuilder('b');
        $queryBuilder->select('count(b) as nbre')
            ->leftJoin('b.authors', 'a')
            ->andWhere('a.authorId = :authorId')
            ->setParameter('authorId', 1);
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        $this->assertEquals(9, $count);
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testNotManagedObject($useRefetchObjectMethod): void
    {
        /** @var Author $author1 */
        $author1 = $this->em->getRepository(Author::class)->find(1);
        $this->assertNotNull($author1);
        /** @var Author $author2 */
        $author2 = $this->em->getRepository(Author::class)->find(2);
        $this->assertNotNull($author2);

        $this->em->clear();

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($author1);
        } else {
            $author1 = $this->refetchManager->getObject($author1);
        }

        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(2);
        $this->assertNotNull($book);
        $book->addAuthor($author1);
        $this->em->flush();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('new entity was found through the relationship');
        $book->addAuthor($author2);
        $this->em->flush();
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testIndirectRefetch($useRefetchObjectMethod): void
    {
        /** @var Author $author */
        $author = $this->em->getRepository(Author::class)->find(2);
        $this->assertNotNull($author);
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(1);
        $this->assertNotNull($book);

        $this->em->clear();

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($book);
        } else {
            $book = $this->refetchManager->getObject($book);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('Entity has to be managed');
        $author->setLastName('My new last name');
        $this->em->flush($author);

        $countAuthors = $book->getAuthors()->count(); // (lazy association collection
        $author->setLastName('My new last name');
        $this->em->flush($author);
        $this->checkUnitOfWork(2, [$book, $author]);
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testWithCompositePk($useRefetchObjectMethod): void
    {
        /** @var Sale $sale */
        $sale = $this->em->getRepository(Sale::class)->findOneBy([
            'book' => 1,
            'year' => 2019,
        ]);
        $this->assertNotNull($sale);

        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($sale);
        } else {
            $sale = $this->refetchManager->getObject($sale);
        }

        $this->assertEquals(1, $sale->getBook()->getBookId());
        $this->assertEquals(2019, $sale->getYear());
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testWithBadObject($useRefetchObjectMethod): void
    {
        $this->expectException(MappingException::class);
        $this->expectErrorMessage('Class "stdClass" is not a valid entity or mapped super class');
        $object = new \stdClass();
        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($object);
        } else {
            $object = $this->refetchManager->getObject($object);
        }
    }

    /**
     * @dataProvider getUseRefetchObjectMethodProdiver
     */
    public function testWithDeletedEntity($useRefetchObjectMethod): void
    {
        /** @var Author $author */
        $author = $this->em->getRepository(Author::class)->find(2);
        $this->assertNotNull($author);

        $this->em->remove($author);
        $this->em->flush();
        $this->em->clear();

        $this->expectException(ExceptionInterface::class);
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Entity of type \'Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Author\' for IDs authorId(2) was not found');
        if ($useRefetchObjectMethod) {
            $this->refetchManager->refetchObject($author);
        } else {
            $author = $this->refetchManager->getObject($author);
        }
    }

    public function testGetCollectionFromCriteriaWithoutClear(): void
    {
        /** @var Category $category */
        $category = $this->em->getRepository(Category::class)->find(2);
        $this->assertNotNull($category);

        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('category', $category));

        $books = $this->refetchManager->getCollectionFromCriteria($criteria, Book::class);

        $this->assertEquals(LazyCriteriaCollection::class, \get_class($books));
        $this->assertEquals(1, $this->countObjectsInUnitOfWork());
        $this->assertCount(5, $books);
        $firstTitle = $books->first()->getTitle();
        $this->assertEquals(6, $this->countObjectsInUnitOfWork());
    }

    public function testGetCollectionFromCriteriaWithClear(): void
    {
        /** @var Category $category */
        $category = $this->em->getRepository(Category::class)->find(2);
        $this->assertNotNull($category);

        $this->em->clear();

        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('category', $category));

        $books = $this->refetchManager->getCollectionFromCriteria($criteria, Book::class);

        $this->assertEquals(LazyCriteriaCollection::class, \get_class($books));
        $this->assertEquals(0, $this->countObjectsInUnitOfWork());
        $this->assertCount(5, $books);
        $firstTitle = $books->first()->getTitle();
        $this->assertEquals(6, $this->countObjectsInUnitOfWork());
    }

    public function testGetCollectionFromCriteriaInIterate(): void
    {
        $entityManager = $this->em;

        // Example in README.md - Beginning

        $refetchManager = RefetchManager::create($entityManager);

        $ctiteria = Criteria::create()
            ->andWhere(Criteria::expr()->gt('authorId', 2));
        $authors = $refetchManager->getCollectionFromCriteria($ctiteria, Author::class);
        $this->assertCount(2, $authors); // Remove this line in Example

        $queryBuilder = $entityManager->getRepository(Book::class)->createQueryBuilder('b');
        $queryBuilder->select('b')
            ->andWhere('b.bookId != :bookId')
            ->setParameter('bookId', 9);
        $iterableResult = $queryBuilder->getQuery()->iterate();

        $i = 0;
        foreach ($iterableResult as $row) {
            ++$i;
            /** @var Book $book */
            $book = current($row);

            foreach ($authors as $author) {
                if (!$book->getAuthors()->contains($author)) {
                    $book->addAuthor($author);
                }
            }

            if (0 === $i % 2) {
                $entityManager->flush();
                $entityManager->clear();
                $authors = $refetchManager->getCollectionFromCriteria($ctiteria, Author::class);
                $this->assertEquals(0, $this->countObjectsInUnitOfWork()); // Remove this line in Example
            }
        }

        $entityManager->flush();
        $entityManager->clear();

        // Example in README.md - End

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->em->getRepository(Book::class)->createQueryBuilder('b');
        $queryBuilder->select('count(b) as nbre')
            ->leftJoin('b.authors', 'a')
            ->andWhere('a.authorId > :authorId')
            ->setParameter('authorId', 2);
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        $this->assertEquals(18, $count);
    }

    public function getUseRefetchObjectMethodProdiver()
    {
        return [
            [true],
            [false],
        ];
    }
}
