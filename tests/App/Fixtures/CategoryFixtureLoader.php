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
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Category;

class CategoryFixtureLoader extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $dataSet = [
            'Novel',
            'Computer science',
        ];

        $categoryId = 0;
        foreach ($dataSet as $data) {
            ++$categoryId;
            $category = new Category();
            $category->setCategoryId($categoryId);
            $category->setName($data);
            $manager->persist($category);
            $this->addReference('category_'.$categoryId, $category);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
