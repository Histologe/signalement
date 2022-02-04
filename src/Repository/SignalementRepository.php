<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use function Sodium\add;

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
        $qb = $this->createQueryBuilder('s');
        if(!$user){
            $qb->select('COUNT(s.id) as count')
                ->addSelect('s.statut');
        } else {
            $qb->leftJoin('s.affectations','a','WITH',':partenaire = a.partenaire')
                ->setParameter('partenaire',$user->getPartenaire())
                ->select('COUNT(a.signalement) as count')
                ->addSelect('s.statut');;
        }
        return $qb->indexBy('s', 's.statut')
            ->groupBy('s.statut')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByUuid($uuid)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.uuid = :uuid')
            ->setParameter('uuid',$uuid);
        $qb
            ->leftJoin('s.situations','situations')
            ->leftJoin('s.affectations','affectations')
            ->leftJoin('situations.criteres','criteres')
            ->leftJoin('criteres.criticites','criticites')
            ->leftJoin('affectations.partenaire','partenaire')
            ->addSelect('situations','affectations','criteres','criticites','partenaire');
        /*$qb->leftJoin('s.situations','situations');
        $qb->leftJoin('situations.criteres','criteres');
        $qb->leftJoin('criteres.criticites','criticites');
        $qb->leftJoin('s.affectations','affectations');
        $qb->leftJoin('affectations.partenaire','partenaire');
        $qb->leftJoin('partenaire.users','user');
        $qb->leftJoin('suivis.createdBy','createdBy');
        $qb->addSelect('affectations','partenaire','user','situations','criteres','criticites','suivis','createdBy');*/
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByStatusAndOrCityForUser(User|UserInterface $user = null, $status = null, $city = null, $search = null, $page = null): Paginator
    {
        $pageSize = 50;
        $firstResult = ($page - 1) * $pageSize;
        $qb = $this->createQueryBuilder('s')
            ->select('PARTIAL s.{id,uuid,reference,nomOccupant,prenomOccupant,adresseOccupant,cpOccupant,villeOccupant,scoreCreation,statut}')
            ->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        $qb->leftJoin('s.affectations','affectations');
        $qb->leftJoin('affectations.partenaire','partenaire');
        $qb->leftJoin('partenaire.users','user');
        $qb->addSelect('affectations','partenaire','user');
        if ($status && $status !== 'all')
            $qb->andWhere('s.statut = :statut')
                ->setParameter('statut', $status);
        if ($city && $city !== 'all')
            $qb->andWhere('s.villeOccupant =:city')
                ->setParameter('city', $city);
        if ($user)
            $qb->andWhere('partenaire = :partenaire')
                ->setParameter('partenaire', $user->getPartenaire());
        if ($search)
            $qb->andWhere('LOWER(s.nomOccupant) LIKE :search OR LOWER(s.prenomOccupant) LIKE :search OR LOWER(s.reference) LIKE :search OR LOWER(s.adresseOccupant) LIKE :search OR LOWER(s.villeOccupant) LIKE :search')
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
                ->leftJoin('affectations.partenaire', 'partenaire')
                ->andWhere('partenaire = :partenaire')
                ->setParameter('partenaire', $user->getPArtenaire());
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
