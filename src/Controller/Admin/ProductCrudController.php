<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Identité');
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom');
        yield SlugField::new('slug')->setTargetFieldName('nom')->hideOnIndex();
        yield TextField::new('sku', 'SKU / Réf.');
        yield AssociationField::new('maison')->setRequired(false);
        yield AssociationField::new('categories')->setFormTypeOption('by_reference', false);

        yield FormField::addFieldset('Prix & disponibilité');
        yield MoneyField::new('prixCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);
        yield TextField::new('stockAffiche', 'Stock affiché')
            ->setHelp('Narratif, ex. « 3 exemplaires. Sur Terre. »')
            ->hideOnIndex();
        yield TextField::new('badge')->hideOnIndex();
        yield BooleanField::new('publie', 'Publié');

        yield FormField::addFieldset('Contenu')->hideOnIndex();
        yield TextareaField::new('descriptionMarketing', 'Description (vitrine)')
            ->setHelp('La version vendeuse.')
            ->hideOnIndex();
        yield TextareaField::new('descriptionVraie', 'Description (vraie)')
            ->setHelp('La version qui révèle. Ton satirique.')
            ->hideOnIndex();

        yield FormField::addFieldset('Visuels')->hideOnIndex();
        yield CollectionField::new('images', 'Images')
            ->useEntryCrudForm(ProductImageCrudController::class)
            ->hideOnIndex();

        yield FormField::addFieldset('SEO')->collapsible()->hideOnIndex();
        yield TextField::new('seoTitle', 'Titre SEO')->hideOnIndex();
        yield TextareaField::new('seoDescription', 'Meta description')->hideOnIndex();
    }
}
