<?php

namespace Database\Factories;

use App\Models\Gig;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            'Pending Requirements',
            'In Progress',
            'Delivered',
            'Completed',
        ]);
        $price = fake()->randomElement([45, 75, 120, 180, 260, 320]);

        return [
            'code' => 'ORD-'.fake()->unique()->numerify('######'),
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'gig_id' => Gig::factory(),
            'service' => fake()->sentence(6),
            'buyer_name' => fake()->name(),
            'seller_name' => fake()->name(),
            'status' => $status,
            'status_class' => $this->statusClass($status),
            'due_date' => fake()->dateTimeBetween('-3 days', '+14 days'),
            'price_cents' => $price * 100,
            'earnings_cents' => (int) round($price * 0.85 * 100),
            'metadata' => [
                'itemSummary' => 'Seeded marketplace package',
                'quantity' => 1,
                'requirements' => [
                    ['label' => 'Project brief', 'answer' => fake()->sentence()],
                ],
                'activity' => [
                    [
                        'title' => 'Order created',
                        'detail' => 'The buyer placed this order from a gig package.',
                        'time' => now()->subDays(2)->format('M j, Y g:i A'),
                    ],
                ],
            ],
        ];
    }

    public function between(User $buyer, User $seller, Gig $gig): static
    {
        return $this->for($buyer, 'buyer')
            ->for($seller, 'seller')
            ->for($gig)
            ->state(fn (array $attributes) => [
                'service' => $gig->title,
                'buyer_name' => $buyer->name,
                'seller_name' => $seller->name,
                'price_cents' => $gig->price_cents,
                'earnings_cents' => (int) round($gig->price_cents * 0.85),
            ]);
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'Delivered', 'Pending Requirements' => 'status-delivered',
            'Completed' => 'status-completed',
            default => 'status-progress',
        };
    }
}
