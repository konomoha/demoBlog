<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleType;
use App\Form\CategoryFormType;
use PhpParser\Node\Expr\Isset_;
use Doctrine\ORM\EntityRepository;
use App\Repository\ArticleRepository;
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
            'photoActuelle'=> $article->getPhoto()
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
            $article = $catRemove->getArticles();
            $nbarticle = count($article);
            $titre = $catRemove->getTitre();

            if($nbarticle == 0)
            {
                // dd($article);
                $manager->remove($catRemove);
                $manager->flush();
                $this->addFlash('success', "La catégorie $titre a bien été supprimée");
                return $this->redirectToRoute('app_admin_categories');

            }

            else
            {
                $this->addFlash('error', "La catégorie $titre est encore liée à un ou plusieurs articles!");
                return $this->redirectToRoute('app_admin_categories');
            }

        }
        
        return $this->render('back_office/admin_categories.html.twig', [
            'category'=>$category,
            'colonnes'=>$colonnes
        ]);
    }
    
    #[Route('/admin/categories/add', name:'app_admin_categories_form')]
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