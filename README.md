# ReservationApp

ReservationApp est une application Symfony permettant aux utilisateurs de réserver des créneaux horaires pour différents services. Elle propose une interface simple pour choisir un jour et un créneau horaire disponible, avec une confirmation avant la validation de la réservation. Les utilisateurs peuvent également créer et gérer leurs propres services.

## Prérequis

- PHP >= 8.0
- Composer
- Symfony CLI (facultatif mais recommandé)
- Serveur Web (ex : Apache ou Nginx) ou Symfony Local Server
- MySQL ou un autre SGBD compatible avec Doctrine

## Installation

Clonez le dépôt de l'application :

```bash
git clone https://github.com/votre-utilisateur/reservation-app.git
cd reservation-app
```

Installez les dépendances PHP avec Composer :

```bash
composer install
```

## Configuration de l'application

### Fichier `.env`

Dans le fichier `.env`, configurez la connexion à votre base de données en modifiant la ligne suivante avec vos identifiants MySQL :

```dotenv
DATABASE_URL="mysql://username:password@127.0.0.1:3306/reservation_app"
```

### Création de la base de données

Créez la base de données et les tables avec les commandes suivantes :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Chargement des fixtures

Pour ajouter des données de test dans la base de données, vous pouvez utiliser les fixtures. Exécutez la commande suivante pour les charger :

```bash
php bin/console doctrine:fixtures:load
```

Les fixtures créeront des services et des réservations pour tester l'interface.

### Lancer le serveur de développement

Démarrez le serveur Symfony avec la commande suivante :

```bash
symfony server:start
```

L'application sera disponible à l'adresse `http://127.0.0.1:8000`.

## Routes de l'application

### Routes pour les réservations

- **`/`** : Page d'accueil avec la liste des services disponibles.
- **`/booking/choose-date`** : Sélection d'un jour pour la réservation d'un service.
- **`/booking/select-time`** : Affichage des créneaux horaires disponibles pour le jour sélectionné.
- **`/booking/confirm`** : Récapitulatif de la réservation avant confirmation.
- **`/booking/new`** : Enregistrement de la réservation pour le créneau horaire sélectionné.
- **`/bookings`** : Liste des réservations de l'utilisateur connecté.
- **`/booking/{id}/manage`** : Détails et gestion de la réservation (disponible uniquement pour le créateur du service).
- **`/booking/{id}/cancel`** : Annulation d'une réservation.
- **`/booking/{id}/accept`** : Acceptation d'une réservation en attente.

### Routes pour les services

- **`/service/new`** : Page pour créer un nouveau service.
- **`/user/services`** : Page listant les services créés par l'utilisateur connecté, avec les réservations associées pour chaque service.
- **`/service/{id}/edit`** *(si existant)* : Page pour modifier un service existant (seulement pour le créateur du service).
- **`/service/{id}/delete`** *(si existant)* : Suppression d'un service (disponible uniquement pour le créateur du service).

### Routes pour le profil utilisateur

- **`/profile`** : Page de profil de l'utilisateur connecté.
- **`/profile/edit`** *(si existant)* : Page pour modifier les informations du profil de l'utilisateur.

### Licence

Ce projet est sous licence MIT. Vous êtes libre de l'utiliser, le modifier et le distribuer conformément aux termes de cette licence.