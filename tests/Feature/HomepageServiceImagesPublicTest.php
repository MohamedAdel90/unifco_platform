<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageServiceImagesPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_homepage_service_images_render_from_cms_for_arabic_and_english(): void
    {
        $arItems = [];
        $enItems = [];

        for ($i = 1; $i <= 13; $i++) {
            $number = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $image = "/storage/homepage-media/service-{$number}.webp";

            $arItems[] = [
                'number' => $number,
                'image' => $image,
                'title' => "خدمة {$number}",
                'desc' => "وصف {$number}",
            ];

            $enItems[] = [
                'number' => $number,
                'image' => $image,
                'title' => "Service {$number}",
                'desc' => "Description {$number}",
            ];
        }

        HomepageSection::query()->create([
            'section_key' => 'services',
            'is_active' => true,
            'sort_order' => 30,
            'data_ar' => [
                'kicker' => 'خدماتنا',
                'title' => 'خدماتنا',
                'text' => 'خدماتنا',
                'more' => 'المزيد',
                'button' => 'عرض جميع الخدمات',
                'items' => $arItems,
            ],
            'data_en' => [
                'kicker' => 'Services',
                'title' => 'Services',
                'text' => 'Services',
                'more' => 'More',
                'button' => 'View all services',
                'items' => $enItems,
            ],
        ]);

        foreach (['ar', 'en'] as $locale) {
            $response = $this->get('/?lang='.$locale);
            $response->assertOk();

            for ($i = 1; $i <= 13; $i++) {
                $number = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $response->assertSee("/storage/homepage-media/service-{$number}.webp", false);
            }
        }
    }
}
