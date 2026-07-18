# Déploiement VPS — Maison Brute (`maisonbrute-app`)

Pile prod : **FrankenPHP** (Caddy+PHP) + **PostgreSQL** + **worker Messenger**, orchestrés par
`compose.prod.yaml`, derrière le **Caddy mutualisé** du VPS (TLS Let's Encrypt automatique).

> ⚠️ Cette app **remplace** l'ancien `~/Projects/maisonbrute` (dropshipping CJ) sur `maisonbrute.fr`.
> N'en déployer **qu'une seule** sur le port **8084**. Si l'ancienne y tourne, l'arrêter d'abord
> (`sudo systemctl disable --now maisonbrute`).

---

## 1. À régler AVANT d'ouvrir au public

Aucun de ces points ne se voit sur un site qui répond 200.

| # | Sujet | Sans ça | Où |
|---|-------|---------|-----|
| 1 | **`MAILER_DSN`** | Défaut `null://null` ⇒ **aucun e-mail** (confirmation, expédition), jeté en silence. | `.env.local` |
| 2 | **`DEFAULT_URI=https://maisonbrute.fr`** | Les e-mails (générés dans le worker, hors HTTP) contiennent des liens `http://localhost`. | `.env.local` |
| 3 | **`STRIPE_WEBHOOK_SECRET`** | Sans lui, `/webhook/stripe` renvoie 503 : le paiement n'est confirmé que par la réconciliation au retour navigateur (fragile). | `.env.local` + dashboard Stripe |
| 4 | **Clés Stripe** | `sk_test_` n'encaisse rien de réel (voulu tant que c'est une satire). Passer en `sk_live_` seulement si l'on veut encaisser — mais alors **ce n'est plus une satire** (cf. garde-fou juridique). | `.env.local` |
| 5 | **`POSTGRES_PASSWORD`** | Lu seulement à l'init du volume : le définir **avant** le 1er démarrage. | `.env.local` |
| 6 | **Mentions légales réelles** | Éditeur, hébergeur, contact. Les `/cgv` et `/confidentialite` portent déjà l'avertissement satirique/fictif, mais un vrai site public doit identifier son éditeur (LCEN). | `templates/content/*` |

---

## 2. Premier déploiement

```bash
# ── 1. Le code
sudo mkdir -p /var/www && cd /var/www
sudo git clone git@github.com:sallakane/maisonbrute-app.git
sudo chown -R deploy:deploy /var/www/maisonbrute-app
cd /var/www/maisonbrute-app

# ── 2. Les secrets  ⚠️ AVANT le premier démarrage
cp .env.local.example .env.local
$EDITOR .env.local          # cf. tableau §1 + APP_SECRET + POSTGRES_PASSWORD
chmod 600 .env.local
```

### VPS mutualisé — vérifier le port AVANT

```bash
ss -tlnp | grep 127.0.0.1:80    # ports déjà pris
docker ps                        # par qui
```
Ports occupés au 2026-07-16 : 8001 (rapport-generator), 8009/8080 (sunu-cagnotte),
8082 (intranet BCEAO), 8083 (Keycloak), **8084 (Maison Brute)**. `APP_HTTP_PORT=8084`.

> ⚠️ **Ne jamais lancer `docker compose up` à la main.** Compose n'interpole les `${...}`
> qu'à partir de `.env` (versionné, sans secret) ou de son shell — **jamais** `.env.local`.
> Seul `EnvironmentFile=` du service systemd les fournit. Un `up` manuel résout
> `${POSTGRES_PASSWORD}` en vide → `password authentication failed`. `exec`/`logs`/`ps`
> ne recréent rien et sont sans risque (c'est ce que font les crons).

```bash
# ── 3. Le service (build + up + migrations + setup Messenger automatiques)
sudo cp infra/maisonbrute-app.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now maisonbrute-app

# ── 4. Vérifier AVANT d'exposer le domaine
docker compose -f compose.prod.yaml ps          # app/worker/database « healthy »
curl -s localhost:8084/health                    # {"status":"ok","db":"ok"}
docker compose -f compose.prod.yaml logs app | grep -i migrat

# Créer le compte admin (pas de seed démo en prod)
docker compose -f compose.prod.yaml exec app php bin/console app:create-admin toi@maisonbrute.fr -p 'MotDePasseFort'

# ── 5. Le domaine (Caddy mutualisé)
sudo cp /etc/caddy/Caddyfile /etc/caddy/Caddyfile.bak.$(date +%F-%H%M%S)
sudo $EDITOR /etc/caddy/Caddyfile                # y coller infra/Caddyfile.snippet
sudo caddy validate --config /etc/caddy/Caddyfile   # ⚠️ valider AVANT
sudo systemctl reload caddy                          # sans coupure pour les voisins

# ── 6. Stripe webhook
# Dashboard Stripe → Developers → Webhooks → endpoint : https://maisonbrute.fr/webhook/stripe
# Événement : checkout.session.completed. Copier le whsec_… dans .env.local, puis :
sudo systemctl restart maisonbrute-app

# ── 7. Les tâches planifiées
sudo mkdir -p /var/backups/maisonbrute && sudo chown deploy:deploy /var/backups/maisonbrute
sudo touch /var/log/maisonbrute-backup.log && sudo chown deploy:deploy /var/log/maisonbrute-backup.log
sudo cp infra/maisonbrute-backup.sh /usr/local/bin/maisonbrute-backup && sudo chmod +x /usr/local/bin/maisonbrute-backup
sudo cp infra/maisonbrute-backup.cron          /etc/cron.d/maisonbrute-backup
sudo cp infra/maisonbrute-advance-tracking.cron /etc/cron.d/maisonbrute-advance-tracking
```

## 3. Mises à jour

```bash
cd /var/www/maisonbrute-app && git pull
sudo systemctl restart maisonbrute-app          # rebuild + migrations + setup Messenger
```

## 4. Exploitation

```bash
# Logs
docker compose -f compose.prod.yaml logs -f app
docker compose -f compose.prod.yaml logs -f worker
# Console dans le conteneur (sans risque)
docker compose -f compose.prod.yaml exec app php bin/console <cmd>
# Créer un admin (le seed de démo n'est PAS chargé en prod ; mot de passe demandé si -p absent)
docker compose -f compose.prod.yaml exec app php bin/console app:create-admin toi@maisonbrute.fr -p 'MotDePasseFort'
```

> Le **worker** consomme les e-mails (async en prod). S'il meurt, les e-mails s'accumulent
> dans `messenger_messages` sans être envoyés : `restart: unless-stopped` le relance, et
> `docker compose ... ps` permet de le surveiller.
