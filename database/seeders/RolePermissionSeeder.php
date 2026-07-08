<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Daftar permission granular ----------------------------------------
        $permissions = [
            'kelola user',
            'kelola role',
            'kelola setting',
            'approve mahasiswa',
            'kelola buku',
            'kelola master',        // kategori, rak, penerbit, penulis
            'lihat buku',
            'kelola peminjaman',    // approve / reject pengajuan
            'input peminjaman',     // staf membuat transaksi
            'kelola pengembalian',
            'kelola denda',
            'lihat laporan',
            'export laporan',
            'pinjam buku',          // mahasiswa mengajukan
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Role + assignment --------------------------------------------------
        $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');
        $librarian = Role::findOrCreate(RoleName::Librarian->value, 'web');
        $staff = Role::findOrCreate(RoleName::Staff->value, 'web');
        $mahasiswa = Role::findOrCreate(RoleName::Anggota->value, 'web');

        // Super Admin: semua permission + bypass Gate::before + halaman Log Aktivitas/keamanan.
        $superAdmin->syncPermissions(Permission::all());

        // Admin: pengelola perpustakaan penuh, TANPA bypass Gate::before dan TANPA halaman
        // Log Aktivitas/keamanan (dibatasi middleware role:Super Admin).
        $admin->syncPermissions(Permission::whereNot('name', 'pinjam buku')->get());

        $librarian->syncPermissions([
            'approve mahasiswa',
            'kelola buku',
            'kelola master',
            'lihat buku',
            'kelola peminjaman',
            'input peminjaman',
            'kelola pengembalian',
            'kelola denda',
            'lihat laporan',
            'export laporan',
        ]);

        $staff->syncPermissions([
            'lihat buku',
            'input peminjaman',
            'kelola pengembalian',
        ]);

        $mahasiswa->syncPermissions([
            'lihat buku',
            'pinjam buku',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
