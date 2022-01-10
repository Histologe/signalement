<?php

namespace App\Repository;

use App\Entity\Signalement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Signalement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Signalement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Signalement[]    findAll()
 * @method Signalement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signalement::class);
    }


    // /**
    //  * @return Signalement[] Returns an array of Signalement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    public function findAlls()
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.situations', 'situations')
            ->leftJoin('situations.criteres', 'criteres')
            ->leftJoin('criteres.criticites', 'criticites')
            ->addSelect('situations')
            ->addSelect('criteres')
            ->addSelect('criticites')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatusAndOrCity($status = null, $city = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.affectations', 'affectations')
            ->leftJoin('s.situations', 'situations')
            ->leftJoin('situations.criteres', 'criteres')
            ->leftJoin('criteres.criticites', 'criticites')
            ->addSelect('affectations')
            ->addSelect('situations')
            ->addSelect('criteres')
            ->addSelect('criticites');
        if ($status)
            $qb->andWhere('s.statut = :statut')
                ->setParameter('statut', $status);
        if ($city)
            $qb->andWhere('s.villeOccupant =:city')
                ->setParameter('city', $city);
        return $qb->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCities()
    {
        return $this->createQueryBuilder('s')
            ->select('s.villeOccupant ville')
            ->groupBy('s.villeOccupant')
            ->getQuery()
            ->getSingleColumnResult();
    }


    /*
    public function findOneBySomeField($value): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
