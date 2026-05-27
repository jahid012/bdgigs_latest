<?php

namespace Database\Seeders;

use App\Models\CreatorMarketplaceItem;
use App\Models\MarketplaceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedCreatorMarketplace();
    }

    private function seedCategories(): void
    {
        foreach ($this->categoryTree() as $index => $categoryData) {
            $category = MarketplaceCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'parent_id' => null,
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'icon' => $categoryData['icon'],
                    'link_url' => $categoryData['link_url'] ?? null,
                    'sort_order' => $index * 10,
                    'active' => true,
                    'show_in_mega_menu' => true,
                ],
            );

            foreach ($categoryData['children'] as $childIndex => $childName) {
                MarketplaceCategory::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'parent_id' => $category->id,
                        'name' => $childName,
                        'description' => $childName.' services from vetted marketplace sellers.',
                        'icon' => $categoryData['icon'],
                        'link_url' => '/categories/'.$category->slug.'/'.Str::slug($childName),
                        'sort_order' => $childIndex * 10,
                        'active' => true,
                        'show_in_mega_menu' => true,
                    ],
                );
            }
        }
    }

    private function seedCreatorMarketplace(): void
    {
        foreach ($this->creatorItems() as $index => $item) {
            CreatorMarketplaceItem::updateOrCreate(
                ['title' => $item['title']],
                [
                    'description' => $item['description'],
                    'image' => $item['image'],
                    'icon' => $item['icon'],
                    'link_url' => $item['link_url'],
                    'sort_order' => $index * 10,
                    'active' => true,
                    'metadata' => ['color' => $item['color']],
                ],
            );
        }
    }

    private function categoryTree(): array
    {
        return [
            [
                'name' => 'Trending',
                'slug' => 'trending',
                'description' => 'Popular and fast-moving marketplace searches.',
                'icon' => 'spark',
                'link_url' => '/search/gigs?query=trending&source=category-nav',
                'children' => ['AI Websites', 'Fast Delivery', 'Business Launch'],
            ],
            [
                'name' => 'Graphics & Design',
                'slug' => 'graphics-design',
                'description' => 'Logos, brand systems, social visuals, and polished design assets.',
                'icon' => 'palette',
                'children' => ['Logo Design', 'Brand Style Guides', 'Social Media Design', 'Presentation Design'],
            ],
            [
                'name' => 'Programming & Tech',
                'slug' => 'programming-tech',
                'description' => 'Websites, applications, integrations, automations, and technical consulting.',
                'icon' => 'code',
                'children' => ['Website Development', 'Web Application Development', 'Website Customization', 'Script Development'],
            ],
            [
                'name' => 'Digital Marketing',
                'slug' => 'digital-marketing',
                'description' => 'SEO, paid ads, content strategy, analytics, and launch campaigns.',
                'icon' => 'megaphone',
                'children' => ['SEO', 'Social Media Marketing', 'Email Marketing', 'Marketing Strategy'],
            ],
            [
                'name' => 'Video & Animation',
                'slug' => 'video-animation',
                'description' => 'Editing, explainers, product demos, reels, and motion graphics.',
                'icon' => 'video',
                'children' => ['Video Editing', 'Short Video Ads', 'Animated Explainers', 'UGC Videos'],
            ],
            [
                'name' => 'Writing & Translation',
                'slug' => 'writing-translation',
                'description' => 'Website copy, articles, scripts, proofreading, and localization.',
                'icon' => 'document',
                'children' => ['Articles & Blog Posts', 'Website Content', 'Proofreading', 'Translation'],
            ],
            [
                'name' => 'AI Services',
                'slug' => 'ai-services',
                'description' => 'AI assistants, automations, prompt systems, and model integrations.',
                'icon' => 'spark',
                'children' => ['AI Applications', 'AI Agents', 'Prompt Engineering', 'Chatbot Development'],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Consulting, operations, research, planning, and financial support.',
                'icon' => 'building',
                'children' => ['Business Consulting', 'Market Research', 'Virtual Assistant', 'Financial Consulting'],
            ],
        ];
    }

    private function creatorItems(): array
    {
        return [
            ['title' => 'Vibe Coding', 'description' => 'Prototype AI-assisted product ideas quickly.', 'image' => '/assets/img/gig_images/1.png', 'icon' => 'spark', 'link_url' => '/search/gigs?query=vibe%20coding&source=creator-card', 'color' => '#b52b55'],
            ['title' => 'Website Development', 'description' => 'Launch fast, polished web experiences.', 'image' => '/assets/img/gig_images/3.png', 'icon' => 'code', 'link_url' => '/categories/programming-tech/website-development', 'color' => '#c9f8e8'],
            ['title' => 'Video Editing', 'description' => 'Turn footage into social-ready stories.', 'image' => '/assets/img/gig_images/4.png', 'icon' => 'video', 'link_url' => '/categories/video-animation/video-editing', 'color' => '#ffd6c8'],
            ['title' => 'Software Development', 'description' => 'Build dashboards, APIs, tools, and workflows.', 'image' => '/assets/img/gig_images/11.png', 'icon' => 'code', 'link_url' => '/categories/programming-tech/web-application-development', 'color' => '#7a6810'],
            ['title' => 'Book Publishing', 'description' => 'Prepare manuscripts and publishing assets.', 'image' => '/assets/img/gig_images/13.png', 'icon' => 'document', 'link_url' => '/search/gigs?query=book%20publishing&source=creator-card', 'color' => '#c9eee0'],
            ['title' => 'Architecture & Interior Design', 'description' => 'Plan spaces with visual concepts and drawings.', 'image' => '/assets/img/gig_images/16.png', 'icon' => 'building', 'link_url' => '/search/gigs?query=architecture%20interior%20design&source=creator-card', 'color' => '#d04472'],
        ];
    }
}
