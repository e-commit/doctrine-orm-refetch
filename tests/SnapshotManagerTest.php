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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Ecommit\DoctrineOrmRefetch\Exception\SnapshotNotDoneException;
use Ecommit\DoctrineOrmRefetch\SnapshotManager;
use Ecommit\DoctrineOrmRefetch\Tests\App\Doctrine;
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Author;
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Book;

class SnapshotManagerTest extends AbstractTestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var SnapshotManager
     */
    protected $snapshotManager;

    protected function setUp(): void
    {
        $this->em = Doctrine::getEntityManager();
        $this->em->getConnection()->beginTransaction();
        $this->snapshotManager = SnapshotManager::create($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        $this->em->clear();
    }

    public function testCreate(): void
    {
        $refetchManager = SnapshotManager::create($this->em);
        $this->assertEquals(SnapshotManager::class, $refetchManager::class);
    }

    public function testGetEntityManager(): void
    {
        $this->assertSame($this->em, $this->snapshotManager->getEntityManager());
    }

    public function testOneObjectWithoutClear(): void
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $this->assertNotNull($book);

        $this->snapshotManager->snapshot();

        $this->checkUnitOfWork(2, [$book, $book->getCategory()]);
        $this->assertEquals(6, $book->getBookId());
    }

    public function testOneObjectWithClearOutsideSnapshot(): void
    {
        $this->snapshotManager->snapshot();

        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $this->assertNotNull($book);

        $this->snapshotManager->clear();

        $this->checkUnitOfWork(0, []);
    }

    public function testOneObjectWithClearInsideSnapshot(): void
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $this->assertNotNull($book);

        $this->snapshotManager->snapshot();
        $this->snapshotManager->clear();

        $this->checkUnitOfWork(2, [$book, $book->getCategory()]);
        $this->assertEquals(6, $book->getBookId());
    }

    public function testOneObjectWithClearAndCollection(): void
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(9);
        $this->assertNotNull($book);

        $this->snapshotManager->snapshot();

        $authors = $book->getAuthors()->getIterator();
        $this->checkUnitOfWork(4, [$book, $book->getCategory()]);

        $this->snapshotManager->clear();

        $this->checkUnitOfWork(2, [$book, $book->getCategory()]);
    }

    public function testInIterate(): void
    {
        $entityManager = $this->em;

        // Example in README.md - Beginning

        $snapshotManager = SnapshotManager::create($entityManager);

        $author = $entityManager->getRepository(Author::class)->find(1);
        $this->assertNotNull($author); // Remove this line in Example

        $snapshotManager->snapshot();

        $queryBuilder = $entityManager->getRepository(Book::class)->createQueryBuilder('b');
        $queryBuilder->select('b')
            ->andWhere('b.bookId != :bookId')
            ->setParameter('bookId', 7);
        $iterableResult = $queryBuilder->getQuery()->toIterable();

        $i = 0;
        /** @var Book $book */
        foreach ($iterableResult as $book) {
            ++$i;

            if (!$book->getAuthors()->contains($author)) {
                $book->addAuthor($author);
            }

            if (0 === $i % 2) {
                // $author and $book are managed
                $entityManager->flush();
                $snapshotManager->clear(); // Detach all entities attached since the snapshot
                // Only $author is managed

                $this->checkUnitOfWork(1, [$author]); // Remove this line in Example
            }
        }

        $entityManager->flush();
        $snapshotManager->clear();

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

    public function testSnapshotNotDone(): void
    {
        /** @var Book $book */
        $book = $this->em->getRepository(Book::class)->find(6);
        $this->assertNotNull($book);

        $this->expectException(SnapshotNotDoneException::class);
        $this->expectExceptionMessage('The snapshot was not done');

        $this->snapshotManager->clear();
    }
}
