# RDV Impôts Matsiatra (RIM)

Application de prise de rendez-vous pour les services fiscaux, en PHP natif + MySQL, CSS pur, JavaScript vanilla.

## Prérequis
- PHP 8.0+
- MySQL 5.7+/8
- Apache (XAMPP/WAMP/MAMP)
- PhpMyAdmin facultatif

## Installation (3 étapes)
1. Copiez ce dossier `rdv-impots-matsiatra/` dans votre répertoire `htdocs` (XAMPP) ou `www` (WAMP/MAMP).
2. Créez la base de données à partir du fichier `sql/database.sql` (via PhpMyAdmin ou CLI).
3. Ouvrez `http://localhost/rdv-impots-matsiatra/` dans votre navigateur.

## Configuration
- Modifiez les identifiants MySQL dans `config/config.php` si nécessaire:
  - DB_HOST, DB_NAME, DB_USER, DB_PASS
- Ajustez `BASE_URL` si votre chemin local diffère.

## Comptes
- Créez des comptes via la page "Créer un compte".
- Types : Client (contribuable) ou Agent.

## Navigation
- Accueil: `/`
- Connexion: `/?page=login`
- Inscription: `/?page=register`
- Espace client: `/?page=dashboard-client`
- Espace agent: `/?page=dashboard-agent`

## Sécurité
- Mots de passe hachés (`password_hash`).
- Sessions sécurisées, timeout (1h).
- Protection CSRF (tokens).
- Requêtes préparées (PDO) et validation côté serveur.

## Fonctionnalités
- Client:
  - Prise de RDV (motif, date, agent, créneau disponible, notes)
  - Historique des RDV et annulation (>24h)
  - Notifications in-app
- Agent:
  - Voir RDV du jour et à venir
  - Mise à jour du statut (confirmé/terminé/annulé)
  - Gestion des créneaux (ajout, blocage/déblocage, suppression)
  - Statistiques (7 jours) via Chart.js (CDN)
- API:
  - `api/auth.php` (login/logout/register)
  - `api/appointments.php` (create/read/update-status, stats)
  - `api/users.php` (list-agents)
  - `api/notifications.php` (list, mark-read, clear-old)
- CRUD séparés par entité (create/read/update/delete).

## Personnalisation
- Styles: `assets/css/style.css` (variables couleur, animations).
- Scripts: `assets/js/script.js`.

## Recommandations PHP.ini
- error_reporting(E_ALL)
- session.cookie_httponly = On
- session.use_strict_mode = 1
- max_execution_time = 30

## Charset MySQL
- character-set-server = utf8mb4

## Emails / SMS
- Les notifications email/SMS sont en TODO (PHPMailer/SMS gateway non inclus).
- Les notifications "system" (in-app) sont implémentées.

## Déploiement
- Déployable sur serveur Apache standard avec PHP/MySQL.
- Placez le dossier, configurez la base, mettez à jour `config/config.php`.
