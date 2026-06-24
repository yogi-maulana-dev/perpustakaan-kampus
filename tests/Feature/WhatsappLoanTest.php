<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappLoanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_wa_number_is_normalized_to_international(): void
    {
        Setting::set('wa_number', '0812-3456-7890');
        $this->assertEquals('6281234567890', Setting::waNumber());
    }

    public function test_wa_url_contains_filled_message(): void
    {
        $url = Setting::waUrl([
            'nama' => 'Budi',
            'identitas' => '2024010001',
            'judul' => 'Laskar Pelangi',
            'kode' => 'BK-0001',
        ]);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/', $url);
        $this->assertStringContainsString(rawurlencode('Laskar Pelangi'), $url);
        $this->assertStringContainsString(rawurlencode('Budi'), $url);
    }

    public function test_wa_url_null_when_number_empty(): void
    {
        Setting::set('wa_number', '');
        $this->assertNull(Setting::waNumber());
        $this->assertNull(Setting::waUrl(['judul' => 'X']));
    }

    public function test_catalog_shows_whatsapp_button(): void
    {
        $budi = User::where('email', 'budi@student.test')->first();

        $this->actingAs($budi)->get('/katalog')
            ->assertOk()
            ->assertSee('Pinjam via WhatsApp');
    }
}
