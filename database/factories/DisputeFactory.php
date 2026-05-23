<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispute>
 */
class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'opened_by_id' => User::factory(),
            'case_code' => 'DSP-'.fake()->unique()->numerify('######'),
            'reason' => fake()->randomElement([
                'Delivery scope disagreement',
                'Missing requirements follow-up',
                'Manual payment review question',
                'Revision window request',
            ]),
            'description' => fake()->sentence(14),
            'priority' => fake()->randomElement(Dispute::PRIORITIES),
            'status' => fake()->randomElement(['open', 'reviewing', 'waiting_buyer']),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
