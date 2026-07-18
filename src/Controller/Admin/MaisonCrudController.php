<?php

namespace App\Controller\Admin;

use App\Entity\Maison;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MaisonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Maison::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Maison')
            ->setEntityLabelInPlural('Maisons')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom');
        yield SlugField::new('slug')->setTargetFieldName('nom')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();
    }
}
