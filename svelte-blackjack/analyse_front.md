# Analyse Front

## Bug routing

- Le lien log in ne marche pas (http://localhost:5173/login) depuis la page sign up
- Signup redirection vers mauvais URL => http://localhost:5173/login

## Bug UI

- Manque le btn 'resume' dans la page http://localhost:5173/user/games
- Affiche le mauvais id de la game dans la page http://localhost:5173/user/games
- La maquette de Wager ne s'affiche pas

## Bug fonctionnel

- Load infini sur la page profil => user is null Page.svelte:32
- Déconnexion lors du reload => bearer null lors du click sur btn 'games'
- Load infini dans la page http://localhost:5173/user/games/[id] => ctx is undefined


# Plan de tests

## Utilisation de Vitest pour la partie front

- Tests de redirection sur chaque page
  - /login 
  - /signup
  - /user/profile
  - /user/games
  - /user/games/[id]
- test get user
- test get games
- test get game by id
  - Id existe
  - Id n'existe pas
- 
