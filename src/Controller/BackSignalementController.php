<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\Suivi;
use App\Entity\User;
use App\Repository\PartenaireRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Positive;

#[Route('/bo/s')]
class BackSignalementController extends AbstractController
{
    #[Positive]
    private function checkAffectation(Signalement $signalement)
    {
        $affectationCurrentUser = $signalement->getAffectations()->filter(function (SignalementUserAffectation $affectation) {
            if ($affectation->getUser() === $this->getUser())
                return $affectation;
        });
        if ($affectationCurrentUser->isEmpty())
            return false;
        return $affectationCurrentUser->first();
    }

    #[Route('/{uuid}', name: 'back_signalement_view')]
    public function viewSignalement(Signalement $signalement, PartenaireRepository $partenaireRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
            return $this->redirectToRoute('back_index');
        $title = 'Administration - Signalement #' . $signalement->getReference();
        $isRefused = $isAccepted = null;

        if ($isAffected = $this->checkAffectation($signalement)) {
            switch ($isAffected->getStatut()) {
                case SignalementUserAffectation::STATUS_ACCEPTED:
                    $isAccepted = $isAffected;
                    break;
                case SignalementUserAffectation::STATUS_REFUSED:
                    $isRefused = $isAffected;
                    break;
            }
        }
        return $this->render('back/signalement/view.html.twig', [
            'title' => $title,
            'isAffected' => $isAffected,
            'isAccepted' => $isAccepted,
            'isRefused' => $isRefused,
            'signalement' => $signalement,
            'partenaires' => $partenaireRepository->findAlls()
        ]);
    }

    #[Route('/s/{id}/suivi/add', name: 'back_signalement_add_suivi', methods: "POST")]
    public function addSuiviSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
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
        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{id}/affectation/{user}/toggle', name: 'back_signalement_toggle_affectation')]
    public function toggleAffectationSignalement(Signalement $signalement, User $user, ManagerRegistry $doctrine): RedirectResponse|JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
            return $this->json(['status' => 'denied'], 400);
        if ($affectation = $doctrine->getRepository(SignalementUserAffectation::class)->findOneBy(['user' => $user, 'signalement' => $signalement])) {
            $doctrine->getManager()->remove($affectation);
        } else {
            $affectation = new SignalementUserAffectation();
            $affectation->setUser($user);
            $affectation->setSignalement($signalement);
            $affectation->setPartenaire($user->getPartenaire());
            $doctrine->getManager()->persist($affectation);
        }
        $doctrine->getManager()->flush();
        return $this->json(['status' => 'success']);
    }

    #[Route('/{id}/affectation/{user}/response', name: 'back_signalement_affectation_response', methods: "GET")]
    public function affectationReturnSignalement(Signalement $signalement, User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_affectation_response', $request->get('_token'))
            && $response = $request->get('signalement-affectation-response')) {
            if (isset($response['accept']))
                $statut = SignalementUserAffectation::STATUS_ACCEPTED;
            else
                $statut = SignalementUserAffectation::STATUS_REFUSED;
            $affectation = $doctrine->getRepository(SignalementUserAffectation::class)->findOneBy(['user' => $user, 'signalement' => $signalement]);
            $affectation->setStatut($statut);
            $affectation->setAnsweredAt(new \DateTimeImmutable());
            $doctrine->getManager()->persist($affectation);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Affectation mise à jour avec succès !');
        } else
            $this->addFlash('error', "Une erreur est survenu lors de l'affectation");
        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{id}/delete', name: 'back_signalement_delete', methods: "POST")]
    public function deleteSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
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