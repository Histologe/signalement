<?php

namespace App\Controller;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Entity\User;
use App\Form\SignalementType;
use App\Repository\PartenaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\String\Slugger\SluggerInterface;
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

    private function viewAs($role)
    {
        $token = new UsernamePasswordToken($this->getUser(), 'main', ['ROLE_USER_PARTENAIRE']);
        $this->container->get('security.token_storage')->setToken($token);
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
            'partenaires' => $partenaireRepository->findAllOrByInseeIfCommune($signalement->getInseeOccupant())
        ]);
    }

    #[Route('/{uuid}/edit', name: 'back_signalement_edit', methods: ['GET', 'POST'])]
    public function editSignalement(Signalement $signalement, Request $request, EntityManagerInterface $entityManager): Response
    {
        $title = 'Administration - Edition signalement #' . $signalement->getReference();
        $form = $this->createForm(SignalementType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $signalement->setModifiedBy($this->getUser());
            $signalement->setModifiedAt(new \DateTimeImmutable());
            $suivi = new Suivi();
            $suivi->setCreatedBy($this->getUser());
            $suivi->setSignalement($signalement);
            $suivi->setIsPublic(false);
            $suivi->setDescription('Modification du signalement par un partenaire');
            $entityManager->persist($suivi);
            $entityManager->flush();
            $this->addFlash('success', 'Signalement modifé avec succés !');
            return $this->redirectToRoute('back_signalement_view', [
                'uuid' => $signalement->getUuid()
            ]);
        }
        return $this->render('back/signalement/edit.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'signalement' => $signalement
        ]);
    }

    #[Route('/s/{uuid}/suivi/add', name: 'back_signalement_add_suivi', methods: "POST")]
    public function addSuiviSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_add_suivi_' . $signalement->getId(), $request->get('_token'))
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
        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]) . '#suivis');
    }

    #[Route('/{uuid}/affectation/{user}/toggle', name: 'back_signalement_toggle_affectation')]
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

    #[Route('/s/{uuid}/file/add', name: 'back_signalement_add_file')]
    public function addFileSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger)
    {
        if ($this->isCsrfTokenValid('signalement_add_file_' . $signalement->getId(), $request->get('_token')) && $files = $request->files->get('signalement-add-file')) {
            if (isset($files['documents']))
                $type = 'documents';
            if (isset($files['photos']))
                $type = 'photos';
            $setMethod = 'set' . ucfirst($type);
            $getMethod = 'get' . ucfirst($type);
            $$type = $signalement->$getMethod();
            foreach ($files[$type] as $file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('uploads_dir'),
                    $newFilename
                );
                array_push($$type, $newFilename);
            }
            $signalement->$setMethod($$type);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Envoi de ' . ucfirst($type) . ' effectué avec succès !');
        } else
            $this->addFlash('error', "Une erreur est survenu lors du téléchargement");
        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]) . '#documents');
    }

    #[Route('/{uuid}/affectation/{user}/response', name: 'back_signalement_affectation_response', methods: "GET")]
    public function affectationResponseSignalement(Signalement $signalement, User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_affectation_response_' . $signalement->getId(), $request->get('_token'))
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

    #[Route('/s/{uuid}/file/{type}/{file}/delete', name: 'back_signalement_delete_file')]
    public function deleteFileSignalement(Signalement $signalement, $type, $file, Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger)
    {
        if ($this->isCsrfTokenValid('signalement_delete_file_' . $signalement->getId(), $request->get('_token'))) {
            $setMethod = 'set' . ucfirst($type);
            $getMethod = 'get' . ucfirst($type);
            $$type = $signalement->$getMethod();
            if (($key = array_search($file, $$type)) !== false) {
                unlink($this->getParameter('uploads_dir') . $file);
                unset($$type[$key]);
            }
            $signalement->$setMethod($$type);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            return $this->json(['response' => 'success']);
        } else
            return $this->json(['response' => 'error'], 400);
    }

    #[Route('/{uuid}/delete', name: 'back_signalement_delete', methods: "POST")]
    public function deleteSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_delete_' . $signalement->getId(), $request->get('_token'))) {
            $signalement->setStatut(Signalement::STATUS_ARCHIVED);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Signalement supprimé avec succès !');
        } else
            $this->addFlash('error', 'Une erreur est survenu lors de la suppression');
        return $this->redirectToRoute('back_index');
    }
}