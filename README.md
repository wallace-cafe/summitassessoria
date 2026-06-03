# Summit Assessoria — Dashboard de Landing Pages & Leads

Aplicação web para a **Summit Assessoria** que centraliza a publicação de
**landing pages** e a captação e gestão de **leads**. Cada landing page é
hospedada e servida pela própria aplicação, captura contatos através de um
formulário e entrega esses leads em um painel administrativo e em uma API.

Construída em **CodeIgniter 4** (PHP), com banco **SQLite** por padrão.

---

## Visão geral

O sistema tem três frentes:

1. **Site institucional (rota pública `/`)** — uma página única com o logotipo da
   empresa, vídeo de fundo e o link para o site institucional
   (`https://summitconsult.com.br`).
2. **Área administrativa (rota `/summit-admin` → `/dashboard`)** — onde a equipe
   cria/edita landing pages e acompanha os leads recebidos.
3. **Landing pages públicas (`/p/{slug}`)** — as páginas de captura propriamente
   ditas, servidas a partir dos arquivos enviados, com formulário de lead,
   integração com Google Tag Manager e redirecionamento para o WhatsApp.

Além das telas, há uma **API autenticada por token** para consumir landing pages
e leads a partir de outros sistemas.

---

## Principais funcionalidades

- **Gestão de landing pages**
  - Cadastro por **upload de arquivos** (`index.html`, `style.css`, `app.js` e uma
    pasta de `assets/` com imagens/vídeos).
  - Cada página tem um **slug único** e fica acessível em `/p/{slug}`.
  - **Edição em blocos**: a página enviada é fatiada em blocos identificáveis
    (BLOCO 1, BLOCO 2, …) que podem ser editados individualmente, com um modo de
    **pré-visualização** que exibe selos/badges sobre cada bloco.
  - **Google Tag Manager por página**: basta informar o ID (`GTM-XXXX`) e ele é
    injetado no lugar do placeholder da página no momento da renderização.
- **Captura de leads**
  - Formulário de contato em cada landing page envia o lead via AJAX para
    `/p/{slug}/lead`.
  - Após o envio, exibe mensagem de sucesso e um **botão de WhatsApp** já com a
    mensagem pré-preenchida pelo nome do contato.
- **Painel de leads**
  - Listagem com **busca** (nome/e-mail), **filtro por landing page** e
    **ordenação** (nome, e-mail, status, data).
  - Cada lead mostra a landing page de origem.
- **API REST** (autenticada por Bearer token) para listar landing pages e leads.
- **Login administrativo protegido** com CSRF, honeypot anti-bot, *rate limiting*
  contra força bruta e cabeçalhos de segurança.

---

## Como funciona

### Renderização das landing pages (`/p/{slug}`)

Quando uma landing page pública é acessada, a aplicação lê o `index.html`
armazenado e aplica uma série de injeções antes de entregar o HTML:

- substitui o placeholder do **GTM** pelo ID configurado para aquela página;
- injeta uma tag `<base>` para que os caminhos relativos de assets funcionem;
- associa o `id` da landing page ao formulário de contato;
- injeta o **handler de envio do lead** (AJAX → `/p/{slug}/lead`);
- troca o bloco de sucesso por uma mensagem + **botão de WhatsApp**.

Os arquivos de cada página ficam em `writable/landing_pages/{slug}/` e os assets
são servidos por `/p/{slug}/assets/...` (com verificação de caminho para impedir
acesso fora do diretório da página).

### Captura de leads

O formulário envia `nome`, `e-mail`, `telefone` e `mensagem`. O lead é validado
(nome e e-mail obrigatórios), persistido com status inicial `New` e vinculado à
landing page de origem (`landing_page_id`).

---

## Mapa de rotas

### Público
| Método | Rota | Descrição |
|--------|------|-----------|
| GET  | `/`                 | Site institucional (logo + vídeo + link) |
| GET  | `/p/{slug}`         | Renderiza a landing page pública |
| GET  | `/p/{slug}/assets/...` | Serve os assets da landing page |
| POST | `/p/{slug}/lead`    | Captura um lead da landing page |

