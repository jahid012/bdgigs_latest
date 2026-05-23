<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gig_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('image')->index();
            $table->text('url');
            $table->text('thumbnail_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['gig_id', 'sort_order']);
        });

        $now = now();

        DB::table('gigs')
            ->select(['id', 'title', 'image', 'gallery_images'])
            ->orderBy('id')
            ->each(function ($gig) use ($now): void {
                $images = collect(json_decode($gig->gallery_images ?: '[]', true))
                    ->when($gig->image, fn ($collection) => $collection->prepend($gig->image))
                    ->filter()
                    ->unique()
                    ->values();

                $images->each(function (string $image, int $index) use ($gig, $now): void {
                    DB::table('gig_media')->insert([
                        'gig_id' => $gig->id,
                        'type' => 'image',
                        'url' => $image,
                        'thumbnail_url' => $image,
                        'alt_text' => $gig->title.' preview '.($index + 1),
                        'sort_order' => $index,
                        'is_primary' => $index === 0,
                        'status' => 'active',
                        'metadata' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('gig_media');
    }
};
