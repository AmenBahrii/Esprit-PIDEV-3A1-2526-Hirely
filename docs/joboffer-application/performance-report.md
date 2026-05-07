# Rapport de performance - Job Offer / Application

## Contexte
- Runtime de mesure : `PHP 8.2.30`
- Itérations par scénario : `5`
- Base de benchmark : SQLite temporaire générée automatiquement

## Mesures Avant / Après

| Scénario | Objectif | Avant (ms) | Avant requêtes | Avant mémoire (KB) | Après (ms) | Après requêtes | Après mémoire (KB) | Gain |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| Applications index | Replace per-row job title lookups with one LEFT JOIN query. | 123.72 | 3601 | 819 | 5.24 | 1 | 0 | 95.8% |
| Job offer search | Push search, filtering, and sorting into SQL instead of scanning everything in PHP. | 0.99 | 1 | 0 | 0.48 | 1 | 0 | 51.8% |

## Commentaire
- Le plus grand gain vient de la liste des candidatures : on remplace des recherches répétées du titre de l’offre par une seule jointure SQL.
- La recherche des offres est plus rapide lorsque le filtrage et le tri sont faits dans la base de données.

## Commande de reproduction
```powershell
C:\tools\php-8.2.30-nts-Win32-vs16-x64\php.exe bin\console app:report:module-performance joboffer-application
```
