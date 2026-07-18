<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Lecture seule : les commandes ne se créent/modifient/suppriment pas depuis l'admin.
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield TextField::new('reference', 'Référence');
        yield TextField::new('email', 'E-mail')->onlyOnDetail();
        yield TextField::new('emailInvite', 'E-mail (invité)')->hideOnIndex();
        yield AssociationField::new('customer', 'Client')->hideOnIndex();
        yield TextField::new('etat', 'État')->formatValue(fn ($v) => str_replace('_', ' ', (string) $v));
        yield TextField::new('statutPaiementLabel', 'Paiement');
        yield MoneyField::new('montantCents', 'Montant')->setCurrency('EUR')->setStoredAsCents(true);
        yield TextField::new('transporteur')->hideOnIndex();
        yield TextField::new('livraisonNom', 'Destinataire')->onlyOnDetail();
        yield TextField::new('livraisonLigne1', 'Adresse')->onlyOnDetail();
        yield TextField::new('livraisonCp', 'Code postal')->onlyOnDetail();
        yield TextField::new('livraisonVille', 'Ville')->onlyOnDetail();
        yield TextField::new('stripePaymentIntentId', 'Stripe PI')->onlyOnDetail();
        yield DateTimeField::new('createdAt', 'Créée le');
    }
}
