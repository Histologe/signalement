<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Cloture;
use App\Entity\Config;
use App\Entity\Signalement;
use App\Form\ConfigType;
use App\Repository\ConfigRepository;
use App\Repository\SignalementRepository;
use App\Service\NewsActivitiesSinceLastLoginService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bo')]
class BackController extends AbstractController
{
    private $req;

    #[Route('/', name: 'back_index')]
    public function index(SignalementRepository $signalementRepository, Request $request): Response
    {
        $title = 'Administration - Tableau de bord';
        $user = null;
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            $user = $this->getUser();
        $filter = [
            'search' => $request->get('search') ?? null,
            'status' => $request->get('bo-filter-statut') ?? 'all',
            'ville' => $request->get('bo-filter-ville') ?? 'all',
            'page' => $request->get('page') ?? 1,
        ];
        if ($user && $filter['status'] === (string)Signalement::STATUS_CLOSED)
            $filter['status'] = [Signalement::STATUS_CLOSED, Signalement::STATUS_ACTIVE];
        $this->req = $signalementRepository->findByStatusAndOrCityForUser($user, $filter['status'], $filter['ville'], $filter['search'], $filter['page']);
        $this->req = $this->req->getIterator()->getArrayCopy();
        if ($this->getUser()->getPartenaire()) {
            foreach ($this->req as $k => $signalement) {
                $signalement->getAffectations()->filter(function (Affectation $affectation) use ($signalement, $filter, $k) {
                    if ($filter['status'] === [Signalement::STATUS_CLOSED, Signalement::STATUS_ACTIVE] && $affectation->getStatut() === Affectation::STATUS_ACCEPTED)
                        unset($this->req[$k]);
                    elseif ($filter['status'] === (string)Signalement::STATUS_ACTIVE && $affectation->getSignalement()->getStatut() !== Signalement::STATUS_ACTIVE && $affectation->getStatut() !== Affectation::STATUS_ACCEPTED)
                        unset($this->req[$k]);
                    else {
                        if ($affectation->getPartenaire()->getId() === $this->getUser()->getPartenaire()->getId() && $affectation->getStatut() === Affectation::STATUS_WAIT)
                            $signalement->setStatut(Signalement::STATUS_NEED_PARTNER_RESPONSE);
                        if ($affectation->getPartenaire()->getId() === $this->getUser()->getPartenaire()->getId() && ($affectation->getStatut() === Affectation::STATUS_CLOSED || $affectation->getStatut() === Affectation::STATUS_REFUSED))
                            $signalement->setStatut(Signalement::STATUS_CLOSED);
                    }

                });
            }
        }
        $signalements = [
            'list' => $this->req,
            'villes' => $signalementRepository->findCities($user),
            'total' => count($this->req),
            'page' => (int)$filter['page'],
            'pages' => (int)ceil(count($this->req) / 50)
        ];
        $signalements['counts'] = $signalementRepository->countByStatus($user);
        if (/*$request->isXmlHttpRequest() && */ $request->get('pagination'))
            return $this->render('back/table_result.html.twig', ['filter' => $filter, 'signalements' => $signalements]);
        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filter' => $filter,
            'signalements' => $signalements,
        ]);
    }
}
