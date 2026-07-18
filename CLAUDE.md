# Maison Brute — CLAUDE.md

Boutique e-commerce **satirique et fictive**. Le concept : des objets de « luxe » si rares que la
livraison n'arrive **jamais**. La mécanique comique (la commande éternellement `en_transit`) est
**encodée dans l'architecture** (Symfony Workflow). Ce fichier fait autorité pour le projet.

⚖️ **Garde-fou juridique — non négociable** : Stripe en **mode test uniquement**, **aucun
encaissement réel**. Vendre un bien livré serait une escroquerie, pas une satire. CGV, footer et
mentions doivent porter le caractère parodique.

## Stack

- PHP 8.4 (hôte) · **Symfony 7.4 LTS** · Doctrine ORM · **PostgreSQL 16**
- Twig + **Tailwind** (`symfonycasts/tailwind-bundle`) + Symfony UX (Turbo/Stimulus) — à venir
- **EasyAdmin 5** (back-office) · Symfony Security (comptes clients + admin) — à venir
- Stripe (`stripe/stripe-php`, test) · Symfony Mailer · Workflow · Scheduler · Translation — à venir

## Environnement de dev

Workflow : **services en Docker** (Postgres + Mailpit) + **app servie par la Symfony CLI** sur l'hôte.

```bash
docker compose up -d            # postgres:5434, mailpit SMTP:1027 / UI http://localhost:8027
symfony server:start -d         # app http://localhost:8000
```

- Config locale non commitée dans `.env.local` (DATABASE_URL → port 5434, MAILER_DSN → mailpit 1027).
- Ports choisis pour **ne pas entrer en conflit** avec l'ancien projet `~/Projects/maisonbrute`
  (qui utilise 5433/8026). Ce projet-ci n'a **aucun lien** avec l'ancien (pas de CJ, pas de vrai
  dropshipping) : refonte from scratch d'un concept neuf.
- Extension requise sur l'hôte : `php8.4-pgsql`.

## Commandes utiles

```bash
php bin/console doctrine:migrations:migrate      # appliquer les migrations
php bin/console make:migration                   # générer depuis le diff d'entités
php bin/phpunit                                   # tests
```

## Références de conception

- `docs/architecture.md` — le blueprint complet (stack, domaine, workflow, tunnel, SEO, ordre de build).
- `design-ref/*.dc.html` — les 16 maquettes (comps `.dc.html`). **Ne pas réutiliser telles quelles** :
  les traduire en Twig Components en respectant fidèlement les design tokens ci-dessous.

### Design tokens (extraits des maquettes)

- Couleurs : fond `#E8E8E3`, encre `#141416`, vert-de-gris `#7E8C82`, fond alt `#E1E5E0`,
  gris texte `#3B3C3D` / `#6E6F6A`.
- Typos : **Archivo** (titres, 800/900, uppercase, letter-spacing négatif), **IBM Plex Sans** (corps),
  **IBM Plex Mono** (labels/prix/refs, uppercase tracké), **Playfair Display** italic (accents).
- Ton : luxe froid, refs produit `MB-xxxx`, stock « 3 exemplaires. Sur Terre. », « bon de convoyage ».
- Accessibilité : focus visible, `prefers-reduced-motion` respecté.

## Ordre de construction (blueprint §10) — v1 = tranche verticale, FR d'abord

1. ✅ Squelette Symfony + Docker (Postgres/Mailpit) + serveur CLI.
2. ⏳ Tailwind + tokens · EasyAdmin · Security · entités catalogue + CRUD + seed démo.
3. Front SSR : accueil, catégorie, fiche produit (+ SEO + JSON-LD).
4. Panier → tunnel → Stripe test → webhook → `Order` + e-mail confirmation.
5. Workflow commande (`en_transit ⟲`, jamais `livree`) + Scheduler + bon de convoyage + suivi.
6→9. Avis · Journal (CMS SEO) · compteur planétaire / CGV parodiques / OG / sitemap · déploiement VPS Hostinger.
