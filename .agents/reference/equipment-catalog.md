# Equipment Preset Catalog

**CRITICAL**: Equipment is system-managed. Users CANNOT create custom equipment.

This catalog must be seeded before any exercises or gyms can be created.

## Equipment Seeder Data

### Bodyweight (1 item)
```php
[
    'name' => 'None (Bodyweight)',
    'category' => 'bodyweight',
    'description' => 'No equipment required - bodyweight only',
    'is_common' => true,
    'sort_order' => 1,
]
```

### Free Weights (10 items)
```php
['name' => 'Barbell', 'category' => 'free_weights', 'is_common' => true],
['name' => 'Dumbbells', 'category' => 'free_weights', 'is_common' => true],
['name' => 'Kettlebell', 'category' => 'free_weights', 'is_common' => true],
['name' => 'EZ Curl Bar', 'category' => 'free_weights', 'is_common' => true],
['name' => 'Trap Bar', 'category' => 'free_weights', 'is_common' => false],
['name' => 'Medicine Ball', 'category' => 'free_weights', 'is_common' => true],
['name' => 'Weight Plates', 'category' => 'free_weights', 'is_common' => true],
['name' => 'Adjustable Dumbbells', 'category' => 'free_weights', 'is_common' => false],
['name' => 'Sandbag', 'category' => 'free_weights', 'is_common' => false],
['name' => 'Slam Ball', 'category' => 'free_weights', 'is_common' => true],
```

### Machines (16 items)
```php
['name' => 'Smith Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Leg Press Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Lat Pulldown Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Seated Row Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Chest Press Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Shoulder Press Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Leg Curl Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Leg Extension Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Hip Abduction Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Hip Adduction Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Pec Deck Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Calf Raise Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Hack Squat Machine', 'category' => 'machines', 'is_common' => false],
['name' => 'Preacher Curl Machine', 'category' => 'machines', 'is_common' => false],
['name' => 'Assisted Pull-up Machine', 'category' => 'machines', 'is_common' => true],
['name' => 'Back Extension Machine', 'category' => 'machines', 'is_common' => true],
```

### Cable (3 items)
```php
['name' => 'Cable Machine', 'category' => 'cable', 'is_common' => true],
['name' => 'Cable Crossover', 'category' => 'cable', 'is_common' => true],
['name' => 'Functional Trainer', 'category' => 'cable', 'is_common' => false],
```

### Cardio (10 items)
```php
['name' => 'Treadmill', 'category' => 'cardio', 'is_common' => true],
['name' => 'Stationary Bike', 'category' => 'cardio', 'is_common' => true],
['name' => 'Rowing Machine', 'category' => 'cardio', 'is_common' => true],
['name' => 'Elliptical', 'category' => 'cardio', 'is_common' => true],
['name' => 'StairMaster', 'category' => 'cardio', 'is_common' => true],
['name' => 'Assault Bike', 'category' => 'cardio', 'is_common' => false],
['name' => 'Ski Erg', 'category' => 'cardio', 'is_common' => false],
['name' => 'VersaClimber', 'category' => 'cardio', 'is_common' => false],
['name' => 'Spin Bike', 'category' => 'cardio', 'is_common' => true],
['name' => 'Air Runner', 'category' => 'cardio', 'is_common' => false],
```

### Plyometric (8 items)
```php
['name' => 'Plyo Box', 'category' => 'plyometric', 'is_common' => true],
['name' => 'Agility Ladder', 'category' => 'plyometric', 'is_common' => true],
['name' => 'Hurdles', 'category' => 'plyometric', 'is_common' => false],
['name' => 'Cones', 'category' => 'plyometric', 'is_common' => true],
['name' => 'Speed Sled', 'category' => 'plyometric', 'is_common' => false],
['name' => 'Battle Ropes', 'category' => 'plyometric', 'is_common' => true],
['name' => 'TRX Suspension Trainer', 'category' => 'plyometric', 'is_common' => true],
['name' => 'Parallette Bars', 'category' => 'plyometric', 'is_common' => false],
```

### Accessories (12 items)
```php
['name' => 'Pull-up Bar', 'category' => 'accessories', 'is_common' => true],
['name' => 'Dip Station', 'category' => 'accessories', 'is_common' => true],
['name' => 'Flat Bench', 'category' => 'accessories', 'is_common' => true],
['name' => 'Adjustable Bench', 'category' => 'accessories', 'is_common' => true],
['name' => 'Incline Bench', 'category' => 'accessories', 'is_common' => true],
['name' => 'Decline Bench', 'category' => 'accessories', 'is_common' => false],
['name' => 'Resistance Bands', 'category' => 'accessories', 'is_common' => true],
['name' => 'Foam Roller', 'category' => 'accessories', 'is_common' => true],
['name' => 'Yoga Mat', 'category' => 'accessories', 'is_common' => true],
['name' => 'Ab Wheel', 'category' => 'accessories', 'is_common' => true],
['name' => 'Gymnastic Rings', 'category' => 'accessories', 'is_common' => false],
['name' => 'Squat Rack', 'category' => 'accessories', 'is_common' => true],
```

## Total Count: ~60 items

## Seeder Implementation Pattern

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = [
            // Bodyweight
            ['name' => 'None (Bodyweight)', 'category' => 'bodyweight', 'description' => 'No equipment required', 'is_common' => true, 'sort_order' => 1],
            
            // Free Weights
            ['name' => 'Barbell', 'category' => 'free_weights', 'description' => 'Standard Olympic barbell', 'is_common' => true, 'sort_order' => 2],
            // ... continue for all items
        ];

        foreach ($equipment as $item) {
            DB::table('equipment')->insert([
                'id' => DB::raw('gen_random_uuid()'),
                'name' => $item['name'],
                'category' => $item['category'],
                'description' => $item['description'] ?? null,
                'is_common' => $item['is_common'],
                'sort_order' => $item['sort_order'] ?? 0,
                'created_at' => now(),
            ]);
        }
    }
}
```

## Usage

Exercises reference equipment via `exercise_equipment` table:
```php
// Bench Press can use multiple equipment
$benchPress->equipment()->attach([
    $barbell->id => ['is_primary' => true],
    $dumbbells->id => ['is_primary' => false, 'notes' => 'Alternative option'],
    $smithMachine->id => ['is_primary' => false, 'notes' => 'Machine variation'],
]);
```

Gyms reference equipment via `gym_equipment` table:
```php
// Gym has certain equipment available
$gym->equipment()->attach([
    $barbell->id => ['quantity' => 5],
    $dumbbells->id => ['quantity' => 10],
    $treadmill->id => ['quantity' => 20],
]);
```

## Compatibility Checking

See `/implementation/compatibility-algorithm.md` for full algorithm.

**Key Concept**: Workout is compatible with gym if ALL exercises have AT LEAST ONE equipment option the gym has.
