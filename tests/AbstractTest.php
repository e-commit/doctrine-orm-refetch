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

use Doctrine\ORM\UnitOfWork;
use Ecommit\DoctrineOrmRefetch\Tests\App\Doctrine;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    protected function countObjectsInUnitOfWork(): int
    {
        $count = 0;
        foreach (Doctrine::getEntityManager()->getUnitOfWork()->getIdentityMap() as $objects) {
            $count += \count($objects);
        }

        return $count;
    }

    protected function unitOfWorkContainsObjects(iterable $objects): bool
    {
        $unitOfWork = Doctrine::getEntityManager()->getUnitOfWork();
        foreach ($objects as $object) {
            if (UnitOfWork::STATE_MANAGED !== $unitOfWork->getEntityState($object)) {
                return false;
            }
        }

        return true;
    }

    protected function checkUnitOfWork(int $expectedCountEntities, iterable $expectedEntities): void
    {
        $this->assertEquals($expectedCountEntities, $this->countObjectsInUnitOfWork());
        $this->assertTrue($this->unitOfWorkContainsObjects($expectedEntities));
    }
}
