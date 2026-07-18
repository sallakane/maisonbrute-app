<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auteur', TextType::class, [
                'label' => 'Votre nom (ou pseudonyme)',
            ])
            ->add('note', ChoiceType::class, [
                'label' => 'Votre note',
                'choices' => [
                    '★★★★★ — 5' => 5,
                    '★★★★ — 4' => 4,
                    '★★★ — 3' => 3,
                    '★★ — 2' => 2,
                    '★ — 1' => 1,
                ],
                'placeholder' => false,
                'data' => 5,
            ])
            ->add('attente', TextType::class, [
                'label' => 'Depuis combien de temps attendez-vous ?',
                'required' => false,
                'attr' => ['placeholder' => 'ex. 3 ans'],
            ])
            ->add('texte', TextareaType::class, [
                'label' => 'Votre témoignage',
                'attr' => ['placeholder' => 'Décrivez votre patience…', 'rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
