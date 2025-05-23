# Backend Laravel pour AgriConnect

## Architecture générale

Le backend est développé avec Laravel, un framework PHP robuste et moderne, structuré autour d'une API REST sécurisée. Il gère la logique métier, la persistance des données, l'authentification, et l'intégration avec des services externes (SMS, drones, stockage cloud).

---

## Principaux modules

### 1. Authentification et sécurité
- Utilisation de JWT (JSON Web Tokens) pour sécuriser les API.
- Gestion des utilisateurs : producteurs, acheteurs, coopératives, administrateurs.
- Middleware pour vérifier les rôles et permissions.

### 2. Gestion des utilisateurs
- CRUD complet des profils utilisateurs.
- Upload et gestion des photos (profils, produits) via Cloudinary ou Firebase.
- Validation des données côté serveur.

### 3. Annonces et produits agricoles
- Création, modification, suppression des annonces par les producteurs.
- Modération des annonces par les administrateurs.
- Recherche et filtrage des produits par région, saison, type.

### 4. Messagerie interne
- Système de messagerie entre producteurs, acheteurs, et coopératives.
- Notifications en temps réel (via WebSockets ou polling).

### 5. Plateforme SMS agricole
- Intégration avec API SMS locale (Orange, MTN).
- Envoi automatique d'alertes météo, maladies, conseils.
- Multilingue (français, baoulé, malinké, etc.).
- Inscription gratuite via numéro de téléphone.

### 6. Données drones
- Stockage et accès aux photos aériennes et données d'évaluation.
- Module réservé aux producteurs abonnés ou coopératives.

### 7. Statistiques et rapports
- Volume de ventes, activité des utilisateurs, tendances.
- Export des données pour analyses.

---

## Technologies et outils

- Laravel 10.x
- Sanctum ou JWT pour l'authentification API
- MySQL pour la base de données
- Cloudinary/Firebase pour le stockage des images
- Intégration API SMS locale
- WebSockets (Pusher, Laravel Echo) pour messagerie temps réel

---

## Structure des dossiers

- `app/Http/Controllers` : Contrôleurs API REST
- `app/Models` : Modèles Eloquent
- `routes/api.php` : Routes API
- `database/migrations` : Migrations de base de données
- `resources/lang` : Fichiers de traduction
- `app/Services` : Services métiers (SMS, drones, etc.)

---

## Sécurité

- Validation stricte des entrées
- Protection CSRF sur les formulaires
- Gestion des rôles et permissions via middleware
- Stockage sécurisé des mots de passe (bcrypt)

---

## Déploiement

- Hébergement sur serveur Linux avec PHP 8.x
- Configuration SSL pour sécuriser les échanges
- Tâches cron pour envoi périodique des SMS et traitements batch

---

## Conclusion

Ce backend Laravel fournit une API complète et sécurisée pour la plateforme AgriConnect, facilitant la modernisation de l'agriculture en Côte d'Ivoire via des outils numériques puissants et accessibles.
