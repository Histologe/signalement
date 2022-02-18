<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationService
{
    const TYPE_ACTIVATION = 1;
    const TYPE_LOST_PASSWORD = 2;
    const TYPE_NEW_SIGNALEMENT = 3;
    const TYPE_AFFECTATION = 4;
    const TYPE_SIGNALEMENT_VALIDE = 5;
    const TYPE_ACCUSE_RECEPTION = 6;
    const TYPE_NOUVEAU_SUIVI = 7;
    const TYPE_NOTIFICATION_MAIL_FRONT = 8;

    private MailerInterface $mailer;
    private ConfigurationService $configuration;

    public function __construct(MailerInterface $mailer,ConfigurationService $configurationService)
    {
        $this->mailer = $mailer;
        $this->configuration = $configurationService;
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
                'template' => 'affectation_email',
                'subject' => 'Vous avez été affecté à un signalement',
                'btntext'=>"Voir le signalement"
            ],
            NotificationService::TYPE_SIGNALEMENT_VALIDE => [
                'template' => 'validation_signalement_email',
                'subject' => 'Votre signalement est validé !',
                'btntext'=>"Suivre mon signalement"
            ],
            NotificationService::TYPE_NOTIFICATION_MAIL_FRONT => [
                'template' => 'nouveau_mail_front',
                'subject' => 'Vous avez reçu un message depuis la page Histologe',
            ],
            NotificationService::TYPE_ACCUSE_RECEPTION => [
                'template' => 'accuse_reception_email',
                'subject' => 'Votre signalement a bien été reçu !',
            ],
            NotificationService::TYPE_NOUVEAU_SUIVI => [
                'template' => 'nouveau_suivi_signalement_email',
                'subject' => 'Nouvelle mise à jour de votre signalement !',
                'btntext'=>"Suivre mon signalement"
            ]
        };
    }

    public function send(int $type, string $email, array $params): TransportExceptionInterface|\Exception|bool
    {
        $message = $this->renderMailContentWithParamsByType($type, $params);
        $message->to($email);
        $message->from(new Address('notifications@histologe.fr','HISTOLOGE'));
        if(!empty($params['attach']))
            $message->attachFromPath($params['attach']);
        if($this->configuration->get()->getEmailReponse() !== null || isset($params['reply']))
            $message->replyTo($params['reply'] ?? $this->configuration->get()->getEmailReponse());
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
        $notification->markAsPublic();
        return $notification->htmlTemplate('emails/' . $config['template'] . '.html.twig')
            ->context(array_merge($params,$config))
            ->subject('Histologe - ' . $config['subject']);
    }
}