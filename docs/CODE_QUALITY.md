# Qualité de code

Ce projet utilise plusieurs outils pour garantir la qualité du code, exécutés
automatiquement par l'intégration continue (`.github/workflows/tests.yml`).

| Outil | Périmètre | Commande |
|-------|-----------|----------|
| **Laravel Pint** | Formatage PHP | `composer format` (corrige) / `composer format:test` (vérifie) |
| **Larastan** (PHPStan) | Analyse statique PHP | `composer analyse` |
| **ESLint** | Analyse JS/Vue | `npm run lint` (vérifie) / `npm run lint:fix` (corrige) |

## Pint

La configuration se trouve dans `pint.json` (preset `laravel`). Lancez
`composer format` avant chaque commit pour aligner le formatage.

## ESLint

La configuration (flat config) se trouve dans `eslint.config.js`. Les erreurs
bloquent la CI ; les avertissements (`warning`) ne la bloquent pas mais doivent
être réduits progressivement. Utilisez `npm run lint:fix` pour corriger
automatiquement ce qui peut l'être.

## Larastan et la baseline

L'analyse statique est configurée au **niveau 5** dans `phpstan.neon`.

Le code existant comporte des erreurs historiques regroupées dans
`phpstan-baseline.neon`. Cette baseline permet à la CI de rester verte tout en
**imposant que tout nouveau code respecte le niveau 5** : une nouvelle erreur,
non présente dans la baseline, fait échouer le job « Static Analysis ».

### Réduire la baseline (corriger les erreurs historiques)

L'objectif à terme est de vider `phpstan-baseline.neon`. Procédez par petits
lots :

1. Ouvrez `phpstan-baseline.neon` et choisissez une ou quelques erreurs à
   corriger (de préférence regroupées par fichier ou par type, ex.
   `property.notFound`).
2. Corrigez la **cause réelle** dans le code. N'ajoutez jamais de commentaire
   `@phpstan-ignore` ni de cast destiné uniquement à masquer l'erreur.
   La page d'un identifiant d'erreur (`https://phpstan.org/error-identifiers/<id>`)
   explique chaque cas et la bonne façon de le corriger.
3. Régénérez la baseline pour retirer les erreurs désormais résolues :

   ```bash
   composer analyse -- --generate-baseline phpstan-baseline.neon
   ```

4. Vérifiez que l'analyse passe toujours, puis committez la mise à jour de la
   baseline avec votre correctif :

   ```bash
   composer analyse
   ```

> La baseline doit donc **diminuer** au fil des PR, jamais grossir : si vous
> devez l'agrandir, c'est que du nouveau code introduit une erreur — corrigez
> le code plutôt que d'étendre la baseline.

### Régénérer après une montée de niveau

Pour augmenter le niveau d'analyse (ex. passer de 5 à 6), modifiez `level` dans
`phpstan.neon`, puis régénérez la baseline avec la commande ci-dessus afin de
capturer les nouvelles catégories d'erreurs avant de les résorber.
