<?php

namespace App\Repository;

use App\Entity\SignalementUserRefus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SignalementUserRefus|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignalementUserRefus|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignalementUserRefus[]    findAll()
 * @method SignalementUserRefus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementUserRefusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignalementUserRefus::class);
    }

    // /**
    //  * @return SignalementUserRefus[] Returns an array of SignalementUserRefus objects
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

    /*
    public function findOneBySomeField($value): ?SignalementUserRefus
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
