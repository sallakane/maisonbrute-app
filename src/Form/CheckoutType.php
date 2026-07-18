<?php

namespace App\Form;

use App\Checkout\CheckoutData;
use App\Checkout\Transporteur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transporteurs = [];
        foreach (Transporteur::CHOIX as $code => $def) {
            $transporteurs[$def['nom']] = $code;
        }

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'disabled' => $options['email_locked'],
            ])
            ->add('nom', TextType::class, ['label' => 'Nom du destinataire'])
            ->add('ligne1', TextType::class, ['label' => 'Adresse'])
            ->add('cp', TextType::class, ['label' => 'Code postal'])
            ->add('ville', TextType::class, ['label' => 'Ville'])
            ->add('pays', TextType::class, ['label' => 'Pays'])
            ->add('transporteur', ChoiceType::class, [
                'label' => 'Mode de convoyage',
                'choices' => $transporteurs,
                'expanded' => true,
                'placeholder' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CheckoutData::class,
            'email_locked' => false,
        ]);
        $resolver->setAllowedTypes('email_locked', 'bool');
    }
}
