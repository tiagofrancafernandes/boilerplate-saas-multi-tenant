# Plano: Arquitetura de Filas Assíncronas Serverless (QStash & Local Bypass)

**Código:** `010-01-serverless-async-queues-infra-v1`
**Prioridade:** Alta
**Status:** 📋 Planejado (Aguardando Aprovação de Execução)
**Data de Criação:** 2026-09-19
**Autor:** Antigravity / Tiago França
**Plano Relacionado:** Módulo de Infraestrutura & Execução Serverless

---

## 1. Visão Geral e Contexto

Em ambientes **Serverless** (como Vercel, AWS Lambda ou Bref), a execução do comando contínuo `queue:work` (worker daemon com polling persistente no Redis) não é viável devido ao ciclo de vida efêmero e timeout máximo de execução das requisições.

Este plano introduz um padrão de **Filas Baseadas em Push (Push-based Webhooks)** plugável e agnóstico:
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
- **Segurança Criptográfica:** Validação da assinatura do QStash (`upstash-signature`) via `Upstash\QStash\Receiver` ou header interno seguro com segredo em ambiente de teste/local.
- **Isolamento de Tenants:** Schema isolation no PostgreSQL preservado via `stancl/tenancy`.

---

## 3. Escopo Detalhado de Implementação

### A. Dependências e Configuração
- [ ] Instalar o pacote oficial do QStash:
  ```bash
  cd apps/api && composer require upstash/qstash
  ```
- [ ] Criar o Backed Enum `App\Enums\QueueDriver`:
  - `LOCAL = 'local'` (bypass síncrono via Kernel HTTP)
  - `QSTASH = 'qstash'` (push webhook via Upstash)
  - `NONE = 'none'` (desativa despacho de filas)
- [ ] Criar o arquivo de configuração `apps/api/config/webhook_queue.php`:
  - Chaves: `driver`, `qstash_token`, `qstash_current_key`, `qstash_next_key`, `internal_secret`.
  - Resolução automática:
    - Se `app()->environment('testing')` => força `QueueDriver::LOCAL`.
    - Se `VERCEL=1` ou `IS_SERVERLESS=true` e token presente => padrão `QueueDriver::QSTASH`.
    - Caso contrário => `QueueDriver::LOCAL`.

### B. O Despachante Adapter (`App\Services\Infra\WebhookDispatcher`)
- [ ] Criar a classe de serviço `WebhookDispatcher`:
  - Método `public static function dispatch(string $actionClass, array $payload = [], ?string $tenantId = null): void`
  - Encapsular payload padrão:
    ```json
    {
      "action": "App\\Jobs\\SampleJob",
      "tenant_id": "tenant_uuid_or_null",
      "payload": { ... },
      "dispatched_at": "2026-09-19T12:00:00Z"
    }
    ```
  - Se driver for `NONE` => early return void.
  - Se driver for `LOCAL` => cria `Request::create('/api/infra/queue/process-task', 'POST', ...)` injetando header `x-internal-webhook-bypass` e delega para `app(Kernel::class)->handle($request)`.
  - Se driver for `QSTASH` => envia requisição via `Http::withToken(...)` para a API do QStash (`https://qstash.upstash.io/v2/publish/...`).

### C. O Controlador Receptor do Webhook (`App\Http\Controllers\Infra\QueueWebhookController`)
- [ ] Criar `App\Http\Controllers\Infra\QueueWebhookController`:
  - Validação da Assinatura:
    - Se driver for `LOCAL` ou em `testing` => valida header `x-internal-webhook-bypass` contra token interno.
    - Se driver for `QSTASH` => instancia `Upstash\QStash\Receiver` e valida cabeçalho `upstash-signature` contra o corpo da requisição bruta.
    - Em caso de falha de validação => retorna HTTP 401 Unauthorized imediato.
  - Execução Multi-Tenant Segura:
    - Se `tenant_id` estiver preenchido => executa `tenancy()->initialize($tenantId)`.
    - Instancia e executa a ação/job solicitada (ou despacha no barramento do Laravel).
    - Em bloco `finally` => garante chamada a `tenancy()->end()`.
  - Retorna HTTP 200 OK com payload estruturado:
    ```json
    {
      "status": "success",
      "message": "Task processed successfully",
      "action": "App\\Jobs\\SampleJob",
      "tenant_id": "tenant_uuid_or_null"
    }
    ```

### D. Registro de Rotas de Infraestrutura
- [ ] Registrar o webhook em [`apps/api/routes/infra.php`](../../apps/api/routes/infra.php):
  ```php
  Route::post('/queue/process-task', QueueWebhookController::class)
      ->name('infra.queue.process_task');
  ```
- [ ] Gerar arquivo de requisição demonstrativa:
  - `backend/dev-contents/demo-requests/infra-queue-process-task-demo.http`.

---

## 4. Plano de Testes Automatizados (Happy Path & Sad Path)

Criar suite de testes em `apps/api/tests/Feature/WebhookQueueTest.php`:

1. **Happy Path (Local Kernel Bypass):**
   - Disparo de job com driver `LOCAL`.
   - Assert: requisição interna tratada com sucesso sem chamada HTTP externa de rede, retornando 200 OK e executando a tarefa.
2. **Happy Path (Multi-Tenant Execution):**
   - Disparo de tarefa especificando um `tenant_id` existente.
   - Assert: a execução ocorre com o schema do tenant ativo e encerra tenancy de forma segura.
3. **Happy Path (QStash Dispatch Mock):**
   - Configuração simulada com driver `QSTASH`.
   - Utilizar `Http::fake()`.
   - Assert: chamada externa enviada à URL correta do QStash com o token e corpo adequados.
4. **Sad Path (Local Bypass Unauthorized):**
   - Requisição direta a `POST /api/infra/queue/process-task` sem o header `x-internal-webhook-bypass`.
   - Assert: retorna status HTTP 401 Unauthorized.
5. **Sad Path (QStash Invalid Signature):**
   - Configuração com driver `QSTASH`.
   - Requisição com cabeçalho `upstash-signature` inválido ou payload corrompido.
   - Assert: interceptado pelo `Receiver` e retorna status HTTP 401 Unauthorized.
6. **Sad Path (Invalid / Non-existent Tenant):**
   - Requisição informando `tenant_id` inexistente.
   - Assert: tratamento adequado de exceção sem vazar dados entre schemas e sem deixar conexões pendentes.

---

## 5. Critérios de Aceitação

- [ ] Todas as novas classes seguem estritamente `declare(strict_types=1);`, métodos estáticos / final classes e sem uso de `else`.
- [ ] 100% dos testes da nova suíte passando (`pnpm test:php --filter=WebhookQueueTest`).
- [ ] 100% da suíte de regressão do monorepo passando (`pnpm test:php` e `pnpm test`).
- [ ] Formatação validada com Pint (`pnpm lint:php`).
- [ ] Rota criada no prefixo `/api/infra` com arquivo `.http` de demonstração correspondente.
