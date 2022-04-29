<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class SearchFilterService
{
    private array $filters;
    private Request $request;

    private function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;
        return $this;
    }

    public function setFilters(): SearchFilterService
    {
        $request = $this->getRequest();
        $this->filters = [
            'searchterms' => $request->get('bo-filter-searchterms') ?? null,
            'statuses' => $request->get('bo-filters-statuses') ?? null,
            'cities' => $request->get('bo-filters-cities') ?? null,
            'partners' => $request->get('bo-filters-partners') ?? null,
            'criteres' => $request->get('bo-filters-criteres') ?? null,
            'allocs' => $request->get('bo-filters-allocs') ?? null,
            'housetypes' => $request->get('bo-filters-housetypes') ?? null,
            'declarants' => $request->get('bo-filters-declarants') ?? null,
            'proprios' => $request->get('bo-filters-proprios') ?? null,
            'interventions' => $request->get('bo-filters-interventions') ?? null,
            'avant1949' => $request->get('bo-filters-avant1949') ?? null,
            'enfantsM6' => $request->get('bo-filters-enfantsM6') ?? null,
            'handicaps' => $request->get('bo-filters-handicaps') ?? null,
            'affectations' => $request->get('bo-filters-affectations') ?? null,
            'visites' => $request->get('bo-filters-visites') ?? null,
            'delays' => $request->get('bo-filters-delays') ?? null,
            'scores' => $request->get('bo-filters-scores') ?? null,
            'dates' => $request->get('bo-filters-dates') ?? null,
            'page' => $request->get('page') ?? 1
        ];

        return $this;
    }

    public function getFilters(): ?array
    {
        return $this->filters ?? null;
    }

    public function getFilter(string $filterName): ?string
    {
        return $this->filters[$filterName] ?? null;
    }

    public function setFilter(string $filterName, string $filterValue): void
    {
        $this->filters[$filterName] = $filterValue;
    }

    public function removeFilter(string $filterName): void
    {
        unset($this->filters[$filterName]);
    }

    public function getFiltersAsString(): string
    {
        $filters = [];
        foreach ($this->filters as $filterName => $filterValue) {
            $filters[] = $filterName . '=' . $filterValue;
        }
        return implode('&', $filters);
    }

    public function getFiltersAsArray(): array
    {
        return $this->filters;
    }

    public function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        if (!empty($filters['searchterms'])) {
            if (preg_match('/([0-9]{4})-[0-9]{0,6}/', $filters['search'])) {
                $qb->andWhere('s.reference = :search');
                $qb->setParameter('search', $filters['search']);
            } else {
                $qb->andWhere('LOWER(s.nomOccupant) LIKE :search 
                OR LOWER(s.prenomOccupant) LIKE :search 
                OR LOWER(s.reference) LIKE :search 
                OR LOWER(s.adresseOccupant) LIKE :search 
                OR LOWER(s.villeOccupant) LIKE :search
                OR LOWER(s.nomProprio) LIKE :search');
                $qb->setParameter('search', "%" . strtolower($filters['search']) . "%");
            }
        }
        if (!empty($filters['affectations']) && !!empty($filters['partners'])) {
            $qb->andWhere('a.statut IN (:affectations)')
                ->setParameter('affectations', $filters['affectations']);
        }
        if (!empty($filters['partners'])) {
            if (in_array('AUCUN', $filters['partners']))
                $qb->andWhere('affectations IS NULL');
            else {
                $qb->andWhere('partenaire IN (:partners)');
                if (!empty($filters['affectations']))
                    $qb->andWhere('a.statut IN (:affectations)')->setParameter('affectations', $filters['affectations']);
                $qb->setParameter('partners', $filters['partners']);
            }
        }
        if (!empty($filters['statuses'])) {
            $qb->andWhere('s.statut IN (:statuses)')
                ->setParameter('statuses', $filters['statuses']);
        }
        if (!empty($filters['cities'])) {
            $qb->andWhere('s.villeOccupant IN (:cities)')
                ->setParameter('cities', $filters['cities']);
        }
        if (!empty($filters['visites'])) {
            $qb->andWhere('IF(s.dateVisite IS NOT NULL,1,0) IN (:visites)')
                ->setParameter('visites', $filters['visites']);
        }
        if (!empty($filters['avant1949'])) {
            $qb->andWhere('s.isConstructionAvant1949 IN (:avant1949)')
                ->setParameter('avant1949', $filters['avant1949']);
        }
        if (!empty($filters['handicaps'])) {
            $qb->andWhere('s.isSituationHandicap IN (:handicaps)')
                ->setParameter('handicaps', $filters['handicaps']);
        }
        if (!empty($filters['dates'])) {
            $field = 's.createdAt';
            if (!empty($filters['visites'])) {
                $field = 's.dateVisite';
            }
            if (!empty($filters['dates']['on'])) {
                $qb->andWhere($field . ' >= :date_in')
                    ->setParameter('date_in', $filters['dates']['on']);
            } elseif (!empty($filters['dates']['off'])) {
                $qb->andWhere($field . ' <= :date_off')
                    ->setParameter('date_in', $filters['dates']['off']);
            }
        }
        if (!empty($filters['criteres'])) {
            $qb->andWhere('criteres IN (:criteres)')
                ->setParameter('criteres', $filters['criteres']);
        }
        if (!empty($filters['housetypes'])) {
            $qb->andWhere('s.isLogementSocial IN (:housetypes)')
                ->setParameter('housetypes', $filters['housetypes']);
        }
        if (!empty($filters['allocs'])) {
            $qb->andWhere('s.isAllocataire IN (:allocs)')
                ->setParameter('allocs', $filters['allocs']);
        }
        if (!empty($filters['declarants'])) {
            $qb->andWhere('s.isNotOccupant IN (:declarants)')
                ->setParameter('declarants', $filters['declarants']);
        }
        if (!empty($filters['proprios'])) {
            $qb->andWhere('s.isProprioAverti IN (:proprios)')
                ->setParameter('proprios', $filters['proprios']);
        }
        if (!empty($filters['interventions'])) {
            $qb->andWhere('s.isRefusIntervention IN (:interventions)')
                ->setParameter('interventions', $filters['interventions']);
        }
        if (!empty($filters['delays'])) {
//            dd(max($filters['delays']));
            $qb->andWhere('DATEDIFF(NOW(),suivis.createdAt) >= :delays')
                ->setParameter('delays', $filters['delays']);
        }
        if (!empty($filters['scores'])) {
//            dd(max($filters['delays']));
            if (!empty($filters['scores']['on'])) {
                $qb->andWhere('s.scoreCreation >= :score_on')
                    ->setParameter('score_on', $filters['scores']['on']);
            } elseif (!empty($filters['scores']['off'])) {
                $qb->andWhere('s.scoreCreation <= :score_off')
                    ->setParameter('score_off', $filters['scores']['off']);
            }
        }

        return $qb;
    }


}