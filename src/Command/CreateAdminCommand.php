<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

/**
 * Crée (ou met à jour) un compte administrateur. Indispensable en prod, où le seed de
 * démo n'est pas chargé. Utilisation :
 *   docker compose -f compose.prod.yaml exec app php bin/console app:create-admin admin@maisonbrute.fr
 */
#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée ou met à jour un compte administrateur (ROLE_ADMIN).',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail de l\'administrateur')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Mot de passe (sinon demandé interactivement)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = trim((string) $input->getArgument('email'));

        $violations = Validation::createValidator()->validate($email, new Email());
        if (\count($violations) > 0) {
            $io->error('Adresse e-mail invalide.');

            return Command::INVALID;
        }

        $password = $input->getOption('password');
        if ($password === null) {
            $question = (new Question('Mot de passe : '))->setHidden(true)->setHiddenFallback(false);
            $password = $io->askQuestion($question);
        }
        if (!\is_string($password) || \strlen($password) < 8) {
            $io->error('Le mot de passe doit comporter au moins 8 caractères.');

            return Command::INVALID;
        }

        $user = $this->users->findOneBy(['email' => $email]);
        $nouveau = $user === null;
        if ($nouveau) {
            $user = (new User())->setEmail($email);
        }

        $roles = $user->getRoles();
        if (!\in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles(array_values(array_unique($roles)));
        }
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('%s : %s (ROLE_ADMIN).', $nouveau ? 'Administrateur créé' : 'Administrateur mis à jour', $email));

        return Command::SUCCESS;
    }
}
