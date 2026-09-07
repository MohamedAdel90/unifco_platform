<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use Tests\TestCase;

class PublicAboutPageTest extends TestCase
{
    public function test_about_page_renders_semantic_english_content(): void
    {
        $this->get('/about?lang=en')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('<h1 id="about-title">About Us</h1>', false)
            ->assertSee('UNIFCO Facilities Contracting is a 100% Saudi enterprise')
            ->assertSee('Facility Management')
            ->assertSee('What Sets Us Apart')
            ->assertSee('Our Mission')
            ->assertSee('Our Vision')
            ->assertSee('Facilities Today... A More Sustainable Tomorrow')
            ->assertSee('hreflang="ar"', false)
            ->assertDontSee('unifco-profile-modal');
    }

    public function test_about_page_renders_semantic_arabic_content(): void
    {
        $this->get('/about?lang=ar')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('<h1 id="about-title">من نحن</h1>', false)
            ->assertSee('وحدة المرافق منشأة سعودية 100%')
            ->assertSee('إدارة المرافق')
            ->assertSee('ما يميزنا')
            ->assertSee('رسالتنا')
            ->assertSee('رؤيتنا')
            ->assertSee('مرافق اليوم ... لمستقبل أكثر استدامة')
            ->assertSee('hreflang="en"', false)
            ->assertDontSee('unifco-profile-modal');
    }

