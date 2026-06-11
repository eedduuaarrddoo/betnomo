# BETNOMO - Guia de Contexto do Projeto

Este arquivo serve como manual e guia de contexto para assistentes de Inteligência Artificial trabalhando no repositório.

## 1. Visão Geral do Projeto
A **BETNOMO** é uma plataforma de bolões dividida em classes (A, B e C), onde usuários adquirem fichas através de pagamentos via PIX e as inserem nos bolões de classe equivalente. Cada rodada de bolão garante pelo menos um vencedor aleatório que recebe todas as fichas acumuladas naquele bolão.

O repositório está dividido em duas partes principais:
*   `/bolaoapi`: Backend estruturado como API em Laravel.
*   `/betnomoFRONT`: Frontend em Vue 3 + Vite + TypeScript.

---

## 2. Pilha Tecnológica (Stack)

### Backend (`bolaoapi`)
*   **Laravel 8** (PHP >= 7.3)
*   **JWT Authentication** (`tymon/jwt-auth: ^1.0`) para autenticação de APIs.
*   **Simple QR Code** (`simplesoftwareio/simple-qrcode: ^4.2`) para geração gráfica de payloads de PIX.
*   **Guzzle / Laravel HTTP** para integração com serviços externos (PagSeguro).

### Frontend (`betnomoFRONT`)
*   **Vue 3** (Composition API, `<script setup lang="ts">`)
*   **Vite** (Build Tool)
*   **TypeScript**
*   **Pinia** (Gerenciamento de Estado Global)
*   **Vue Router** (Roteamento de Páginas)
*   **Tailwind CSS v3** (Estilização Utilitária)

### 2.1 Mapeamento de Bibliotecas

