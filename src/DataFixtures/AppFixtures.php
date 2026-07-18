<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Maison;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // --- Compte administrateur (dev) ---
        $admin = new User();
        $admin->setEmail('admin@maisonbrute.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin'));
        $manager->persist($admin);

        // --- Maison (marque fictive) ---
        $atelier = (new Maison())
            ->setNom('Atelier du Presque')
            ->setSlug('atelier-du-presque')
            ->setDescription("Fabrique d'objets suspendus entre l'intention et l'arrivée.");
        $manager->persist($atelier);

        // --- Catégories (rayons) ---
        $reliques = (new Category())
            ->setNom('Reliques')
            ->setSlug('reliques')
            ->setDescription("Des objets dont la rareté confine à l'absence.")
            ->setSeoTitle('Reliques — Maison Brute')
            ->setSeoDescription('Pièces rares, numérotées, destinées à ne jamais tout à fait arriver.');
        $manager->persist($reliques);

        $eclairage = (new Category())
            ->setNom('Éclairage')
            ->setSlug('eclairage')
            ->setDescription('La lumière, rationnée avec goût.')
            ->setSeoTitle('Éclairage — Maison Brute')
            ->setSeoDescription('Appliques et sources rares. Livraison conjecturale.');
        $manager->persist($eclairage);

        $acoustique = (new Category())
            ->setNom('Acoustique')
            ->setSlug('acoustique')
            ->setDescription('Le silence, mis en objet.')
            ->setSeoTitle('Acoustique — Maison Brute')
            ->setSeoDescription("Absorbeurs et pièces sonores d'exception.");
        $manager->persist($acoustique);

        // --- Produits (tirés des maquettes) ---
        $data = [
            [
                'nom' => 'Le Vide Contenu', 'slug' => 'le-vide-contenu', 'sku' => 'MB-4042-77',
                'prix' => 240000, 'cats' => [$reliques],
                'badge' => 'Objet du mois', 'stock' => '1 exemplaire. Quelque part.',
                'marketing' => "Un flacon soufflé à la bouche, scellé sur du vide. Chaque pièce est numérotée, certifiée, et destinée à ne jamais quitter tout à fait notre entrepôt.",
                'vraie' => "C'est un flacon vide. Il restera vide, et il restera chez nous. Vous payez la certitude de son existence.",
            ],
            [
                'nom' => 'Le Silence Domestique', 'slug' => 'le-silence-domestique', 'sku' => 'MB-118',
                'prix' => 620000, 'cats' => [$acoustique],
                'badge' => 'Édition limitée', 'stock' => 'Quelques unités. Sur Terre.',
                'marketing' => "Un absorbeur de bruit d'intérieur, taillé pour éteindre le monde autour de vous.",
                'vraie' => "Il absorbe surtout l'attente. Le colis, lui, reste silencieux aussi.",
            ],
            [
                'nom' => "L'Objet Différé", 'slug' => 'objet-differe', 'sku' => 'MB-204',
                'prix' => 390000, 'cats' => [$reliques],
                'badge' => 'Dernières unités sur Terre', 'stock' => '3 exemplaires. Sur Terre.',
                'marketing' => "Un presse-papier d'une densité rare, pensé pour tenir en place ce qui menace de s'envoler.",
                'vraie' => "Il tient en place. Lui. Sa livraison, en revanche, plane indéfiniment.",
            ],
            [
                'nom' => 'La Patine Anticipée', 'slug' => 'patine-anticipee', 'sku' => 'MB-091',
                'prix' => 185000, 'cats' => [$reliques],
                'badge' => 'Épuisé pour cause de finitude', 'stock' => 'Épuisé.',
                'marketing' => "Une pièce déjà vieillie pour vous, afin que le temps n'ait plus rien à faire.",
                'vraie' => "Vieillie en effet. Comme votre patience, à force d'attendre le convoyage.",
                'publie' => false,
            ],
            [
                'nom' => 'La Lumière Rationnée', 'slug' => 'lumiere-rationnee', 'sku' => 'MB-337',
                'prix' => 475000, 'cats' => [$eclairage],
                'badge' => '3 exemplaires. Sur Terre.', 'stock' => '3 exemplaires. Sur Terre.',
                'marketing' => "Une applique qui distribue la clarté avec la parcimonie d'un bien précieux.",
                'vraie' => "Elle éclaire peu, et de loin. Un peu comme nos délais de livraison.",
            ],
        ];

        $pos = 0;
        foreach ($data as $row) {
            $product = (new Product())
                ->setNom($row['nom'])
                ->setSlug($row['slug'])
                ->setSku($row['sku'])
                ->setPrixCents($row['prix'])
                ->setDevise('EUR')
                ->setMaison($atelier)
                ->setBadge($row['badge'])
                ->setStockAffiche($row['stock'])
                ->setDescriptionMarketing($row['marketing'])
                ->setDescriptionVraie($row['vraie'])
                ->setSeoTitle($row['nom'].' — Maison Brute')
                ->setSeoDescription(mb_substr($row['marketing'], 0, 150))
                ->setPublie($row['publie'] ?? true);

            foreach ($row['cats'] as $cat) {
                $product->addCategory($cat);
            }

            $image = (new ProductImage())
                ->setChemin('/images/placeholder-'.($pos % 3 + 1).'.svg')
                ->setAlt($row['nom'].' — visuel produit')
                ->setPosition(0);
            $product->addImage($image);

            $manager->persist($product);
            ++$pos;
        }

        $manager->flush();
    }
}
