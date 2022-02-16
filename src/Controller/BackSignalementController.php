<?php

namespace App\Controller;


use App\Entity\Affectation;
use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\Suivi;
use App\Entity\User;
use App\Form\ClotureType;
use App\Form\SignalementType;
use App\Repository\PartenaireRepository;
use App\Repository\SituationRepository;

use App\Service\CriticiteCalculatorService;
use App\Service\NewsActivitiesSinceLastLoginService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/bo/s')]
class BackSignalementController extends AbstractController
{
    private static function sendMailOcupantDeclarant(Signalement $signalement, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator, $type)
    {
        if ($signalement->getMailOccupant())
            $notificationService->send($type, $signalement->getMailOccupant(), [
                'signalement' => $signalement,
                'lien_suivi' => $urlGenerator->generate('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()], 0)
            ]);
        if ($signalement->getMailDeclarant())
            $notificationService->send($type, $signalement->getMailDeclarant(), [
                'signalement' => $signalement,
                'lien_suivi' => $urlGenerator->generate('front_suivi_signalement', ['code' => $signalement->getCodeSuivi()], 0)
            ]);
    }

    #[Positive]
    private function checkAffectation(Signalement $signalement)
    {
        $affectationCurrentUser = $signalement->getAffectations()->filter(function (Affectation $affectation) {
            if ($affectation->getPartenaire() === $this->getUser()->getPartenaire())
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

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/{uuid}', name: 'back_signalement_view')]
    public function viewSignalement($uuid, Request $request, EntityManagerInterface $entityManager, HttpClientInterface $httpClient, PartenaireRepository $partenaireRepository, NewsActivitiesSinceLastLoginService $newsActivitiesSinceLastLoginService): Response
    {
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findByUuid($uuid);
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement))
            return $this->redirectToRoute('back_index');
        $title = 'Administration - Signalement #' . $signalement->getReference();
        $isRefused = $isAccepted = null;
        if ($isAffected = $this->checkAffectation($signalement)) {
            switch ($isAffected->getStatut()) {
                case Affectation::STATUS_ACCEPTED:
                    $isAccepted = $isAffected;
                    break;
                case Affectation::STATUS_REFUSED:
                    $isRefused = $isAffected;
                    break;
            }
        }
        $isClosedForMe = false;
        if ($this->getUser()->getPartenaire()) {
            $clotureCurrentUser = $signalement->getAffectations()->filter(function (Affectation $affectation) {
                if ($affectation->getPartenaire()->getId() === $this->getUser()->getPartenaire()->getId() && $affectation->getStatut() === Affectation::STATUS_CLOSED)
                    return $affectation;
            });
            if (!$clotureCurrentUser->isEmpty())
                $isClosedForMe = $clotureCurrentUser->first();
        }


        $newsActivitiesSinceLastLoginService->update($signalement);

        $clotureForm = $this->createForm(ClotureType::class);
        $clotureForm->handleRequest($request);
        if ($clotureForm->isSubmitted() && $clotureForm->isValid()) {
            $motifCloture = $clotureForm->get('motif')->getData();
            $sujet = $this->getUser()?->getPartenaire()?->getNom();
            if ($clotureForm->get('type')->getData() === 'all') {
                $signalement->setStatut(Signalement::STATUS_CLOSED);
                $signalement->setMotifCloture($motifCloture);
                $sujet = 'tous les partenaires';
                $signalement->getAffectations()->map(function (Affectation $affectation) use ($entityManager) {
                    $affectation->setStatut(Affectation::STATUS_CLOSED);
                    $entityManager->persist($affectation);
                });
            }
            $suivi = new Suivi();
            $suivi->setDescription('Le signalement à été cloturé pour ' . $sujet . ' avec le motif suivant: <br> <strong>' . $motifCloture . '</strong>');
            $suivi->setCreatedBy($this->getUser());
            $signalement->addSuivi($suivi);
            /** @var Affectation $isAffected */
            if ($isAffected) {
                $isAffected->setStatut(Affectation::STATUS_CLOSED);
                $isAffected->setAnsweredAt(new \DateTimeImmutable());
                $isAffected->setMotifCloture($motifCloture);
                $entityManager->persist($isAffected);
            }
            $entityManager->persist($signalement);
            $entityManager->persist($suivi);
            $entityManager->flush();
            $this->addFlash('success', 'Signalement cloturé avec succès !');
            return $this->redirectToRoute('back_index');
        }
        $criticitesArranged = [];
        foreach ($signalement->getCriticites() as $criticite) {
            $criticitesArranged[$criticite->getCritere()->getSituation()->getLabel()][$criticite->getCritere()->getLabel()] = $criticite;
        }

        return $this->render('back/signalement/view.html.twig', [
            'title' => $title,
            'situations' => $criticitesArranged,
            'affectations' => $signalement->getAffectations(),
            'needValidation' => $signalement->getStatut() === Signalement::STATUS_NEED_VALIDATION,
            'isAffected' => $isAffected,
            'isAccepted' => $isAccepted,
            'isClosed' => $signalement->getStatut() === Signalement::STATUS_CLOSED,
            'isClosedForMe' => $isClosedForMe,
            'isRefused' => $isRefused,
            'signalement' => $signalement,
            'partenaires' => $partenaireRepository->findAllOrByInseeIfCommune($signalement->getInseeOccupant()),
            'clotureForm' => $clotureForm->createView()
        ]);
    }

    #[Route('/{uuid}/edit', name: 'back_signalement_edit', methods: ['GET', 'POST'])]
    public function editSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, SituationRepository $situationRepository): Response
    {
        $title = 'Administration - Edition signalement #' . $signalement->getReference();
        $etats = ["Etat moyen", "Mauvais état", "Très mauvais état"];
        $etats_classes = ["moyen", "grave", "tres-grave"];
        $form = $this->createForm(SignalementType::class, $signalement);
        $form->handleRequest($request);
        if ($form->isSubmitted() /*&& $form->isValid()*/) {
            $signalement->setModifiedBy($this->getUser());
            $signalement->setModifiedAt(new \DateTimeImmutable());
            $score = new CriticiteCalculatorService($signalement, $doctrine);
            $signalement->setScoreCreation($score->calculate());
            $data = [];
            $data['situation'] = $form->getExtraData()['situation'];
            foreach ($data['situation'] as $idSituation => $criteres) {
                $situation = $doctrine->getManager()->getRepository(Situation::class)->find($idSituation);
                $signalement->addSituation($situation);
                $data['situation'][$idSituation]['label'] = $situation->getLabel();
                foreach ($criteres as $critere) {
                    foreach ($critere as $idCritere => $criticites) {
                        $critere = $doctrine->getManager()->getRepository(Critere::class)->find($idCritere);
                        $signalement->addCritere($critere);
                        $data['situation'][$idSituation]['critere'][$idCritere]['label'] = $critere->getLabel();
                        $criticite = $doctrine->getManager()->getRepository(Criticite::class)->find($data['situation'][$idSituation]['critere'][$idCritere]['criticite']);
                        $signalement->addCriticite($criticite);
                        $data['situation'][$idSituation]['critere'][$idCritere]['criticite'] = [$criticite->getId() => ['label' => $criticite->getLabel(), 'score' => $criticite->getScore()]];
                    }
                }
            }
            $signalement->setJsonContent($data['situation']);
            $suivi = new Suivi();
            $suivi->setCreatedBy($this->getUser());
            $suivi->setSignalement($signalement);
            $suivi->setIsPublic(false);
            $suivi->setDescription('Modification du signalement par un partenaire');
            $doctrine->getManager()->persist($suivi);
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Signalement modifé avec succés !');
            return $this->json(['response' => 'success_edited']);
        } else if ($form->isSubmitted()) {
            dd($form->getErrors()[0]);
            return $this->json(['response' => 'error']);
        }
        return $this->render('back/signalement/edit.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'signalement' => $signalement,
            'situations' => $situationRepository->findAllWithCritereAndCriticite(),
            'etats' => $etats,
            'etats_classes' => $etats_classes
        ]);
    }

