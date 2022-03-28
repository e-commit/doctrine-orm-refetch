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

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Utility\IdentifierFlattener;
use Ecommit\DoctrineOrmRefetch\Exception\EntityNotFoundException;

class RefetchManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public static function create(EntityManagerInterface $entityManager): self
    {
        return new self($entityManager);
    }

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function refetchObject(object &$object): void
    {
        $object = $this->getObject($object);
    }

    public function getObject(object $object): object
    {
        $classMetadata = $this->getObjectMetadata($object);
        $newObject = $this->entityManager->find($classMetadata->getName(), $classMetadata->getIdentifierValues($object));

        if (null === $newObject) {
            throw EntityNotFoundException::fromClassNameAndIdentifier($classMetadata->getName(), $this->getIdentifierFlattener()->flattenIdentifier($classMetadata, $classMetadata->getIdentifierValues($object)));
        }

        return $newObject;
    }

    public function getCollectionFromCriteria(Criteria $criteria, $class): Collection
    {
        return $this->entityManager->getRepository($class)->matching($criteria);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function getObjectMetadata(object $object): ClassMetadata
    {
        return $this->entityManager->getClassMetadata(\get_class($object));
    }

    protected function getIdentifierFlattener(): IdentifierFlattener
    {
        return new IdentifierFlattener($this->entityManager->getUnitOfWork(), $this->entityManager->getMetadataFactory());
    }
}
