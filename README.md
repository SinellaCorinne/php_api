# Task Manager API

API REST de gestion de tâches et de projets, construite avec Laravel 13 et Sanctum.

---

## Stack technique

| Élément | Version |
|---------|---------|
| PHP | 8.5 |
| Laravel | 13.31 |
| Laravel Sanctum | 4.3 |
| Base de données | SQLite |
| Tests | PHPUnit 12 |

---

## Installation

```bash
# 1. Cloner le projet
git clone <url-du-repo>
cd task-manager

# 2. Installer les dépendances PHP
composer install

# 3. Copier le fichier d'environnement
cp .env.example .env

# 4. Générer la clé d'application
php artisan key:generate

# 5. Créer la base de données SQLite
touch database/database.sqlite

# 6. Lancer les migrations
php artisan migrate

# 7. Démarrer le serveur de développement
php artisan serve
```

L'API est accessible sur `http://127.0.0.1:8000/api`.

---

## Authentification

L'API utilise **Laravel Sanctum** avec des tokens Bearer.

Toutes les routes sauf `/register` et `/login` nécessitent un token dans le header :

```
Authorization: Bearer {votre_token}
```

---

## Endpoints

### Authentification

#### Créer un compte

```
POST /api/register
```

**Body JSON :**
```json
{
    "name": "Sinella",
    "email": "sinella@example.com",
    "password": "motdepasse123",
    "password_confirmation": "motdepasse123"
}
```

**Réponse `201` :**
```json
{
    "user": {
        "id": 1,
        "name": "Sinella",
        "email": "sinella@example.com"
    },
    "token": "1|abc123..."
}
```

---

#### Se connecter

```
POST /api/login
```

**Body JSON :**
```json
{
    "email": "sinella@example.com",
    "password": "motdepasse123"
}
```

**Réponse `200` :**
```json
{
    "user": {
        "id": 1,
        "name": "Sinella",
        "email": "sinella@example.com"
    },
    "token": "2|xyz456..."
}
```

---

#### Se déconnecter

```
POST /api/logout
```

> Nécessite d'être authentifié.

**Réponse `200` :**
```json
{
    "message": "Déconnecté avec succès."
}
```

---

### Projets

> Toutes les routes projets nécessitent d'être authentifié.
> Un utilisateur ne peut voir et modifier que ses propres projets.

---

#### Lister ses projets

```
GET /api/projects
```

**Paramètres optionnels :**

| Paramètre | Type | Description | Défaut |
|-----------|------|-------------|--------|
| `per_page` | integer | Nombre de résultats par page (1-50) | 10 |

**Exemple :** `GET /api/projects?per_page=5`

**Réponse `200` :**
```json
{
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "title": "Site e-commerce",
            "description": "Refonte du site vitrine",
            "created_at": "2026-09-12T10:00:00.000000Z",
            "updated_at": "2026-09-12T10:00:00.000000Z"
        }
    ],
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1,
    "next_page_url": null,
    "prev_page_url": null
}
```

---

#### Créer un projet

```
POST /api/projects
```

**Body JSON :**
```json
{
    "title": "Mon nouveau projet",
    "description": "Description optionnelle"
}
```

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `title` | string | Oui | Titre du projet (max 255 caractères) |
| `description` | string | Non | Description libre |

**Réponse `201` :**
```json
{
    "id": 2,
    "user_id": 1,
    "title": "Mon nouveau projet",
    "description": "Description optionnelle",
    "created_at": "2026-09-12T10:05:00.000000Z",
    "updated_at": "2026-09-12T10:05:00.000000Z"
}
```

---

#### Voir un projet

```
GET /api/projects/{id}
```

Retourne le projet avec toutes ses tâches.

**Réponse `200` :**
```json
{
    "id": 1,
    "user_id": 1,
    "title": "Site e-commerce",
    "description": "Refonte du site vitrine",
    "created_at": "2026-09-12T10:00:00.000000Z",
    "updated_at": "2026-09-12T10:00:00.000000Z",
    "tasks": [
        {
            "id": 1,
            "project_id": 1,
            "title": "Maquette UI",
            "description": null,
            "status": "done",
            "due_date": "2026-10-01",
            "created_at": "2026-09-12T10:10:00.000000Z",
            "updated_at": "2026-09-12T10:10:00.000000Z"
        }
    ]
}
```

---

#### Modifier un projet

```
PUT /api/projects/{id}
```

Les champs sont tous optionnels — on peut n'envoyer que ce qu'on veut modifier.

**Body JSON :**
```json
{
    "title": "Nouveau titre",
    "description": "Nouvelle description"
}
```

**Réponse `200` :** l'objet projet mis à jour.

---

#### Supprimer un projet

```
DELETE /api/projects/{id}
```

Supprime le projet et toutes ses tâches (suppression en cascade).

**Réponse `200` :**
```json
{
    "message": "Projet supprimé."
}
```

---

### Tâches

> Les tâches sont imbriquées dans un projet.
> Un utilisateur ne peut accéder qu'aux tâches de ses propres projets.

---

#### Lister les tâches d'un projet

```
GET /api/projects/{project_id}/tasks
```

**Paramètres optionnels :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `status` | string | Filtre par statut | `todo`, `in_progress`, `done` |
| `due_before` | date | Tâches dont l'échéance est avant cette date | `2026-12-31` |
| `due_after` | date | Tâches dont l'échéance est après cette date | `2026-10-01` |
| `per_page` | integer | Nombre de résultats par page (1-100) | `15` |

**Exemples :**
```
GET /api/projects/1/tasks?status=todo
GET /api/projects/1/tasks?due_before=2026-12-31
GET /api/projects/1/tasks?status=in_progress&due_after=2026-10-01&per_page=5
```