### Administrativo (protegido por login)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET  | `/summit-admin`              | Tela de login |
| POST | `/summit-admin`              | Autenticação |
| POST | `/logout`                    | Sair |
| GET  | `/dashboard`                 | Painel |
| GET  | `/landing-pages`             | Lista de landing pages |
| GET/POST | `/landing-pages/create` / `/landing-pages` | Criar landing page |
| GET/POST | `/landing-pages/edit/{id}` / `/landing-pages/update/{id}` | Editar landing page |
| GET  | `/landing-pages/delete/{id}` | Remover landing page |
| GET  | `/leads`                     | Painel de leads (busca/filtro/ordenação) |

### API (Bearer token)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/lp/list`         | Lista as landing pages |
| GET | `/api/lp/leads`        | Todos os leads (com `landing_page_slug` e `landing_page_title`) |
| GET | `/api/lp/leads/{id}`   | Leads de uma landing page específica (por ID) |

Autenticação da API: cabeçalho `Authorization: Bearer <token>`, onde o token é a
`encryption.key` definida no `.env`. Sem token válido a API responde `401`.

---

## Acesso administrativo

- **Usuário:** `admin`
- **Senha:** dinâmica, **rotaciona a cada dia** (derivada da data atual, no fuso de
  Brasília). A regra exata fica em `app/Controllers/AuthController::authenticate()`.
- Não é necessário cadastrar usuário no banco — as credenciais são derivadas.

A rota de login (`/summit-admin`) é protegida por:

- **CSRF** (baseado em sessão, token randomizado);
- **Honeypot** anti-bot (campo invisível);
- **Rate limiting** contra força bruta (5 tentativas por minuto por IP, resposta
  `429` com `Retry-After`);
- **Cabeçalhos de segurança** (`X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`).

---

## Modelo de dados

- **`landing_pages`** — `id`, `title`, `slug` (único), `file_path`, `gtm_id`,
  `created_at`, `updated_at`.
- **`leads`** — `id`, `landing_page_id` (FK → `landing_pages`, `ON DELETE CASCADE`),
  `name`, `email`, `phone`, `message`, `status` (padrão `New`), `created_at`.
- **`block_templates`** — `id`, `name`, `html_template`, `created_at`,
  `updated_at` (blocos reutilizáveis para a edição).
- **`users`** — tabela legada; o login atual não a utiliza.

---

## Stack & requisitos

- **PHP** 8.2+ (com extensões `intl`, `mbstring`, `json`).
- **CodeIgniter 4** (`^4.7`).
- **Banco:** SQLite3 por padrão (`writable/database/default.db`). MySQL pode ser
  configurado via `.env` (config de exemplo já comentada em `app/Config/Database.php`).
- **Composer** para dependências.

---

## Instalação e execução

```bash
# 1. Dependências
composer install

# 2. Configuração (cria o .env a partir do template do framework)
cp vendor/codeigniter4/framework/env .env
# edite o .env: defina app.baseURL e a encryption.key (usada também como token da API)

# 3. Banco de dados (cria as tabelas)
php spark migrate

# 4. Servidor de desenvolvimento
php spark serve --port 8081
```

A aplicação ficará disponível em `http://localhost:8081` (ajuste `app.baseURL`
no `.env` conforme a porta usada).

> **Atenção (produção):** o servidor web deve apontar para a pasta **`public/`**,
> nunca para a raiz do projeto.

### Testes

```bash
composer test   # PHPUnit
```

---

## Estrutura do projeto

```
app/
├── Controllers/
│   ├── Home.php                # site institucional (rota /)
│   ├── AuthController.php       # login/logout (/summit-admin)
│   ├── DashboardController.php  # painel
│   ├── LandingPagesController.php # CRUD de landing pages
│   ├── LeadsController.php      # painel de leads
│   ├── PublicController.php     # renderização pública /p/{slug} + captura de lead
│   └── Api/LpController.php     # API de landing pages e leads
├── Filters/                    # AuthFilter, BearerTokenFilter, ThrottleFilter
├── Models/                     # LandingPageModel, LeadModel, UserModel
├── Helpers/block_editor_helper.php  # fatiamento/edição em blocos
├── Views/                      # layouts, auth, dashboard, landing_pages, leads, home
└── Database/Migrations/        # esquema do banco

public/
└── assets/                     # css, js, img (logo) e video (fundo do site)

writable/
├── database/                   # SQLite (não versionado)
└── landing_pages/{slug}/       # arquivos das landing pages (não versionado)
```

> As pastas `writable/database` e `writable/landing_pages` guardam dados de
> runtime e **não são versionadas** — a aplicação recria os diretórios conforme o
> uso (a primeira landing page cadastrada recria `writable/landing_pages`).
