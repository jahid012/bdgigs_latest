<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Support\EmailTemplateDefaults;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (EmailTemplateDefaults::all() as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                collect($template)->except('key')->all(),
            );
        }
    }
}
