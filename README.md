# Doctrine ORM Refetch

This library allows to re-fetch Doctrine ORM objects after clear the object manager.

![Tests](https://github.com/e-commit/doctrine-orm-refetch/workflows/Tests/badge.svg)

## Installation ##

To install doctrine-orm-refetch with Composer just run :

```bash
$ composer require ecommit/doctrine-orm-refetch
```

## Usage ##

Create the utility (`$entityManager` is the Doctrine ORM entity manager):

```php
use Ecommit\DoctrineOrmRefetch\RefetchManager;

$refetchManager = RefetchManager::create($entityManager);
```

### Refetch an object ###

```php
$myObject = $refetchManager->getFetchedObject($myObject);
//or $refetchManager->refreshObject($myObject);
```

Example:

```php
use Ecommit\DoctrineOrmRefetch\RefetchManager;

$refetchManager = RefetchManager::create($entityManager);

$author = $entityManager->getRepository(Author::class)->find(1);

$queryBuilder = $entityManager->getRepository(Book::class)->createQueryBuilder('b');
$queryBuilder->select('b')
    ->andWhere('b.bookId != :bookId')
    ->setParameter('bookId', 7);
$iterableResult = $queryBuilder->getQuery()->iterate();

$i = 0;
foreach ($iterableResult as $row) {
    ++$i;
    $book = current($row);

    if (!$book->getAuthors()->contains($author)) {
        $book->addAuthor($author);
    }

    if (0 === $i % 20) {
        //$author is managed
        $entityManager->flush();
        $entityManager->clear();
        //$author is not managed
        $author = $refetchManager->getObject($author);
        //$author is managed
    }
}

$entityManager->flush();
$entityManager->clear();
```

### Get collection by critera ###

```php
$collection = $refetchManager->getCollectionFromCriteria($criteria, 'MyClass');
```

Example:

```php
use Ecommit\DoctrineOrmRefetch\RefetchManager;

$refetchManager = RefetchManager::create($entityManager);

$ctiteria = Criteria::create()
    ->andWhere(Criteria::expr()->gt('authorId', 2));
$authors = $refetchManager->getCollectionFromCriteria($ctiteria, Author::class);

$queryBuilder = $entityManager->getRepository(Book::class)->createQueryBuilder('b');
$queryBuilder->select('b')
    ->andWhere('b.bookId != :bookId')
    ->setParameter('bookId', 9);
$iterableResult = $queryBuilder->getQuery()->iterate();

$i = 0;
foreach ($iterableResult as $row) {
    ++$i;
    $book = current($row);

    foreach ($authors as $author) {
        if (!$book->getAuthors()->contains($author)) {
            $book->addAuthor($author);
        }
    }

    if (0 === $i % 20) {
        $entityManager->flush();
        $entityManager->clear();
        $authors = $refetchManager->getCollectionFromCriteria($ctiteria, Author::class);
    }
}

$entityManager->flush();
$entityManager->clear();
```


## License ##

This librairy is under the MIT license. See the complete license in *LICENSE* file.
