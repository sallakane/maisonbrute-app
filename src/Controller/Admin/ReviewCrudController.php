<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Avis')
            ->setEntityLabelInPlural('Avis')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('auteur');
        yield ChoiceField::new('note')
            ->setChoices([5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1])
            ->renderAsBadges();
        yield TextField::new('attente', 'Attente')->hideOnIndex();
        yield TextareaField::new('texte')->hideOnIndex();
        yield AssociationField::new('product', 'Produit')->setRequired(false);
        // Switch de modération, basculable directement depuis la liste.
        yield BooleanField::new('modere', 'Publié');
        yield DateTimeField::new('createdAt', 'Déposé le')->onlyOnIndex();
    }
}
