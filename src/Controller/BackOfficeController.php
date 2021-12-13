<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Category;
use App\Form\ArticleType;
use App\Form\CommentFormType;
use App\Form\CategoryFormType;
use App\Form\RegistrationFormType;
use PhpParser\Node\Expr\Isset_;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Repository\RepositoryFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BackOfficeController extends AbstractController
{
    // Méthode qui affiche la page Home du backoffice
    #[Route('/admin', name: 'app_admin')]
    public function adminHome(): Response
    {
        return $this->render('back_office/index.html.twig', [
            'controller_name' => 'BackOfficeController',
        ]);
    }

    #[Route('/admin/articles', name: 'app_admin_articles')]
    #[Route('/admin/article/{id}/remove', name: 'app_admin_article_remove')]
    public function adminArticles(EntityManagerInterface $manager, ArticleRepository $repoArticle, Article $artRemove=null): Response
    {
        // dd($artRemove);
        $colonnes = $manager->getClassMetadata(Article::class)->getFieldNames();
        // dd($colonnes);

        /*
            Exo: affiche sous forme de tableau HTML l'ensemble des articles stockés en BDD
            1.Sélectionner en BDD l'ensemble de la table 'article' et transmettre le résultat à la méthode render()
            2.dans le template 'admin_articles.html.twig, mettre en forme l'affichage des articles dans un tableau HTML
            3. Afficher le nom de la catégorie de chaque article
            4. Afficher le nombre de commentaire de chaque article
            5. Prévoir un lien modifier/supprimer pour chaque article
        */

        $articles = $repoArticle->findAll();
        // dd($articles);

        //Traitement suppression article en BDD

        if($artRemove)
        {
            $manager->remove($artRemove);
            $manager->flush();
            $titre = $artRemove->getTitre();
            $this->addFlash('success', "l'article $titre a bien été supprimé");
            return $this->redirectToRoute('app_admin_articles');
        }

        return $this->render('back_office/admin_articles.html.twig', [
            'colonnes'=>$colonnes,
            'articles' =>$articles
            
        ]);
    }

    #[Route('/admin/article/add', name:'app_admin_article_add')]
    #[Route('/admin/article/{id}/update', name:'app_admin_article_update')]
    public function admin_article_form(Article $article=null, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
        if(!$article)
        {
            $article = new Article;
        }

       if($article)
       {
           $photoActuelle = $article->getPhoto();
       }
        

        $formArticle = $this->createForm(ArticleType::class, $article);

        $formArticle->handleRequest($request);

        if($formArticle->isSubmitted() && $formArticle->isValid())
        {
            if(!$article->getId())
            {
                 $article->setDate(new \DateTime());
            }
           

            //Traitement de la photo
            
            $photo = $formArticle->get('photo')->getData();
            // dd($photo);

            
            if($photo)
            {
            
                $nomphoto = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);

                // dd($nomphoto);
                
                $securenomphoto = $slugger->slug($nomphoto);

                // dd($securenomphoto);

                $nouveaunomphoto = $securenomphoto.'-'. uniqid().'.'. $photo->guessExtension();

                // dd($nouveaunomphoto);

                try
                {
                    $photo->move(
                        $this->getParameter('photo_directory'),
                        $nouveaunomphoto
                    );

                }
                catch(FileException $e)
                {

                }

                $article->setPhoto($nouveaunomphoto);

            }

            else
            {
                //Si l'article possède une image mais qu'on ne souhaite pas la modifier, on renvoie la même image en BDD
                if(Isset($photoActuelle))
                {
                    $article->setPhoto($photoActuelle);
                }

                //Sinon on crée un nouvel article mais on ne souhaite pas uploader d'image, alors on renvoie NULL pour le champ photo dans la BDD
                else
                {
                    $article->setPhoto(null);
                }

            }
            if($article->getId())
            {
                $txt = 'modifié';
            }
            else
            {
                $txt = 'enregistré';
            }
            
            $manager->persist($article);
            $manager->flush();
            $this->addFlash('success', "Article $txt!");
            return $this->redirectToRoute('app_admin_articles');
        }
        
       
        return $this->render('back_office/admin_article_form.html.twig', [
            'formArticle'=>$formArticle->createView(),
            'photoActuelle'=> $article->getPhoto(),
            'editMode'=>$article->getId()
        ]);
    }

    #[Route('/admin/categories', name:'app_admin_categories')]
    #[Route('/admin/categories/{id}/delete', name:'app_admin_categories_delete')]
    public function adminCategories(CategoryRepository $repoCategory, EntityManagerInterface $manager, Category $catRemove=null):Response
    {
        $colonnes = $manager->getClassMetadata(Category::class)->getFieldNames();
        // dd($colonnes);
        $category = $repoCategory->findAll();
        
       
        if($catRemove)
        { 
            //On récupère le titre de la catégorie avant la suppression pour l'intégrer dans le message utilisateur
            $titre = $catRemove->getTitre();

            if($catRemove->getArticles()->isEmpty())
            {
                // dd($article);
                $manager->remove($catRemove);
                $manager->flush();
                $this->addFlash('success', "La catégorie $titre a bien été supprimée");
                return $this->redirectToRoute('app_admin_categories');

            }

            else
            {
                $this->addFlash('danger', "La catégorie $titre est encore liée à un ou plusieurs articles!");
                return $this->redirectToRoute('app_admin_categories');
            }

        }
        
        return $this->render('back_office/admin_categories.html.twig', [
            'category'=>$category,
            'colonnes'=>$colonnes
        ]);
    }
    
    #[Route('/admin/categories/add', name:'app_admin_categories_add')]
    #[Route('/admin/categories/{id}/update', name:'app_admin_categories_update')]
    public function addCategory(Request $request, EntityManagerInterface $manager, Category $category=null):Response
    {
        if(!$category)
        {
            $category = new Category;
        }
        

        $categoryForm = $this->createForm(CategoryFormType::class, $category);
        $categoryForm->handleRequest($request);

        if($categoryForm->isSubmitted() && $categoryForm->isValid())
        {
            $titre = $category->getTitre();
            
            if($category->getId())
            {
                $txt = "modifiée";
            }

            else
            {
                $txt = "ajoutée";
            }

            $manager->persist($category);
            $manager->flush();
            $this->addFlash('success', "Félicitations! la catégorie $titre a bien été $txt!");
            return $this->redirectToRoute('app_admin_categories');

        }
        return $this->render('back_office/admin_categorie_form.html.twig', [
            'categoryForm'=>$categoryForm->createView()
        ]);
    }

    #[Route('/admin/comments', name:'app_admin_comments')]
    #[Route('/admin/comments/{id}/delete', name:'app_admin_comments_delete')]
    public function adminComments(CommentRepository $comment, EntityManagerInterface $manager, Comment $commentRemove=null): Response
    {
        $commentaires = $comment->findAll();
        // dd($commentaires);
        $colonnes = $manager->getClassMetadata(Comment::class)->getFieldNames();
        // dd($colonnes);
        

        if($commentRemove)
        {
            $auteur = $commentRemove->getAuteur();
            $manager->remove($commentRemove);
            $manager->flush();
            $this->addFlash('success', "le commentaire de $auteur à bien été supprimé");
            return $this->redirectToRoute('app_admin_comments');
        }


        return $this->render('back_office/admin_comments.html.twig', [
            'colonnes'=>$colonnes,
            'commentaires'=>$commentaires
        ]);
    }

    #[Route('/admin/comments/{id}/update', name:'app_admin_comments_update')]
    #[Route('admin/comments/add', name: 'app_admin_comments_add')]
    public function updateComment(Comment $comment, EntityManagerInterface $manager, Request $request): Response
    {
        
        if($comment)
        {
            $formComment = $this->createForm(CommentFormType::class, $comment, ['commentFormBack'=>true]);

            $formComment->handleRequest($request);

            if($formComment->isSubmitted() && $formComment->isValid())

            {
                $auteur = $comment->getAuteur();
                $manager->persist($comment);
                $manager->flush();
                $this->addFlash('success', "le commentaire de $auteur à bien été modifié");
                return $this->redirectToRoute('app_admin_comments');
            }       
        }

        return $this->render('back_office/admin_comments_update.html.twig',[
            'formComment'=>$formComment->createView()
        ]);

    }

    #[Route('admin/users', name: 'app_admin_users')]
    #[Route('admin/users/{id}/delete', name:'app_admin_users_delete')]
    public function adminUser(UserRepository $user, EntityManagerInterface $manager, User $userdelete=null): Response
    {
        if($userdelete)
        {
            $name = $userdelete->getNom(). ' '. $userdelete->getPrenom();
            $manager->remove($userdelete);
            $manager->flush();
            $this->addFlash('success', "$name a bien été supprimé de la liste!");
            return $this->redirectToRoute('app_admin_users');
        }
        $dataUser = $user->findAll();
        $colonnes = $manager->getClassMetadata(User::class)->getFieldNames();

        // dd($dataUser);

        return $this->render('back_office/admin_users.html.twig', [
            'colonnes'=>$colonnes,
            'dataUser'=>$dataUser
        ]);
    }
    
    #[Route('/admin/users/add', name:'app_admin_users_add')]
    #[Route('/admin/users/{id}/update', name:'app_admin_users_update')]
    public function addUser(EntityManagerInterface $manager, Request $request, UserPasswordHasherInterface $userPasswordHasher):Response
    {
    
            $user = new User;
        
        

        $userForm = $this->createForm(RegistrationFormType::class, $user, ['userRegistration'=>true]);

        $userForm->handleRequest($request);

        if($userForm->isSubmitted() && $userForm->isValid())
        {
            $hash = $userPasswordHasher->hashPassword(
                $user,
                $userForm->get('password')->getData()
            );

            $user->setPassword($hash);
            $newuser = $user->getNom(). ' '. $user->getPrenom();
            $manager->persist($user);
            $manager->flush();
            $this->addFlash('success', "$newuser a bien été ajouté(e) à la liste des membres!");
            return $this->redirectToRoute('app_admin_users');
        }
        
        return $this->render('back_office/admin_users_form.html.twig', [
            'userForm'=>$userForm->createView()
        ]);

       
    }
     #[Route('/admin/users/{id}/update', name:'app_admin_users_update')]
     public function updateUser(User $user, EntityManagerInterface $manager, Request $request):Response
    {
    
        $userForm = $this->createForm(RegistrationFormType::class, $user, ['adminUpdate'=>true]);

        $userForm->handleRequest($request);

        if($userForm->isSubmitted() && $userForm->isValid())
        {
           
            $name = $user->getNom(). ' '. $user->getPrenom();
            $manager->persist($user);
            $manager->flush();
            $this->addFlash('success', "le Role de $name a bien été modifié!");
            return $this->redirectToRoute('app_admin_users');
        }
        
        return $this->render('back_office/admin_users_update.html.twig', [
            'userForm'=>$userForm->createView()
        ]);
    }
    
}


/*
    Exo: création d'une nouvelle méthode permettant d'insérer et de modifier 1 article en BDD
    1. Créer une route '/admin/article/add' (name:app_admin_article_add)
    2. Créer la méthode admin_article_form()
    3. Créer un nouveau template 'admin_article_form.html.twig'
    4. Importer et créer le formulaire au sein de la méthode adminArticleForm (createForm)
    5.Afficher le formulaire sur le template
    6.Gérer l'upload de la photo
    7.Dans la méthode adminArticleForm(), réaliser le traitement permettant d'insérer un nouvel article en BDD (persist()/ flush())
    8. fonction delete et update
*/