<?php

namespace Tests\Unit;

use App\Services\HomepageHeroRenderer;
use PHPUnit\Framework\TestCase;

class HomepageHeroRendererTest extends TestCase
{
    private string $html = '<!doctype html><html><head></head><body><section class="hero"><div class="hero-inner"><div class="hero-copy">CMS COPY</div></div></section></body></html>';

    public function test_background_mode_uses_one_background_image_layer_and_keeps_cms_content(): void
    {
        $renderer = new HomepageHeroRenderer();
        $html = $renderer->render($this->html, [
            'hero_render_mode' => 'background',
            'hero_image' => '/images/raw-background.webp',
        ]);

        $this->assertStringContainsString("url('/images/raw-background.webp')", $html);
        $this->assertStringContainsString('CMS COPY', $html);
        $this->assertStringContainsString('.hero::before', $html);
        $this->assertStringNotContainsString('<img class="hero-full-banner-image"', $html);
    }

    public function test_full_banner_mode_injects_exactly_one_image_and_hides_cms_overlay_content(): void
    {
        $renderer = new HomepageHeroRenderer();
        $html = $renderer->render($this->html, [
            'hero_render_mode' => 'full_banner',
            'hero_image' => '/images/full-banner.webp',
            'lang' => 'ar',
        ]);

        $this->assertSame(1, substr_count($html, '<img class="hero-full-banner-image"'));
        $this->assertStringContainsString('src="/images/full-banner.webp"', $html);
        $this->assertStringContainsString('.hero-inner{display:none!important}', $html);
        $this->assertStringContainsString('.hero::before,.hero::after{display:none!important', $html);
    }

    public function test_full_banner_mode_adds_accessible_request_and_contact_hotspots(): void
    {
        $renderer = new HomepageHeroRenderer();
        $html = $renderer->render($this->html, [
            'hero_render_mode' => 'full_banner',
            'hero_image' => '/images/full-banner.webp',
            'lang' => 'ar',
        ]);

        $this->assertStringContainsString('hero-banner-hotspot--request', $html);
        $this->assertStringContainsString('hero-banner-hotspot--contact', $html);
        $this->assertStringContainsString('href="#contact"', $html);
        $this->assertStringContainsString('aria-label="طلب الخدمة"', $html);
        $this->assertStringContainsString('aria-label="تواصل معنا"', $html);
    }

    public function test_full_banner_mode_replaces_old_proof_area_with_approved_three_icon_strip(): void
    {
        $renderer = new HomepageHeroRenderer();
        $html = $renderer->render($this->html, [
            'hero_render_mode' => 'full_banner',
            'hero_image' => '/images/full-banner.webp',
            'lang' => 'ar',
        ]);

        $this->assertSame(1, substr_count($html, 'class="hero-approved-proofs"'));
        $this->assertSame(3, substr_count($html, 'class="hero-approved-proof"'));
        $this->assertStringContainsString('تغطية شاملة', $html);
        $this->assertStringContainsString('الجودة المعتمدة', $html);
        $this->assertStringContainsString('دعم على مدار 24/7', $html);
        $this->assertStringNotContainsString('متجر المرافق الواحد', $html);
    }

    public function test_english_full_banner_keeps_approved_icon_meanings_in_english_without_translating_one_facility_shop(): void
    {
        $renderer = new HomepageHeroRenderer();
        $html = $renderer->render($this->html, [
            'hero_render_mode' => 'full_banner',
            'hero_image' => '/images/full-banner-en.webp',
            'lang' => 'en',
        ]);

        $this->assertStringContainsString('Nationwide Coverage', $html);
        $this->assertStringContainsString('Certified Quality', $html);
        $this->assertStringContainsString('24/7 Support', $html);
        $this->assertStringNotContainsString('متجر المرافق الواحد', $html);
    }

    public function test_invalid_mode_falls_back_to_background_mode(): void
    {
        $renderer = new HomepageHeroRenderer();
        $html = $renderer->render($this->html, [
            'hero_render_mode' => 'something-else',
            'hero_image' => '/images/raw.webp',
        ]);

        $this->assertStringContainsString("url('/images/raw.webp')", $html);
        $this->assertStringNotContainsString('<img class="hero-full-banner-image"', $html);
    }

    public function test_missing_image_uses_safe_project_fallback(): void
    {
        $renderer = new HomepageHeroRenderer();
        $html = $renderer->render($this->html, ['hero_render_mode' => 'background']);

        $this->assertStringContainsString('/images/unifco-home-hero-20260902.webp', $html);
    }
}
