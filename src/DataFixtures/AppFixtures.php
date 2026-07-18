<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Maison;
use App\Entity\JournalArticle;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\Review;
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
        $produits = [];
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
            $produits[$row['slug']] = $product;
            ++$pos;
        }

        // --- Avis (modérés, publiés) — le mur satirique ---
        $avis = [
            ['auteur' => 'Hélène D.', 'attente' => "4 ans d'attente", 'note' => 5, 'produit' => null,
                'texte' => "Commandé en 2022. Toujours pas reçu. Le service client est admirable de constance : il me répond chaque année, à la même date, le même message rassurant. Je recommande."],
            ['auteur' => 'Marc-Antoine V.', 'attente' => 'attente sereine', 'note' => 5, 'produit' => 'le-vide-contenu',
                'texte' => "Au début, on guette le facteur. Puis on comprend. L'objet n'arrivera pas, et c'est très bien ainsi : il ne pourra jamais me décevoir. Cinq étoiles."],
            ['auteur' => 'Sabine R.', 'attente' => "6 ans d'attente", 'note' => 4, 'produit' => 'le-vide-contenu',
                'texte' => "Une étoile en moins car le bon de convoyage est arrivé légèrement froissé. Le colis, lui, n'est pas arrivé du tout — mais cela, je ne saurais le leur reprocher."],
            ['auteur' => 'Anonyme', 'attente' => 'client depuis toujours', 'note' => 5, 'produit' => 'lumiere-rationnee',
                'texte' => "Je ne me souviens plus de ce que j'ai commandé, ni quand. Mais chaque matin, l'espoir est intact. C'est un abonnement au désir. Merci Maison Brute."],
            ['auteur' => 'Th. Lemaître', 'attente' => "2 ans d'attente", 'note' => 5, 'produit' => 'objet-differe',
                'texte' => "L'Objet Différé porte admirablement son nom. Je l'ai différé, il me diffère. Nous sommes quittes, et ravis."],
        ];

        foreach ($avis as $a) {
            $review = (new Review())
                ->setAuteur($a['auteur'])
                ->setAttente($a['attente'])
                ->setNote($a['note'])
                ->setTexte($a['texte'])
                ->setModere(true);
            if ($a['produit'] !== null && isset($produits[$a['produit']])) {
                $review->setProduct($produits[$a['produit']]);
            }
            $manager->persist($review);
        }

        // --- Journal (articles éditoriaux SEO) ---
        $articles = [
            [
                'titre' => "Éloge de l'attente", 'slug' => 'eloge-de-l-attente',
                'rubrique' => 'Philosophie', 'temps' => 7, 'jours' => 12,
                'chapo' => "Nous avons appris à confondre la possession et la réception. Et si l'objet le plus précieux était celui que l'on attend indéfiniment ? Petit traité à l'usage des impatients.",
                'corps' => "<p>On croit désirer un objet. En réalité, on désire le moment qui précède l'objet — cet intervalle suspendu où tout est encore possible, où la déception n'a pas eu lieu.</p><p>La Maison Brute a fait de cet intervalle son unique produit. Nos objets ne vous parviendront pas, et c'est précisément là leur valeur : ils restent, pour toujours, à l'état de promesse.</p><h2>Le colis de Schrödinger</h2><p>Tant que la boîte n'est pas ouverte, l'objet est à la fois parfait et absent. L'ouvrir, ce serait risquer le réel. Nous vous épargnons ce risque.</p><p>Attendre, ce n'est pas perdre son temps. C'est le tenir en réserve.</p>",
            ],
            [
                'titre' => 'La rareté comme service après-vente', 'slug' => 'rarete-service-apres-vente',
                'rubrique' => 'Service', 'temps' => 5, 'jours' => 20,
                'chapo' => "Comment transformer un délai illimité en argument commercial d'exception.",
                'corps' => "<p>Le service après-vente traditionnel répare. Le nôtre entretient — non pas l'objet, mais le désir de l'objet.</p><p>Chaque relance que nous vous adressons est une œuvre en soi : une lettre qui ne dit rien, mais le dit admirablement. Nos clients les collectionnent.</p><p>La rareté n'est pas un défaut d'approvisionnement. C'est une politique.</p>",
            ],
            [
                'titre' => 'La plus faible empreinte : ne rien livrer', 'slug' => 'plus-faible-empreinte-ne-rien-livrer',
                'rubrique' => 'Écologie', 'temps' => 6, 'jours' => 30,
                'chapo' => "Le colis le plus vert est celui qui ne prend jamais la route. Étude de cas.",
                'corps' => "<p>Un camion qui ne roule pas ne pollue pas. Un avion cargo au sol est un avion vertueux. La logistique la plus durable est celle qui n'a jamais lieu.</p><p>En ne livrant rien, la Maison Brute atteint une empreinte carbone que nos concurrents mettront des décennies à approcher : celle de l'immobilité parfaite.</p><p>Vous n'avez pas acheté un objet. Vous avez financé une absence de trajet. La planète vous remercie.</p>",
            ],
        ];

        foreach ($articles as $art) {
            $manager->persist((new JournalArticle())
                ->setTitre($art['titre'])
                ->setSlug($art['slug'])
                ->setRubrique($art['rubrique'])
                ->setTempsLecture($art['temps'])
                ->setChapo($art['chapo'])
                ->setCorps($art['corps'])
                ->setSeoTitle($art['titre'].' — Le Journal, Maison Brute')
                ->setSeoDescription(mb_substr($art['chapo'], 0, 150))
                ->setPublieLe(new \DateTimeImmutable('-'.$art['jours'].' days')));
        }

        $manager->flush();
    }
}
