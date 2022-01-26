<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class NotificationService
{
    const TYPE_ACTIVATION = 1;
    const TYPE_LOST_PASSWORD = 2;
    const TYPE_NEW_SIGNALEMENT = 3;
    const TYPE_AFFECTATION = 4;
    const TYPE_SIGNALEMENT_VALIDE = 5;
    const TYPE_ACCUSE_RECEPTION_DECLARANT = 6;

    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    private function config(int $type): array
    {
        return match ($type) {
            NotificationService::TYPE_ACTIVATION => [
                'template' => 'login_link_email',
                'subject' => 'Activation de votre compte',
                'btntext'=>"J'active mon compte"
            ],
            NotificationService::TYPE_LOST_PASSWORD => [
                'template' => 'lost_pass_email',
                'subject' => 'Récupération de votre mot de passe',
                'btntext'=>"Je créer un nouveau mot de passe"
            ],
            NotificationService::TYPE_NEW_SIGNALEMENT => [
                'template' => 'login_link_email',
                'subject' => 'Un nouveau signalement vous attend',
                'btntext'=>"Voir le signalement"
            ],
            NotificationService::TYPE_AFFECTATION => [
                'template' => 'login_link_email',
                'subject' => 'Vous avez été affecté à un signalement'
            ],
            NotificationService::TYPE_SIGNALEMENT_VALIDE => [
                'template' => 'validation_signalement_email',
                'subject' => 'Votre signalement à été validé par nos service',
                'btntext'=>"Suivre mon signalement"
            ]
        };
    }

    public function send(int $type, string $email, array $params): TransportExceptionInterface|\Exception|bool
    {
        $message = $this->renderMailContentWithParamsByType($type, $params);
        $message->to($email);
        try {
            $this->mailer->send($message);
            return true;
        } catch (TransportExceptionInterface $e) {
            return $e;
        }
    }

    private function renderMailContentWithParamsByType(int $type, array $params): NotificationEmail
    {
        $config = $this->config($type);
        $notification = new NotificationEmail();
        return $notification->htmlTemplate('emails/' . $config['template'] . '.html.twig')
            ->context(array_merge($params,$config))
            ->subject('Histologe - ' . $config['subject']);
    }
}