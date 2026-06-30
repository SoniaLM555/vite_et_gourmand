# Vite & Gourmand - Application Web

Application web permettant la présentation et la commande de menus de prestation traiteur pour l'entreprise Vite & Gourmand.
**Application en ligne :** https://vite-et-gourmand-wandering-tide-4117.fly.dev/

*Ce projet a été développé et testé sur Windows (PHP 8.5, Laragon). Les instructions d'installation ci-dessous sont adaptées à cet environnement.*

---

## Table des matières

- [Prérequis](#prérequis)
- [Installation](#installation)
  - [1. Clonage du dépôt](#1-clonage-du-dépôt)
  - [2. Installation des dépendances](#2-installation-des-dépendances)
  - [3. Configuration de l'environnement](#3-configuration-de-lenvironnement)
  - [4. Base de données](#4-base-de-données)
  - [5. Lancement de l'application](#5-lancement-de-lapplication)
- [Choix techniques](#choix-techniques)
  - [Architecture hybride MySQL / MongoDB](#architecture-hybride-mysql--mongodb)
  - [APIs externes](#apis-externes)
  - [Envoi d'e-mails](#envoi-de-mails)
  - [Sécurisation](#sécurisation)

---

## Prérequis

- **PHP 8.5**
  Extensions requises : `pdo_mysql`, `mongodb`, `intl`, `mbstring`
  Vérifier : `php -v` puis `php -m`
  Téléchargement : https://www.php.net/downloads

- **Composer** (dernière version stable)
  Vérifier : `composer -V`
  Téléchargement : https://getcomposer.org/download/

- **Symfony CLI** (compatible Symfony 8.0)
  Vérifier : `symfony check:requirements`
  Installation : https://symfony.com/download

- **MySQL**
  Via Laragon, WAMP, ou installation autonome
  Laragon : https://laragon.org/download/

- **MongoDB** (Community Server - version gratuite et open source )
  Téléchargement : https://www.mongodb.com/try/download/community
  L'extension PHP `mongodb` doit être activée dans `php.ini`: télécharger `php_mongodb.dll` sur https://pecl.php.net/package/mongodb, la placer dans le dossier `ext/` de PHP, puis ajouter `extension=mongodb` dans `php.ini`.

---

## Installation

### 1. Clonage du dépôt

```bash
git clone <url-du-depot>
cd vite-et-gourmand
```

### 2. Installation des dépendances

```bash
composer install
```

### 3. Configuration de l'environnement

Créer un fichier `.env.local` à la racine du projet à partir du fichier `.env`, puis y renseigner les variables suivantes :

```bash
# Configuration MySQL
DATABASE_URL="mysql://root:@127.0.0.1:3306/vite_et_gourmand?serverVersion=8.0&charset=utf8mb4"

# Configuration MongoDB
MONGODB_URI="mongodb://127.0.0.1:27017"
MONGODB_DB="vite_et_gourmand"

# Configuration Mailer (utiliser 'null://default' pour le développement)
MAILER_DSN="null://default"
```

### 4. Base de données

**Import de la structure et des données** (incluant les comptes de démonstration et le compte administrateur) :

```bash
mysql -u root -p vite_et_gourmand < annexes_sql/1_structure.sql
mysql -u root -p vite_et_gourmand < annexes_sql/2_jeu_d_essai.sql
```

> **Note :** Si la commande `mysql` n'est pas reconnue dans votre terminal, ouvrez le terminal intégré de Laragon via le menu *Terminal* > *MySQL* et relancez la commande depuis là.

**Application des migrations Doctrine** :

```bash
php bin/console doctrine:migrations:migrate
```

> **MongoDB :** aucune création préalable n'est nécessaire. La base et la collection de statistiques sont créées automatiquement lors de la première synchronisation des données.

> **Comptes de connexion :** contacter l'auteur du projet pour obtenir les identifiants de démonstration.

### 5. Lancement de l'application

```bash
symfony server:start
```

L'application est accessible à l'adresse indiquée par la commande (par défaut `https://127.0.0.1:8000`).

---

## Choix techniques

### Architecture hybride MySQL / MongoDB

La base de données relationnelle **MySQL** est utilisée pour l'ensemble des données transactionnelles de l'application : utilisateurs, menus, plats, commandes, avis. Ces données nécessitent des garanties **ACID** (atomicité, cohérence, isolation, durabilité), indispensables pour gérer correctement les commandes, les statuts et les relations entre entités (utilisateur, menu, plat, allergène, etc.).

**MongoDB** est utilisé pour les besoins analytiques de l'espace administrateur, notamment le calcul et l'agrégation du nombre de commandes par menu et du chiffre d'affaires associé. MongoDB est mieux adapté pour lire et calculer de grandes quantités de données statistiques. Contrairement à MySQL qui impose une structure fixe (les mêmes colonnes pour chaque ligne), MongoDB est flexible , c'est à dire que chaque document peut avoir sa propre structure. Et surtout, en séparant les statistiques dans MongoDB, on évite de surcharger MySQL avec des calculs lourds qui pourraient ralentir les commandes des clients.

La synchronisation des données vers MongoDB est déclenchée lors de chaque changement de statut d'une commande à "livré" ou "annulé", afin que les statistiques affichées dans l'espace administrateur restent à jour sans solliciter directement la base transactionnelle pour des requêtes d'agrégation coûteuses.

### APIs externes

- **API Nationale des Adresses** : géolocalisation de l'adresse de livraison saisie par le client
- **API IGN** : calcul de la distance routière réelle pour les frais de livraison

Ces appels sont effectués côté serveur, les calculs ne peuvent pas être falsifiés côté client.

### Envoi d'e-mails

En développement, `MAILER_DSN=null://default` désactive l'envoi réel des e-mails, visualisés via le **Web Profiler Symfony**. En production, remplacer par un service SMTP sans modifier le code.

### Sécurisation

L'accès aux différentes sections de l'application est contrôlé via le fichier `security.yaml`, qui définit les rôles `ROLE_USER`, `ROLE_EMPLOYE` et `ROLE_ADMIN`. Chaque rôle dispose d'un accès restreint aux routes correspondant à son périmètre fonctionnel, conformément à la hiérarchie de rôles définie (un administrateur héritant des droits d'un employé).

Tous les formulaires (création de compte, connexion, commande, contact, gestion des menus) sont protégés contre les attaques **CSRF** via les tokens générés et vérifiés nativement par le composant Form de Symfony.

Les mots de passe sont hachés avec l'algorithme **bcrypt** avant leur stockage en base de données, garantissant qu'aucun mot de passe n'est conservé en clair, conformément aux exigences de sécurité et au RGPD.