<?php

namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //PHP namespace resolver : extension permettant d'importer les classes (ctrl + alt + i)
        //La boucle tourne 10 fois afin de créer 10 articles FICTIFS dans la BDD
        for($i=1; $i<=10; $i++)
        {
            /* Pour insérer des données dans la table SQL Article, nous sommes obligés de passer par sa classe app\Entity\Article, cette classe esty le reflet de la table SQL Article*/
            $article = new Article;

            $article->setTitre("Titre de l'article $i")
                    ->setContenu("<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Eveniet accusamus placeat aliquid hic iusto odit.</p>")
                    ->setPhoto("https://picsum.photos/id/237/300/700")
                    ->setDate(new \DateTime());

            //Nous faisons appel à l'objet $manager issu de la class ObjectManager
            //Une classe qui permet entre autre de manipuler les lignes de la BDD (INSERT,UPDATE,DELETE)
            //persist() : Méthode issu de la class ObjectManager (ORM Doctrine) permettant de garder en mémoire les 10 objets $articles et de préparer les requêtes SQL.
            $manager->persist($article);
        }
        // $product = new Product();
        // $manager->persist($product);

        //FLush() est une méthode issu de la clase ObjectManager (ORM Doctrine) permettant véritablement d'exécuter les requetes SQL en BDD.
        $manager->flush();
    }
}
