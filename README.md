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
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado
- Git instalado

### Passo a passo

**1. Clone o projeto**
```bash
git clone <url-do-repositorio>
cd sige-laravel-vue
```

**2. Configure o arquivo .env do backend**
```bash
cp backend/.env.example backend/.env
```

**3. Suba todos os serviços com Docker**
```bash
docker compose up -d
```
> Aguarde ~2 minutos para o banco e o PHP iniciarem completamente.

**4. Instale as dependências PHP e configure o Laravel**
```bash
# Acessa o container do backend
docker exec -it sige_backend bash

# Dentro do container, rode:
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
exit
```

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