| Nome da Biblioteca | Propósito | Onde é utilizada no projeto (Arquivo / Link) |
| :--- | :--- | :--- |
| **`tymon/jwt-auth`** | Autenticação e emissão de tokens JWT para a API. | <ul><li>[routes/api.php](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/bolaoapi/routes/api.php#L19)</li><li>[app/Models/User.php](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/bolaoapi/app/Models/User.php#L6)</li><li>[app/Http/Controllers/AuthController.php](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/bolaoapi/app/Http/Controllers/AuthController.php)</li></ul> |
| **`simplesoftwareio/simple-qrcode`** | Geração do QR Code gráfico para o pagamento PIX estático. | <ul><li>[app/Services/FichaService.php](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/bolaoapi/app/Services/FichaService.php#L7)</li><li>[app/Http/Controllers/FichaController.php](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/bolaoapi/app/Http/Controllers/FichaController.php#L48)</li></ul> |
| **`guzzlehttp/guzzle`** *(Laravel HTTP Client)* | Comunicação via requisições HTTP externas com a API de cobranças Pix do PagSeguro. | <ul><li>[app/Services/Pagseguroservice.php](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/bolaoapi/app/Services/Pagseguroservice.php#L5)</li></ul> |
| **`pinia`** | Gerenciamento de estado global (dados do usuário ativo, tokens de sessão e status de carregamento). | <ul><li>[src/stores/auth.ts](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/betnomoFRONT/src/stores/auth.ts#L1)</li><li>[src/router/index.ts](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/betnomoFRONT/src/router/index.ts#L3)</li><li>[src/views/UserHomeView.vue](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/betnomoFRONT/src/views/UserHomeView.vue#L3)</li></ul> |
| **`vue-router`** | Gerenciador do roteamento Single Page Application (SPA) e guardas de rotas restritas. | <ul><li>[src/router/index.ts](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/betnomoFRONT/src/router/index.ts#L1)</li></ul> |
| **`vue`** | Biblioteca/Framework base de construção de interfaces de componentes reativos. | <ul><li>Todos os componentes e telas (ex: [src/views/UserHomeView.vue](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/betnomoFRONT/src/views/UserHomeView.vue))</li></ul> |
| **`tailwindcss`** / **`autoprefixer`** | Estilização utilitária visual dos elementos da interface da BETNOMO. | <ul><li>`tailwind.config.js`</li><li>`postcss.config.js`</li></ul> |

---

## 3. Estrutura de Diretórios e Arquivos Chave

### Backend (`/bolaoapi`)
*   **Rotas**:
    *   `routes/api.php`: Define endpoints públicos (`/login`, `/register`, `/verify-email/{token}`, `/pagamentos/webhook`) e autenticados via JWT (`/boloes`, `/fichas/*`, `/admin/*`).
*   **Controladores** (`app/Http/Controllers/`):
    *   `AuthController.php`: Registro, login, verificação de e-mail e logout de usuários.
    *   `BolaoController.php`: Listagem de bolões, participação e lógica de sorteio (admin).
    *   `FichaController.php`: Listagem de fichas do usuário, geração de PIX estático e confirmação manual.
*   **Serviços** (`app/Services/`):
    *   `FichaService.php`: Encapsula a lógica de geração de tokens únicos de fichas, validação e formatação de payload de PIX estático BR.GOV.BCB.PIX.
    *   `PagSeguroService.php`: Comunicação com a API de cobranças Pix do PagSeguro (sandbox/produção) e validação de assinaturas HMAC-SHA256 em webhooks.
*   **Modelos** (`app/Models/`):
    *   `User.php`: Usuário (com flag `is_admin` e chaves Pix).
    *   `Ficha.php`: Ficha adquirida (vinculada a um usuário, possui `tipo`, `valor`, `token` único e indicador de uso `usada`).
    *   `Bolao.php`: Registro dos bolões (armazena JSON de participantes e fichas inseridas, além de guardar o vencedor e se já foi sorteado).
    *   `Pagamento.php` (*Inconsistência*: Contém duplicata do código do PagSeguroService em vez de um modelo Eloquent).
*   **Middlewares** (`app/Http/Middleware/`):
    *   `AdminMiddleware.php`: Filtra acesso a endpoints sob o prefixo `/admin` checando a flag `is_admin` do usuário autenticado.

### Frontend (`/betnomoFRONT`)
*   **Páginas** (`src/views/`):
    *   `HomeView.vue`: Landing page institucional.
    *   `UserHomeView.vue`: Painel principal do jogador. Permite visualizar saldo de fichas por classe, bolões ativos e acessar o modal de compra.
    *   `AdminHomeView.vue`: Painel de administração para criação e sorteio de bolões.
    *   `VerifyEmailPage.vue`: Tela de verificação de token de e-mail.
*   **Componentes** (`src/components/`):
    *   `ComprarFichaModal.vue`: Modal de seleção do tipo de ficha, exibição do QR Code do PIX e botão de confirmação manual.
    *   `BolaoCard.vue`: Card visual de exibição e interação com um bolão.
*   **Estado & Rotas**:
    *   `src/stores/auth.ts`: Gerencia token JWT, dados do usuário ativo, processos de login/logout e verificação de e-mail.
    *   `src/router/index.ts`: Define rotas e guardas de navegação (`requiresAuth` e `requiresAdmin`).

---

## 4. Fluxos de Negócio Detalhados

### Fluxo de Aquisição de Fichas (PIX Estático)
1. Jogador seleciona a ficha (Classe A = R$50, B = R$25, C = R$5) no frontend.
2. O frontend requisita `/api/fichas/gerar-qr`. O backend gera um payload de Pix estático com um UUID de referência.
3. O frontend exibe o QR Code e o código "Copia e Cola".
4. Após o pagamento, o usuário clica em "Já paguei", acionando `/api/fichas/confirmar`. A ficha é gerada na tabela `fichas` e vinculada ao usuário com um token único.

### Fluxo de Participação em Bolão
1. O usuário entra no dashboard e seleciona a categoria de bolão desejada.
2. O usuário escolhe um bolão aberto e insere o token de uma de suas fichas da classe equivalente.
3. O backend valida se a ficha existe, pertence ao usuário, não foi usada e possui a mesma classe do bolão.
4. Sendo válida, a ficha é marcada como `usada` e o ID do usuário e da ficha são adicionados ao array JSON do bolão.

### Fluxo de Sorteio
1. O administrador acessa o painel admin e aciona o botão de sortear em um bolão com pelo menos 2 participantes.
2. O backend seleciona aleatoriamente um dos IDs do array de participantes.
3. O vencedor assume a propriedade de todas as fichas vinculadas àquele bolão (o campo `user_id` das fichas correspondentes na tabela é alterado para o ID do vencedor).
4. O bolão é marcado como sorteado (`sorteado = true`) e o vencedor é registrado em `vencedor_id`.

---

## 5. Inconsistências Conhecidas (Pendentes de Correção)
*   **Pagamento.php**: O arquivo localizado em `bolaoapi/app/Models/Pagamento.php` contém o código do `PagSeguroService` e o namespace `App\Services`. O model Eloquent real de `Pagamento` para gerenciar a tabela `pagamentos` no banco de dados está ausente.
*   **PagamentoController**: As rotas `/api/fichas/iniciar-compra`, `/api/fichas/compra-status/{referencia}` e `/api/pagamentos/webhook` estão declaradas no [api.php](file:///c:/Users/Eduardo%20Afonso/OneDrive/Documentos/LaravelTest/bolaoapi/routes/api.php), mas o controlador `PagamentoController.php` correspondente não existe em `bolaoapi/app/Http/Controllers/`.

## OBSERVAÇÃO SEMPRE QUE PRECISAR ARQUIVOS DE TESTE CRIAR NA PASTA DE TESTE NA RAIZ DO PROJETO pasta "testes" sendo a pasta principal e dentro dela teste front e back em pastas separadas, por exemplo: testes/test front e testes/test back. 