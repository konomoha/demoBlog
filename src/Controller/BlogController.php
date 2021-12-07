<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BlogController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        //méthode rendu, en fonction de la route dans l'URL, la méthode render() envoie un template, un rendu sur le navigateur.
        return $this->render('blog/home.html.twig', [
            'title'=> 'Bienvenue sur le blog Symfony',
            'age'=> 25,
        ]);
    }


    #[Route('/blog', name: 'blog')]
    public function blog(ArticleRepository $repoArticle): Response
    {
        /* 
            Injections de dépendances : c'est un des fondements de Symfony, ici notre méthode DEPEND de la classe ArticleRepository pour fonctionner correctement 
            Ici, symfony comprend que la méthode blog() attend en argument un objet issu de la classe ArticleRepository, automatiquement Symfony envoie une instance de cette classe en argument de cette classe.
            $repoArticle est un objet issu de la classe ArticleRepository, nous n'avons plus qu'à piocher dans l'objet pour atteindre des méthodes de la classe
            SYMFONY est une application qui est capable de répondre à un navigateur lorsque celui-ci appel une adresse (ex:localhost:8000/blog), le controller doit être capable d'envoyer un rendu, un template sur le navigateur.
            
            Ici, lorsque l'on transmet la route '/blog' dans l'URL, cela exécute la méthode index() dans le controller qui renvoie le template '/blog/index.html.twig' sur le navigateur.
        */
        
        //Pour sélectionner des données en BDD

        //Ici nous devons importer au sein de notre controller la classe Article Repository pour pouvoir sélectionner dans la table article 
        //$repoArticle est un objet issu de la classe ArticleRepository
        //getRepository() est une méthode issue de l'objet Doctrine permettant ici d'importer la classe ArticleRepository
        // $repoArticle=$doctrine->getRepository(Article::class);
        //dump() / dd() : outil de debug symfony
        //dd($repoArticle);
        
        //findAll(): méthode issue de la classe ArticleRepository permettant de sélectionner l'ensemble de la table SQL et de récupérer un tableau multi contenant l'ensemble des articles stockés en BDD

        $articles=$repoArticle->findAll(); //SELECT * FROM article + Fectch_ALL
        // dump($articles);

        return $this->render('blog/blog.html.twig', [
            'articles'=> $articles // on transmet au template les articles sélectionnés en BDD afin que twig traite l'affichage
        ]);
    }

    // Méthode permettant d'insérer / modifier un article en BDD
    #[Route('/blog/new', name: 'blog_create')]
    #[Route('/blog/{id}/edit', name: 'blog_edit')]
    public function blogCreate(Article $article = null, Request $request, EntityManagerInterface $manager): Response
    {
        // La classe Request de Symfony contient toutes les données véhiculées par les superglobales ($_GET, $_POST, $_SERVER, $_COOKIE etc...)
        // $request->request : la propriété 'request' de l'objet $request contient toutes les données de $_POST

        //Si les données dans le tableau ARRAY $_POST sont supérieur à 0, alors on entre dans la condition IF
        //if(count($_POST) > 0)

        // if($request->request->count()>0)
        //{
            //$request->$_POST
            // dd($request->request);

            // Pour insérer dans la table SQL 'article', nous avons besoin d'un objet de son entité correspondante
            // $article = new Article;

            // $article->setTitre($request->request->get('titre'))
            //         ->setContenu($request->request->get('contenu'))
            //         ->setPhoto($request->request->get('photo'))
            //         ->setDate(new \DateTime());

            // dd($article);
            
            //persist(): méthode issue de l'interface EntityManagerInterface permettant de préparer la requete d'insertion et de garder en mémoire l'objet / la requête
            // $manager->persist($article);

            // flush(): méthode issue de l'interface EntityManagerInterface permettant véritablement d'executer la requete INSERT en BDD (ORM doctrine)
            // $manager->flush();
        // }
        
        //si la variable article est null, cela veut dire que nous sommes sur la route '/blog/new', on entre dans le IF et on créée une nouvelle instance de l'entité article
        //Si la variable $article n'est pas null, cela veut dire que nous sommes sur la route '/blog/{id}/edit', nous n'entrons pas dans le IF car $article contient un article de la BDD
        if(!$article)
        {
            $article = new Article;
        }
        

        // $article->setTitre("titre à la con")
        //         ->setContenu("contenu à la con");

        $formArticle = $this->createForm(ArticleType::class, $article);

        $formArticle->handleRequest($request);

        if($formArticle->isSubmitted() && $formArticle->isValid())
        {
            // Le seul setter que l'on appelle de l'entité, c'est celui de la date puisqu'il n'y a pas de champ 'date' dans le formmulaire
            $article->setDate(new \DateTime());
            // dd($article);
            $manager->persist($article);
            $manager->flush();

        }

        return $this->render('blog/blog_create.html.twig', ['formArticle'=>$formArticle->createView()
        // on transmet le formulaire au template afin de poouvoir l'afficher avec Twig
        // createView() retourne un petit objet qui représente l'affichage du formulaire, on le récupère dans le template blog_create.html.twig
    ]);
    }

    // Méthode permettant d'afficher le détail d'un article.
    // On défini une route 'paramétrée' {id}, ici la route permet de recevoir l'id d'un article stocké en BDD
    #[Route('/blog/{id}', name:'blog_show')]
    public function blogShow(Article $article): Response
    {
        /*
            Ici, nous envoyons un ID dans l'url et nous imposons en argument un objet issu de l'entité Article donc la table SQL
            Donc symfony est capable de sélectionner en BDD l'article en fonction de l'id passé dans l'url et de l'envoyer automatiquement en argument de la méthode blogShow() dans la variable de réception $article
        */

        //On importe la classe ArticleRepository dans la méthode BlogShow pour sélectionner (SELECT) dans la table SQL 'article'
        // $repoArticle = $doctrine->getRepository(Article::class);
       
        // find() : méthode issue de la classe ArticleRepository permettant de sélectionner un élément par son ID qu'on lui fournit en argument.
        // $article = $repoArticle->find($id);
        // dd($article);

        // L'id transmit dans la route '/blog/5' est transmit automatiquement en argument de la méthode blogShow($id) dans la variable de réception $id
        // dd($id);
        return $this->render('blog/blog_show.html.twig',[ 'article'=>$article //On transmet au template l'article sélectionné en BDD afin que twig puisse traiter et afficher les données sur la page
    ]);
    }
}
