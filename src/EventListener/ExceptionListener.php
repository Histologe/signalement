<?php

namespace App\EventListener;

use App\Service\NotificationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    private NotificationService $notificationService;

    const ERROR_500 = ['title'=>'Une erreur est survenue...','icon'=>'icon-bug-lg','message'=>"Une erreur inattendue s'est produite ! Notre équipe fait tout pour la corriger au plus vite.<br>Veuillez réessayer plus tard ou cliquer sur le bouton ci-dessous pour retourner à l'accueil."];
    const ERROR_403 = ['title'=>'Accès refusé','icon'=>'icon-stop-lg','message'=>"Vous n'avez pas les droits nécessaires pour accéder à cette page.<br>Cliquez sur le bouton ci-dessous pour retourner à l'accueil."];
    const ERROR_404 = ['title'=>'Page introuvable','icon'=>'icon-search-lg','message'=>"Il semblerait que la page que vous essayez d'atteindre n'existe pas ou a été déplacée !<br>Cliquez sur le bouton ci-dessous pour retourner à l'accueil."];

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        if ($event->getRequest()->get('signalement') !== null) {
            $this->notificationService->send(NotificationService::TYPE_ERREUR_SIGNALEMENT, 'sam@bzez.dev', [
                'url' => $_SERVER['SERVER_NAME'],
                'code' => $event->getThrowable()->getCode(),
                'error' => $event->getThrowable()->getMessage(),
                'signalement' => var_dump($event->getRequest()->get('signalement'))
            ]);
        }
    }
}