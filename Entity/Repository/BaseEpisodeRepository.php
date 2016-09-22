<?php
namespace Oktolab\MediaBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class BaseEpisodeRepository extends EntityRepository
{
    public function findActive($episode_class, $query_only = false)
    {
        $query = $this->getEntityManager()->createQuery(
                'SELECT e, v, p FROM '.$episode_class.' e LEFT JOIN e.posterframe p LEFT JOIN e.video v WHERE e.isActive = 1'
            );

        if ($query_only) {
            return $query;
        }
        return $query->getResult();
    }

    public function findByUniqID($episode_class, $uniqID, $query_only = false)
    {
        $query = $this->getEntityManager()->createQuery(
                'SELECT e, v, p FROM '.$episode_class.' e LEFT JOIN e.posterframe p LEFT JOIN e.video v WHERE e.uniqID = :uniqID'
            );
        $query->setParameter('uniqID', $uniqID);

        if ($query_only) {
            return $query;
        }
        return $query->getOneOrNullResult();
    }
}
 ?>
