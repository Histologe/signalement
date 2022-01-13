<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\SignalementUserAccept;
use App\Entity\SignalementUserAffectation;
use App\Entity\SignalementUserRefus;
use App\Entity\Suivi;
use App\Entity\User;
use App\Repository\PartenaireRepository;
use App\Repository\SignalementRepository;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackController extends AbstractController
{
    #[Route('/', name: 'back_index')]
    public function index(SignalementRepository $signalementRepository, Request $request): Response
    {
        $title = 'Administration';
        $user = null;
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            $user = $this->getUser();
        $signalements = [
            'list' => $signalementRepository->findByStatusAndOrCityForUser($user, $request->get('bo-filter-statut') ?? Signalement::STATUS_NEW, $request->get('bo-filter-ville') ?? 'all'),
            'counts' => [
                Signalement::STATUS_NEW => $signalementRepository->count(['statut' => Signalement::STATUS_NEW]),
                Signalement::STATUS_AWAIT => $signalementRepository->count(['statut' => Signalement::STATUS_AWAIT]),
                Signalement::STATUS_NEED_REVIEW => $signalementRepository->count(['statut' => Signalement::STATUS_NEED_REVIEW]),
                Signalement::STATUS_CLOSED => $signalementRepository->count(['statut' => Signalement::STATUS_CLOSED]),
            ],
            'villes' => $signalementRepository->findCities()
        ];
        return $this->render('back/index.html.twig', [
            'title' => $title,
            'signalements' => $signalements,
        ]);
    }

    #[Route('/s/{uuid}', name: 'back_signalement_view')]
    public function viewSignalement(Signalement $signalement, PartenaireRepository $partenaireRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$signalement->getAffectations()->contains($this->getUser()))
            return $this->redirectToRoute('back_index');
        $title = 'Administration - Signalement #' . $signalement->getReference();
        return $this->render('back/signalement/view.html.twig', [
            'title' => $title,
            'signalement' => $signalement,
            'partenaires' => $partenaireRepository->findAlls()
        ]);
    }

    #[Route('/s/{id}/suivi/add', name: 'back_signalement_add_suivi', methods: "POST")]
    public function addSuiviSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$signalement->getAffectations()->contains($this->getUser()))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_add_suivi', $request->get('_token'))
            && $form = $request->get('signalement-add-suivi')) {
            $suivi = new Suivi();
            $suivi->setDescription($form['content']);
            $suivi->setIsPublic($form['isPublic']);
            $suivi->setSignalement($signalement);
            $suivi->setCreatedBy($this->getUser());
            $doctrine->getManager()->persist($suivi);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Suivi publié avec succès !');
        } else
            $this->addFlash('error', 'Une erreur est survenu lors de la publication');
        return $this->redirectToRoute('back_signalement_view', ['id' => $signalement->getId()]);
    }

    #[Route('/s/{id}/affectation/{user}/toggle', name: 'back_signalement_toggle_affectation')]
    public function toggleAffectationSignalement(Signalement $signalement, User $user, ManagerRegistry $doctrine): RedirectResponse|JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$signalement->getAffectations()->contains($this->getUser()))
            return $this->json(['status' => 'denied'], 400);
        if ($affectation =$doctrine->getRepository(SignalementUserAffectation::class)->findOneBy(['user'=>$user,'signalement'=>$signalement])) {
            $doctrine->getManager()->remove($affectation);
        } else {
            $affectation = new SignalementUserAffectation();
            $affectation->setUser($user);
            $affectation->setSignalement($signalement);
            $doctrine->getManager()->persist($affectation);
        }
        $doctrine->getManager()->flush();
        return $this->json(['status' => 'success']);
    }

    #[Route('/s/{id}/affectation/{user}/response', name: 'back_signalement_affectation_response', methods: "GET")]
    public function affectationReturnSignalement(Signalement $signalement,User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$signalement->getAffectations()->contains($this->getUser()))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_affectation_response', $request->get('_token'))
            && $response = $request->get('signalement-affectation-response')) {
            if(isset($response['accept']))
                $class = SignalementUserAccept::class;
            else
                $class = SignalementUserRefus::class;
            if ($acceptation = $doctrine->getRepository($class)->findOneBy(['user'=>$user,'signalement'=>$signalement])) {
                $doctrine->getManager()->remove($acceptation);
            } else {
                $acceptation = new $class;
                $acceptation->setUser($user);
                $acceptation->setSignalement($signalement);
                $doctrine->getManager()->persist($acceptation);
            }
            $affectation = $doctrine->getRepository(SignalementUserAffectation::class)->findOneBy(['user'=>$user,'signalement'=>$signalement]);
            $doctrine->getManager()->remove($affectation);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Affectation mise à jour avec succès !');
        } else
            $this->addFlash('error', "Une erreur est survenu lors de l'affectation");
        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/s/{id}/delete', name: 'back_signalement_delete', methods: "POST")]
    public function deleteSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$signalement->getAffectations()->contains($this->getUser()))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_delete', $request->get('_token'))) {
            $signalement->setStatut(Signalement::STATUS_ARCHIVED);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Signalement supprimé avec succès !');
        } else
            $this->addFlash('error', 'Une erreur est survenu lors de la suppression');
        return $this->redirectToRoute('back_index');
    }
}
