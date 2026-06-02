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

final class RefetchManager
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

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return-out T
     */
    public function refetchObject(object &$object): void
    {
        $object = $this->getObject($object);
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function getObject(object $object): object
    {
        $classMetadata = $this->getObjectMetadata($object);
        $newObject = $this->entityManager->find($classMetadata->getName(), $classMetadata->getIdentifierValues($object));

        if (null === $newObject) {
            throw EntityNotFoundException::fromClassNameAndIdentifier($classMetadata->getName(), $this->getIdentifierFlattener()->flattenIdentifier($classMetadata, $classMetadata->getIdentifierValues($object)));
        }

        return $newObject;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return Collection<int, T>
     */
    public function getCollectionFromCriteria(Criteria $criteria, string $class): Collection
    {
        return $this->entityManager->getRepository($class)->matching($criteria);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return ClassMetadata<T>
     */
    protected function getObjectMetadata(object $object): ClassMetadata
    {
        return $this->entityManager->getClassMetadata($object::class);
    }

    protected function getIdentifierFlattener(): IdentifierFlattener
    {
        return new IdentifierFlattener($this->entityManager->getUnitOfWork(), $this->entityManager->getMetadataFactory());
    }
}
