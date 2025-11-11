#!/bin/sh

# Attendre que la base de données soit prête (si nécessaire)
# Vous pouvez ajouter des commandes ici pour attendre la base de données

# Générer la clé d'application si elle n'existe pas
if [ ! -f .env ]; then
    cp .env.example .env
fi

php artisan key:generate

# Exécuter les migrations
php artisan migrate

# Démarrer le serveur
exec "$@"