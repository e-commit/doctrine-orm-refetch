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

namespace Ecommit\DoctrineOrmRefetch\Exception;

class EntityNotFoundException extends \Exception implements ExceptionInterface
{
    public static function fromClassNameAndIdentifier(string $className, array $id): self
    {
        $ids = [];

        foreach ($id as $key => $value) {
            $ids[] = $key.'('.$value.')';
        }

        return new self(
            'Entity of type \''.$className.'\''.($ids ? ' for IDs '.implode(', ', $ids) : '').' was not found'
        );
    }
}
