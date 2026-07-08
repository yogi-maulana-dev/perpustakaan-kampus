<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Imports\BooksImport;
use App\Imports\MembersImport;
use App\Models\Book;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_import_creates_books_and_auto_relations(): void
    {
        $import = new BooksImport;
        $import->collection(collect([
            collect(['nama_pengarang' => 'Ubedilah Badrun', 'judul_buku' => 'Sistem Politik Indonesia', 'penerbit' => 'Bumi Aksara', 'tahun_terbit' => 2016, 'cet_ed' => '1']),
            collect(['nama_pengarang' => 'Nurudin', 'judul_buku' => 'Ilmu Komunikasi', 'penerbit' => 'Rajawali Pers', 'tahun_terbit' => 2017, 'cet_ed' => '2']),
            collect(['judul_buku' => '']), // dilewati: tanpa judul
        ]));

        $this->assertEquals(2, $import->imported);
        $this->assertEquals(1, $import->skipped);

        $book = Book::where('judul', 'Sistem Politik Indonesia')->first();
        $this->assertNotNull($book);
        $this->assertEquals('1', $book->cetakan);
        $this->assertEquals(2016, $book->tahun_terbit);
        $this->assertEquals('Ubedilah Badrun', $book->author->nama);
        $this->assertEquals('Bumi Aksara', $book->publisher->nama);
        $this->assertNotNull($book->category);
        $this->assertNotEmpty($book->kode_buku);
        $this->assertEquals($book->jumlah_stok, $book->stok_tersedia);
    }

    public function test_members_import_creates_active_anggota(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $import = new MembersImport;
        $import->collection(collect([
            collect(['nama' => 'Budi Test', 'email' => 'buditest@example.com', 'tipe' => 'mahasiswa', 'nim' => '2024010001', 'fakultas' => 'Fakultas Teknik', 'program_studi' => 'Informatika', 'no_hp' => '081200000000']),
            collect(['nama' => '']), // dilewati
        ]));

        $this->assertEquals(1, $import->imported);
        $this->assertEquals(1, $import->skipped);

        $user = User::where('email', 'buditest@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Anggota'));
        $this->assertEquals(UserStatus::Active, $user->status);
        $this->assertEquals('2024010001', $user->mahasiswaProfile->nim);
    }

    public function test_members_import_generates_email_and_skips_duplicates(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $import = new MembersImport;
        $import->collection(collect([
            collect(['nama' => 'Tanpa Email', 'tipe' => 'umum', 'nomor_identitas' => '1871999888']),
        ]));

        $user = User::whereHas('mahasiswaProfile', fn ($q) => $q->where('nomor_identitas', '1871999888'))->first();
        $this->assertNotNull($user);
        $this->assertStringContainsString('@anggota.local', $user->email);

        // Email yang sudah ada → dilewati.
        $dup = new MembersImport;
        $dup->collection(collect([
            collect(['nama' => 'Duplikat', 'email' => $user->email, 'tipe' => 'umum']),
        ]));
        $this->assertEquals(0, $dup->imported);
        $this->assertEquals(1, $dup->skipped);
    }

    public function test_duplicate_book_title_shows_warning(): void
    {
        $this->seed(DatabaseSeeder::class);
        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $judul = Book::first()->judul;

        $book = Book::whereNotNull('isbn')->first();

        $c = Volt::actingAs($librarian)->test('staff.books')
            ->set('judul', $judul)
            ->set('isbn', $book->isbn);

        $this->assertNotNull($c->get('judulWarning'));
        $this->assertNotNull($c->get('isbnWarning'));
    }

    public function test_edit_book_with_null_cetakan_opens_without_error(): void
    {
        $this->seed(DatabaseSeeder::class);
        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $book = Book::first(); // cetakan null dari seeder

        Volt::actingAs($librarian)->test('staff.books')
            ->call('edit', $book->id)
            ->assertHasNoErrors()
            ->assertSet('showForm', true);
    }

    public function test_create_prefills_sequential_kode(): void
    {
        $this->seed(DatabaseSeeder::class);
        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();

        $c = Volt::actingAs($librarian)->test('staff.books')->call('create');

        $this->assertMatchesRegularExpression('/^BK-\d{5}$/', $c->get('kode_buku'));
    }

    public function test_books_can_be_sorted_without_error(): void
    {
        $this->seed(DatabaseSeeder::class);
        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();

        Volt::actingAs($librarian)->test('staff.books')
            ->set('sort', 'judul_az')->assertHasNoErrors()
            ->set('sort', 'tahun_baru')->assertHasNoErrors()
            ->set('sort', 'terlama')->assertHasNoErrors()
            ->set('sort', 'kode')->assertHasNoErrors();
    }

    public function test_bulk_cover_upload_matches_by_kode_buku(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $book = Book::first();

        Volt::actingAs($librarian)->test('staff.books')
            ->set('covers', [UploadedFile::fake()->image($book->kode_buku.'.jpg')])
            ->call('uploadCovers');

        $book->refresh();
        $this->assertNotNull($book->cover);
        Storage::disk('public')->assertExists($book->cover);
    }

    public function test_bulk_photo_upload_matches_by_identitas(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $budi = User::where('email', 'budi@student.test')->first();
        $nim = $budi->mahasiswaProfile->nim;
        $this->assertNotEmpty($nim);

        Volt::actingAs($librarian)->test('staff.students')
            ->set('photos', [UploadedFile::fake()->image($nim.'.jpg')])
            ->call('uploadPhotos');

        $budi->refresh();
        Storage::disk('public')->assertExists($budi->mahasiswaProfile->foto);
    }
}
