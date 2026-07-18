#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	# Attendre que Postgres réponde avant de démarrer (évite les crashs au boot).
	if [ -n "$DATABASE_URL" ]; then
		echo 'En attente de la base de données...'
		ATTEMPTS=0
		until php bin/console dbal:run-sql 'SELECT 1' >/dev/null 2>&1 || [ "$ATTEMPTS" -ge 30 ]; do
			ATTEMPTS=$((ATTEMPTS + 1))
			sleep 1
		done
	fi

	# Migrations + création des tables de transport Messenger, sur le SEUL service
	# portant RUN_MIGRATIONS=1 (l'app, jamais le worker : deux conteneurs migrant
	# le même schéma en parallèle finiraient mal).
	#
	# Un échec de migration empêche le conteneur de démarrer — c'est voulu : mieux
	# vaut un site down qu'un site servant un schéma qu'il ne comprend pas.
	if [ "${RUN_MIGRATIONS:-0}" = '1' ]; then
		echo 'Migrations Doctrine...'
		php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
		# Le transport Messenger est en doctrine://default?auto_setup=0 : on crée la
		# table messenger_messages explicitement (sinon les e-mails async en prod
		# échouent au premier envoi).
		php bin/console messenger:setup-transports --no-interaction || true
	fi
fi

exec docker-php-entrypoint "$@"
