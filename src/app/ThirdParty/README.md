# Présence — correctif du module Attendance

Cette archive est un **correctif intégrable** pour le module de présence CodeIgniter 4. Elle ne contient pas le cœur de l’application (`app/`, `public/`, `spark`, `composer.json` ni `.env`) : ne remplace donc pas ton projet entier par ce ZIP. Elle contient le module `Attendance` corrigé, le module `Evaluation` reçu dans l’archive d’origine pour conserver sa mise en page, et ce guide.

## Changements inclus

- QR signé HMAC et stocké en base, valide **10 secondes** ; un QR expiré est refusé.
- Page étudiante mobile au lieu d’une réponse JSON brute ; le ticket de page est valable 5 minutes après un scan accepté pour laisser le temps de saisir le matricule et le code individuel.
- Inscription par passkey WebAuthn avec vérification biométrique / PIN obligatoire côté authentificateur, validation de l’origine exacte, défi individuel expirant et consommation atomique.
- Code d’inscription différent par étudiant, stocké sous forme de hash et utilisable une seule fois. Il est exporté dans un CSV pour distribution privée.
- Présence unique par couple appel/étudiant, avec heure UTC, adresse IP, navigateur, délai depuis l’ouverture et indicateur de retard supérieur à 10 minutes.
- Tableau de bord qui rafraîchit les présents, écran de projection QR, tirage aléatoire de cinq présents, historique et export CSV présents/absents.
- Déconnexion par POST, protection de la zone administrative et prise en charge du jeton CSRF dans les requêtes AJAX.
- Commande de réinitialisation d’accès si un étudiant perd son accès.

## 1. Installer le correctif

1. Fais une copie de sauvegarde de ton projet et de ta base MySQL.
2. Décompresse ce ZIP **à la racine de ton projet CodeIgniter**, puis fusionne le dossier `Modules/` avec le dossier `Modules/` existant. Accepte le remplacement des fichiers de `Modules/Attendance`. Le dossier `Modules/Evaluation` fourni est conservé depuis l’archive d’origine pour que les vues d’administration continuent d’utiliser ta mise en page existante.
3. Ouvre un terminal à la racine du projet (là où se trouve le fichier `spark`). Vérifie PHP :

   ```bash
   php -v
   php spark
   ```

   Le projet cible CodeIgniter 4 avec PHP 8.2 ou plus récent. Les extensions PHP OpenSSL, mbstring et le pilote MySQL utilisé par ton `Config\Database` (généralement `mysqli` ou `pdo_mysql`) doivent être actifs.

4. Installe la bibliothèque WebAuthn utilisée par le serveur :

   ```bash
   composer require lbuchs/webauthn
   composer dump-autoload
   ```

5. Vérifie que l’autoload PSR-4 contient bien le namespace `Modules\\` pointant vers `Modules/`. Dans `composer.json`, ajoute cette entrée **sans remplacer les autres entrées** si elle n’existe pas :

   ```json
   "Modules\\": "Modules/"
   ```

   Puis relance `composer dump-autoload`.

## 2. Enregistrer les routes et le filtre

Cette archive ne peut pas écraser `app/Config/Routes.php` ou `app/Config/Filters.php`, car ces fichiers n’étaient pas dans le ZIP d’origine. Ajoute les éléments suivants en préservant le reste de ta configuration.

### `app/Config/Routes.php`

Dans le fichier de routes, une fois la variable `$routes` disponible, ajoute à la fin :

```php
require ROOTPATH . 'Modules/Attendance/Config/Routes.php';
```

Ne crée pas une deuxième variable `$routes` et ne remplace pas les routes du module Evaluation.

### `app/Config/Filters.php`

Dans le tableau `$aliases`, ajoute :

```php
'attendanceAdmin' => \\Modules\\Attendance\\Filters\\AdminFilter::class,
```

Si le filtre CSRF n’est pas déjà activé, active-le dans `$globals['before']` :

```php
'csrf',
```

Les formulaires et appels AJAX du module prévoient un jeton CSRF. Si le projet possède déjà un filtre CSRF global, ne le duplique pas.

## 3. Configuration `.env`

