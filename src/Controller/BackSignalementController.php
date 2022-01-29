<?php

namespace App\Controller;


use App\Entity\Cloture;
use App\Entity\Partenaire;
use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\Suivi;
use App\Entity\User;
use App\Form\ClotureType;
use App\Form\SignalementType;
use App\Repository\PartenaireRepository;
use App\Repository\UserRepository;
use App\Service\CriticiteCalculatorService;
use App\Service\NewsActivitiesSinceLastLoginService;
use App\Service\NotificationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Exception;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Positive;
use function Symfony\Component\String\u;

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
    public function viewSignalement(Request $request, EntityManagerInterface $entityManager, Signalement $signalement, PartenaireRepository $partenaireRepository, NewsActivitiesSinceLastLoginService $newsActivitiesSinceLastLoginService): Response
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
        $clotureCurrentUser = $signalement->getClotures()->filter(function (Cloture $cloture) {
            if ($cloture->getPartenaire()->getId() === $this->getUser()->getPartenaire()->getId())
                return $cloture;
        });
        if ($clotureCurrentUser->isEmpty())
            $isClosedForMe = false;
        else $isClosedForMe = $clotureCurrentUser->first();

        $newsActivitiesSinceLastLoginService->update($signalement);

        $cloture = new Cloture();
        $clotureForm = $this->createForm(ClotureType::class, $cloture);
        $clotureForm->handleRequest($request);
        if ($clotureForm->isSubmitted() && $clotureForm->isValid()) {
            $sujet = $this->getUser()->getPartenaire()->getNom();
            if ($cloture->getType() === Cloture::TYPE_CLOTURE_ALL) {
                $signalement->setStatut(Signalement::STATUS_CLOSED);
                $sujet = 'tous les partenaires';
            }
            $suivi = new Suivi();
            $suivi->setDescription('Le signalement à été cloturer pour ' . $sujet . ' avec le motif suivant: <br> <strong>' . $cloture->getMotif()->getLabel() . '</strong>');
            $suivi->setCreatedBy($this->getUser());
            $signalement->addSuivi($suivi);
            $cloture->setSignalement($signalement);
            $cloture->setPartenaire($this->getUser()->getPartenaire());
            $cloture->setClosedBy($this->getUser());
            $entityManager->persist($suivi);
            $entityManager->persist($signalement);
            $entityManager->persist($suivi);
            $entityManager->persist($cloture);
            $entityManager->flush();
        }
        return $this->render('back/signalement/view.html.twig', [
            'title' => $title,
            'needValidation' => $signalement->getStatut() === Signalement::STATUS_NEED_VALIDATION,
            'isAffected' => $isAffected,
            'isAccepted' => $isAccepted,
            'isInvalid' => $signalement->getStatut() === Signalement::STATUS_INVALID,
            'isClosed' => $signalement->getStatut() === Signalement::STATUS_CLOSED,
            'isClosedForMe' => $isClosedForMe,
            'isRefused' => $isRefused,
            'signalement' => $signalement,
            'partenaires' => $partenaireRepository->findAllOrByInseeIfCommune($signalement->getInseeOccupant()),
            'clotureForm' => $clotureForm->createView()
        ]);
    }

    #[Route('/{uuid}/edit', name: 'back_signalement_edit', methods: ['GET', 'POST'])]
    public function editSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        $title = 'Administration - Edition signalement #' . $signalement->getReference();
        $form = $this->createForm(SignalementType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $signalement->setModifiedBy($this->getUser());
            $signalement->setModifiedAt(new \DateTimeImmutable());
            $score = new CriticiteCalculatorService($signalement, $doctrine);
            $signalement->setScoreCreation($score->calculate());
            $suivi = new Suivi();
            $suivi->setCreatedBy($this->getUser());
            $suivi->setSignalement($signalement);
            $suivi->setIsPublic(false);
            $suivi->setDescription('Modification du signalement par un partenaire');
            $doctrine->getManager()->persist($suivi);
            $doctrine->getManager()->flush();
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

    #[Route('/{uuid}/affectation/toggle', name: 'back_signalement_toggle_affectation')]
    public function toggleAffectationSignalement(Signalement $signalement, ManagerRegistry $doctrine, Request $request, UserRepository $userRepository, NotificationService $notificationService): RedirectResponse|JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
            return $this->json(['status' => 'denied'], 400);
        if ($this->isCsrfTokenValid('signalement_affectation_' . $signalement->getId(), $request->get('_token'))) {
            $data = $request->get('signalement-affectation');
            if (isset($data['users'])) {
                $postedUsers = $data['users'];
                $alreadyAffectedUsers = $signalement->getAffectations()->map(function (SignalementUserAffectation $affectation) {
                    return $affectation->getUser()->getId();
                })->toArray();
                $usersToAdd = array_diff($postedUsers, $alreadyAffectedUsers);
                $usersToRemove = array_diff($alreadyAffectedUsers, $postedUsers);
                foreach ($usersToAdd as $userIdToAdd) {
                    $user = $userRepository->find($userIdToAdd);
                    if ($user->getIsGenerique()) {
                        $user->getPartenaire()->getUsers()->map(function (User $user) use ($signalement, $doctrine) {
                            $affectation = new SignalementUserAffectation();
                            $affectation->setUser($user);
                            $affectation->setSignalement($signalement);
                            $affectation->setPartenaire($user->getPartenaire());
                            $doctrine->getManager()->persist($affectation);
                        });
                    } else {
                        $affectation = new SignalementUserAffectation();
                        $affectation->setUser($user);
                        $affectation->setSignalement($signalement);
                        $affectation->setPartenaire($user->getPartenaire());
                        $doctrine->getManager()->persist($affectation);
                    }
                }
                foreach ($usersToRemove as $userIdToRemove) {
                    $user = $userRepository->find($userIdToRemove);
                    $signalement->getAffectations()->filter(function (SignalementUserAffectation $affectation) use ($doctrine, $user) {
                        if ($affectation->getUser()->getId() === $user->getId())
                            $doctrine->getManager()->remove($affectation);
                    });
                }
            } else {
                $signalement->getAffectations()->filter(function (SignalementUserAffectation $affectation) use ($doctrine) {
                    $doctrine->getManager()->remove($affectation);
                });
            }

            $doctrine->getManager()->flush();
            return $this->json(['status' => 'success']);
        }
        return $this->json(['status' => 'denied'], 400);
    }

    #[Route('/s/{uuid}/file/add', name: 'back_signalement_add_file')]
    public function addFileSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): RedirectResponse
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
                try {
                    $file->move(
                        $this->getParameter('uploads_dir'),
                        $newFilename
                    );
                } catch (Exception $e) {
                    dd($e);
                }
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

    #[Route('/{uuid}/validation/response', name: 'back_signalement_validation_response', methods: "GET")]
    public function validationResponseSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, NotificationService $notificationService): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_validation_response_' . $signalement->getId(), $request->get('_token'))
            && $response = $request->get('signalement-validation-response')) {
            if (isset($response['accept'])) {
                $statut = Signalement::STATUS_NEW;
                $description = 'validé';
                $signalement->setCodeSuivi(md5(uniqid()));
            } else {
                $statut = Signalement::STATUS_IS_INVALID;
                $description = 'non-valide';
            }
            $suivi = new Suivi();
            $suivi->setSignalement($signalement);
            $suivi->setDescription('Signalement ' . $description);
            $suivi->setCreatedBy($this->getUser());
            $suivi->setIsPublic(true);
            $signalement->setStatut($statut);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->persist($suivi);
            $doctrine->getManager()->flush();

            $this->addFlash('success', 'Statut du signalement mis à jour avec succés !');
        } else
            $this->addFlash('error', "Une erreur est survenue...");
        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
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