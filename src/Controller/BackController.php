<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Repository\AffectationRepository;
use App\Repository\CritereRepository;
use App\Repository\PartenaireRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackController extends AbstractController
{
    private $req;
    private $iterator;

    private function setFilters($request)
    {
        return [
            'search' => $request->get('search') ?? null,
            'statuses' => $request->get('bo-filter-statut') ?? null,
            'cities' => $request->get('bo-filter-ville') ?? null,
            'partners' => $request->get('bo-filter-partenaires') ?? null,
            'criteres' => $request->get('bo-filter-criteres') ?? null,
            'allocs' => $request->get('bo-filter-allocs') ?? null,
            'housetypes' => $request->get('bo-filter-housetypes') ?? null,
            'declarants' => $request->get('bo-filter-declarants') ?? null,
            'proprios' => $request->get('bo-filter-proprios') ?? null,
            'interventions' => $request->get('bo-filter-interventions') ?? null,
            'avant1949' => $request->get('bo-filter-avant1949') ?? null,
            'enfantsM6' => $request->get('bo-filter-enfantsM6') ?? null,
            'handicaps' => $request->get('bo-filter-handicaps') ?? null,
            'affectations' => $request->get('bo-filter-affectations') ?? null,
            'visites' => $request->get('bo-filter-visites') ?? null,
            'delays' => $request->get('bo-filter-delays') ?? null,
            'scores' => $request->get('bo-filter-scores') ?? null,
            'dates' => $request->get('bo-filter-dates') ?? null,
            'page' => $request->get('page') ?? 1,
        ];
    }

    #[Route('/', name: 'back_index')]
    public function index(EntityManagerInterface $em,
                          CritereRepository      $critereRepository,
                          UserRepository         $userRepository,
                          SignalementRepository  $signalementRepository,
                          Request                $request,
                          AffectationRepository  $affectationRepository,
                          PartenaireRepository   $partenaireRepository): Response
    {
        $title = 'Administration - Tableau de bord';
        $user = null;
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            $user = $this->getUser();
        $filter = $this->setFilters($request);
        if ($user || $filter['partners']) {
            $this->req = $affectationRepository->findByStatusAndOrCityForUser($user, $filter);
            $this->iterator = $this->req->getIterator()->getArrayCopy();
            if ($user && $user->getPartenaire()) {
                $counts = $affectationRepository->countByStatusForUser($user);
                $signalementsCount = [
                    Signalement::STATUS_NEED_VALIDATION => $counts[0] ?? ['count' => 0],
                    Signalement::STATUS_ACTIVE => $counts[1] ?? ['count' => 0],
                    Signalement::STATUS_CLOSED => ['count' => ($counts[3]['count'] ?? 0) + ($counts[2]['count'] ?? 0)],
                ];
                $signalementsCount['total'] = count($this->req);
                $status = [
                    Affectation::STATUS_WAIT => Signalement::STATUS_NEED_VALIDATION,
                    Affectation::STATUS_ACCEPTED => Signalement::STATUS_ACTIVE,
                    Affectation::STATUS_CLOSED => Signalement::STATUS_CLOSED,
                    Affectation::STATUS_REFUSED => Signalement::STATUS_CLOSED,
                ];
                foreach ($this->iterator as $item)
                    $item->getSignalement()->setStatut((int)$status[$item->getStatut()]);
            }
        } else {
            $this->req = $signalementRepository->findByStatusAndOrCityForUser($user, $filter, $request->get('export'));
            $signalementsCount = $signalementRepository->countByStatus();
        }
        if(!$user){
            $criteria = new Criteria();
            $criteria->where(Criteria::expr()->neq('statut', 7));
            $signalementsCount['total'] = $signalementRepository->matching($criteria)->count();
        }
        $signalements = [
            'list' => $this->req,
            'total' => count($this->req),
            'page' => (int)$filter['page'],
            'pages' => (int)ceil(count($this->req) / 50),
            'counts' => $signalementsCount
        ];

        if (/*$request->isXmlHttpRequest() && */ $request->get('pagination'))
            return $this->stream('back/table_result.html.twig', ['filter' => $filter, 'signalements' => $signalements]);
        $criteres = $critereRepository->findAllList();
//        dd($criteres);
        if ($this->isGranted('ROLE_ADMIN_TERRITOIRE') && $request->get('export') && $this->isCsrfTokenValid('export_token_' . $this->getUser()->getId(), $request->get('_token'))) {
            return $this->export($this->req->getIterator()->getArrayCopy(), $em);
        }
        $users = [
            'active' => $userRepository->count(['statut' => 1]),
            'inactive' => $userRepository->count(['statut' => 0]),
        ];
        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filter' => $filter,
            'cities' => $signalementRepository->findCities($user),
            'partenaires' => $partenaireRepository->findAllList(),
            'signalements' => $signalements,
            'users' => $users,
            'criteres' => $criteres
        ]);
    }

    private function export(array $signalements, EntityManagerInterface $em): Response
    {
        $tmpFileName = (new Filesystem())->tempnam(sys_get_temp_dir(), 'sb_');
        $tmpFile = fopen($tmpFileName, 'wb+');
        $headers = $em->getClassMetadata(Signalement::class)->getFieldNames();
//        $csvContent = implode(';', $headers) . "\r\n" . $csvContent;
        fputcsv($tmpFile, array_merge($headers, ['situations', 'criteres']), ';');
        foreach ($signalements as $signalement) {
            $data = [];
            foreach ($headers as $header) {

                $method = 'get' . ucfirst($header);
                if ($header === "documents" || $header === "photos") {
                    $items = $signalement->$method();
                    if (!$items)
                        $data[] = "SANS";
                    else {
                        $arr = [];
                        foreach ($items as $item)
                            $arr[] = $item['titre'] ?? $item['file'] ?? $item;
                        $data[] = implode(",\r\n", $arr);
                    }
                } elseif ($header === "statut") {
                    $statut = match ($signalement->$method()) {
                        Signalement::STATUS_NEED_VALIDATION => 'A VALIDER',
                        Signalement::STATUS_ACTIVE => 'EN COURS',
                        Signalement::STATUS_CLOSED => 'CLOS',
                        Signalement::STATUS_REFUSED => 'REFUSE',
                        default => $signalement->$method(),
                    };
                    $data[] = $statut;
                } elseif ($header === "geoloc" && !empty($signalement->$method()['lat']) && !empty($signalement->$method()['lng'])) {
                    $data[] = "LAT: " . $signalement->$method()['lat'] . ' LNG: ' . $signalement->$method()['lng'];
                } elseif ($signalement->$method() instanceof \DateTimeImmutable || $signalement->$method() instanceof \DateTime)
                    $data[] = $signalement->$method()->format('d.m.Y');
                elseif (is_bool($signalement->$method()))
                    $data[] = $signalement->$method() ? 'OUI' : 'NON';
                elseif (!is_array($signalement->$method()) && !($signalement->$method() instanceof ArrayCollection))
                    $data[] = str_replace(';', '', $signalement->$method());
                elseif ($signalement->$method() == "")
                    $data[] = "N/R";
                else
                    $data[] = "[]";
            }
            $situations = $criteres = new ArrayCollection();
            $signalement->getCriticites()->filter(function (Criticite $criticite) use ($situations, $criteres) {
                $labels = ['DANGER', 'MOYEN', 'GRAVE', 'TRES GRAVE'];
                $critere = $criticite->getCritere();
                $situation = $criticite->getCritere()->getSituation();
                $critereAndCriticite = $critere->getLabel() . ' (' . $labels[$criticite->getScore()] . ')';
                if (!$situations->contains($situation->getLabel()))
                    $situations->add($situation->getLabel());
                if (!$criteres->contains($critereAndCriticite))
                    $situations->add($critereAndCriticite);
            });
            $data[] = implode(",\r\n", $situations->toArray());
            $data[] = implode(",\r\n", $criteres->toArray());
            fputcsv($tmpFile, $data, ';');
        }
        fclose($tmpFile);
        $response = $this->file($tmpFileName, 'dynamic-csv-file.csv');
        $response->headers->set('Content-type', 'application/csv');
        return $response;
    }
}
