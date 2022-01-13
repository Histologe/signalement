<?php

namespace App\Repository;

use App\Entity\SignalementUserAccept;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SignalementUserAccept|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignalementUserAccept|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignalementUserAccept[]    findAll()
 * @method SignalementUserAccept[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementUserAcceptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignalementUserAccept::class);
    }

    // /**
    //  * @return SignalementUserAccept[] Returns an array of SignalementUserAccept objects
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
    public function findOneBySomeField($value): ?SignalementUserAccept
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
