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


    public function findByStatusAndOrCityForUser(User|UserInterface $user = null,array $options): Paginator
    {
        $pageSize = 50;
        $page = (int)$options['page'];
        $firstResult = ($page - 1) * $pageSize;
        $qb = $this->createQueryBuilder('a')
            ->select('PARTIAL s.{id,uuid,reference,nomOccupant,prenomOccupant,adresseOccupant,cpOccupant,villeOccupant,scoreCreation,statut,createdAt,scoreCreation}')
            ->addSelect('a')
            ->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        $qb->leftJoin('a.signalement', 's');
        $qb->leftJoin('a.partenaire', 'partenaire');
        $qb->leftJoin('s.affectations', 'affectations');
        $qb->leftJoin('s.suivis', 'suivis');
        $qb->leftJoin('s.criteres', 'criteres');
        $qb->leftJoin('suivis.createdBy', 'createdBy');
        $qb->addSelect('affectations', 'suivis', 'createdBy');
        $stat = $statOr = null;
        if ($options['statuses']) {
            foreach ($options['statuses'] as $k=>$statu) {
                if ($statu === (string)Signalement::STATUS_CLOSED) {
                    $options['statuses'][$k] = Affectation::STATUS_CLOSED;
                    $options['statuses'][count($options['statuses'])] = Affectation::STATUS_REFUSED;
                } else if ($statu === (string)Signalement::STATUS_ACTIVE)
                    $options['statuses'][$k] = Affectation::STATUS_ACCEPTED;
                else if ($statu === (string)Signalement::STATUS_NEED_VALIDATION)
                    $options['statuses'][$k] = Affectation::STATUS_WAIT;
            }
            $qb->andWhere('a.statut IN (:statuses)');
            if ($statOr)
                $qb->orWhere('a.statut IN (:statuses)');
            $qb->setParameter('statuses', $options['statuses']);
        }
        if ($options['cities']) {
            $qb->andWhere('s.villeOccupant IN (:cities)')
                ->setParameter('cities', $options['cities']);
        }
        if (isset($options['dates'])) {
            if (isset($options['dates']['on'])) {
                $qb->andWhere('s.createdAt >= :date_in')
                    ->setParameter('date_in', $options['dates']['on']);
            } elseif (isset($options['dates']['off'])) {
                $qb->andWhere('s.createdAt <= :date_off')
                    ->setParameter('date_in', $options['dates']['off']);
            }
        }
        if (isset($options['criteres'])) {
            $qb->andWhere('criteres IN (:criteres)')
                ->setParameter('criteres', $options['criteres']);
        }
        if (isset($options['housetypes'])) {
            $qb->andWhere('s.isLogementSocial IN (:housetypes)')
                ->setParameter('housetypes', $options['housetypes']);
        }
        if (isset($options['allocs'])) {
            $qb->andWhere('s.isAllocataire IN (:allocs)')
                ->setParameter('allocs', $options['allocs']);
        }
        if (isset($options['declarants'])) {
            $qb->andWhere('s.isNotOccupant IN (:declarants)')
                ->setParameter('declarants', $options['declarants']);
        }
        if (isset($options['proprios'])) {
            $qb->andWhere('s.isProprioAverti IN (:proprios)')
                ->setParameter('proprios', $options['proprios']);
        }
        if (isset($options['interventions'])) {
            $qb->andWhere('s.isRefusIntervention IN (:interventions)')
                ->setParameter('interventions', $options['interventions']);
        }
        if (isset($options['visites'])) {
            $qb->andWhere('IF(s.dateVisite IS NOT NULL,1,0) IN (:visites)')
                ->setParameter('visites', $options['visites']);
        }
        if ($user)
            $qb->andWhere(':partenaire IN (partenaire)')
                ->setParameter('partenaire', $user->getPartenaire());
        if ($options['search']) {
            if (preg_match('/([0-9]{4})-[0-9]{0,6}/', $options['search'])) {
                $qb->andWhere('s.reference = :search');
                $qb->setParameter('search', $options['search']);
            } else {
                $qb->andWhere('LOWER(s.nomOccupant) LIKE :search OR LOWER(s.prenomOccupant) LIKE :search OR LOWER(s.reference) LIKE :search OR LOWER(s.adresseOccupant) LIKE :search OR LOWER(s.villeOccupant) LIKE :search');
                $qb->setParameter('search', "%" . strtolower($options['search']) . "%");
            }
        }
        $qb->orderBy('s.createdAt', 'DESC')
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
