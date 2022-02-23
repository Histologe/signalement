<?php

namespace App\EventListener;

use App\Service\NotificationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        if($event->getRequest()->get('signalement') !== null)
        {
            $this->notificationService->send(NotificationService::TYPE_ERROR, 'sam@bzez.dev', [
                'url' => $_SERVER['SERVER_NAME'],
                'code' => $event->getThrowable()->getCode(),
                'error' => $event->getThrowable()->getMessage(),
                'signalement' => $event->getRequest()->get('signalement')
            ]);
        }
    }
}