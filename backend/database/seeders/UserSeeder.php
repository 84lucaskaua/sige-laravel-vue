<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Admin',        'email' => 'admin@sige.com',        'password' => 'Admin@2024',    'perfil' => 'admin',        'pin' => '7409'],
            ['name' => 'Amanda',       'email' => 'amanda@sige.com',       'password' => 'Operador@2024', 'perfil' => 'operador',     'pin' => '8401'],
            ['name' => 'Daniela',      'email' => 'daniela@sige.com',      'password' => 'Operador@2024', 'perfil' => 'operador',     'pin' => '1974'],
            ['name' => 'Visualizador', 'email' => 'visualizador@sige.com', 'password' => 'Visual@2024',   'perfil' => 'visualizador', 'pin' => null],
            ['name' => 'Angel',        'email' => 'angel@sige.com',        'password' => 'Angel@2024',    'perfil' => 'admin',        'pin' => '3256'],
            ['name' => 'Natan',        'email' => 'natan@sige.com',        'password' => 'Natan@2024',    'perfil' => 'admin',        'pin' => '6183'],
            ['name' => 'Miguel',       'email' => 'miguel@sige.com',       'password' => 'Operador@2024', 'perfil' => 'operador',     'pin' => '6935'],
        ];

        foreach ($usuarios as $u) {
            DB::table('users')->updateOrInsert(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($u['password']),
                    'perfil' => $u['perfil'],
                    'pin' => $u['pin'] ? Hash::make($u['pin']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}