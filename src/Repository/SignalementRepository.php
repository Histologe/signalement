<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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


    /* public function findAlls()
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
     }*/

    public function findAllWithGeoData()
    {
        return $this->createQueryBuilder('s')
            ->select('s.geoloc')
            ->addSelect('s.uuid')
            ->addSelect('s.reference')
            ->where('s.geoloc IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus($user)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id) as count')
            ->addSelect('s.statut');
        if ($user)
            $qb->leftJoin('s.affectations', 'a', 'WITH', 'a.user = :user')
                ->setParameter('user', $user);
        return $qb->indexBy('s', 's.statut')
            ->groupBy('s.statut')
            ->getQuery()
            ->getResult();
    }

    public function findByStatusAndOrCityForUser(User|UserInterface $user = null, $status = null, $city = null, $search = null, $page = null): Paginator
    {
        $pageSize = 50;
        $firstResult = ($page - 1) * $pageSize;
        $qb = $this->createQueryBuilder('s')
            ->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        $qb->leftJoin('s.affectations', 'affectations')
            ->leftJoin('s.affectations', 'awaitBy', Expr\Join::WITH, 'affectations.statut = ' . SignalementUserAffectation::STATUS_AWAIT)
            ->leftJoin('s.affectations', 'acceptedBy', Expr\Join::WITH, 'affectations.statut = ' . SignalementUserAffectation::STATUS_ACCEPTED)
            ->leftJoin('s.affectations', 'refusedBy', Expr\Join::WITH, 'affectations.statut = ' . SignalementUserAffectation::STATUS_REFUSED);
        $qb->leftJoin('s.situations', 'situations')
            ->leftJoin('affectations.partenaire', 'partenaires')
            ->leftJoin('partenaires.users', 'partenaires_users')
            ->leftJoin('affectations.user', 'user')
            ->leftJoin('situations.criteres', 'criteres')
            ->leftJoin('criteres.criticites', 'criticites')
            ->addSelect('affectations')
            ->addSelect('partenaires')
            ->addSelect('partenaires_users')
            ->addSelect('user')
            ->addSelect('refusedBy')
            ->addSelect('acceptedBy')
            ->addSelect('situations')
            ->addSelect('criteres')
            ->addSelect('criticites');
        if ($status && $status !== 'all')
            $qb->andWhere('s.statut = :statut')
                ->setParameter('statut', $status);
        if ($city && $city !== 'all')
            $qb->andWhere('s.villeOccupant =:city')
                ->setParameter('city', $city);
        if ($user)
            $qb->andWhere('user = :user')
                ->setParameter('user', $user);
        if ($search)
            $qb->andWhere('LOWER(s.nomOccupant) LIKE :search OR LOWER(s.prenomOccupant) LIKE :search OR LOWER(s.reference) LIKE :search')
                ->setParameter('search', "%" . strtolower($search) . "%");
        $qb->orderBy('s.id', 'DESC')
            ->setFirstResult($firstResult)
            ->setMaxResults($pageSize)
            ->getQuery();

        return new Paginator($qb, true);
    }


    public function findCities($user = null): array|int|string
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.villeOccupant ville')
            ->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        if ($user)
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.user', 'user')
                ->andWhere('user = :user')
                ->setParameter('user', $user);
        return $qb->groupBy('s.villeOccupant')
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
