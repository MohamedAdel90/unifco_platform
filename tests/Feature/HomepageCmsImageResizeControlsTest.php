<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\User;
use Database\Seeders\HomepageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCmsImageResizeControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_image_fields_expose_editable_pixel_dimensions_and_resize_action(): void
    {
        $this->seed(HomepageContentSeeder::class);
        $admin = User::query()->where('role', 'ADMIN')->firstOrFail();
        $section = HomepageSection::query()->where('section_key', 'hero')->firstOrFail();

        $html = $this->actingAs($admin)
            ->get(route('admin.homepage.sections.edit', $section))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-resize-width', $html);
        $this->assertStringContainsString('data-resize-height', $html);
        $this->assertStringContainsString('data-resize-lock', $html);
        $this->assertStringContainsString('Resize &amp; Use Image', $html);
        $this->assertStringContainsString('Creates a new resized copy', $html);
        $this->assertStringContainsString(route('admin.homepage.images.upload'), $html);
    }
}
