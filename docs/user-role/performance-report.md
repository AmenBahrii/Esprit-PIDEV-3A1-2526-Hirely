# Rapport de performance - User / Role

## Contexte
- Runtime de mesure : `PHP 8.2.30`
- Itérations par scénario : `5`
- Base de benchmark : SQLite temporaire générée automatiquement

## Mesures Avant / Après

| Scénario | Objectif | Avant (ms) | Avant requêtes | Avant mémoire (KB) | Après (ms) | Après requêtes | Après mémoire (KB) | Gain |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| Admin users summary | Move grouped status/role counting from PHP loops to focused SQL queries. | 0.82 | 8 | 0 | 0.51 | 3 | 0 | 37.4% |
| User directory filtering | Filter active recruiter accounts in SQL instead of loading every user and scanning them in PHP. | 0.88 | 1 | 0 | 0.13 | 1 | 0 | 84.8% |

## Commentaire
- L’optimisation principale consiste à calculer les statistiques de rôles et de statuts directement en SQL au lieu de charger tous les utilisateurs puis de les regrouper en PHP.
- Le filtrage du répertoire utilisateur est également plus léger lorsqu’il est exécuté dans la requête SQL.

## Commande de reproduction
```powershell
C:\tools\php-8.2.30-nts-Win32-vs16-x64\php.exe bin\console app:report:module-performance user-role
```
