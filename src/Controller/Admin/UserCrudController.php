<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['email' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield EmailField::new('email');
        yield ChoiceField::new('roles')
            ->setChoices(['Administrateur' => 'ROLE_ADMIN', 'Client' => 'ROLE_USER'])
            ->allowMultipleChoices()
            ->renderExpanded()
            ->onlyOnForms();
        yield ArrayField::new('roles', 'Rôles')->onlyOnIndex();
        yield TextField::new('plainPassword', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->setFormTypeOption('required', $pageName === Crud::PAGE_NEW)
            ->setHelp($pageName === Crud::PAGE_EDIT ? 'Laisser vide pour conserver le mot de passe actuel.' : '')
            ->onlyOnForms();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->hashPlainPassword($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->hashPlainPassword($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPlainPassword(User $user): void
    {
        $plain = $user->getPlainPassword();
        if ($plain === null || $plain === '') {
            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
        $user->eraseCredentials();
    }
}
