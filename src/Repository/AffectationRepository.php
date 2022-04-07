<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Affectation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Affectation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Affectation[]    findAll()
 * @method Affectation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affectation::class);
    }

    // /**
    //  * @return Affectation[] Returns an array of Affectation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
    public function countByStatusForUser($user)
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.signalement) as count')
            ->andWhere('a.partenaire = :partenaire')
            ->leftJoin('a.signalement', 's', 'WITH', 's = a.signalement')
            ->andWhere('s.statut != 7')
            ->setParameter('partenaire', $user->getPartenaire())
            ->addSelect('a.statut')
            ->indexBy('a', 'a.statut')
            ->groupBy('a.statut')
            ->getQuery()
            ->getResult();
    }


    public function findByStatusAndOrCityForUser(User|UserInterface $user = null, $status = null, $city = null, $search = null,$partenaire = null, $page = null): Paginator
    {
        $pageSize = 50;
        $page = (int)$page;
        $firstResult = ($page - 1) * $pageSize;
        $qb = $this->createQueryBuilder('a')
            ->select('a,PARTIAL signalement.{id,uuid,reference,nomOccupant,prenomOccupant,adresseOccupant,cpOccupant,villeOccupant,scoreCreation,statut,createdAt,scoreCreation}')
            ->where('signalement.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        $qb->leftJoin('a.signalement', 'signalement');
        $qb->leftJoin('a.partenaire', 'partenaire');
        $qb->leftJoin('partenaire.users', 'user');
        $qb->addSelect('signalement', 'partenaire', 'user');
        if ($status && $status !== 'all') {
            if($status === (string)Signalement::STATUS_CLOSED) {
                $qb->andWhere('a.statut = '.Affectation::STATUS_CLOSED)
                    ->orWhere('a.statut = '.Affectation::STATUS_REFUSED);
            } else if ($status === (string)Signalement::STATUS_ACTIVE)
                $qb->andWhere('a.statut = '.Affectation::STATUS_ACCEPTED);
            else if ($status === (string)Signalement::STATUS_NEED_VALIDATION)
                $qb->andWhere('a.statut = '.Affectation::STATUS_WAIT);
        }
        if ($city && $city !== 'all')
            $qb->andWhere('signalement.villeOccupant =:city')
                ->setParameter('city', $city);
        if ($user)
            $qb->andWhere(':partenaire IN (partenaire)')
                ->setParameter('partenaire', $user->getPartenaire());
        if ( $partenaire && $partenaire !== 'all')
            $qb->andWhere(':partenaire IN (partenaire)')
                ->setParameter('partenaire', $partenaire);
        if ($search) {
            if (preg_match('/([0-9]{4})-[0-9]{0,6}/', $search)) {
                $qb->andWhere('signalement.reference = :search');
                $qb->setParameter('search', $search);
            } else {
                $qb->andWhere('LOWER(signalement.nomOccupant) LIKE :search OR LOWER(signalement.prenomOccupant) LIKE :search OR LOWER(signalement.reference) LIKE :search OR LOWER(signalement.adresseOccupant) LIKE :search OR LOWER(signalement.villeOccupant) LIKE :search');
                $qb->setParameter('search', "%" . strtolower($search) . "%");
            }
        }
        $qb->orderBy('signalement.createdAt', 'DESC')
            ->setFirstResult($firstResult)
            ->setMaxResults($pageSize)
            ->getQuery();

        return new Paginator($qb, true);
    }

    /*
    public function findOneBySomeField($value): ?Affectation
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
