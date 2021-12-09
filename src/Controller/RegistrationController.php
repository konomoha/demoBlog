<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            // on fait appel à l'objet $userPasswordHasher de l'interface UserPasswordHasherInterface afin d'encoder le mot de passe en BDD
            //En argument on lui fournit l'objet entité dans lequel nous allons encoder un élément ($user) et on lui fournit le mot de passe saisi dans le formulaire à encoder
            $hash = $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                );
                
                // dd($hash);
            
            // On transmet au setter du passwordf la clé de hachage à enregistré en BDD
            $user->setPassword($hash);

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            
            $this->addFlash(
                        'success',
                        'félicitations, vous êtes inscrit'
                        );

            return $this->redirectToRoute('home');
            
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /*
        Exo : créer une  page profil affichant les données de l'utilisateur authentifié
        1. Créer une nouvelle route '/profil'
        2.Créer une nouvelle méthode userProfil()
        3.Cette méthode renvoie un template 'registration/profil.html.twig'
        4.Afficher dans ce template les informations de l'utilisateur
    */
    #[Route('/profil', name: 'profil')]
    public function userProfil(): Response
    {
        return $this->render('registration/profil.html.twig', [
            'profildata'=>])

    }
}
