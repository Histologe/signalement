<?php

namespace App\Controller;

use App\Entity\Partenaire;
use App\Entity\User;
use App\Form\PartenaireType;
use App\Repository\PartenaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/partenaire')]
class BackPartenaireController extends AbstractController
{
    private static function checkFormExtraData(FormInterface $form,Partenaire $partenaire,EntityManagerInterface $entityManager)
    {
        if ($form->getExtraData()['users'])
            foreach ($form->getExtraData()['users'] as $id => $userData) {
                if($id !== 'new'){
                    $userPartenaire = $partenaire->getUsers()->filter(function (User $user) use ($id) {
                        if ($user->getId() === $id)
                            return $user;
                    });
                    if (!$userPartenaire->isEmpty())
                    {
                        $user = $userPartenaire->first();
                        self::setUserData($user,$userData['nom'],$userData['prenom'],$userData['roles'],$userData['email']);
                        $entityManager->persist($user);
                    }
                } else {
                    foreach ($userData as $newUserData)
                    {
                        $user = new User();
                        $user->setPartenaire($partenaire);
                        //TODO: Generate pass & send mail activation compte
                        $user->setPassword('123-456-789');
                        self::setUserData($user,$newUserData['nom'],$newUserData['prenom'],$newUserData['roles'],$newUserData['email']);
                        $entityManager->persist($user);
                    }
                }
            }
    }

    private static function setUserData(User $user, mixed $nom, mixed $prenom, mixed $roles, mixed $email)
    {
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setRoles([$roles]);
        $user->setEmail($email);
    }

    #[Route('/', name: 'back_partenaire_index', methods: ['GET'])]
    public function index(PartenaireRepository $partenaireRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            return $this->redirectToRoute('back_index');
        return $this->render('back/partenaire/index.html.twig', [
            'partenaires' => $partenaireRepository->findAlls(),
        ]);
    }

    #[Route('/new', name: 'back_partenaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            return $this->redirectToRoute('back_index');
        $partenaire = new Partenaire();
        $form = $this->createForm(PartenaireType::class, $partenaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            self::checkFormExtraData($form,$partenaire,$entityManager);
            $entityManager->persist($partenaire);
            $entityManager->flush();
            return $this->redirectToRoute('back_partenaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('back/partenaire/edit.html.twig', [
            'partenaire' => $partenaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'back_partenaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Partenaire $partenaire, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            return $this->redirectToRoute('back_index');
        $form = $this->createForm(PartenaireType::class, $partenaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            self::checkFormExtraData($form,$partenaire,$entityManager);
            $entityManager->flush();

            return $this->redirectToRoute('back_partenaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('back/partenaire/edit.html.twig', [
            'partenaire' => $partenaire,
            'form' => $form,
        ]);
    }

    #[Route('/{user}/delete', name: 'back_partenaire_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request,User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('partenaire_user_delete_'.$user->getId(), $request->request->get('_token'))) {
            $user->setStatut(User::STATUS_ARCHIVE);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('back_partenaire_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'back_partenaire_delete', methods: ['POST'])]
    public function delete(Request $request, Partenaire $partenaire, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            return $this->redirectToRoute('back_index');
        if ($this->isCsrfTokenValid('partenaire_delete_' . $partenaire->getId(), $request->request->get('_token'))) {
            $partenaire->setIsArchive(true);
            foreach ($partenaire->getUsers() as $user)
                $user->setStatut(User::STATUS_ARCHIVE) && $entityManager->persist($user);
            $entityManager->persist($partenaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('back_partenaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
