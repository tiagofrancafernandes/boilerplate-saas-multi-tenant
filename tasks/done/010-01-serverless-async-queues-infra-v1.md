# Plano: Arquitetura de Filas Assíncronas Serverless (QStash & Local Bypass)

**Código:** `010-01-serverless-async-queues-infra-v1`
**Prioridade:** Alta
**Status:** ✅ Concluído
**Iniciado em:** 2026-09-19 às 12:16
**Concluído em:** 2026-09-19 às 12:20
**Tempo Total:** ~4 minutos
**Responsável:** Antigravity
**Plano Relacionado:** Módulo de Infraestrutura & Execução Serverless

---

## 1. Visão Geral e Contexto

Em ambientes **Serverless** (como Vercel, AWS Lambda ou Bref), a execução do comando contínuo `queue:work` (worker daemon com polling persistente no Redis) não é viável devido ao ciclo de vida efêmero e timeout máximo de execução das requisições.

Este plano implementou um padrão de **Filas Baseadas em Push (Push-based Webhooks)** plugável e agnóstico:
1. **Em Produção Serverless (Vercel):** Dispara mensagens para o **Upstash QStash**, que orquestra a entrega pontual chamando um webhook seguro (`POST /api/infra/queue/process-task`) assinado criptograficamente.
2. **Em Desenvolvimento Local e Testes Automatizados:** Utiliza um **Bypass Interno Síncrono** através do `Illuminate\Contracts\Http\Kernel` do Laravel com cabeçalho de autenticação interna (`x-internal-webhook-bypass`), permitindo testes 100% offline, livres de rede externa, instantâneos e sem custos.
3. **Isolamento Multi-Tenant Garantido (`stancl/tenancy`):** O payload transporta explicitamente o `tenant_id`, garantindo que o webhook receptor inicialize o schema correto no PostgreSQL (`tenancy()->initialize()`) antes de rodar o Job, finalizando com `tenancy()->end()`.
4. **Alocação Padronizada no Prefixo `infra`:** O endpoint receptor do webhook fica centralizado dentro de [`apps/api/routes/infra.php`](../../apps/api/routes/infra.php), isolado de rotas públicas, de tenant ou de super admin.

---

## 2. Padrões de Código e Diretrizes Arquiteturais (Obrigatórios)

- **Idioma:** 100% em inglês (classes, métodos, variáveis, DTOs e comentários).
- **Tipagem Estrita:** `declare(strict_types=1);` no topo de todos os arquivos PHP. Tipagem explícita para parâmetros e retornos.
- **Domain Services:** Classes `final class` com métodos estáticos, protegidas com `if (!class_exists(...))`.
- **Early Return & Elseless:** Proibido uso de `else` ou `elseif`. Aninhamento máximo de 1 nível de `if`.
- **Static Binding:** Proibido `self::`, utilizar `static::`.
- **Segurança Criptográfica:** Validação de assinatura JWT compatível com QStash (`Upstash-Signature`) com fallback para chave atual e próxima, além de validação segura por token no bypass local.
- **Isolamento de Tenants:** Schema isolation no PostgreSQL preservado via `stancl/tenancy`.

---

## 3. Escopo Detalhado de Implementação

### A. Dependências e Configuração
- [x] Criar o Backed Enum `App\Enums\QueueDriver`:
  - `LOCAL = 'local'` (bypass síncrono via Kernel HTTP)
  - `QSTASH = 'qstash'` (push webhook via Upstash)
  - `REDIS = 'redis'` (daemon worker tradicional)
  - `NONE = 'none'` (desativa despacho de filas)
- [x] Criar o serviço `App\Services\Infra\QStashSignatureVerifier` para verificação e geração de JWTs do QStash nativo em PHP com HMAC-SHA256, sem dependências instáveis de terceiros.
- [x] Criar o arquivo de configuração `apps/api/config/webhook_queue.php`:
  - Chaves: `driver`, `qstash_token`, `qstash_current_key`, `qstash_next_key`, `internal_secret`, `header_bypass_name`, `qstash_publish_url`, `endpoint_url`.
  - Resolução automática:
    - Se `app()->environment('testing')` => força `QueueDriver::LOCAL`.
    - Se `VERCEL=1` ou `IS_SERVERLESS=true` => padrão `QueueDriver::QSTASH`.
    - Caso contrário => `QueueDriver::LOCAL`.

### B. O Despachante Adapter (`App\Services\Infra\WebhookDispatcher`)
- [x] Criar a classe de serviço `WebhookDispatcher`:
  - Método `public static function dispatch(string $actionClass, array $payload = [], ?string $tenantId = null): array`
  - Encapsular payload padrão:
    ```json
    {
      "action": "App\\Jobs\\SampleJob",
      "tenant_id": "tenant_uuid_or_null",
      "payload": { ... },
      "dispatched_at": "2026-09-19T12:00:00Z"
    }
    ```
  - Se driver for `NONE` => early return com status `skipped`.
  - Se driver for `LOCAL` => cria `Request::create('/api/infra/queue/process-task', 'POST', ...)` injetando header `x-internal-webhook-bypass` e cabeçalhos JSON, delegando para `app(Kernel::class)->handle($request)`.
  - Se driver for `QSTASH` => envia requisição via `Http::withToken(...)` para a API do QStash (`https://qstash.upstash.io/v2/publish/...`).

