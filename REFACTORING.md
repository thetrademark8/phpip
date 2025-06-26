# Refonte technique de phpIP - Cahier des charges

## Vue d'ensemble du projet

Le projet de refonte technique de **phpIP**, initialement développé par [jjjdejong](https://github.com/jjdejong/phpip), vise à moderniser et améliorer cet outil afin de le rendre plus évolutif et mieux adapté aux besoins actuels. Bien que phpIP soit une solution fonctionnelle, son architecture et ses technologies sous-jacentes limitent sa flexibilité et sa capacité à évoluer avec les standards modernes du développement web.

Ce fork a pour objectif d'optimiser la gestion des marques et, plus largement, de faire de l'outil une solution polyvalente pour la gestion de la propriété intellectuelle. En supprimant les fonctionnalités obsolètes et en intégrant des améliorations essentielles, cette refonte garantira une meilleure maintenabilité, une sécurité renforcée et une expérience utilisateur optimisée.

### Le concept de fork en open-source

Un *fork* est une dérivation d'un projet open-source existant, permettant à une nouvelle équipe de développement de poursuivre son évolution indépendamment de la version originale. Dans le cas de phpIP, qui est sous licence GPL-3.0, ce fork est conforme aux principes du logiciel libre : il garantit l'accès au code source, permet les modifications et assure que toute évolution reste ouverte et accessible à la communauté. Cette approche favorise l'innovation et la pérennité du projet en lui offrant une nouvelle direction technique adaptée aux besoins contemporains.

## 1. Fonctionnalités à retirer

Certaines fonctionnalités de phpIP sont devenues obsolètes ou ne répondent plus aux standards actuels en matière de gestion de la propriété intellectuelle. Cette section identifiera les éléments à supprimer afin d'alléger l'outil et de recentrer son développement sur des fonctionnalités pertinentes et modernes.

## 2. Fonctionnalités must-have

Cette section liste les fonctionnalités essentielles à intégrer dans la nouvelle version, notamment en matière de gestion des marques et d'autres actifs de propriété intellectuelle. L'outil devra permettre une organisation claire, un suivi efficace et une sécurité optimale des données.

### Améliorations de l'interface et de l'expérience utilisateur

- **Calendrier interactif** : Ajouter un calendrier interactif pour la saisie des dates afin d'éviter les erreurs manuelles
- **Conservation de la casse** : Conserver la casse des titres saisis dans les fiches (actuellement, tout passe en minuscules après validation)
- **Logo du cabinet** : Afficher le logo du cabinet sur l'écran de connexion et, si possible, à d'autres endroits stratégiques dans l'interface

### Gestion des catégories et des statuts

- **Mise à jour du menu "Matters"** :
    - Ajouter toutes les catégories manquantes
    - Supprimer la catégorie "Patents"
- **Gestion automatique des statuts** : Lorsqu'un statut est défini sur *Refused* ou *Abandoned*, supprimer automatiquement les dates de renouvellement associées

### Améliorations des résultats de recherche et de l'affichage des données

- **Compteur de résultats** : Afficher le nombre total de dossiers correspondant à une requête
- **Refonte de la présentation des résultats** :
    - Supprimer les colonnes liées aux brevets
    - Ajouter des colonnes plus pertinentes pour l'utilisateur
    - Fusionner *Actor View* et *Status View* en un seul tableau (l'export devra correspondre à cette nouvelle présentation)

### Notifications et automatisations

- **Notifications e-mail** : Envoyer des notifications par e-mail pour les tâches en statut "rouge" ou "orange" à accomplir dans les 7 prochains jours
- **Gestion des marques internationales** : Ajouter les pays associés aux marques internationales et créer automatiquement les dossiers correspondants (*PAYS/WO*)
- **Champ libellé marque** : Ajouter un champ spécifique pour le libellé des marques sur la fiche marque

### Améliorations techniques et compatibilité

- **E-mails HTML** : Permettre la génération d'e-mails en HTML sur Outlook (actuellement fonctionnel uniquement sur Thunderbird)
- **Liens Design Patents** : Adapter les liens des numéros de dépôt des *Design Patents* en France et en UE pour qu'ils pointent vers les bonnes bases de données
- **Liens automatiques marques** : Ajouter des liens automatiques sur les numéros de dépôt des marques pour les bases suivantes :
    - **OMPI**
    - **UKIPO**
    - **USPTO**
      (comme c'est déjà le cas pour les dépôts en France et en UE)
- **Logo dans les e-mails** : Permettre l'insertion du logo de la marque lors de la génération d'emails

### Configuration et personnalisation

- **Templates d'e-mails** : Ajouter de nouvelles règles et templates d'e-mails par défaut
- **Modèles personnalisés** : Intégrer les modèles de mails personnalisés déjà créés par l'utilisateur dans la configuration standard
- **Sécurité des modèles** : Empêcher l'accès aux modèles d'emails pour les utilisateurs externes (clients auxquels on a donné un accès à leurs dossiers)

## 3. Fonctionnalités nice-to-have

En complément des fonctionnalités incontournables, certaines améliorations pourront enrichir l'expérience utilisateur et offrir des capacités avancées. Cette section explore les ajouts potentiels permettant d'accroître la flexibilité et l'interopérabilité de l'outil selon les ressources et priorités du projet.

### Améliorations du suivi et de la gestion

- **Système de renouvellements** : Adapter (ou simplifier) le système de gestion des renouvellements, actuellement conçu pour les brevets, afin qu'il soit plus adapté aux marques et garantisse un suivi efficace des échéances

### Tableaux de bord et statistiques

- **Dashboards interactifs** : Ajouter des tableaux de bord interactifs et des outils d'extraction de statistiques, incluant :
    - Nombre de dépôts effectués sur l'année en cours
    - Répartition des dépôts par territoire (FR, UE, IR…)
    - Nombre de renouvellements prévus pour l'année suivante

### Améliorations de l'interface et de l'accessibilité

- **Modernisation du design** : Moderniser le design de l'interface pour une expérience utilisateur plus agréable et intuitive
- **Traduction française** : Ajouter une traduction complète du logiciel en français

### Personnalisation et compatibilité avancée

- **Logo du cabinet étendu** :
    - Dans les exports CSV *(À vérifier techniquement si c'est possible avec les formats d'export actuels)*
    - Dans le listing des marques sur phpIP *(Si techniquement faisable)*
- **Liens cliquables dans les fiches** :
    - Ajouter un encadré dédié ou intégrer cette fonctionnalité dans les *classifiers*
    - Permettre d'ajouter des liens vers des ressources externes (ex. fiche *Pappers* d'une société, site web, base de données externe…)
- **Intégrations externes** :
    - Ajout d'un lien automatique vers la base Pappers ou infogreffe sur la base du numéro SIREN de l'entreprise
    - Mettre les liens dans les extractions CSV
- **Gestion des images** : Pouvoir mettre plusieurs images sur une même fiche

## Ajouts récents (27/05/2025)

- **Design Patents** : Mettre la date et le numéro d'enregistrement d'un Design Patent dans les colonnes "granted/reg'd" et "number"

## Notes techniques

- **Licence** : GPL-3.0 (maintenir la compatibilité open-source)
- **Langage** : PHP (base existante)
- **Base de données** : Compatible avec l'architecture existante
- **Modernisation** : Mise à jour vers les standards modernes du développement web
- **Sécurité** : Renforcement des aspects sécuritaires
- **Maintenabilité** : Architecture modulaire et évolutive