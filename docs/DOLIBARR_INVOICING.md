# Clients et facturation Dolibarr

## Synchronisation des clients Dolibarr → phpIP

La synchronisation importe les tiers marqués **clients** ou **clients et
prospects** (champ Dolibarr `client` égal à 1 ou 3). Elle peut fonctionner
sans activer la facturation des renouvellements. Les contacts individuels
rattachés à une société dans le répertoire de contacts Dolibarr ne sont pas
importés : un tiers client personne physique est en revanche pris en charge.
Aucune donnée n'est écrite dans Dolibarr.

### Configuration et premier import

Activer le module API REST dans Dolibarr et utiliser une clé API d'un utilisateur
autorisé à consulter les tiers clients. Configurer l'instance phpIP concernée :

```dotenv
DOLIBARR_URL=https://votre-instance.example/api/index.php
DOLIBARR_API_KEY=secret
DOLIBARR_SYNC_ENABLED=true
DOLIBARR_SYNC_SCHEDULED=false
RENEWAL_INVOICE_BACKEND=none
```

L'URL est celle de l'API, pas celle de la liste des factures. Les paramètres de
connexion sont communs à la synchronisation et à la facturation. Chaque
installation phpIP doit conserver la même instance Dolibarr : les identifiants
enregistrés ne sont pas transférables à une autre instance.

Déployer les migrations, notamment celle ajoutant `actor.dolibarr_socid`, puis
reconstruire le cache de configuration selon la procédure habituelle. Exécuter :

```sh
php artisan dolibarr:sync --dry-run
```

Sans option, la commande effectue également une simulation sans écriture.
Elle affiche les identifiants tiers/acteurs, le résultat prévu et les raisons
des conflits. Les valeurs possibles sont `created`, `updated`, `unchanged`,
`conflict` et `invalid`.

Lorsqu'un acteur possède un nom, nom d'affichage, email, numéro de TVA ou
identifiant professionnel correspondant, le tiers est signalé en conflit.
Même une correspondance unique doit être confirmée. Comparer les deux fiches,
puis associer le tiers depuis la fiche acteur ou avec la commande suivante
(42 = tiers Dolibarr, 123 = acteur phpIP) :

```sh
php artisan dolibarr:sync --link=42:123
php artisan dolibarr:sync --link=42:123 --apply
```

Avec `--link`, seules les associations indiquées sont traitées ; leur présence
distante et leur statut client sont vérifiés. L'application d'un lien en ligne
de commande importe aussi les coordonnées. Le champ de liaison de la fiche
acteur enregistre uniquement l'association ; les coordonnées sont actualisées
au prochain passage de la synchronisation.

Après revue des conflits, appliquer l'import :

```sh
php artisan dolibarr:sync --apply
```

Les nouveaux clients sont créés ; les clients déjà liés sont mis à jour par
leur identifiant `dolibarr_socid`. Relancer ne recrée pas ces clients. Une
erreur sur un enregistrement invalide ou ambigu n'empêche pas le traitement
des autres ; la commande retourne toutefois un code d'échec. En cas de panne
API, elle s'arrête et les enregistrements déjà appliqués sont conservés.
Corriger la cause et relancer la commande.

### Règles de données

- Dolibarr fait référence pour les champs renseignés : nom, email, téléphone,
  adresse (rue, code postal, ville), pays ISO, TVA, premier identifiant
  professionnel et langue. La classification connue du tiers permet de
  distinguer personne physique et personne morale.
- Un champ absent ou vide ne supprime pas la valeur locale. Une modification
  locale d'un champ synchronisé sera remplacée au prochain passage si Dolibarr
  fournit une valeur. Les suppressions dans Dolibarr ne suppriment aucun acteur.
- Les noms d'affichage, prénoms existants, rôles, relations, coordonnées de
  facturation/courrier, dossiers et identifiants de connexion sont préservés.
  Les acteurs utilisateurs de phpIP ne sont pas modifiés par l'import.
- Une valeur incompatible avec phpIP (longueur, email ou pays inconnu) bloque
  l'enregistrement entier au lieu de tronquer les données. Corriger la fiche
  source avant de relancer. Les limites actuelles incluent 45 caractères pour
  l'email, 20 pour le téléphone et 256 pour l'adresse composée.
