<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
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


    public function findAllWithGeoData()
    {
        return $this->createQueryBuilder('s')
            ->select('s.geoloc')
            ->addSelect('s.uuid')
            ->addSelect('s.reference')
            ->where("JSON_EXTRACT(s.geoloc,'$.lat') != ''")
            ->andWhere("JSON_EXTRACT(s.geoloc,'$.lng') != ''")
            ->andWhere('s.statut != 7')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithAffectations($year)
    {
        return $this->createQueryBuilder('s')
            ->where('s.statut != 7')
            ->andWhere('YEAR(s.createdAt) = ' . $year)
            ->leftJoin('s.affectations', 'affectations')
            ->addSelect('affectations', 's')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus()
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(s.id) as count')
            ->addSelect('s.statut');
        $qb->indexBy('s', 's.statut');
        $qb->groupBy('s.statut');
        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByUuid($uuid)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.uuid = :uuid')
            ->setParameter('uuid', $uuid);
        $qb
            ->leftJoin('s.situations', 'situations')
            ->leftJoin('s.affectations', 'affectations')
            ->leftJoin('situations.criteres', 'criteres')
            ->leftJoin('criteres.criticites', 'criticites')
            ->leftJoin('affectations.partenaire', 'partenaire')
            ->addSelect('situations', 'affectations', 'criteres', 'criticites', 'partenaire');
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
            ->select('PARTIAL s.{id,uuid,reference,nomOccupant,prenomOccupant,adresseOccupant,cpOccupant,villeOccupant,scoreCreation,statut,createdAt}')
            ->where('s.statut != :status')
            ->setParameter('status', Signalement::STATUS_ARCHIVED);
        $qb->leftJoin('s.affectations', 'affectations');
        $qb->leftJoin('affectations.partenaire', 'partenaire');
        $qb->leftJoin('partenaire.users', 'user');
        $qb->leftJoin('s.suivis', 'suivis');
        $qb->addSelect('affectations', 'partenaire', 'user','suivis');
        if ($status && $status !== 'all') {
            $qb->andWhere('s.statut = :statut')
                ->setParameter('statut', $status);
        }
        if ($city && $city !== 'all')
            $qb->andWhere('s.villeOccupant =:city')
                ->setParameter('city', $city);
        if ($user)
            $qb->andWhere(':partenaire IN (partenaire)')
                ->setParameter('partenaire', $user->getPartenaire());
        if ($search) {
            if (preg_match('/([0-9]{4})-[0-9]{0,6}/', $search)) {
                $qb->andWhere('s.reference = :search');
                $qb->setParameter('search', $search);
            } else {
                $qb->andWhere('LOWER(s.nomOccupant) LIKE :search 
                OR LOWER(s.prenomOccupant) LIKE :search 
                OR LOWER(s.reference) LIKE :search 
                OR LOWER(s.adresseOccupant) LIKE :search 
                OR LOWER(s.villeOccupant) LIKE :search
                OR LOWER(s.nomProprio) LIKE :search');
                $qb->setParameter('search', "%" . strtolower($search) . "%");
            }
        }
        $qb->orderBy('s.createdAt', 'DESC')
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
            ->getResult();
    }


    public function findOneByCodeForPublic($code): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.codeSuivi = :code')
            ->setParameter('code', $code)
            ->leftJoin('s.suivis','suivis',Join::WITH,'suivis.isPublic = 1')
            ->addSelect('suivis')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

}
