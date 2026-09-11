---
name: portalfi-design
description: Système de design PortalFi (identité visuelle, typographie, contraintes de performance). À charger dès qu'un écran (portail public ou admin) est créé ou modifié en Livewire/Flux/Tailwind.
---

# Identité visuelle PortalFi

Choix délibérés, pas des valeurs par défaut — ne pas dévier sans raison explicite.

## Couleurs
- Accent / marque : turquoise `#0F9D8C`
- Texte principal : `#0B2B26` (noir-vert profond, pas de noir pur)
- Fond : blanc `#FFFFFF` ou gris très clair `#F7FAF9`
- États (identiques portail + admin) : succès `#0F9D6B`, attente `#E8A33D`,
  échec `#D6483F`
- Bordures fines et claires (`#E5E7EB`), jamais d'ombre lourde

## Typographie
- Une seule famille : **Manrope**, chargée depuis Google Fonts
- Titres en poids fort (700), corps de texte en 400/500
- Pas de police système par défaut, mais pas non plus de police éditoriale ou
  fantaisiste — priorité à la lisibilité pour un public grand public, parfois peu
  technophile, sur mobile

## Contrainte de performance — prioritaire sur l'esthétique
C'est un portail captif : l'utilisateur peut arriver avec une connexion très
faible, avant même d'avoir un accès internet complet.
- Pas de dégradés en couches, pas d'arrière-plans avec effets, pas de vidéos
- Pas d'animations d'entrée en cascade ni de micro-interactions décoratives —
  seulement un feedback fonctionnel (état de bouton, confirmation de copie)
- CSS et SVG légers, images optimisées, premier écran quasi instantané
- Préférer la simplicité "plate" à toute forme de profondeur visuelle

## Composants
- Flux (tier gratuit) pour tous les éléments d'interface — boutons, dropdowns,
  modals, inputs, switch
- Tableaux/listes admin construits à la main en Tailwind (pas de Flux Pro)
- Cartes : fond blanc, bordure fine, `border-radius: 12px`, padding généreux
- Un seul CTA dominant par écran — jamais deux actions de même poids visuel
  côte à côte (cf. "Acheter un accès" vs "J'ai déjà un code" : le premier est
  toujours visuellement plus fort)

## Wording
Toujours orienté utilisateur final, jamais de jargon technique ou d'erreur brute.
"Ce code n'est pas valide", pas "HTTP 422" ni "Radius authentication failed".