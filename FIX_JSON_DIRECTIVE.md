# Correction de la directive @json pour les closures imbriquées

## Problème

La directive `@json` de Blade ne pouvait pas parser correctement les expressions complexes contenant des closures imbriquées. Le problème était causé par la méthode `stripParentheses` qui utilisait simplement `substr($expression, 1, -1)` pour enlever les parenthèses externes, sans tenir compte des parenthèses et crochets imbriqués.

### Exemple du problème

```blade
@json([
    'items' => $helpers['benefit']['getAll']()->map(fn($item) => [
        'icon' => $item->icon,
        'title' => (string)$item->title,
        'description' => (string)$item->description
    ]),
    'translation' => '%:booking.benefits%'
])
```

Cette expression était coupée incorrectement, ne gardant que :
```php
<?php echo json_encode([
    'items' => $helpers['benefit']['getAll']()->map(fn($item) => [
        'icon' => $item->icon, 'title' => (string)$item->title, 'description' => (string)$item->description
    ])) ?>
```

## Solution

J'ai créé une nouvelle méthode `parseJsonExpression` dans le trait `CompilesJson` qui :

1. **Parse correctement les parenthèses imbriquées** : Compte les parenthèses et crochets pour déterminer où se termine l'expression de données
2. **Gère les chaînes de caractères** : Ignore les parenthèses et virgules à l'intérieur des chaînes de caractères
3. **Sépare les paramètres** : Identifie correctement les paramètres d'options et de profondeur séparés par des virgules au niveau racine
4. **Maintient la compatibilité** : Fonctionne avec tous les cas d'usage existants

### Implémentation

La nouvelle méthode `parseJsonExpression` :
- Utilise un parser caractère par caractère
- Suit l'état des chaînes de caractères (simples et doubles)
- Compte les parenthèses et crochets pour déterminer le niveau d'imbrication
- Identifie les virgules au niveau racine pour séparer les paramètres

## Tests

J'ai ajouté plusieurs tests pour couvrir :

1. **Le cas spécifique de l'issue #56331** : Reproduit exactement le problème rapporté
2. **Les closures imbriquées complexes** : Teste des structures avec plusieurs niveaux d'imbrication
3. **Les options personnalisées** : Vérifie que les paramètres d'options et de profondeur fonctionnent
4. **Les cas limites** : Chaînes avec virgules, guillemets échappés, guillemets mixtes
5. **La compatibilité descendante** : S'assure que les cas d'usage existants continuent de fonctionner

## Résultat

La directive `@json` peut maintenant parser correctement :
- Des closures imbriquées complexes
- Des structures avec plusieurs niveaux de parenthèses et crochets
- Des chaînes contenant des virgules et des guillemets
- Tous les cas d'usage existants

Cette correction résout complètement l'issue #56331 et améliore la robustesse du compilateur Blade pour les expressions JSON complexes.