<?php

namespace App\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;

class DatabaseService
{

    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find(string $className, int $id)
    {
        return $this->em->find($className, $id);
    }

    public function findAll(string $className): array
    {
        return $this->em->getRepository($className)->findAll();
    }

    public function findOneBy(string $className, array $criteria, array $orderBy = null)
    {
        return $this->em->getRepository($className)->findOneBy($criteria, $orderBy);
    }

    public function findBy(string $className, array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->em->getRepository($className)->findBy($criteria, $orderBy, $limit, $offset);
    }

    public function getRepo(string $className): ObjectRepository
    {
        return $this->em->getRepository($className);
    }

    public function persist($entity)
    {
        $this->em->persist($entity);
    }

    public function flush()
    {
        $this->em->flush();
    }

    public function remove($entity)
    {
        $this->em->remove($entity);
    }

}