Ne colle jamais ton vrai `.env` dans un dépôt public. Pour un test local, mets à jour les valeurs existantes ou ajoute ces lignes :

```ini
app.baseURL = 'http://localhost:8080/'
ATTENDANCE_ADMIN_PASSWORD = 'REMPLACE_PAR_UN_MOT_DE_PASSE_LONG'
ATTENDANCE_EXPECTED_ORIGIN = 'http://localhost:8080'
ATTENDANCE_RP_ID = 'localhost'
```

Utilise un mot de passe administrateur long et unique. L’application stocke les dates de présence en UTC et affiche les heures d’export en heure de Madagascar. La limitation des tentatives utilise le service Throttler de CodeIgniter : vérifie que le cache de l’application utilise un vrai gestionnaire (par exemple `file` ou Redis), pas le gestionnaire `dummy`.

## 4. Migrations

Le module reçu avait déjà cinq migrations Attendance. Le correctif ajoute `20261020100000_CreateEnrollmentCodes.php` pour enregistrer les hashes des codes d’inscription.

Vérifie les migrations disponibles :

```bash
php spark migrate:status
```

Puis applique les migrations non exécutées de toutes les namespaces enregistrées par ton application :

```bash
php spark migrate --all
```

La commande `--all` est importante pour un projet modulaire. Si la migration `CreateEnrollmentCodes` n’apparaît pas, vérifie que `Modules/` est bien déclaré dans l’autoload PSR-4 et que le namespace `Modules\Attendance` est découvert par le système de migrations. **Ne supprime pas les tables existantes et n’exécute pas `migrate:refresh` sur les données de la classe.**

## 5. Importer la classe et générer les codes privés

Le CSV attendu est `writable/import/etudiants.csv`, avec un en-tête puis quatre colonnes : horodateur, matricule/ETU, nom, prénom. Exemple :

```csv
Horodateur,ETU,Nom,Prénom
2026-10-20 08:00,12345,Rakoto,Soa
2026-10-20 08:01,12346,Rabe,Anja
```

Teste l’import avant de l’enregistrer :

```bash
php spark attendance:import-students --dry-run
```

Puis importe réellement :

```bash
php spark attendance:import-students
```

Génère ensuite les codes d’inscription à distribuer **en privé** :

```bash
php spark attendance:generate-enrollment-codes
```

Le fichier sera créé dans `writable/export/codes-inscription-AAAAmmjj-HHMMSS.csv`. La base conserve seulement le hash des codes ; le CSV contient les codes en clair. Remets chaque code à l’étudiant correspondant, puis supprime le CSV ou conserve-le dans un emplacement chiffré auquel seul le responsable a accès. Ne publie pas ce fichier dans un groupe de classe ou sur GitHub.

Si un étudiant perd son accès, le chef peut exécuter cette commande avec son matricule exact :

```bash
php spark attendance:reset-student-access 12345
```

La commande supprime son ancienne passkey, crée un nouveau code et l’affiche une seule fois dans le terminal. Remets le nouveau code directement à l’étudiant. N’utilise pas cette commande pour réinitialiser toute la classe.

## 6. Démarrer l’application localement

Depuis la racine du projet, lance le serveur CodeIgniter :

```bash
php spark serve --host 0.0.0.0 --port 8080
```

Dans le navigateur de l’ordinateur, ouvre :

- Administration : `http://localhost:8080/attendance/admin/login`
- Écran du QR à projeter : `http://localhost:8080/attendance/admin/chef`

Connecte-toi avec `ATTENDANCE_ADMIN_PASSWORD`, ouvre un appel, puis ouvre l’écran du chef dans une fenêtre séparée ou sur un projecteur.

## 7. Accès depuis les téléphones avec Cloudflare Tunnel

### Installer Cloudflare Tunnel sur macOS

```bash
brew install cloudflared
cloudflared --version
```

Garde le serveur CodeIgniter actif dans un premier terminal. Dans un **deuxième terminal**, exécute :

```bash
cloudflared tunnel --url http://127.0.0.1:8080
```

