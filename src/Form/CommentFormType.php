<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if($options ['commentFormBack'] == true)
        $builder
            ->add('auteur', TextType::class,[
                'label' => 'Nom',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir votre nom'
                    ])
                ]
            ])
            ->add('commentaire', TextType::class,[
                'label' => 'Saisir votre commentaire',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir votre commentaire'
                        
                    ])
                ]
            ])
        ;
    

    elseif($options['commentFormFront'] == true)
    {
        $builder
        
        ->add('commentaire', TextType::class,[
            'label' => 'Saisir votre commentaire',
            'required' => false,
            'constraints' => [
                new NotBlank([
                    'message' => 'Merci de saisir votre commentaire'
                ])
            ]
        ]);
    }
}
    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'commentFormFront' =>false,
            'commentFormBack' => false
        ]);
    }
}
