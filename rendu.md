# Plan de Test - Application Blackjack

## Analyse de l'Application

### Architecture Technique
- **Backend** : Symfony 7 (API REST)
- **Frontend** : SvelteKit avec Skeleton UI et Tailwind CSS  
- **Infrastructure** : Docker avec 2 services (symfony-blackjack + svelte-blackjack)
- **Base de données** : MySQL (via Docker)
- **Authentification** : JWT avec LexikJWTAuthenticationBundle

### Fonctionnalités Métier

#### Gestion des utilisateurs
- Inscription et connexion
- Gestion de profil (consultation, modification, suppression)
- Gestion administrative des utilisateurs

#### Jeu de Blackjack
- Création de parties contre l'IA
- Gestion des rounds (mise, hit, stand)
- Application des règles du blackjack :
  - Score max : 21
  - Blackjack (As + figure) = gains x2
  - Croupier tire jusqu'à 16 minimum

### Contexte Business
- Startup de jeu en ligne
- ~100 utilisateurs journaliers
- Application développée sans tests automatisés
- Nécessité de vérifier la conformité aux spécifications

## Stratégie de Test

### 1. Analyse des Risques

**Risques Élevés :**
- Logique de calcul des scores (règles du blackjack)
- Gestion des gains et mises
- Sécurité de l'authentification JWT
- Intégrité des données de jeu

**Risques Moyens :**
- Interface utilisateur et UX
- Performance avec 100+ utilisateurs
- Gestion des sessions et états

**Risques Faibles :**
- Affichage statique
- Navigation simple

### 2. Types de Tests à Implémenter

#### Backend (Symfony) - 12 points
1. **Tests Unitaires** (4 points)
   - Entités et modèles métier
   - Services de calcul des scores
   - Validation des règles du blackjack
   
2. **Tests Fonctionnels/API** (6 points)
   - Authentification JWT
   - CRUD utilisateurs
   - API de jeu (création partie, rounds)
   - Tests de permissions et rôles
   
3. **Tests d'Intégration** (2 points)
   - Base de données
   - Services métier complets

### 3. Outils et Technologies

- **PHPUnit** : Tests unitaires et fonctionnels
- **Symfony WebTestCase** : Tests d'API
- **PHPStan** : Analyse statique
- **PHP CS Fixer** : Qualité de code

#### CI/CD
- **GitHub Actions** : Pipeline d'intégration continue
- **Docker** : Environnement de test reproductible

## Scénarios de Test Critiques

### Couverture de Code
- **Backend** : Objectif 80% minimum

### Performance
- **API** : Temps de réponse < 200ms

### Sécurité
- Tests de vulnérabilités communes (OWASP)
- Validation des permissions et autorisations