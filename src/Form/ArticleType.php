<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class,[
                'label' => "Titre de l'article",
                'required' => false,
                'attr'=>[
                    'placeholder' => "Saisir le titre de l'article",
                ],
                'constraints' => [
                    new Length([
                        'min' => 10, 
                        'max' => 50,
                        'minMessage' => "Titre trop court",
                        'maxMessage'=> "Titre trop long"
                    ]),
                    new NotBlank([
                        'message' => "Merci de sasir un titre d'article"
                    ])
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'attr'=> [
                    'placeholder' => "Saisir le contenu de l'article",
                    'row' => 10
                ],
                'required'=>false,
                'constraints'=>[
                    new NotBlank([
                        'message'=>"Merci de saisir un contenu"
                    ])
                ]
            ])
            ->add('photo', FileType::class, [
                'label' => "Uploader une photo",
                'mapped' => true, // siginifie que le champ est associé à une propriété et qu'il sera inséré en BDD
                'required' => false,
                'data_class'=> null,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg'
                        ],
                        'mimeTypesMessage' => 'Formats autorisés : jpg/jpeg/png'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
