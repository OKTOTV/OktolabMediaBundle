<?php
namespace Oktolab\MediaBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class BaseSeriesRepository extends EntityRepository
{
    public function findActive($series_class, $query_only = false)
    {
        $query = $this->getEntityManager()->createQuery(
                'SELECT s, p FROM '.$series_class.' s
                LEFT JOIN s.posterframe p
                WHERE s.isActive = 1'
            );

        if ($query_only) {
            return $query;
        }
        return $query->getResult();
    }

    public function findByUniqID($series_class, $uniqID, $query_only = false)
    {
        $query = $this->getEntityManager()->createQuery(
                'SELECT s, p FROM '.$series_class.' s
                LEFT JOIN s.posterframe p
                WHERE s.uniqID = :uniqID'
            )->setParameter('uniqID', $uniqID);

        if ($query_only) {
            return $query;
        }

        return $query->getOneOrNullResult();
    }

    public function findOneByStreamkey($series_class, $streamkey)
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT s FROM '.$series_class.' s
            WHERE s.streamkey = :streamkey'
        )->setParameter('streamkey', $streamkey);

        return $query->getOneOrNullResult();
    }
}
 ?>
