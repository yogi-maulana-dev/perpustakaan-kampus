<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function librarian(): User
    {
        return User::where('email', 'librarian@perpustakaan.test')->first();
    }

    public function test_pdf_reports_download(): void
    {
        $user = $this->librarian();
        foreach (['books', 'students', 'loans', 'fines'] as $type) {
            $res = $this->actingAs($user)->get("/laporan/pdf/{$type}");
            $res->assertOk();
            $this->assertEquals('application/pdf', $res->headers->get('content-type'));
        }
    }

    public function test_excel_reports_download(): void
    {
        $user = $this->librarian();
        foreach (['books', 'transactions'] as $type) {
            $this->actingAs($user)->get("/laporan/excel/{$type}")->assertOk();
        }
    }

    public function test_invalid_report_type_404(): void
    {
        $this->actingAs($this->librarian())->get('/laporan/pdf/unknown')->assertNotFound();
    }

    public function test_student_cannot_export(): void
    {
        $student = User::where('email', 'budi@student.test')->first();
        $this->actingAs($student)->get('/laporan/pdf/books')->assertForbidden();
    }
}
