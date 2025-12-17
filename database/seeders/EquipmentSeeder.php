<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentSeeder extends Seeder
{
    /**
     * Seed the equipment table with preset catalog items.
     *
     * CRITICAL: Equipment is system-managed. Users CANNOT create custom equipment.
     * This catalog must be seeded before any exercises or gyms can be created.
     */
    public function run(): void
    {
        $equipment = [
            // Bodyweight (1 item)
            ['name' => 'None (Bodyweight)', 'category' => 'bodyweight', 'description' => 'No equipment required - bodyweight only', 'is_common' => true, 'sort_order' => 1],

            // Free Weights (10 items)
            ['name' => 'Barbell', 'category' => 'free_weights', 'description' => 'Standard Olympic barbell', 'is_common' => true, 'sort_order' => 2],
            ['name' => 'Dumbbells', 'category' => 'free_weights', 'description' => 'Adjustable or fixed weight dumbbells', 'is_common' => true, 'sort_order' => 3],
            ['name' => 'Kettlebell', 'category' => 'free_weights', 'description' => 'Cast iron or steel kettlebell', 'is_common' => true, 'sort_order' => 4],
            ['name' => 'EZ Curl Bar', 'category' => 'free_weights', 'description' => 'Angled barbell for bicep and tricep exercises', 'is_common' => true, 'sort_order' => 5],
            ['name' => 'Trap Bar', 'category' => 'free_weights', 'description' => 'Hexagonal bar for deadlifts and carries', 'is_common' => false, 'sort_order' => 6],
            ['name' => 'Medicine Ball', 'category' => 'free_weights', 'description' => 'Weighted ball for functional training', 'is_common' => true, 'sort_order' => 7],
            ['name' => 'Weight Plates', 'category' => 'free_weights', 'description' => 'Olympic or standard weight plates', 'is_common' => true, 'sort_order' => 8],
            ['name' => 'Adjustable Dumbbells', 'category' => 'free_weights', 'description' => 'Quick-adjust dumbbell system', 'is_common' => false, 'sort_order' => 9],
            ['name' => 'Sandbag', 'category' => 'free_weights', 'description' => 'Weighted sandbag for functional training', 'is_common' => false, 'sort_order' => 10],
            ['name' => 'Slam Ball', 'category' => 'free_weights', 'description' => 'Heavy ball designed for slamming exercises', 'is_common' => true, 'sort_order' => 11],

            // Machines (16 items)
            ['name' => 'Smith Machine', 'category' => 'machines', 'description' => 'Guided barbell machine', 'is_common' => true, 'sort_order' => 12],
            ['name' => 'Leg Press Machine', 'category' => 'machines', 'description' => 'Seated or angled leg press', 'is_common' => true, 'sort_order' => 13],
            ['name' => 'Lat Pulldown Machine', 'category' => 'machines', 'description' => 'Cable-based lat pulldown', 'is_common' => true, 'sort_order' => 14],
            ['name' => 'Seated Row Machine', 'category' => 'machines', 'description' => 'Horizontal rowing machine', 'is_common' => true, 'sort_order' => 15],
            ['name' => 'Chest Press Machine', 'category' => 'machines', 'description' => 'Seated chest press', 'is_common' => true, 'sort_order' => 16],
            ['name' => 'Shoulder Press Machine', 'category' => 'machines', 'description' => 'Seated overhead press', 'is_common' => true, 'sort_order' => 17],
            ['name' => 'Leg Curl Machine', 'category' => 'machines', 'description' => 'Hamstring curl machine', 'is_common' => true, 'sort_order' => 18],
            ['name' => 'Leg Extension Machine', 'category' => 'machines', 'description' => 'Quadriceps extension machine', 'is_common' => true, 'sort_order' => 19],
            ['name' => 'Hip Abduction Machine', 'category' => 'machines', 'description' => 'Outer thigh abduction', 'is_common' => true, 'sort_order' => 20],
            ['name' => 'Hip Adduction Machine', 'category' => 'machines', 'description' => 'Inner thigh adduction', 'is_common' => true, 'sort_order' => 21],
            ['name' => 'Pec Deck Machine', 'category' => 'machines', 'description' => 'Chest fly machine', 'is_common' => true, 'sort_order' => 22],
            ['name' => 'Calf Raise Machine', 'category' => 'machines', 'description' => 'Standing or seated calf raise', 'is_common' => true, 'sort_order' => 23],
            ['name' => 'Hack Squat Machine', 'category' => 'machines', 'description' => 'Angled squat machine', 'is_common' => false, 'sort_order' => 24],
            ['name' => 'Preacher Curl Machine', 'category' => 'machines', 'description' => 'Isolated bicep curl machine', 'is_common' => false, 'sort_order' => 25],
            ['name' => 'Assisted Pull-up Machine', 'category' => 'machines', 'description' => 'Counterweight pull-up/dip machine', 'is_common' => true, 'sort_order' => 26],
            ['name' => 'Back Extension Machine', 'category' => 'machines', 'description' => 'Lower back hyperextension', 'is_common' => true, 'sort_order' => 27],

            // Cable (3 items)
            ['name' => 'Cable Machine', 'category' => 'cable', 'description' => 'Single or dual adjustable cable machine', 'is_common' => true, 'sort_order' => 28],
            ['name' => 'Cable Crossover', 'category' => 'cable', 'description' => 'Dual cable system with crossover capability', 'is_common' => true, 'sort_order' => 29],
            ['name' => 'Functional Trainer', 'category' => 'cable', 'description' => 'Adjustable dual cable trainer', 'is_common' => false, 'sort_order' => 30],

            // Cardio (10 items)
            ['name' => 'Treadmill', 'category' => 'cardio', 'description' => 'Motorized or manual treadmill', 'is_common' => true, 'sort_order' => 31],
            ['name' => 'Stationary Bike', 'category' => 'cardio', 'description' => 'Upright or recumbent bike', 'is_common' => true, 'sort_order' => 32],
            ['name' => 'Rowing Machine', 'category' => 'cardio', 'description' => 'Air or water resistance rower', 'is_common' => true, 'sort_order' => 33],
            ['name' => 'Elliptical', 'category' => 'cardio', 'description' => 'Elliptical cross trainer', 'is_common' => true, 'sort_order' => 34],
            ['name' => 'StairMaster', 'category' => 'cardio', 'description' => 'Stair climbing machine', 'is_common' => true, 'sort_order' => 35],
            ['name' => 'Assault Bike', 'category' => 'cardio', 'description' => 'Air resistance fan bike', 'is_common' => false, 'sort_order' => 36],
            ['name' => 'Ski Erg', 'category' => 'cardio', 'description' => 'Vertical skiing ergometer', 'is_common' => false, 'sort_order' => 37],
            ['name' => 'VersaClimber', 'category' => 'cardio', 'description' => 'Vertical climbing machine', 'is_common' => false, 'sort_order' => 38],
            ['name' => 'Spin Bike', 'category' => 'cardio', 'description' => 'Indoor cycling bike', 'is_common' => true, 'sort_order' => 39],
            ['name' => 'Air Runner', 'category' => 'cardio', 'description' => 'Curved manual treadmill', 'is_common' => false, 'sort_order' => 40],

            // Plyometric (8 items)
            ['name' => 'Plyo Box', 'category' => 'plyometric', 'description' => 'Wooden or foam jumping box', 'is_common' => true, 'sort_order' => 41],
            ['name' => 'Agility Ladder', 'category' => 'plyometric', 'description' => 'Ground ladder for footwork drills', 'is_common' => true, 'sort_order' => 42],
            ['name' => 'Hurdles', 'category' => 'plyometric', 'description' => 'Adjustable training hurdles', 'is_common' => false, 'sort_order' => 43],
            ['name' => 'Cones', 'category' => 'plyometric', 'description' => 'Training cones for agility drills', 'is_common' => true, 'sort_order' => 44],
            ['name' => 'Speed Sled', 'category' => 'plyometric', 'description' => 'Weighted push/pull sled', 'is_common' => false, 'sort_order' => 45],
            ['name' => 'Battle Ropes', 'category' => 'plyometric', 'description' => 'Heavy conditioning ropes', 'is_common' => true, 'sort_order' => 46],
            ['name' => 'TRX Suspension Trainer', 'category' => 'plyometric', 'description' => 'Suspension training system', 'is_common' => true, 'sort_order' => 47],
            ['name' => 'Parallette Bars', 'category' => 'plyometric', 'description' => 'Low parallel bars for gymnastics', 'is_common' => false, 'sort_order' => 48],

            // Accessories (12 items)
            ['name' => 'Pull-up Bar', 'category' => 'accessories', 'description' => 'Fixed or wall-mounted pull-up bar', 'is_common' => true, 'sort_order' => 49],
            ['name' => 'Dip Station', 'category' => 'accessories', 'description' => 'Parallel bars for dips', 'is_common' => true, 'sort_order' => 50],
            ['name' => 'Flat Bench', 'category' => 'accessories', 'description' => 'Standard flat weight bench', 'is_common' => true, 'sort_order' => 51],
            ['name' => 'Adjustable Bench', 'category' => 'accessories', 'description' => 'Multi-angle adjustable bench', 'is_common' => true, 'sort_order' => 52],
            ['name' => 'Incline Bench', 'category' => 'accessories', 'description' => 'Fixed incline bench', 'is_common' => true, 'sort_order' => 53],
            ['name' => 'Decline Bench', 'category' => 'accessories', 'description' => 'Fixed decline bench', 'is_common' => false, 'sort_order' => 54],
            ['name' => 'Resistance Bands', 'category' => 'accessories', 'description' => 'Elastic resistance bands', 'is_common' => true, 'sort_order' => 55],
            ['name' => 'Foam Roller', 'category' => 'accessories', 'description' => 'Myofascial release roller', 'is_common' => true, 'sort_order' => 56],
            ['name' => 'Yoga Mat', 'category' => 'accessories', 'description' => 'Exercise mat for floor work', 'is_common' => true, 'sort_order' => 57],
            ['name' => 'Ab Wheel', 'category' => 'accessories', 'description' => 'Rolling wheel for core exercises', 'is_common' => true, 'sort_order' => 58],
            ['name' => 'Gymnastic Rings', 'category' => 'accessories', 'description' => 'Hanging rings for gymnastics', 'is_common' => false, 'sort_order' => 59],
            ['name' => 'Squat Rack', 'category' => 'accessories', 'description' => 'Power rack or squat stand', 'is_common' => true, 'sort_order' => 60],
        ];

        foreach ($equipment as $item) {
            DB::table('equipment')->insert([
                'id' => DB::raw('gen_random_uuid()'),
                'name' => $item['name'],
                'category' => $item['category'],
                'description' => $item['description'],
                'is_common' => $item['is_common'],
                'sort_order' => $item['sort_order'],
                'created_at' => now(),
            ]);
        }

        $this->command->info('Equipment seeder completed: 60 items seeded');
    }
}
