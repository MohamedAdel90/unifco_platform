<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\Tenant;
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

        $tenant = Tenant::query()->create([
            'name' => 'Test Tenant',
            'code' => 'TEST',
            'status' => 'ACTIVE',
        ]);

        $admin = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Homepage CMS Admin',
            'email' => 'homepage-cms-admin@example.test',
            'password' => 'password',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $section = HomepageSection::query()->where('section_key', 'hero')->firstOrFail();

        $html = $this->actingAs($admin)
            ->get(route('admin.homepage.sections.edit', $section))
            ->assertOk()
            ->getContent();

        // Verify the functional resize contract rather than presentation copy,
        // which may change without affecting the image-resize controls.
        $this->assertStringContainsString('data-resize-width', $html);
        $this->assertStringContainsString('data-resize-height', $html);
        $this->assertStringContainsString('data-resize-lock', $html);
        $this->assertStringContainsString(route('admin.homepage.images.upload'), $html);
    }
}
