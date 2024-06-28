# Document de Tests

## 1. Introduction

Dans ce document, nous allons décrire l'ensemble des tests qui ont été réalisé sur l'application. Résumer les bugs trouvé, la façon dont ils ont été corrigé, ainsi que les tests unitaires et d'intégration mis en place pour assurer la qualité de l'application. 

## 2. Résumé des Bugs

### Bug 1: Impossible d'accéder à une nouvelle partie
- **Description**: Le bouton de suppression actuel ne permet pas la suppression effective des jeux de l'interface frontend. Une solution proposée est d'effectuer des tests unitaires pour garantir le bon fonctionnement de cette fonctionnalité.

### Bug 2: Impossible de parier
- **Description**: Lorsque je crée une nouvelle partie et que je tente de miser, rien ne se produit après avoir saisi ma mise.

### Bug 3: Les parties ne se lancent pas
- **Description**: La création d'une nouvelle partie ne fonctionne pas. Aucun événement ne se produit lorsque l'utilisateur tente de lancer une nouvelle partie.

### Bug 4: Erreur "500 Internal Server Error" lors de la connexion
- **Description**: Description: Lors de la tentative de connexion avec des identifiants valides, une erreur "500 Internal Server Error" est renvoyée.

### Bug 5: "Delete" ne fonctionne pas correctement
- **Description**: La suppression d'une partie via le bouton "Delete" ne fonctionne que sur le front-end. La partie réapparaît après le rechargement de la page car elle n'est pas supprimée côté back-end.

### Bug 6: Problème de chargement des informations du profil utilisateur
- **Description**: La page de profil utilisateur affiche uniquement le texte "loading" au lieu des informations du compte.

### Bug 7: Mauvaise redirection après la création de compte
- **Description**: Lors de la création d'un nouveau compte, l'utilisateur est redirigé vers une page incorrecte, résultant en une erreur 404.

## 3. Tests Manuels

### Test 1: Se connecter à l'application
- **Étapes**:
  1. Ouvrir l'application
  2. Saisir des identifiants valide
  3. Vérifier si la connexion d'un utilisateur fonctionne
- **Résultat**: Une erreur "500 Internal Server Error" est renvoyée.
- **Correctif apporté ?**: Une correction a été apporté sur ce bug "https://github.com/ArthurDelaporte/blackjack/issues/2"

### Test 2: Affichage des informations de l'utilisateur
- **Étapes**:
  1. Ouvrir l'application
  2. Saisir des identifiants valide
  3. Une redirection vers la page "profile" est faite
- **Résultat**: La page de profil utilisateur affiche uniquement le texte "loading" au lieu des informations du compte.
- **Correctif apporté ?**: Une correction a été apporté sur ce bug "https://github.com/ArthurDelaporte/blackjack/issues/3"

### Test 3: Lancement d'une nouvelle partie
- **Étapes**:
  1. Ouvrir l'application
  2. Saisir des identifiants valide
  3. Une redirection vers la page "profile" est faite
  4. Aller sur la page "My games"
  5. Cliquer sur le boutton "New game"
  6. Une nouvelle partie doit être crée et le jeu doit se lancer
- **Résultat**: Une nouvelle partie est crée mais le jeu ne se lance pas
- **Correctif apporté ?**: Une correction a été apporté sur ce bug "https://github.com/ArthurDelaporte/blackjack/issues/7"

### Test 4: Le bouton "Delete" ne fonctionne pas correctement
- **Étapes**:
  1. Ouvrir l'application
  2. Saisir des identifiants valide
  3. Une redirection vers la page "profile" est faite
  4. Aller sur la page "My games"
  5. Cliquer sur le boutton "New game"
  6. Aller sur la page "My games"
  7. Cliquer sur le boutton delete pour supprimer la partie
- **Résultat**: Le jeu est supprimé de l'interface (front-end) mais reste présent après le rechargement de la page car il n'a pas été supprimé côté back-end.
- **Correctif apporté ?**: Une correction a été apporté sur ce bug "https://github.com/deozza/blackjack/pull/4/commits/b650a859347dc905b694b35023347b9b84384141"

### Test 5: Déconnexion
- **Étapes**:
  1. Ouvrir l'application
  2. Saisir des identifiants valide
  3. Une redirection vers la page "profile" est faite
  4. Cliquer sur le boutton "Logout"
- **Résultat**: Nous sommes bien déconnecté du site et l'accèes au différentes pages est bloqué, expté sur la page "profile" qui reste toujours accéssible
- **Correctif apporté ?**: Un correctif n'as pas encore été apporté sur ce bug

## 4. Tests Unitaires

### Test unitaire 1: Rendu du composant App