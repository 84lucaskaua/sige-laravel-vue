<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder: Dados Iniciais do SIGE
 *
 * Popula o banco com os dados mínimos para o sistema funcionar.
 * Rode com: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->criarUsuarioAdmin();
        $this->criarCategoriasIniciais();

        $this->command->info('✅ Banco de dados populado com sucesso!');
        $this->command->info('');
        $this->command->info('📧 Login admin: admin@sige.com');
        $this->command->info('🔑 Senha admin: Admin@2024');
    }

    /**
     * Cria o usuário administrador padrão
     */
    private function criarUsuarioAdmin(): void
    {
        // Verifica se o admin já existe antes de criar
        if (Usuario::where('email', 'admin@sige.com')->exists()) {
            $this->command->warn('Admin já existe, pulando criação.');
            return;
        }

        Usuario::create([
            'nome'   => 'Administrador',
            'email'  => 'admin@sige.com',
            'senha'  => Hash::make('Admin@2024'),
            'perfil' => 'admin',
            'ativo'  => true,
        ]);

        // Também cria um operador e um visualizador para testes
        Usuario::create([
            'nome'   => 'Operador Teste',
            'email'  => 'operador@sige.com',
            'senha'  => Hash::make('Operador@2024'),
            'perfil' => 'operador',
            'ativo'  => true,
        ]);

        Usuario::create([
            'nome'   => 'Visualizador Teste',
            'email'  => 'visualizador@sige.com',
            'senha'  => Hash::make('Visual@2024'),
            'perfil' => 'visualizador',
            'ativo'  => true,
        ]);
    }

    /**
     * Cria as categorias padrão para um almoxarifado de saúde/gastronomia
     */
    private function criarCategoriasIniciais(): void
    {
        $categorias = [
            ['nome' => 'Material de Limpeza',    'cor' => '#3B82F6'],
            ['nome' => 'Alimentos não perecíveis','cor' => '#F59E0B'],
            ['nome' => 'Material de Escritório', 'cor' => '#8B5CF6'],
            ['nome' => 'EPI',                    'cor' => '#EF4444'],
            ['nome' => 'Utensílios de Cozinha',  'cor' => '#10B981'],
            ['nome' => 'Descartáveis',           'cor' => '#F97316'],
            ['nome' => 'Produtos de Higiene',    'cor' => '#06B6D4'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(
                ['nome' => $categoria['nome']],
                ['cor'  => $categoria['cor']]
            );
        }
    }
}
