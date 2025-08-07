<?php

/**
 * Demo script to test the new configurable prevent accessing missing attributes feature.
 */

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;

class DemoModel extends Model
{
    protected $fillable = ['id', 'name'];
    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->exists = true; // Simulate a retrieved model
    }
}

echo "=== Testing Laravel's Configurable Missing Attributes Feature ===\n\n";

// Test 1: Default behavior (should return null)
echo "1. Default behavior (feature disabled):\n";
Model::preventAccessingMissingAttributes(false);

$model = new DemoModel(['id' => 1, 'name' => 'Test']);
echo '   Model has: '.json_encode($model->getAttributes())."\n";
echo "   Accessing missing attribute 'email': ";

try {
    $result = $model->email;
    echo 'returned '.($result === null ? 'null' : json_encode($result))." ✓\n";
} catch (MissingAttributeException $e) {
    echo "threw exception (unexpected) ✗\n";
}

echo "\n";

// Test 2: Feature enabled (should throw exception)
echo "2. Feature enabled (should throw exception):\n";
Model::preventAccessingMissingAttributes(true);

$model2 = new DemoModel(['id' => 2, 'name' => 'Test 2']);
echo '   Model has: '.json_encode($model2->getAttributes())."\n";
echo "   Accessing missing attribute 'email': ";

try {
    $result = $model2->email;
    echo 'returned '.($result === null ? 'null' : json_encode($result))." (unexpected) ✗\n";
} catch (MissingAttributeException $e) {
    echo "threw MissingAttributeException ✓\n";
}

echo "\n";

// Test 3: Accessing existing attribute (should work in both modes)
echo "3. Accessing existing attribute (should always work):\n";
echo "   Accessing existing attribute 'name': ";

try {
    $result = $model2->name;
    echo "returned '".$result."' ✓\n";
} catch (Exception $e) {
    echo "threw exception (unexpected) ✗\n";
}

echo "\n";

// Test 4: Configuration simulation
echo "4. Configuration simulation:\n";
echo "   In config/database.php, you would set:\n";
echo "   'eloquent' => ['prevent_accessing_missing_attributes' => true]\n";
echo "   Or use environment variable: DB_PREVENT_MISSING_ATTRIBUTES=true\n";

echo "\n=== Demo Complete ===\n";

// Reset to default
Model::preventAccessingMissingAttributes(false);
