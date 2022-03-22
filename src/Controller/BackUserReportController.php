<?php

namespace App\Controller;

use App\Entity\UserReport;
use App\Repository\UserReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bo/report')]
class BackUserReportController extends AbstractController
{
    #[Route('/',name:'back_user_report_index')]
    public function index(UserReportRepository $userReportRepository): Response
    {
        return $this->render('back/bug_report/index.html.twig',[
            'reports'=>$userReportRepository->findAlls()
        ]);
    }

    #[Route('/send',name: 'back_bug_report')]
    public function sendReport(Request $request, SluggerInterface $slugger, ManagerRegistry $doctrine): JsonResponse
    {
        if ($this->isCsrfTokenValid('user_bug_report', $request->get('_token')) && $request->get('bug-report')) {
            $report = new UserReport();
            $report->setCreatedBy($this->getUser());
            $content = $request->get('bug-report')['content'];
            $url = $request->get('bug-report')['url'];
            $route = $request->get('bug-report')['route'];
            $report->setContent($content);
            $report->setRoute($route);
            $report->setUrl($url);
            if ($file = $request->files->get('capture')) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('capture_dir'),
                    $newFilename
                );
                $report->setCapture($newFilename);
            }
            $em = $doctrine->getManager();
            $em->persist($report);
            $em->flush();
            return $this->json(['response' => 'success', 'id_report' => $report->getId()]);
        }
        return $this->json(['response' => 'error'], 400);
    }

    #[Route('/{id}', name: 'back_user_report_delete', methods: ['POST'])]
    public function delete(Request $request, UserReport $userReport, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('user_report_delete_' . $userReport->getId(), $request->request->get('_token'))) {
            $userReport->setIsArchive(true);
            $userReport->setArchivedAt(new \DateTimeImmutable());
            $entityManager->persist($userReport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('back_user_report_index', [], Response::HTTP_SEE_OTHER);
    }
}