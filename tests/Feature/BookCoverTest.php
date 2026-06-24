<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BookCoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_cover_uploads_to_public_disk_and_url_is_relative(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();

        Volt::actingAs($librarian)
            ->test('staff.books')
            ->call('create')
            ->set('kode_buku', 'BK-TEST-1')
            ->set('judul', 'Buku Bergambar')
            ->set('category_id', Category::first()->id)
            ->set('author_id', Author::first()->id)
            ->set('publisher_id', Publisher::first()->id)
            ->set('jumlah_stok', 3)
            ->set('cover', UploadedFile::fake()->image('cover.jpg', 400, 600))
            ->call('save')
            ->assertHasNoErrors();

        $book = Book::where('kode_buku', 'BK-TEST-1')->first();
        $this->assertNotNull($book->cover);
        Storage::disk('public')->assertExists($book->cover);

        // URL harus relatif /storage (bukan absolut http://localhost) agar tampil di host manapun.
        $url = Storage::disk('public')->url($book->cover);
        $this->assertStringStartsWith('/storage/', $url);
    }
}
