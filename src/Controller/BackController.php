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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bo')]
class BackController extends AbstractController
{
    #[Route('/', name: 'back_index')]
    public function index(SignalementRepository $signalementRepository, Request $request): Response
    {
        $title = 'Administration - Tableau de bord';
        $user = null;
        if (!$this->isGranted('ROLE_ADMIN_TERRITOIRE'))
            $user = $this->getUser();
        $filter = [
            'search' => $request->get('search') ?? null,
            'status' => $request->get('bo-filter-statut') ?? 'all',
            'ville' => $request->get('bo-filter-ville') ?? 'all',
            'page' => $request->get('page') ?? 1,
        ];
        $req = $signalementRepository->findByStatusAndOrCityForUser($user, $filter['status'], $filter['ville'], $filter['search'], $filter['page']);
        if ($this->getUser()->getPartenaire()) {
            foreach ($req as $signalement) {
                $signalement->getAffectations()->filter(function (Affectation $affectation) use ($signalement) {
                    if ($affectation->getPartenaire()->getId() === $this->getUser()->getPartenaire()->getId() && $affectation->getStatut() === Affectation::STATUS_WAIT)
                        $signalement->setStatut(Signalement::STATUS_NEED_PARTNER_RESPONSE);
                    $signalement->getClotures()->filter(function (Cloture $cloture) use ($signalement) {
                        if ($cloture->getPartenaire()->getId() === $this->getUser()->getPartenaire()->getId() && $cloture->getType() === Cloture::TYPE_CLOTURE_PARTENAIRE)
                            $signalement->setStatut(Signalement::STATUS_CLOSED);
                    });
                });
            }
        }
        $signalements = [
            'list' => $req,
            'villes' => $signalementRepository->findCities($user),
            'total' => count($req),
            'page' => (int)$filter['page'],
            'pages' => (int)ceil(count($req) / 50)
        ];
//        dd($signalementRepository->countByStatus($user));
        $signalements['counts'] = $signalementRepository->countByStatus($user);
//        dd($signalementRepository->countByStatus($user));
        if (/*$request->isXmlHttpRequest() && */ $request->get('pagination'))
            return $this->render('back/table_result.html.twig', ['filter' => $filter, 'signalements' => $signalements]);
        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filter' => $filter,
            'signalements' => $signalements,
        ]);
    }


    #[
        Route('/news', name: 'back_news_activities')]
    public function newsActivitiesSinceLastLogin(NewsActivitiesSinceLastLoginService $newsActivitiesSinceLastLoginService): Response
    {
        $title = 'Administration - Nouveaux suivis';
        $suivis = $newsActivitiesSinceLastLoginService->getAll();
        if ($suivis->isEmpty())
            return $this->redirectToRoute('back_index');
        return $this->render('back/news_activities/index.html.twig', [
            'title' => $title,
            'suivis' => $suivis
        ]);
    }

    #[Route('/config', name: 'back_config')]
    public function config(ConfigRepository $configRepository, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $title = 'Administration - Configration';
        if (isset($configRepository->findLast()[0]))
            $config = $configRepository->findLast()[0];
        else
            $config = new Config();
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (isset($request->files->get('config')['logotype'])) {
                $logotype = $request->files->get('config')['logotype'];
                $originalFilename = pathinfo($logotype->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logotype->guessExtension();
                try {
                    $logotype->move(
                        $this->getParameter('images_dir'),
                        $newFilename
                    );
                    $config->setLogotype($newFilename);
                } catch (UploadException $e) {
                    //TODO: Notif fail upload
                }

            }
            $entityManager->persist($config);
            $entityManager->flush();
        }
        return $this->render('back/config/index.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'logotype' => $config->getLogotype()
        ]);
    }
}
