<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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
    public function admin_article_form(Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
        $article = new Article;

        $formArticle = $this->createForm(ArticleType::class, $article);

        $formArticle->handleRequest($request);

        if($formArticle->isSubmitted() && $formArticle->isValid())
        {

            $article->setDate(new \DateTime());

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

            $manager->persist($article);
            $manager->flush();
            $this->addFlash('success', 'Insertion réussie');
            return $this->redirectToRoute('app_admin_articles');
        }
        
       
        return $this->render('back_office/admin_article_form.html.twig', [
            'formArticle'=>$formArticle->createView()
            
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