    #[Route('/s/{uuid}/suivi/add', name: 'back_signalement_add_suivi', methods: "POST")]
    public function addSuiviSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator): Response
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
            //TODO: Mail Sendinblue
            if ($suivi->getIsPublic())
                self::sendMailOcupantDeclarant($signalement, $notificationService, $urlGenerator, NotificationService::TYPE_NOUVEAU_SUIVI);
        } else
            $this->addFlash('error', 'Une erreur est survenu lors de la publication');
        return $this->redirect($this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()]) . '#suivis');
    }

    #[Route('/{uuid}/affectation/toggle', name: 'back_signalement_toggle_affectation')]
    public function toggleAffectationSignalement(Signalement $signalement, ManagerRegistry $doctrine, Request $request, PartenaireRepository $partenaireRepository, NotificationService $notificationService): RedirectResponse|JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN_TERRITOIRE') && !$this->checkAffectation($signalement))
            return $this->json(['status' => 'denied'], 400);
        if ($this->isCsrfTokenValid('signalement_affectation_' . $signalement->getId(), $request->get('_token'))) {
            $data = $request->get('signalement-affectation');
            if (isset($data['partenaires'])) {
                $postedPartenaire = $data['partenaires'];
                $alreadyAffectedPartenaire = $signalement->getAffectations()->map(function (Affectation $affectation) {
                    return $affectation->getPartenaire()->getId();
                })->toArray();
                $partenairesToAdd = array_diff($postedPartenaire, $alreadyAffectedPartenaire);
                $partenairesToRemove = array_diff($alreadyAffectedPartenaire, $postedPartenaire);
                foreach ($partenairesToAdd as $partenaireIdToAdd) {
                    $partenaire = $partenaireRepository->find($partenaireIdToAdd);
                    $affectation = new Affectation();
                    $affectation->setSignalement($signalement);
                    $affectation->setPartenaire($partenaire);
                    $affectation->setAffectedBy($this->getUser());
                    $doctrine->getManager()->persist($affectation);
                    //TODO: Mail Sendinblue
                    $partenaire->getUsers()->map(function (User $user) use ($signalement, $notificationService) {
                        if ($user->getIsMailingActive() && $user->getStatut() === User::STATUS_ACTIVE) {
                            $notificationService->send(NotificationService::TYPE_AFFECTATION, $user->getEmail(), [
                                'link' => $this->generateUrl('back_signalement_view', [
                                    'uuid' => $signalement->getUuid()
                                ], 0)
                            ]);
                        }
                    });
                }
                foreach ($partenairesToRemove as $partenaireIdToRemove) {
                    $partenaire = $partenaireRepository->find($partenaireIdToRemove);
                    $signalement->getAffectations()->filter(function (Affectation $affectation) use ($doctrine, $partenaire) {
                        if ($affectation->getPartenaire()->getId() === $partenaire->getId())
                            $doctrine->getManager()->remove($affectation);
                    });
                }
            } else {
                $signalement->getAffectations()->filter(function (Affectation $affectation) use ($doctrine) {
                    $doctrine->getManager()->remove($affectation);
                });
            }

            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Les affectations ont bien été effectuées.');
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
                $titre = $originalFilename . '.' . $file->guessExtension();
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
                array_push($$type, ['file' => $newFilename, 'titre' => $titre, 'user' => $this->getUser()->getId()]);
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
    public function validationResponseSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine, UrlGeneratorInterface $urlGenerator, NotificationService $notificationService): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_TERRITOIRE'))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_validation_response_' . $signalement->getId(), $request->get('_token'))
            && $response = $request->get('signalement-validation-response')) {
            if (isset($response['accept'])) {
                $statut = Signalement::STATUS_ACTIVE;
                $description = 'validé';
                $signalement->setValidatedAt(new \DateTimeImmutable());
                $signalement->setCodeSuivi(md5(uniqid()));
                //TODO: Mail Sendinblue
                self::sendMailOcupantDeclarant($signalement, $notificationService, $urlGenerator, NotificationService::TYPE_SIGNALEMENT_VALIDE);

            } else {
                $statut = Signalement::STATUS_CLOSED;
                $description = 'cloturé car non-valide';
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
                $statut = Affectation::STATUS_ACCEPTED;
            else
                $statut = Affectation::STATUS_REFUSED;
            $affectation = $doctrine->getRepository(Affectation::class)->findOneBy(['partenaire' => $user->getPartenaire(), 'signalement' => $signalement]);
            $affectation->setStatut($statut);
            $affectation->setAnsweredAt(new \DateTimeImmutable());
            $affectation->setAnsweredBy($this->getUser());
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
            foreach ($$type as $k => $v) {
                if ($file === $v['file'])
                    if (file_exists($this->getParameter('uploads_dir') . $file))
                        unlink($this->getParameter('uploads_dir') . $file);
                unset($$type[$k]);
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
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE') && !$this->checkAffectation($signalement) && !$this->isGranted('ROLE_ADMIN_TERRITOIRE'))
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

    #[Route('/{uuid}/export', name: 'back_signalement_export_pdf', methods: "POST")]
    public function exportSignalement(Signalement $signalement, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_USER_PARTENAIRE') && !$this->checkAffectation($signalement) && !$this->isGranted('ROLE_ADMIN_TERRITOIRE'))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('signalement_export_' . $signalement->getId(), $request->get('_token'))) {
            $this->file();
            $this->addFlash('success', 'Signalement supprimé avec succès !');
        } else
            $this->addFlash('error', 'Une erreur est survenu lors de la suppression');
        return $this->redirectToRoute('back_index');
    }
}