Cloudflare affichera une URL HTTPS de la forme `https://nom-aleatoire.trycloudflare.com`. Copie **l’URL exacte affichée**. Dans `.env`, remplace la configuration locale par les valeurs de cette URL :

```ini
app.baseURL = 'https://nom-aleatoire.trycloudflare.com/'
ATTENDANCE_EXPECTED_ORIGIN = 'https://nom-aleatoire.trycloudflare.com'
ATTENDANCE_RP_ID = 'nom-aleatoire.trycloudflare.com'
ATTENDANCE_ADMIN_PASSWORD = 'TON_MOT_DE_PASSE_LONG'
```

Remplace `nom-aleatoire.trycloudflare.com` par le vrai domaine affiché par Cloudflare. **Laisse le processus `cloudflared` en marche** : si tu l’arrêtes puis relances un Quick Tunnel, l’URL peut changer. Après la modification du `.env`, redémarre le serveur `php spark serve` dans le premier terminal, mais laisse Cloudflare tourner.

Ouvre ensuite sur l’ordinateur :

- `https://nom-aleatoire.trycloudflare.com/attendance/admin/login`
- `https://nom-aleatoire.trycloudflare.com/attendance/admin/chef`

Le téléphone doit scanner le QR à l’écran ; le QR contient le lien étudiant. N’utilise pas l’adresse locale `localhost` sur le téléphone : elle désignerait le téléphone lui-même. Derrière Cloudflare Tunnel, l’adresse IP enregistrée peut être celle du proxy tant que le projet n’est pas configuré pour reconnaître explicitement les proxys de confiance. Ne fais confiance qu’aux adresses/proxys Cloudflare officiels et à la configuration `proxyIPs` de CodeIgniter ; ne lis pas simplement un en-tête fourni librement par le navigateur.

### Important sur les passkeys

Les passkeys sont liées au domaine/RP ID. Un Quick Tunnel aléatoire convient pour une démonstration temporaire avec cinq téléphones, mais si l’URL change, les passkeys créées sur l’ancienne URL ne seront pas utilisables sur la nouvelle. Pour un usage régulier avec une classe, configure plutôt un **tunnel Cloudflare nommé avec un domaine stable** et conserve ce même hostname. Ne change pas `ATTENDANCE_RP_ID` après avoir commencé à inscrire les étudiants.

## 8. Test fonctionnel dans cet ordre

1. Connecte-toi comme chef et ouvre un appel.
2. Vérifie que le QR change environ toutes les 10 secondes.
3. Scanne un QR encore valide. La page étudiante doit s’afficher, pas du JSON brut.
4. Pour la première inscription, choisis le nom, saisis le matricule et le code privé ; confirme avec Face ID / empreinte / code PIN de l’appareil. La présence doit apparaître dans le tableau de bord.
5. Scanne de nouveau le QR avec ce même étudiant ; confirme avec la passkey. La présence ne doit pas être comptée deux fois.
6. Essaie un ancien lien QR après sa rotation : le serveur doit le refuser et afficher une page « QR expiré ».
7. Essaie un code d’inscription déjà consommé ou incorrect : l’inscription doit être refusée.
8. Utilise « Tirer 5 présents au hasard » et vérifie le résultat.
9. Ferme l’appel, ouvre l’historique, exporte le CSV et vérifie les lignes présents/absents dans Sheets.
10. Répète avec cinq téléphones (iOS et Android), puis monte en charge progressivement.

## Ce que je n’ai pas pu valider depuis cette archive

Le projet complet, son `.env`, son `composer.json`, ses fichiers `app/Config`, sa base MySQL et son tunnel actif n’étaient pas fournis. J’ai donc effectué la vérification syntaxique PHP du module, mais pas un test d’intégration réel sur MySQL, une authentification biométrique réelle ou un test de charge à 100 scans/minute. Fais d’abord les dix vérifications ci-dessus avant d’utiliser le système pour prendre l’appel officiel.

La passkey prouve qu’un appareil contrôle une clé enregistrée et a effectué la vérification locale demandée ; elle ne prouve pas, à elle seule, l’identité physique de la personne. Les codes individuels et le tirage aléatoire réduisent les abus faciles, sans garantir une présence anti-fraude absolue.
