<?php

namespace App\Controller\Admin;

use App\Entity\JournalArticle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class JournalArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return JournalArticle::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Journal')
            ->setDefaultSort(['publieLe' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Article');
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('titre');
        yield SlugField::new('slug')->setTargetFieldName('titre')->hideOnIndex();
        yield TextField::new('rubrique');
        yield IntegerField::new('tempsLecture', 'Temps de lecture (min)');
        yield TextField::new('auteur')->hideOnIndex();
        yield DateTimeField::new('publieLe', 'Publié le')
            ->setHelp('Vide = brouillon. Date future = programmé.');

        yield FormField::addFieldset('Contenu')->hideOnIndex();
        yield TextareaField::new('chapo', 'Chapô')->hideOnIndex();
        yield TextareaField::new('corps', 'Corps (HTML)')
            ->setNumOfRows(18)
            ->hideOnIndex();

        yield FormField::addFieldset('SEO')->collapsible()->hideOnIndex();
        yield TextField::new('seoTitle', 'Titre SEO')->hideOnIndex();
        yield TextareaField::new('seoDescription', 'Meta description')->hideOnIndex();
    }
}
