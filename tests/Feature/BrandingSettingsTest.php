<?php

namespace Tests\Feature;

use App\Models\{BrandingSetting,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function users(): array
    {
        $tenant=Tenant::create(['name'=>'Brand Tenant','code'=>'BRAND','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'Brand Org','code'=>'BRAND-HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Brand Admin','email'=>'brand-admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Normal User','email'=>'brand-user@example.test','password'=>'password','role'=>'USER','status'=>'ACTIVE']);
        return compact('tenant','org','admin','user');
    }

    public function test_admin_can_open_branding_settings_and_system_settings_redirects_there(): void
    {
        $u=$this->users();
        $this->assertTrue(\Route::has('admin.branding.index'));
        $this->assertTrue(\Route::has('brand.logo'));
        $this->actingAs($u['admin'])->get('/workspace/system-settings')->assertRedirect('/admin/branding');
        $this->actingAs($u['admin'])->get('/admin/branding')->assertOk()->assertSee('Upload & Apply Everywhere',false);
    }

    public function test_non_admin_cannot_manage_branding(): void
    {
        $u=$this->users();
        $this->actingAs($u['user'])->get('/admin/branding')->assertForbidden();
    }

    public function test_uploaded_logo_is_served_by_shared_brand_endpoint_and_can_reset(): void
    {
        Storage::fake('local');
        $u=$this->users();
        $png=UploadedFile::fake()->image('new-logo.png',320,120);

        $this->actingAs($u['admin'])->post('/admin/branding/logo',['logo'=>$png])->assertRedirect();
        $setting=BrandingSetting::firstOrFail();
        Storage::disk('local')->assertExists($setting->logo_path);

        $response=$this->get('/brand/unifco-logo-v3.webp');
        $response->assertOk();
        $this->assertStringStartsWith('image/',(string)$response->headers->get('Content-Type'));
        $response->assertDontSee('dynamic-brand-logo-presentation',false);

        $this->actingAs($u['admin'])->post('/admin/branding/reset')->assertRedirect();
        $this->assertDatabaseCount('branding_settings',0);
    }

    public function test_logo_presentation_rules_are_injected_into_login_and_public_html(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('dynamic-brand-logo-presentation',false)
            ->assertSee('.card-logo',false)
            ->assertSee('.hero-logo',false);

        $arabic = $this->get('/')->assertOk();
        $arabic
            ->assertSee('dynamic-brand-logo-presentation',false)
            ->assertSee('.top .logo',false)
            ->assertSee('.foot-logo',false)
            ->assertSee('.top .nav .nav-actions .lang{order:1!important}',false)
            ->assertSee('.top .nav .nav-actions .btn:not(.red){order:2!important}',false)
            ->assertSee('.top .nav .nav-actions .btn.red{order:3!important}',false)
            ->assertSee('<nav class="nav-links public-primary-nav"><a href="#home">الرئيسية</a><a href="#about">من نحن</a><a href="#services">الخدمات</a><a href="#industries">عملاؤنا</a><a href="#projects">المشاريع</a><a href="#careers">الوظائف</a><a href="#contact">تواصل معنا</a></nav>',false)
            ->assertDontSee('nav-dropdown-menu',false)
            ->assertSee('تسجيل الدخول',false)
            ->assertSee('طلب خدمة',false)
            ->assertDontSee('دخول العملاء',false)
            ->assertDontSee('اطلب خدمة',false)
            ->assertDontSee('\\n</head>',false);

        $english = $this->get('/?lang=en')->assertOk();
        $english
            ->assertSee('<nav class="nav-links public-primary-nav"><a href="#home">Home</a><a href="#about">About Us</a><a href="#services">Services</a><a href="#industries">Our Clients</a><a href="#projects">Projects</a><a href="#careers">Careers</a><a href="#contact">Contact us</a></nav>',false)
            ->assertDontSee('nav-dropdown-menu',false)
            ->assertSee('Sign In',false)
            ->assertSee('Request Service',false)
            ->assertDontSee('Client Login',false);
    }
}
