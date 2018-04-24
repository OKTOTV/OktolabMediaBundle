<?php
namespace Oktolab\MediaBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class BaseStreamRepository extends EntityRepository
{
    public function findAllForClass($stream_class, $query_only = false)
    {
        $query = $this->getEntityManager()->createQuery(
                sprintf("SELECT s FROM %s s ", $stream_class)
            );

        if ($query_only) {
            return $query;
        }
        return $query->getResult();
    }
}
 ?>
