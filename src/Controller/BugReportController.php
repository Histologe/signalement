<?php

namespace App\Controller;

use App\Entity\UserReport;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bo/bugreport')]
class BugReportController extends AbstractController
{
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
}