# SIGE — Sistema de Gestão de Estoque

Sistema de almoxarifado com **Laravel** (backend), **Vue.js** (frontend) e **Docker**.

---

## 📁 Estrutura do projeto

```
sige-laravel-vue/
│
├── docker-compose.yml          ← Sobe tudo com um comando
├── docker/
│   ├── nginx/default.conf      ← Configuração do servidor web
│   └── mysql/init.sql          ← Script inicial do banco
│
├── backend/                    ← API Laravel (PHP)
│   ├── Dockerfile
│   ├── .env.example            ← Copie para .env e configure
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    ← Lógica de cada rota da API
│   │   │   └── Middleware/     ← Verificação de perfil de acesso
│   │   └── Models/             ← Representação das tabelas do banco
│   ├── database/
│   │   ├── migrations/         ← Criação das tabelas
│   │   └── seeders/            ← Dados iniciais (admin, categorias)
│   └── routes/api.php          ← Todas as rotas da API
│
└── frontend/                   ← Interface Vue.js
    ├── index.html
    ├── vite.config.js
    └── src/
        ├── main.js             ← Ponto de entrada do Vue
        ├── App.vue             ← Componente raiz
        ├── router.js           ← Rotas do frontend
        ├── paginas/            ← Uma pasta por tela do sistema
        ├── componentes/
        │   ├── layout/         ← Sidebar, header
        │   ├── ui/             ← Componentes genéricos (cards, modais)
        │   ├── produtos/       ← Modais e tabelas de produtos
        │   └── movimentos/     ← Modais de entrada/saída
        ├── servicos/           ← API (axios) e stores (Pinia)
        └── estilos/            ← CSS global
```

---

## 🚀 Como rodar o projeto

### Pré-requisitos

- Git instalado
- Node.js instalado
- SQL (banco de dados do sistema)

### Passo a passo

1 - Ligar o apache e mysql (xampp)

2 - Configurar o workbench(criar o banco de dados)

3 - De o comando "php artisan db:seed --class=UserSeeder" para rodar administrador

4 - Configurar o arquivo .env (Criar dentro do backend)

5 - Fazer "mkdir bootstrap/cache"

6 - Composer install dentro do backend

7 - "Php artisan serve" dentro do backend

8 - Abra outro terminal, entre na pasta frontend

9 - De o comando "npm install" e "npm install lucide-vue-next"

10 - npm run dev (frontend)

11 - php artisan serve (backend)


**5. Acesse o sistema**

| Serviço   | Endereço                  |
|-----------|---------------------------|
| Frontend  | http://localhost:3000     |
| Backend   | http://localhost:8000/api |
| Banco     | localhost:3306            |

---

## 👤 Usuários para teste

| Email                    | Senha          | Perfil       |
|--------------------------|----------------|--------------|
| admin@sige.com           | Admin@2024     | Admin        |
| operador@sige.com        | Operador@2024  | Operador     |
| visualizador@sige.com    | Visual@2024    | Visualizador |

---

## 🔐 Perfis de acesso

| Ação                          | Admin | Operador | Visualizador |
|-------------------------------|:-----:|:--------:|:------------:|
| Ver produtos/lotes/movimentos |  ✅   |    ✅    |      ✅      |
| Criar/editar produtos         |  ✅   |    ✅    |      ❌      |
| Registrar entrada/saída       |  ✅   |    ✅    |      ❌      |
| Gerenciar usuários            |  ✅   |    ❌    |      ❌      |
| Remover produtos/categorias   |  ✅   |    ❌    |      ❌      |

---

## 🌐 Rotas da API

### Públicas (sem login)
| Método | Rota         | Descrição        |
|--------|--------------|------------------|
| POST   | /api/login   | Fazer login      |

### Protegidas (precisam do token no header `Authorization: Bearer {token}`)

| Método | Rota                        | Descrição                    |
|--------|-----------------------------|------------------------------|
| POST   | /api/logout                 | Fazer logout                 |
| GET    | /api/me                     | Dados do usuário logado      |
| GET    | /api/dashboard              | Resumo geral                 |
| GET    | /api/produtos               | Listar produtos              |
| POST   | /api/produtos               | Criar produto                |
| PUT    | /api/produtos/{id}          | Editar produto               |
| DELETE | /api/produtos/{id}          | Desativar produto            |
| GET    | /api/lotes                  | Listar lotes com itens       |
| POST   | /api/lotes                  | Criar lote com itens         |
| GET    | /api/lotes/itens            | Itens disponíveis (p/ select)|
| GET    | /api/movimentos             | Histórico de movimentos      |
| POST   | /api/movimentos/entrada     | Registrar entrada            |
| POST   | /api/movimentos/saida       | Registrar saída              |
| GET    | /api/relatorios/estoque     | Relatório de estoque         |
| GET    | /api/relatorios/vencimentos | Itens vencidos/vencendo      |
| GET    | /api/relatorios/auditoria   | Log de auditoria             |
| GET    | /api/usuarios               | Listar usuários (admin)      |
| POST   | /api/usuarios               | Criar usuário (admin)        |
| PUT    | /api/usuarios/{id}          | Editar usuário (admin)       |
| DELETE | /api/usuarios/{id}          | Desativar usuário (admin)    |

---

## 🛑 Parar o projeto

```bash
docker compose down
```

Para parar **e apagar os dados do banco**:
```bash
docker compose down -v
```

---

## 🧰 Tecnologias usadas

| Camada     | Tecnologia                          |
|------------|-------------------------------------|
| Backend    | PHP 8.2 + Laravel 11                |
| Auth       | Laravel Sanctum (tokens)            |
| Frontend   | Vue.js 3 + Vite                     |
| Estado     | Pinia                               |
| Rotas      | Vue Router 4                        |
| HTTP       | Axios                               |
| Estilo     | Tailwind CSS                        |
| Banco      | MySQL 8                             |
| Servidor   | Nginx + PHP-FPM                     |
| Container  | Docker + Docker Compose             |
