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

namespace Ecommit\DoctrineOrmRefetch\Tests\App;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

class Doctrine
{
    /**
     * @var EntityManagerInterface
     */
    protected static $entityManager;

    public static function getEntityManager(): EntityManagerInterface
    {
        if (static::$entityManager) {
            return static::$entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/Entity'], true);
        $connection = DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            $config
        );
        static::$entityManager = new EntityManager($connection, $config);

        return static::$entityManager;
    }

    public static function createSchema(): void
    {
        $entityManager = self::getEntityManager();

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema(
            $entityManager->getMetadataFactory()->getAllMetadata()
        );
    }

    public static function loadFixtures(): void
    {
        $em = static::getEntityManager();

        $loader = new Loader();
        $loader->loadFromDirectory(__DIR__.'/Fixtures');

        $purger = new ORMPurger();
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        $em->clear();
    }
}
