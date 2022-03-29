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

namespace Ecommit\DoctrineOrmRefetch;

use Doctrine\ORM\EntityManagerInterface;
use Ecommit\DoctrineOrmRefetch\Exception\SnapshotNotDoneException;

final class SnapshotManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    private $snapshot;

    public static function create(EntityManagerInterface $entityManager): self
    {
        return new self($entityManager);
    }

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function snapshot(): void
    {
        $this->snapshot = $this->entityManager->getUnitOfWork()->getIdentityMap();
    }

    public function clear(): void
    {
        if (null === $this->snapshot) {
            throw new SnapshotNotDoneException('The snapshot was not done');
        }

        $identityMap = $this->entityManager->getUnitOfWork()->getIdentityMap();
        foreach ($identityMap as $class => $objects) {
            foreach ($objects as $id => $object) {
                if (!\array_key_exists($class, $this->snapshot) || !\array_key_exists($id, $this->snapshot[$class])) {
                    $this->entityManager->detach($object);
                }
            }
        }
    }
}
