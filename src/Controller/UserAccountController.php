<?php

namespace App\Controller;

use App\Entity\User;
use App\Notifier\CustomLoginLinkNotification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkNotification;

class UserAccountController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/activation', name: 'login_activation')]
    public function requestLoginLink(LoginLinkHandlerInterface $loginLinkHandler, UserRepository $userRepository, Request $request, MailerInterface $mailer): \Symfony\Component\HttpFoundation\Response
    {
        $title = 'Activation de votre compte';
        if ($request->isMethod('POST') && $email = $request->request->get('email')) {
            $user = $userRepository->findOneBy(['email' => $email]);
            $userRequest = clone $request;
            $userRequest->setLocale($request->getDefaultLocale());
            $loginLinkDetails = $loginLinkHandler->createLoginLink($user, $userRequest);
            $loginLink = $loginLinkDetails->getUrl();
            $notif = new NotificationEmail();
            $notif->htmlTemplate('emails/login_link_email.html.twig')
                ->context(['link' => $loginLink])
                ->to($email)
                ->subject('Histologe - Activation');
            $mailer->send($notif);
            return $this->render('security/login_link_sent.html.twig', [
                'title' => 'Lien de connexion envoyé !',
                'email' => $email
            ]);
        }

        // if it's not submitted, render the "login" form
        return $this->render('security/login_activation.html.twig', [
            'title' => $title
        ]);
    }

    #[Route('/activation-incorrecte', name: 'login_activation_fail')]
    public function activationFail()
    {
        $this->addFlash('error', 'Le lien utilisé est invalide ou expiré, veuillez en generer un nouveau');
        return $this->forward('App\Controller\UserAccountController::requestLoginLink');
    }

    #[Route('/mot-de-pass-perdu', name: 'login_mdp_perdu')]
    public function requestNewPass(LoginLinkHandlerInterface $loginLinkHandler, UserRepository $userRepository, Request $request, MailerInterface $mailer)
    {
        $title = 'Récupération de votre mot de passe';
        if ($request->isMethod('POST') && $email = $request->request->get('email')) {
            $user = $userRepository->findOneBy(['email' => $email]);
            $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
            $loginLink = $loginLinkDetails->getUrl();
            //NOTIFICATION
            $notif = new NotificationEmail();
            $notif->htmlTemplate('emails/login_link_email.html.twig')
                ->context(['link' => $loginLink])
                ->to($email)
                ->subject('Histologe - Activation');
            $mailer->send($notif);
            //END NOTIFICATION
            return $this->render('security/login_link_sent.html.twig', [
                'title' => 'Lien de récupération envoyé !',
                'email' => $email
            ]);
        }

        // if it's not submitted, render the "login" form
        return $this->render('security/login_activation.html.twig', [
            'title' => $title
        ]);
    }

    #[Route('/bo/nouveau-mot-de-passe', name: 'login_creation_pass')]
    public function createPassword(Request $request, PasswordHasherFactoryInterface $hasherFactory, EntityManagerInterface $entityManager)
    {
        $title = 'Création de votre mot de passe';
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('create_password_' . $this->getUser()->getId(), $request->get('_csrf_token'))) {
            $user = $this->getUser();
            $password = $hasherFactory->getPasswordHasher($user)->hash($request->get('password'));
            $user->setPassword($password);
            $user->setStatut(User::STATUS_ACTIVE);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Votre compte est maintenant activé !');
            return $this->redirectToRoute('back_index');
        }
        return $this->render('security/login_creation_mdp.html.twig', [
            'title' => $title
        ]);
    }
}