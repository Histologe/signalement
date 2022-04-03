<?php

namespace App\Controller;

use App\Entity\Critere;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Entity\Situation;
use App\Entity\User;
use App\Form\SignalementType;
use App\Repository\SignalementRepository;
use App\Repository\SituationRepository;
use App\Service\CriticiteCalculatorService;
use App\Service\NotificationService;
use App\Service\UploadHandlerService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/')]
class FrontSignalementController extends AbstractController
{
    #[Route('/signalement', name: 'front_signalement')]
    public function index(SituationRepository $situationRepository, Request $request): Response
    {
        $title = "Signalez vos problèmes de logement";
        $etats = ["Etat moyen", "Mauvais état", "Très mauvais état"];
        $etats_classes = ["moyen", "grave", "tres-grave"];
        $signalement = new Signalement();
        $form = $this->createForm(SignalementType::class);
        $form->handleRequest($request);
        return $this->render('front/signalement.html.twig', [
            'title' => $title,
            'situations' => $situationRepository->findAllActive(),
            'signalement' => $signalement,
            'form' => $form->createView(),
            'etats' => $etats,
            'etats_classes' => $etats_classes
        ]);
    }

    #[Route('/signalement/handle', name: 'handle_upload', methods: "POST")]
    public function handleUpload(UploadHandlerService $uploadHandlerService, Request $request, RequestStack $requestStack)
    {
        if ($files = $request->files->get('signalement')) {
            foreach ($files as $key => $file)
                return $this->json($uploadHandlerService->toTempFolder($file)->setKey($key));
        }
        return $this->json(['error' => 'Aucun fichiers'], 400);

    }

    /**
     * @throws Exception
     */
    #[Route('/signalement/envoi', name: 'envoi_signalement', methods: "POST")]
    public function envoi(Request $request, ManagerRegistry $doctrine, NotificationService $notificationService,UploadHandlerService $uploadHandlerService): Response
    {
        if ($data = $request->get('signalement')) {
            $em = $doctrine->getManager();
            $signalement = new Signalement();
            $files_array = [];
            if (isset($data['files'])) {
                $dataFiles = $data['files'];
                foreach ($dataFiles as $key => $files) {
                    foreach ($files as $titre => $file)
                    {
                        $files_array[$key][] =  ['file'=>$uploadHandlerService->toUploadFolder($file),'titre'=>$titre];
                    }
                }
                unset($data['files']);
            }
            if (isset($files_array['documents']))
                $signalement->setDocuments($files_array['documents']);
            if (isset($files_array['photos']))
                $signalement->setPhotos($files_array['photos']);
            foreach ($data as $key => $value) {
                $method = 'set' . ucfirst($key);
                switch ($key) {
                    case 'situation':
                        foreach ($data[$key] as $idSituation => $criteres) {
                            $situation = $em->getRepository(Situation::class)->find($idSituation);
                            $signalement->addSituation($situation);
                            foreach ($criteres as $critere) {
                                foreach ($critere as $idCritere => $criticites) {
                                    $critere = $em->getRepository(Critere::class)->find($idCritere);
                                    $signalement->addCritere($critere);
                                    $criticite = $em->getRepository(Criticite::class)->find($data[$key][$idSituation]['critere'][$idCritere]['criticite']);
                                    $signalement->addCriticite($criticite);
                                }
                            }
                        }
                        break;
                    case
                    'dateEntree':
                        $value = new \DateTimeImmutable($value);
                        $signalement->$method($value);
                        break;
                    case
                    'geoloc':
                        $signalement->setGeoloc(["lat" => $data[$key]['lat'], "lng" => $data[$key]['lng']]);
                        break;
                    default:
                        if ($value === "" || $value === " ")
                            $value = null;
                        $signalement->$method($value);
                }
            }
            if (!$signalement->getIsNotOccupant()) {
                $signalement->setNomDeclarant(null);
                $signalement->setPrenomDeclarant(null);
                $signalement->setMailDeclarant(null);
                $signalement->setStructureDeclarant(null);
                $signalement->setTelDeclarant(null);
            }

            $year = (new \DateTime())->format('Y');
            $reqId = $doctrine->getRepository(Signalement::class)->createQueryBuilder('s')
                ->select('s.reference')
                ->where('YEAR(s.createdAt) = :year')
                ->setParameter('year', $year)
                ->orderBy('s.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            if ($reqId)
                $id = (int)explode('-', $reqId['reference'])[1] + 1;
            else
                $id = 1;
            $signalement->setReference($year . '-' . $id);

            $score = new CriticiteCalculatorService($signalement, $doctrine);
            $signalement->setScoreCreation($score->calculate());
//            $signalement->setReference(null);
            $em->persist($signalement);
            $em->flush();

            if (!$signalement->getIsProprioAverti())
                $attachment = file_exists($this->getParameter('mail_attachment_dir') . 'ModeleCourrier.pdf') ? $this->getParameter('mail_attachment_dir') . 'ModeleCourrier.pdf' : null;
            //TODO: Mail Sendinblue
            if ($signalement->getMailOccupant())
                $notificationService->send(NotificationService::TYPE_ACCUSE_RECEPTION, $signalement->getMailOccupant(), ['signalement' => $signalement, 'attach' => $attachment ?? null]);
            if ($signalement->getMailDeclarant())
                $notificationService->send(NotificationService::TYPE_ACCUSE_RECEPTION, $signalement->getMailDeclarant(), ['signalement' => $signalement, 'attach' => $attachment ?? null]);
            foreach ($doctrine->getRepository(User::class)->findAdmins() as $admin) {
                if ($admin->getIsMailingActive())
                    $notificationService->send(NotificationService::TYPE_NEW_SIGNALEMENT, $admin->getEmail(), ['link' => $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()], 0)]);
            }
            return $this->json(['response' => 'success']);
        }
        return $this->json(['response' => 'error'], 400);
    }

    #[Route('/suivre-mon-signalement/{code}', name: 'front_suivi_signalement', methods: "GET")]
    public function suiviSignalement(string $code, SignalementRepository $signalementRepository)
    {
        if ($signalement = $signalementRepository->findOneBy(['codeSuivi' => $code])) {
            //TODO: Verif info perso pour plus de sécu
            return $this->render('front/suivi_signalement.html.twig', [
                'signalement' => $signalement
            ]);
        }
        $this->addFlash('error', 'Le lien utilisé est expiré ou invalide, verifier votre saisie.');
        return $this->redirectToRoute('front_signalement');
    }
}
