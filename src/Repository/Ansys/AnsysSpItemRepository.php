<?php

namespace App\Repository\Ansys;

use App\Repository\AbstractRepository;

class AnsysSpItemRepository extends AbstractRepository
{
    public function getAllAsArray()
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('m');
        $query = $qb->getQuery();
        return $query->getArrayResult();
    }

}
