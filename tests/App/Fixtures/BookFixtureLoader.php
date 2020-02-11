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

namespace Ecommit\DoctrineOrmRefetch\Tests\App\Fixtures;

use Doctrine\Persistence\ObjectManager;
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Book;

class BookFixtureLoader extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = $this->getFaker();
        $authorIdsByBookId = [
            1 => [2],
            2 => [3],
            3 => [4],
            4 => [1],
            5 => [4],
            6 => [1, 2, 3],
            7 => [2, 3],
            8 => [1, 3, 4],
            9 => [1, 2],
            10 => [4],
        ];

        for ($i = 1; $i <= 10; ++$i) {
            $book = new Book();
            $book->setBookId($i);
            $book->setTitle($faker->title);
            $categoryId = ($i % 2) + 1;
            $book->setCategory($this->getReference('category_'.$categoryId));

            foreach ($authorIdsByBookId[$i] as $authorId) {
                $book->addAuthor($this->getReference('author_'.$authorId));
            }

            $manager->persist($book);
            $this->addReference('book_'.$i, $book);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 3;
    }
}
