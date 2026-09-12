---
name: portalfi-design
description: Système de design PortalFi (identité visuelle, typographie, contraintes de performance). À charger dès qu'un écran (portail public ou admin) est créé ou modifié en Livewire/Flux/Tailwind.
---

# Identité visuelle PortalFi

Choix délibérés, pas des valeurs par défaut — ne pas dévier sans raison explicite.

## Couleurs
- Accent / marque : turquoise `#0F9D8C`
- Texte principal : `#0B2B26` (noir-vert profond, pas de noir pur)
- Fond : blanc `#FFFFFF` ou gris très clair `#F7FAF9`. Un dégradé de fond
  doux à deux couleurs est autorisé (ex. blanc → turquoise très pâle,
  `#F7FAF9` → `#0F9D8C` à faible opacité) — voir contrainte de performance
  ci-dessous pour ses limites
- États (identiques portail + admin) : succès `#0F9D6B`, attente `#E8A33D`,
  échec `#D6483F`
- Bordures fines et claires (`#E5E7EB`), jamais d'ombre lourde

## Typographie
- Une seule famille : **Manrope**, chargée depuis Google Fonts
- Titres en poids fort (700), corps de texte en 400/500
- Pas de police système par défaut, mais pas non plus de police éditoriale ou
  fantaisiste — priorité à la lisibilité pour un public grand public, parfois peu
  technophile, sur mobile

## Contrainte de performance — était prioritaire sur l'esthétique
C'est un portail captif : l'utilisateur peut arriver avec une connexion très
faible, avant même d'avoir un accès internet complet. Ce principe reste la
référence par défaut, **sauf exception explicite ci-dessous**.
- Pas d'animations d'entrée en cascade ni de micro-interactions décoratives
  hors exception "fond animé" ci-dessous — sinon, seulement un feedback
  fonctionnel (état de bouton, confirmation de copie)
- CSS et SVG légers, images optimisées, premier écran quasi instantané —
  hors exception glassmorphism ci-dessous
- Préférer la simplicité "plate" à toute forme de profondeur visuelle — hors
  exception glassmorphism ci-dessous

### Exception actée : glassmorphism ("Liquid Glass")
Décision produit explicite d'Armel (2026-09-11), en connaissance du coût de
performance (filtre SVG de distorsion + `backdrop-filter: blur()` sont parmi
les effets CSS les plus coûteux, potentiellement sensibles sur mobile bas de
gamme) : l'esthétique prime ici sur la contrainte de performance par défaut.
- Fond de page : photo fixe (`resources/images/hero-background.jpg`, choisie
  par Armel) avec un overlay dégradé vert profond semi-transparent
  (`.page-background` dans `app.css`) pour garantir le contraste du texte
  blanc posé dessus — jamais l'image seule sans overlay
- Cartes et boutons : effet "verre" avec distorsion SVG
  (`feTurbulence`/`feDisplacementMap`) + `backdrop-filter: blur()`, texte
  blanc avec `text-shadow` (le fond riche est ce qui rend ce texte blanc
  lisible et l'effet de distorsion visible — ne jamais poser ce style sur
  un fond blanc/uni)
- Le filtre SVG (`#glass-distortion`) n'est déclaré qu'une seule fois dans le
  layout, jamais dupliqué par écran
- Si un test réel sur mobile bas de gamme montre un problème de fluidité,
  revenir à Armel plutôt que d'improviser un correctif

## Composants
- Flux (tier gratuit) pour tous les éléments d'interface — boutons, dropdowns,
  modals, inputs, switch
- Tableaux/listes admin construits à la main en Tailwind (pas de Flux Pro)
- Cartes : effet glass (voir exception ci-dessus), `border-radius: 12px`,
  padding généreux
- Un seul CTA dominant par écran — jamais deux actions de même poids visuel
  côte à côte (cf. "Acheter un accès" vs "J'ai déjà un code" : le premier est
  toujours visuellement plus fort)

## Wording
Toujours orienté utilisateur final, jamais de jargon technique ou d'erreur brute.
"Ce code n'est pas valide", pas "HTTP 422" ni "Radius authentication failed".