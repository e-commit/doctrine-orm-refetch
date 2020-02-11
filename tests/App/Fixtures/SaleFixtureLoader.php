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
use Ecommit\DoctrineOrmRefetch\Tests\App\Entity\Sale;

class SaleFixtureLoader extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; ++$i) {
            if (8 === $i) {
                continue;
            }

            $years = [
                2018,
                2019,
            ];
            if (9 === $i) {
                $years = [2019];
            }

            foreach ($years as $year) {
                $sale = new Sale();
                $sale->setBook($this->getReference('book_'.$i));
                $sale->setYear($year);
                $sale->setCountSales(random_int(1, 5000000));

                $manager->persist($sale);
            }
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 4;
    }
}
