# AgriConnect - Plateforme de Commerce Agricole

AgriConnect est une plateforme complète qui connecte les producteurs agricoles aux acheteurs, avec des fonctionnalités avancées de gestion des cultures, surveillance par drones et système d'alertes.

## 🌟 Fonctionnalités

- **Gestion des Utilisateurs**
  - Inscription/Connexion
  - Rôles : Producteur, Acheteur, Coopérative, Admin
  - Profils personnalisés

- **Commerce Agricole**
  - Publication de produits
  - Système de transactions sécurisées
  - Négociation des prix
  - Historique des transactions

- **Surveillance par Drones**
  - Collecte de données
  - Analyse des cultures
  - Rapports détaillés
  - Alertes automatiques

- **Système de Messagerie**
  - Chat en temps réel
  - Notifications
  - Archivage des conversations

- **Alertes SMS**
  - Alertes météo
  - Conseils agricoles
  - Notifications de transactions

## 🚀 Installation

### Prérequis

- PHP 8.1+
- Node.js 16+
- MySQL 8.0+
- Composer
- Redis

### Backend (Laravel)

1. Cloner le repository :
```bash
git clone https://github.com/votre-repo/agriconnect.git
cd agriconnect/backend
```

2. Installer les dépendances :
```bash
composer install
```

3. Configurer l'environnement :
```bash
cp .env.example .env
php artisan key:generate
```

4. Configurer la base de données dans .env :
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agriconnect
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

5. Migrer la base de données :
```bash
php artisan migrate
```

6. Démarrer le serveur :
```bash
php artisan serve
```

### Frontend (Next.js)

1. Aller dans le dossier frontend :
```bash
cd ../frontend
```

2. Installer les dépendances :
```bash
npm install
```

3. Configurer l'environnement :
```bash
cp .env.example .env.local
```

4. Démarrer le serveur de développement :
```bash
npm run dev
```

## 🔧 Configuration

### Services SMS (Twilio)
1. Créer un compte Twilio
2. Configurer les variables d'environnement :
```
TWILIO_ACCOUNT_SID=votre_sid
TWILIO_AUTH_TOKEN=votre_token
TWILIO_FROM_NUMBER=votre_numero
```

### Stockage des fichiers (AWS S3)
1. Créer un bucket S3
2. Configurer les variables d'environnement :
```
AWS_ACCESS_KEY_ID=votre_key
AWS_SECRET_ACCESS_KEY=votre_secret
AWS_DEFAULT_REGION=votre_region
AWS_BUCKET=votre_bucket
```

## 📱 API Endpoints

Documentation complète de l'API disponible sur : `/api/documentation`

Endpoints principaux :
- POST `/api/auth/register` - Inscription
- POST `/api/auth/login` - Connexion
- GET `/api/products` - Liste des produits
- POST `/api/transactions` - Créer une transaction
- GET `/api/drone-data` - Données des drones

## 🔒 Sécurité

- Authentification JWT
- Validation des requêtes
- Rate limiting
- Protection CSRF
- Sanitization des données

## 🤝 Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 License

Distribué sous la licence MIT. Voir `LICENSE` pour plus d'informations.

## 📞 Support

Pour toute question ou assistance :
- Email : support@agriconnect.com
- Documentation : https://docs.agriconnect.com
- Issues : https://github.com/votre-repo/agriconnect/issues

## 🙏 Remerciements

- [Laravel](https://laravel.com)
- [Next.js](https://nextjs.org)
- [Tailwind CSS](https://tailwindcss.com)
- [Tous nos contributeurs](https://github.com/votre-repo/agriconnect/contributors)
