<?php

namespace Database\Factories;

use App\Models\Gig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Gig>
 */
class GigFactory extends Factory
{
    protected $model = Gig::class;

    public function definition(): array
    {
        $category = fake()->randomElement($this->categories());
        $title = 'I will '.fake()->randomElement([
            'build a conversion focused Laravel marketplace feature',
            'design a clean website and dashboard experience',
            'develop a React and API workflow for your business',
            'customize a modern ecommerce website',
            'audit performance and fix frontend bottlenecks',
            'create a responsive product landing page',
        ]);
        $price = fake()->randomElement([25, 45, 60, 80, 95, 120, 150, 220]);
        $deliveryDays = fake()->numberBetween(2, 10);
        $image = fake()->randomElement($this->gigImages());

        return [
            'seller_id' => User::factory(),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'title' => $title,
            'seller_name' => fake()->name(),
            'seller_avatar' => null,
            'seller_level' => fake()->randomElement(['New Seller', 'Level 1', 'Level 2']),
            'badge' => fake()->boolean(20) ? 'Popular' : null,
            'image' => $image,
            'category_id' => Str::slug($category),
            'category_label' => $category,
            'price_cents' => $price * 100,
            'rating' => fake()->randomFloat(1, 4.4, 5),
            'reviews' => fake()->numberBetween(0, 280),
            'delivery_days' => $deliveryDays,
            'seller_details' => ['english', fake()->boolean(60) ? 'online' : 'responsive'],
            'service_options' => collect([$category, 'Laravel', 'React', 'Responsive'])
                ->map(fn (string $option) => Str::slug($option))
                ->values()
                ->all(),
            'pro' => $price >= 120,
            'instant' => $deliveryDays <= 3,
            'consultation' => fake()->boolean(45),
            'featured' => false,
            'search_text' => strtolower($title.' '.$category.' laravel react dashboard'),
            'tag' => $category,
            'orders_label' => fake()->numberBetween(0, 40).' active',
            'conversion_label' => fake()->numberBetween(5, 22).'% conversion',
            'status' => 'Published',
            'status_class' => 'status-completed',
            'packages' => $this->packagesFor($price, $deliveryDays),
            'extras' => [],
            'requirements' => [
                ['id' => 'brief', 'label' => 'Project brief', 'required' => true],
                ['id' => 'assets', 'label' => 'Brand assets or references', 'required' => false],
            ],
            'gallery_images' => [$image, fake()->randomElement($this->gigImages())],
            'metadata' => [
                'about' => fake()->paragraphs(2),
                'source' => 'factory',
            ],
        ];
    }

    public function withSeller(User $seller): static
    {
        return $this->for($seller, 'seller')->state(fn (array $attributes) => [
            'seller_name' => $seller->name,
            'seller_avatar' => $seller->avatar,
        ]);
    }

    private function categories(): array
    {
        return [
            'Website Development',
            'Web Application Development',
            'Website Customization',
            'UI UX Design',
            'Script Development',
        ];
    }

    private function gigImages(): array
    {
        return collect(range(1, 21))
            ->map(fn (int $number) => '/assets/img/gig_images/'.$number.'.png')
            ->all();
    }

    private function packagesFor(int $basePrice, int $deliveryDays): array
    {
        return [
            [
                'id' => 'basic',
                'name' => 'Basic',
                'title' => 'Starter package',
                'description' => 'A focused first delivery for a clear scope.',
                'delivery' => $deliveryDays.'-day delivery',
                'revisions' => '2 revisions',
                'price' => (string) $basePrice,
            ],
            [
                'id' => 'standard',
                'name' => 'Standard',
                'title' => 'Growth package',
                'description' => 'Expanded delivery with implementation support.',
                'delivery' => ($deliveryDays + 2).'-day delivery',
                'revisions' => '4 revisions',
                'price' => (string) ($basePrice * 2),
            ],
            [
                'id' => 'premium',
                'name' => 'Premium',
                'title' => 'Complete package',
                'description' => 'Full delivery for a larger marketplace goal.',
                'delivery' => ($deliveryDays + 5).'-day delivery',
                'revisions' => 'Unlimited revisions',
                'price' => (string) ($basePrice * 4),
            ],
        ];
    }
}