### C. O Controlador Receptor do Webhook (`App\Http\Controllers\Infra\QueueWebhookController`)
- [x] Criar `App\Http\Controllers\Infra\QueueWebhookController`:
  - Validação da Assinatura:
    - Se driver for `LOCAL` ou em `testing` => valida header `x-internal-webhook-bypass` contra token interno via `hash_equals`.
    - Se driver for `QSTASH` => valida cabeçalho `upstash-signature` contra o corpo da requisição bruta via `QStashSignatureVerifier`.
    - Em caso de falha de validação => retorna HTTP 401 Unauthorized imediato.
  - Execução Multi-Tenant Segura:
    - Se `tenant_id` estiver preenchido => busca o Tenant e executa `tenancy()->initialize($tenant)`. Se o tenant não existir, retorna 404.
    - Executa a ação solicitada (método estático `execute()`, `dispatch()` ou instância com `handle()`).
    - Em bloco `finally` => garante chamada a `tenancy()->end()`.
  - Retorna HTTP 200 OK com payload estruturado:
    ```json
    {
      "status": "success",
      "message": "Task processed successfully",
      "action": "App\\Jobs\\SampleJob",
      "tenant_id": "tenant_uuid_or_null",
      "result": { ... }
    }
    ```

### D. Registro de Rotas de Infraestrutura
- [x] Registrar o webhook em [`apps/api/routes/infra.php`](../../apps/api/routes/infra.php):
  ```php
  Route::post('/queue/process-task', QueueWebhookController::class)
      ->name('infra.queue.process_task');
  ```
- [x] Gerar arquivo de requisição demonstrativa:
  - `backend/dev-contents/demo-requests/infra-async-queue-demo.http`.
  - Atualizado `backend/dev-contents/demo-requests/infra-demo.http`.

---

## 4. Plano de Testes Automatizados (Happy Path & Sad Path)

Suíte criada em `apps/api/tests/Feature/WebhookQueueTest.php` com 12 testes completos:

1. [x] **Happy Path (Local Kernel Bypass):** Disparo via `WebhookDispatcher::dispatch` executa internamente sem tocar rede externa e retorna 200 OK.
2. [x] **Happy Path (Multi-Tenant Execution):** Disparo especificando `tenant_id` inicializa tenancy e encerra com segurança após execução.
3. [x] **Happy Path (QStash Dispatch Mock):** Mock do `Http::fake()` e validação da chamada POST com payload para URL do QStash.
4. [x] **Happy Path (Skip None Driver):** Disparo com driver `none` ignora sem falhas.
5. [x] **Happy Path (QStash Signature Verification):** Validação de assinatura gerada pelo `QStashSignatureVerifier` retorna 200 OK.
6. [x] **Sad Path (Local Bypass Unauthorized):** Requisição sem o header `x-internal-webhook-bypass` retorna HTTP 401.
7. [x] **Sad Path (Local Bypass Invalid Secret):** Requisição com secret errado retorna HTTP 401.
8. [x] **Sad Path (Missing Action Parameter):** Requisição sem o parâmetro `action` retorna HTTP 422.
9. [x] **Sad Path (QStash Invalid Signature):** Assinatura JWT forjada ou inválida retorna HTTP 401.
10. [x] **Sad Path (Invalid / Non-existent Tenant):** `tenant_id` inexistente retorna HTTP 404 e encerra tenancy com segurança.

---

## 5. Critérios de Aceitação

- [x] Todas as novas classes seguem estritamente `declare(strict_types=1);`, métodos estáticos / final classes e sem uso de `else`.
- [x] 100% dos testes da nova suíte passando (`pnpm test:php --filter=WebhookQueueTest`).
- [x] 100% da suíte de regressão do monorepo passando (119 testes PHP + 33 testes Vitest = 152 testes).
- [x] Formatação validada com Pint (`pnpm lint:php`).
- [x] Formatação validada com Prettier (`pnpm format`).
- [x] Build do frontend validado (`pnpm build`).
- [x] Rota criada no prefixo `/api/infra` com arquivos `.http` de demonstração correspondentes.

---

## 6. Resultado Final
Todos os requisitos foram atendidos e verificados. A aplicação agora suporta processamento de filas assíncronas de modo transparente tanto para deploy Serverless na Vercel (via QStash) quanto em desenvolvimento local e testes rápidos 100% offline (via HTTP Kernel Bypass), mantendo a garantia do isolamento multi-tenant do PostgreSQL.
