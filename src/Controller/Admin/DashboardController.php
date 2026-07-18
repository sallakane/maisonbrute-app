<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Maison Brute — Administration')
            ->setFaviconPath('favicon.ico')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Commerce');
        yield MenuItem::linkTo(OrderCrudController::class, 'Commandes', 'fa fa-receipt');

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fa fa-box-archive');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fa fa-sitemap');
        yield MenuItem::linkTo(MaisonCrudController::class, 'Maisons', 'fa fa-landmark');

        yield MenuItem::section('Accès');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-user-shield');

        yield MenuItem::section();
        yield MenuItem::linkToUrl('Voir la boutique', 'fa fa-store', '/');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-right-from-bracket');
    }
}
