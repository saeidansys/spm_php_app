<?php

namespace App\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;

abstract class AbstractRepository extends EntityRepository
{

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function persist($entity): AbstractRepository
    {
        $this->_em->persist($entity);
        return $this;
    }

    public function flush($entity = null): AbstractRepository
    {
        $this->_em->flush($entity);
        return $this;
    }

}
