<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CategoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class,[
                'label' => "Titre de la catégorie",
                'attr'=>[
                    'placeholder'=> "Veuillez indiquer le titre de la catégorie"
                ],
                'constraints' => [
                    new Length([
                        'min' => 3, 
                        'max' => 50,
                        'minMessage' => "Titre trop court",
                        'maxMessage'=> "Titre trop long"
                    ]),
                    new NotBlank([
                        'message' => "Merci de sasir un titre de catégorie"
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'attr'=> [
                    'placeholder' => "Saisir la description de la catégorie",
                    'row' => 10
                ],
                'required'=>false,
                'constraints'=>[
                    new NotBlank([
                        'message'=>"Merci de saisir une description"
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
