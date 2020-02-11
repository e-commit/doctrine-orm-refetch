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
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Author;

class AuthorFixtureLoader extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = $this->getFaker();

        for ($i = 1; $i <= 4; ++$i) {
            $author = new Author();
            $author->setAuthorId($i);
            $author->setFirstName($faker->firstName);
            $author->setLastName($faker->lastName);
            $manager->persist($author);
            $this->addReference('author_'.$i, $author);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}
