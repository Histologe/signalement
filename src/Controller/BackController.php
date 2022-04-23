<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Cloture;
use App\Entity\Config;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Form\ConfigType;
use App\Repository\AffectationRepository;
use App\Repository\ConfigRepository;
use App\Repository\CritereRepository;
use App\Repository\PartenaireRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\NewsActivitiesSinceLastLoginService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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
            'visites' => $request->get('bo-filter-visites') ?? null,
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
        if ($user) {
            $this->req = $affectationRepository->findByStatusAndOrCityForUser($user, $filter);
            $this->iterator = $this->req->getIterator()->getArrayCopy();
            $counts = $affectationRepository->countByStatusForUser($user);
            $signalementsCount = [
                Signalement::STATUS_NEED_VALIDATION => $counts[0] ?? ['count' => 0],
                Signalement::STATUS_ACTIVE => $counts[1] ?? ['count' => 0],
                Signalement::STATUS_CLOSED => ['count' => ($counts[3]['count'] ?? 0) + ($counts[2]['count'] ?? 0)],
            ];
            $signalementsCount['total'] = count($this->req);
            if ($user->getPartenaire()) {
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
            $this->iterator = $this->req->getIterator()->getArrayCopy();
            $signalementsCount = $signalementRepository->countByStatus();
            $criteria = new Criteria();
            $criteria->where(Criteria::expr()->neq('statut', 7));
            $signalementsCount['total'] = $signalementRepository->matching($criteria)->count();
        }
        $signalements = [
            'list' => $this->iterator,
            'total' => count($this->req),
            'page' => (int)$filter['page'],
            'pages' => (int)ceil(count($this->req) / 25),
            'counts' => $signalementsCount
        ];

        if (/*$request->isXmlHttpRequest() && */ $request->get('pagination'))
            return $this->render('back/table_result.html.twig', ['filter' => $filter, 'signalements' => $signalements]);
        $criteres = $critereRepository->findAllList();
//        dd($criteres);
        if ($this->isGranted('ROLE_ADMIN_TERRITOIRE') && $request->get('export') && $this->isCsrfTokenValid('export_token-'.$this->getUser()->getId(), $request->get('_token'))) {
            return $this->export($this->iterator, $em);
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
        $headers = $em->getClassMetadata(Signalement::class)->getFieldNames();
        $csvContent = "";
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
                            $arr[] = $item['titre'] ?? $item['file'];
                        $data[] = '"' . implode(",\r\n", $arr) . '"';
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
                } elseif ($signalement->$method() instanceof \DateTimeImmutable ||$signalement->$method() instanceof \DateTime)
                    $data[] = $signalement->$method()->format('d.m.Y');
                elseif (is_bool($signalement->$method()))
                    $data[] = $signalement->$method() ? 'OUI' : 'NON';
                elseif (!is_array($signalement->$method()) && !($signalement->$method() instanceof ArrayCollection))
                    $data[] = '"' . str_replace(';', '', $signalement->$method()) . '"';
                elseif ($signalement->$method() == "")
                    $data[] = "N/R";
                else
                    $data[] = "[]";
            }

            $situations = $criteres = new ArrayCollection();
            $signalement->getCriticites()->filter(function (Criticite $criticite) use ($situations, $criteres) {
                $labels = ['DANGER', 'MOYEN', 'GRAVE', 'TRES DANGER'];
                $critere = $criticite->getCritere();
                $situation = $criticite->getCritere()->getSituation();
                $critereAndCriticite = $critere->getLabel() . ' (' . $labels[$criticite->getScore()] . ')';
                if (!$situations->contains($situation->getLabel()))
                    $situations->add($situation->getLabel());
                if (!$criteres->contains($critereAndCriticite))
                    $situations->add($critereAndCriticite);
            });
            $data[] = '"' . implode(",\r\n", $situations->toArray()) . '"';
            $data[] = '"' . implode(",\r\n", $criteres->toArray()) . '"';
            $csvContent .= implode(';', $data) . "\r\n";
        }
        array_push($headers, 'situations', 'criteres');
        $csvContent = implode(';', $headers) . "\r\n" . $csvContent;
//        dd($csvContent);
        $response = new Response($csvContent);
        $response->headers->set('Content-Encoding', 'UTF-8');
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=sample.csv');
        return $response;
    }
}
