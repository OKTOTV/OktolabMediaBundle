<?php
namespace Oktolab\MediaBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class BaseSeriesRepository extends EntityRepository
{
    public function findActive($series_class, $query_only = false)
    {
        $query = $this->getEntityManager()->createQuery(
                'SELECT s, p FROM '.$series_class.' s LEFT JOIN s.posterframe p WHERE s.isActive = 1'
            );

        if ($query_only) {
            return $query;
        }
        return $query->getResult();
    }
}
 ?>
