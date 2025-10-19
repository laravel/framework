# Fix Blade @json directive to handle nested closures and arrays

## Problem

The Blade `@json` directive could not correctly parse complex expressions containing nested closures. The issue was caused by the `stripParentheses` method which simply used `substr($expression, 1, -1)` to remove outer parentheses, without considering nested parentheses and brackets.

### Example of the problem

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

This expression was incorrectly truncated, keeping only:

```php
<?php echo json_encode([
    'items' => $helpers['benefit']['getAll']()->map(fn($item) => [
        'icon' => $item->icon, 'title' => (string)$item->title, 'description' => (string)$item->description
    ])) ?>
```

## Solution

I created a new `parseJsonExpression` method in the `CompilesJson` trait that:

1. **Correctly parses nested parentheses**: Counts parentheses and brackets to determine where the data expression ends
2. **Handles string literals**: Ignores parentheses and commas inside string literals
3. **Separates parameters**: Correctly identifies options and depth parameters separated by commas at root level
4. **Maintains compatibility**: Works with all existing use cases

### Implementation

The new `parseJsonExpression` method:

-   Uses a character-by-character parser
-   Tracks string literal state (single and double quotes)
-   Counts parentheses and brackets to determine nesting level
-   Identifies commas at root level to separate parameters

## Tests

I added several tests to cover:

1. **The specific case from issue #56331**: Reproduces exactly the reported problem
2. **Complex nested closures**: Tests structures with multiple nesting levels
3. **Custom options**: Verifies that options and depth parameters work
4. **Edge cases**: Strings with commas, escaped quotes, mixed quotes
5. **Backward compatibility**: Ensures existing use cases continue to work

## Result

The `@json` directive can now correctly parse:

-   Complex nested closures
-   Structures with multiple levels of parentheses and brackets
-   Strings containing commas and quotes
-   All existing use cases

This fix completely resolves issue #56331 and improves the robustness of the Blade compiler for complex JSON expressions.
