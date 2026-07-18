# Architecture Claude Code — « Maison Brute »

> Blueprint pour construire le site avec Symfony. À coller dans Claude Code. La cible : un e-commerce complet, rigoureux et SEO-first, dont la mécanique satirique (la commande qui n'arrive jamais) est **encodée dans l'architecture elle-même**.

---

## 1. Stack (versions de juillet 2026)

- **PHP 8.3**
- **Symfony 7.4 LTS** (support jusqu'à ~2028 ; alternative bleeding-edge : Symfony 8.1 / PHP 8.4)
- **Doctrine ORM** + **PostgreSQL** (MySQL possible)
- **EasyAdmin 5.2** (`easycorp/easyadmin-bundle`) pour tout le back-office
- **Twig** + **Tailwind CSS** (`symfonycasts/tailwind-bundle`)
- **Symfony UX** : Turbo (navigation SPA-like sans sacrifier le SSR) + Stimulus (interactions), Twig Components
- **Stripe** (`stripe/stripe-php`) — **mode test uniquement**
- **Symfony Mailer** + un transporteur transactionnel (Brevo/Mailgun/Postmark)
- **Symfony Workflow** — cycle de vie de la commande
- **Symfony Scheduler** (ou cron) — fait « avancer » le suivi
- **Symfony Security** — comptes clients + accès admin
- **Symfony Translation** — FR par défaut, structure prête pour l'EN

**Déploiement** : sur ton **VPS Hostinger** existant. Docker Compose (php-fpm + nginx + postgres) recommandé pour reproductibilité.

## 2. Modèle de domaine (entités Doctrine)

```
Maison (marque)         id, nom, slug, description
Category                id, nom, slug, parent (self-ref), description, seoMeta
Product                 id, nom, slug, maison, categories[], prixCents, devise,
                        descriptionMarketing, descriptionVraie(satire), sku,
                        stockAffiche("3 sur Terre"), badge, images[], seoMeta, publie
ProductImage            id, product, chemin, alt, position   (images générées par IA)
Cart / CartItem         session ou user, items(product, qty, prixUnitaireCents)
Customer (User)         id, email, password, adresses[], commandes[]   (Security)
Address                 id, customer(null si invité), ligne1, cp, ville, pays
Order                   id, reference(MB-xxxx), customer(null=invité), emailInvite,
                        adresseLivraison, transporteur, montantCents, statutPaiement,
                        etat(workflow), stripePaymentIntentId, createdAt
OrderItem               id, order, product(snapshot nom+prix), qty
TrackingEvent           id, order, code, libelle, createdAt   (le fil du convoyage)
Review                  id, product, auteur, note, texte, moderé, createdAt
JournalArticle          id, titre, slug, chapo, corps, seoMeta, publieLe   (CMS éditorial)
```

Notes :
- `descriptionMarketing` vs `descriptionVraie` : deux champs pour orchestrer le contraste (l'un vendeur, l'autre qui révèle).
- Commande **invité** = `customer` null + `emailInvite` renseigné. Un compte peut être proposé après coup (rattachement par email).
- Snapshot du prix/nom dans `OrderItem` (le catalogue peut changer).

## 3. La commande qui n'arrive jamais (Symfony Workflow)

Machine à états `order` (type `state_machine`) :

```
panier → payee → en_preparation → expediee → en_transit ⟲ (boucle infinie)
                                                    ↓ (jamais)
                                                 livree
```

- La transition vers `livree` **n'est jamais déclenchée** : c'est le cœur de la blague, inscrit dans le workflow.
- Un **handler Scheduler** (ex. toutes les 24 h) parcourt les commandes `en_transit` et **ajoute un `TrackingEvent`** puisé dans une liste de libellés qui dérivent vers l'existentiel (voir brief design). Il n'avance jamais l'état final.
- Chaque transition `payee`/`expediee` déclenche un e-mail via un **listener** sur les événements de workflow.

## 4. Tunnel de commande & paiement

- Étapes : panier → identification (invité **ou** connexion/inscription) → adresse → transporteur → paiement → confirmation.
- **Stripe** : `PaymentIntent` (ou Checkout Session hébergée, plus simple/PCI-friendly). **Mode test exclusivement**, carte `4242 4242 4242 4242`.
- **Webhook Stripe** (`/webhook/stripe`) : à réception de `payment_intent.succeeded`, on applique la transition `panier → payee` et on envoie la confirmation. Ne jamais se fier au retour navigateur seul.
- ⚖️ **Garde-fou juridique** : **on n'encaisse jamais réellement.** Rester en clés de test, ou afficher clairement le caractère fictif. Encaisser pour un bien non livré n'est plus une satire, c'est une escroquerie. Les CGV et le pied de page doivent porter, même à demi-mot, la mention parodique.

## 5. E-mails (Symfony Mailer)

- **Confirmation de commande** : gabarit corporate impeccable, avec la phrase de trop.
- **Mises à jour de suivi** (optionnel) : relances qui n'annoncent jamais l'arrivée.
- Templates Twig + `TemplatedEmail`, transporteur transactionnel en prod, `MAILER_DSN` en test/local via Mailpit.

## 6. Back-office EasyAdmin 5

Dashboard + un `CrudController` par entité :
- **Produits** (avec upload/rattachement des images IA, édition des deux descriptions, badge, stock affiché, statut de publication).
- **Catégories** (arborescence), **Maisons**.
- **Commandes** (lecture, réf, état workflow, historique des `TrackingEvent` — tu peux y écrire manuellement un statut de convoyage bien senti).
- **Avis** (modération : `moderé`).
- **Journal** (CMS des articles éditoriaux/SEO).
- Accès protégé par `ROLE_ADMIN` (Security).

## 7. SEO-first

- **Slugs** propres partout : `/{category}/{product-slug}`, `/journal/{slug}`.
- **JSON-LD `schema.org/Product`** sur chaque fiche (nom, image, prix, disponibilité, `AggregateRating` depuis les avis) — délicieusement ironique de baliser richement des produits qui ne partiront jamais.
- **Sitemap.xml** dynamique + **robots.txt**, balises `<title>`/meta/canonical par page (champ `seoMeta` sur les entités).
- **Performance** : Turbo, cache HTTP, images en `webp`/`avif` + `loading="lazy"`, HTTP/2 — un site ultra-rapide qui vend de la lenteur.
- **Le Journal** est le principal moteur de trafic organique et de partage : contenu long, satirique, optimisé. C'est là que se joue la popularité.
- **Open Graph / Twitter cards** soignées (la fiche produit et le Bon de convoyage doivent être beaux en aperçu de partage).

## 8. Frontend (Twig + Tailwind + UX)

- Templates Twig organisés en **Twig Components** réutilisables (carte produit, badge, bon de convoyage, encart avis…).
- **Turbo** pour la navigation instantanée et le panier sans rechargement ; **Stimulus** pour les micro-interactions (filtres, quantités, compteur planétaire).
- Design tokens (couleurs/typo du brief) en variables Tailwind (`tailwind.config`).
- Accessibilité : focus visible, contrastes, `prefers-reduced-motion`.

## 9. i18n

- FR par défaut. Chaînes dans `translations/` dès le départ, `_locale` routable pour ajouter l'EN sans refonte (l'humour anglais s'exporte bien).

## 10. Ordre de construction suggéré (v1 → complet)

**Hypothèses de départ** (dis-moi si tu ajustes) : **v1 = une tranche verticale propre** sur **2-3 catégories** menée de bout en bout, avec l'archi prête pour tout le catalogue ; **langue FR d'abord**, structure i18n en place.

1. Squelette Symfony + Docker + Tailwind + EasyAdmin + Security.
2. Entités `Category`/`Maison`/`Product`/`ProductImage` + CRUD admin + seed de démo.
3. Front : accueil, catégorie, fiche produit (SSR + SEO + JSON-LD).
4. Panier → tunnel → Stripe test → webhook → `Order` + confirmation e-mail.
5. Workflow commande + Scheduler + Bon de convoyage + page de suivi.
6. Avis (dépôt + modération + affichage + `AggregateRating`).
7. Journal (CMS + pages éditoriales SEO).
8. Compteur planétaire, CGV/cookies parodiques, 404, Open Graph, sitemap.
9. Déploiement VPS Hostinger, clés Stripe test, domaine `maisonbrute.fr`.

## 11. Conformité & sécurité

- **Stripe en test**, aucun encaissement réel.
- **RGPD** : bandeau cookies, page vie privée (parodique mais réelle), minimisation des données.
- **Mentions légales / CGV** portant le caractère fictif et satirique de la boutique.
- Hardening standard : HTTPS, CSRF sur les formulaires, rate-limiting sur login/inscription, secrets hors du dépôt (`.env.local` / secrets vault).