    public function test_about_page_language_switch_preserves_the_page(): void
    {
        $english = $this->get('/about?lang=en')->assertOk()->getContent();
        $arabic = $this->get('/about?lang=ar')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('public.about', ['lang' => 'ar']).'" hreflang="ar"', $english);
        $this->assertStringContainsString('href="'.route('public.about', ['lang' => 'en']).'" hreflang="en"', $arabic);
        $this->assertStringContainsString('href="'.route('public.home', ['lang' => 'en']).'"', $english);
        $this->assertStringContainsString('href="'.route('public.home', ['lang' => 'ar']).'"', $arabic);
    }

    public function test_homepage_about_links_open_the_localized_html_page_without_a_modal(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $html = $this->get('/?lang='.$locale)->assertOk()->getContent();
            $aboutUrl = route('public.about', ['lang' => $locale]);

            $this->assertStringContainsString('href="'.$aboutUrl.'"', $html);
            $this->assertStringNotContainsString('id="unifco-profile-modal"', $html);
            $this->assertStringNotContainsString('id="company-profile-overlay"', $html);
            $this->assertStringNotContainsString('unifco-about-card-', $html);
        }

        $showcaseScript = file_get_contents(public_path('js/home-showcase.js'));
        $this->assertIsString($showcaseScript);
        $this->assertStringNotContainsString('fixAboutDialog', $showcaseScript);
        $this->assertStringNotContainsString('aboutImage', $showcaseScript);
    }

    public function test_about_page_includes_responsive_html_layout_rules(): void
    {
        $html = $this->get('/about?lang=en')->assertOk()->getContent();

        $this->assertStringContainsString('.value-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr))', $html);
        $this->assertStringContainsString('@media(max-width:850px)', $html);
        $this->assertStringContainsString('@media(max-width:520px)', $html);
        $this->assertStringContainsString('grid-template-areas:"copy" "media"', $html);
    }

    public function test_about_page_and_homepage_load_the_same_locale_fonts(): void
    {
        $about = $this->get('/about?lang=en')->assertOk()->getContent();
        $home = $this->get('/?lang=en')->assertOk()->getContent();

        foreach ([$about, $home] as $html) {
            $this->assertStringContainsString('family=Cairo:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900', $html);
        }

        $this->assertStringContainsString('body[dir="ltr"]{font-family:Inter,Arial,sans-serif}', $about);
        $this->assertStringContainsString('<body dir="ltr">', $about);
    }

    public function test_about_page_uses_the_homepage_navigation_in_both_locales(): void
    {
        $labels = [
            'ar' => ['الرئيسية', 'تعرف علينا', 'الخدمات', 'القطاعات', 'المشاريع', 'العملاء', 'الوظائف', 'تواصل معنا'],
            'en' => ['Home', 'About Us', 'Services', 'Industries', 'Projects', 'Clients', 'Careers', 'Contact Us'],
        ];

        foreach ($labels as $locale => $expectedLabels) {
            $html = $this->get('/about?lang='.$locale)->assertOk()->getContent();
            $homeUrl = route('public.home', ['lang' => $locale]);

            $this->assertStringContainsString('<header class="top about-page-header">', $html);
            $this->assertStringContainsString('<nav class="nav-links public-primary-nav"', $html);
            $this->assertStringContainsString('aria-current="page"', $html);
            $this->assertSame(8, substr_count($html, 'class="nav-icon"'));
            $this->assertStringContainsString('href="'.$homeUrl.'#services"', $html);
            $this->assertStringContainsString('href="'.$homeUrl.'#industries"', $html);
            $this->assertStringContainsString('href="'.$homeUrl.'#projects"', $html);
            $this->assertStringContainsString('href="'.$homeUrl.'#clients"', $html);
            $this->assertStringContainsString('href="'.$homeUrl.'#contact"', $html);
            $this->assertStringContainsString('href="'.route('login').'"', $html);
            $this->assertStringContainsString('href="'.route('public.request-service', ['lang' => $locale]).'"', $html);
            $this->assertStringContainsString('id="menu-toggle"', $html);
            $this->assertStringContainsString('id="mobile-menu"', $html);

            foreach ($expectedLabels as $label) {
                $this->assertStringContainsString($label, $html);
            }
        }
    }

    public function test_about_page_prefers_cms_content_when_an_about_page_section_exists(): void
    {
        HomepageSection::updateOrCreate(
            ['section_key' => 'about_page'],
            [
                'is_active' => true,
                'sort_order' => 130,
                'data_ar' => [
                    'title' => 'قصتنا',
                    'intro' => 'محتوى من لوحة التحكم بالعربية',
                    'mission_title' => 'مهمتنا الجديدة',
                    'values' => [
                        ['icon' => 'building', 'title' => 'قيمة أولى', 'sub' => 'وصف أول'],
                    ],
                ],
                'data_en' => [
                    'title' => 'Our Story',
                    'intro' => 'Dashboard-managed English content',
                    'mission_title' => 'New Mission',
                    'values' => [
                        ['icon' => 'building', 'title' => 'Value One', 'sub' => 'Description one'],
                    ],
                ],
            ],
        );

        try {
            $en = $this->get('/about?lang=en')->assertOk()->getContent();
            $this->assertStringContainsString('<h1 id="about-title">Our Story</h1>', $en);
            $this->assertStringContainsString('Dashboard-managed English content', $en);
            $this->assertStringContainsString('New Mission', $en);
            $this->assertStringContainsString('Value One', $en);
            $this->assertStringNotContainsString('>Innovation</strong>', $en);

            $ar = $this->get('/about?lang=ar')->assertOk()->getContent();
            $this->assertStringContainsString('<h1 id="about-title">قصتنا</h1>', $ar);
            $this->assertStringContainsString('محتوى من لوحة التحكم بالعربية', $ar);
            $this->assertStringContainsString('قيمة أولى', $ar);
            $this->assertStringNotContainsString('>الجودة</strong>', $ar);
        } finally {
            HomepageSection::query()->where('section_key', 'about_page')->delete();
        }
    }

    public function test_about_page_falls_back_to_defaults_when_the_cms_section_is_missing_or_inactive(): void
    {
        HomepageSection::query()->where('section_key', 'about_page')->delete();

        $html = $this->get('/about?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('UNIFCO Facilities Contracting is a 100% Saudi enterprise', $html);
        $this->assertStringContainsString('What Sets Us Apart', $html);
        $this->assertStringContainsString('Facility Management', $html);
        $this->assertStringContainsString('<h1 id="about-title">About Us</h1>', $html);
    }
}