- Les correspondances sont conservatrices, pas une détection exhaustive des
  doublons : contrôler notamment les clients qui n'ont plus aucun identifiant
  ou nom commun entre les deux applications.

### Actualisation automatique et suivi

Une fois la reprise validée, activer `DOLIBARR_SYNC_SCHEDULED=true` et reconstruire
le cache de configuration. Le scheduler Laravel doit être exécuté par le serveur
(`php artisan schedule:run` chaque minute). La synchronisation passe toutes les
15 minutes quand les deux options de synchronisation sont actives.

Un verrou empêche deux commandes de synchronisation de s'exécuter en parallèle.
Sur plusieurs serveurs, utiliser un cache partagé supportant les verrous
(par exemple Redis ou le cache en base) ; le cache fichier par défaut ne protège
que les processus d'un même serveur. Une exécution est bornée en durée et en
nombre de pages ; une limite atteinte produit un échec explicite à investiguer.

Les journaux Laravel contiennent le bilan de chaque passage et les identifiants
des fiches à revoir, sans clé API ni coordonnées clients. Surveiller les entrées
`Dolibarr customer requires review` et `Dolibarr customer synchronization interrupted`.
Le mode simulation reste un aperçu : toute modification des fiches entre la
simulation et l'application peut changer le résultat.

## Facturation des renouvellements

L'intégration crée des factures brouillon dans Dolibarr à partir des
renouvellements. Elle est désactivée par défaut afin qu'une installation sans
Dolibarr conserve le flux d'export historique.

## Configuration

Renseigner au minimum les variables suivantes dans `.env` :

```dotenv
RENEWAL_INVOICE_BACKEND=dolibarr
DOLIBARR_URL=https://dolibarr.example.com/api/index.php
DOLIBARR_API_KEY=secret
DOLIBARR_ACCOUNT_ID=2
DOLIBARR_PAYMENT_TERMS_ID=1
DOLIBARR_PAYMENT_METHOD_ID=2
```

Les délais réseau et les tentatives de lecture sont configurables avec
`DOLIBARR_CONNECT_TIMEOUT`, `DOLIBARR_TIMEOUT`, `DOLIBARR_RETRY_TIMES` et
`DOLIBARR_RETRY_SLEEP_MS`. `DOLIBARR_LOCK_SECONDS` et
`DOLIBARR_LOCK_WAIT_SECONDS` pilotent le verrou anti-double-clic ; le cache
doit être partagé entre les instances web lors d'un déploiement multi-serveur.
Les anciennes clés applicatives
`renewal.api.dolibarr_url`, `renewal.api.DOLAPIKEY` et
`renewal.api.fk_account` restent disponibles.

Après toute modification, exécuter `php artisan config:clear` (ou reconstruire
le cache de configuration du déploiement).

## Mise en service

1. Laisser `RENEWAL_INVOICE_BACKEND=none` pendant la migration de la base et
   renseigner les paramètres de connexion.
2. Vérifier l'URL, la clé API, le compte, les conditions et le mode de paiement.
3. Activer `RENEWAL_INVOICE_BACKEND=dolibarr` et vider le cache de configuration.
4. Associer chaque acteur client à son identifiant tiers Dolibarr unique
   (`actor.dolibarr_socid`) depuis sa fiche.
5. Vérifier que Dolibarr est en version 12 ou ultérieure ; phpIP contrôle cette
   version avant toute création car le rapprochement dépend de `ref_ext`.

Lorsque le backend Dolibarr est actif, le marquage manuel « facturé » est
désactivé : chaque passage à l'étape facturée doit correspondre à un lot
Dolibarr confirmé. Avec le backend `none`, le flux manuel et l'export historique
restent disponibles.

Chaque groupe facturé est réservé dans `renewal_invoice_batches` avant l'appel
externe. `ref_ext` et l'unicité de la tâche dans
`renewal_invoice_batch_tasks` servent à rapprocher une réponse incertaine et à
empêcher les doubles factures. Ne pas modifier ou supprimer ces enregistrements
pour relancer manuellement une facture.

Les clés API et le payload complet ne doivent jamais être copiés dans les
journaux applicatifs ou les messages affichés aux utilisateurs.