**Réponse `200` :**
```json
{
    "data": [
        {
            "id": 1,
            "project_id": 1,
            "title": "Développer l'API",
            "description": "Endpoints REST complets",
            "status": "in_progress",
            "due_date": "2026-11-15",
            "created_at": "2026-09-12T10:10:00.000000Z",
            "updated_at": "2026-09-12T10:10:00.000000Z"
        }
    ],
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1,
    "next_page_url": null,
    "prev_page_url": null
}
```

---

#### Créer une tâche

```
POST /api/projects/{project_id}/tasks
```

**Body JSON :**
```json
{
    "title": "Écrire les tests",
    "description": "Couvrir tous les endpoints",
    "status": "todo",
    "due_date": "2026-12-01"
}
```

| Champ | Type | Requis | Valeurs acceptées | Défaut |
|-------|------|--------|-------------------|--------|
| `title` | string | Oui | max 255 caractères | — |
| `description` | string | Non | texte libre | `null` |
| `status` | string | Non | `todo`, `in_progress`, `done` | `todo` |
| `due_date` | date | Non | format `YYYY-MM-DD` | `null` |

**Réponse `201` :** l'objet tâche créé.

---

#### Modifier une tâche

```
PUT /api/projects/{project_id}/tasks/{task_id}
```

Les champs sont tous optionnels — envoyer seulement ce qu'on veut changer.

**Body JSON (exemple : passer une tâche en "done") :**
```json
{
    "status": "done"
}
```

| Champ | Type | Valeurs acceptées |
|-------|------|-------------------|
| `title` | string | max 255 caractères |
| `description` | string | texte libre ou `null` |
| `status` | string | `todo`, `in_progress`, `done` |
| `due_date` | date | format `YYYY-MM-DD` ou `null` |

> `project_id`, `id`, `created_at` et `updated_at` sont ignorés même s'ils sont envoyés.

**Réponse `200` :** l'objet tâche mis à jour.

---

#### Supprimer une tâche

```
DELETE /api/projects/{project_id}/tasks/{task_id}
```

**Réponse `200` :**
```json
{
    "message": "Tâche supprimée."
}
```

---

## Codes d'erreur

| Code | Signification | Message retourné |
|------|---------------|-----------------|
| `401` | Non authentifié (token absent ou invalide) | `"Non authentifié. Veuillez vous connecter."` |
| `403` | Accès refusé (ressource appartenant à un autre utilisateur) | `"Accès refusé. Vous n'êtes pas autorisé à effectuer cette action."` |
| `404` | Ressource introuvable | `"Project introuvable."` / `"Task introuvable."` |
| `422` | Données invalides (validation échouée) | `"Les données fournies sont invalides."` + détail des erreurs |

**Format standard d'une erreur `422` :**
```json
{
    "message": "Les données fournies sont invalides.",
    "errors": {
        "title": ["The title field is required."],
        "status": ["The selected status is invalid."]
    }
}
```

---

## Structure du projet

```
app/
├── Http/
│   └── Controllers/
│       ├── AuthController.php      — register, login, logout
│       ├── ProjectController.php   — CRUD projets
│       └── TaskController.php      — CRUD tâches + filtres + pagination
├── Models/
│   ├── User.php                    — utilisateur (HasApiTokens, HasMany projects)
│   ├── Project.php                 — projet (BelongsTo user, HasMany tasks)
│   └── Task.php                    — tâche (BelongsTo project)
└── Policies/
    └── ProjectPolicy.php           — règles d'autorisation (propriétaire uniquement)

bootstrap/
└── app.php                         — configuration des routes et des erreurs

database/
├── factories/
│   ├── ProjectFactory.php          — données fictives pour les tests
│   └── TaskFactory.php
└── migrations/
    ├── ..._create_users_table.php
    ├── ..._create_personal_access_tokens_table.php
    ├── ..._create_projects_table.php
    └── ..._create_tasks_table.php

routes/
└── api.php                         — définition des 12 routes API

tests/
└── Feature/
    ├── AuthTest.php                — 8 tests d'authentification
    ├── ProjectTest.php             — 12 tests sur les projets
    └── TaskTest.php                — 17 tests sur les tâches
```

---

## Lancer les tests

```bash
php vendor/bin/phpunit --no-coverage
```

Les tests utilisent une base SQLite en mémoire (`:memory:`), indépendante de la base de développement. Chaque test repart d'une base vide.

**Résultat attendu :**
```
OK (37 tests, 102 assertions)
```

---

## Modèle de données

```
users
  id            integer  PK
  name          string
  email         string   unique
  password      string   (hashé)
  created_at    datetime
  updated_at    datetime

projects
  id            integer  PK
  user_id       integer  FK → users.id (cascade delete)
  title         string
  description   text     nullable
  created_at    datetime
  updated_at    datetime

tasks
  id            integer  PK
  project_id    integer  FK → projects.id (cascade delete)
  title         string
  description   text     nullable
  status        enum     todo | in_progress | done  (défaut: todo)
  due_date      date     nullable
  created_at    datetime
  updated_at    datetime

personal_access_tokens      (géré par Sanctum)
  id
  tokenable_type / tokenable_id
  name
  token         (hashé)
  last_used_at
  expires_at
```

---

## Règles de sécurité

- Un utilisateur ne peut accéder qu'à ses propres projets et tâches.
- Toute tentative d'accès à une ressource appartenant à un autre utilisateur retourne `403`.
- Les mots de passe sont hashés avec bcrypt avant stockage.
- Les tokens Sanctum sont hashés en base — le token en clair n'est retourné qu'une seule fois à la création.
- Les champs sensibles (`password`, `remember_token`) sont exclus de toutes les réponses JSON.
- La validation stricte sur les updates empêche la modification de `project_id`, `id` ou `created_at`.
