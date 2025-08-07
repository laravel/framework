<?php

// Example demonstrating the prevent_accessing_missing_attributes feature

require_once 'vendor/autoload.php';

use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;

// Example Model
class User extends Model
{
    protected $fillable = ['name', 'email'];
    public $timestamps = false;
}

echo "=== Laravel Eloquent Missing Attribute Prevention Demo ===\n\n";

// Create a user model that represents a retrieved record
$user = new User(['name' => 'John Doe', 'email' => 'john@example.com']);
$user->exists = true; // Simulate that this was loaded from database

echo "User created with attributes: name = '{$user->name}', email = '{$user->email}'\n\n";

// Test 1: Default behavior (disabled)
echo "Test 1: Default behavior (missing attributes return null)\n";
echo 'Model::preventsAccessingMissingAttributes() = '.(Model::preventsAccessingMissingAttributes() ? 'true' : 'false')."\n";
echo 'Accessing $user->non_existent_field: '.json_encode($user->non_existent_field)."\n\n";

// Test 2: Enable the feature
echo "Test 2: Enable prevention of missing attribute access\n";
Model::preventAccessingMissingAttributes(true);
echo 'Model::preventsAccessingMissingAttributes() = '.(Model::preventsAccessingMissingAttributes() ? 'true' : 'false')."\n";

try {
    $value = $user->non_existent_field;
    echo "ERROR: Should have thrown an exception!\n";
} catch (MissingAttributeException $e) {
    echo '✓ MissingAttributeException thrown: '.$e->getMessage()."\n";
}
echo "\n";

// Test 3: Valid attributes still work
echo "Test 3: Valid attributes still work normally\n";
echo "Accessing \$user->name: '{$user->name}'\n";
echo "Accessing \$user->email: '{$user->email}'\n\n";

// Test 4: Custom exception handler
echo "Test 4: Custom exception handler\n";
Model::handleMissingAttributeViolationUsing(function ($model, $key) {
    echo "Custom handler called for missing attribute: {$key}\n";

    return 'DEFAULT_VALUE';
});

$result = $user->another_missing_field;
echo "Result from custom handler: '{$result}'\n\n";

// Test 5: Reset to default behavior
echo "Test 5: Reset to default behavior\n";
Model::preventAccessingMissingAttributes(false);
Model::handleMissingAttributeViolationUsing(null);
echo 'Model::preventsAccessingMissingAttributes() = '.(Model::preventsAccessingMissingAttributes() ? 'true' : 'false')."\n";
echo 'Accessing $user->final_missing_field: '.json_encode($user->final_missing_field)."\n\n";

// Test 6: New models (not from database) don't throw exceptions
echo "Test 6: Newly created models don't throw exceptions even when enabled\n";
Model::preventAccessingMissingAttributes(true);
$newUser = new User(['name' => 'Jane Doe']);
// Note: exists = false (default), wasRecentlyCreated = false (default)
echo 'New user exists: '.($newUser->exists ? 'true' : 'false')."\n";
echo 'Accessing missing attribute on new model: '.json_encode($newUser->missing_on_new_model)."\n";

echo "\n=== Demo Complete ===\n";
