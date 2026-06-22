# Wallet Transfers — Documentação da API

API REST para transferências financeiras entre usuários e lojistas, construída com **Hyperf 3.x + Swoole** seguindo arquitetura **DDD Light (Monólito Modular)**.

---

## Índice

- [Stack](#stack)
- [Arquitetura](#arquitetura)
- [Como rodar](#como-rodar)
- [Endpoints](#endpoints)
  - [POST /transfer](#post-transfer)
  - [GET /transfer/{id}](#get-transferid)
  - [GET /users/{id}/transfers](#get-usersidtransfers)
  - [GET /users/{id}/wallet](#get-usersidwallet)
  - [GET /health](#get-health)
- [Regras de negócio](#regras-de-negócio)
- [Concorrência e Idempotência](#concorrência-e-idempotência)
- [Cache](#cache)
- [Mapa de classes](#mapa-de-classes)
- [CLI Commands](#cli-commands)
- [Testes](#testes)

---

## Stack

| Componente | Tecnologia |
|---|---|
| Runtime | PHP 8.3 + Swoole |
| Framework | Hyperf 3.x |
| Banco de dados | MySQL 8 |
| Cache / Lock | Redis 7 |
| Fila / Notificações | RabbitMQ 3.12 |
| Containerização | Docker Compose |
| Testes | PHPUnit 10 + Mockery |
| Análise estática | PHPStan nível 8 |
| Padrão de código | ECS / PSR-12 |

---

## Arquitetura

```
app/
├── Domain/                     # Regras de negócio puras — sem dependências externas
│   ├── User/
│   │   ├── Entities/           User.php, Wallet.php
│   │   ├── Enums/              UserType.php (common | merchant)
│   │   └── Contracts/         UserRepositoryInterface.php, WalletRepositoryInterface.php
│   ├── Transfer/
│   │   ├── Entities/           Transfer.php
│   │   ├── Enums/              TransferStatus.php (pending | authorized | completed | failed)
│   │   ├── Events/             TransferCreated.php
│   │   └── Contracts/         TransferRepositoryInterface.php
│   └── Shared/Exceptions/     InsufficientBalanceException, UnauthorizedTransferException,
│                               TransferNotAuthorizedException
│
├── Application/                # Orquestração dos casos de uso
│   ├── UseCases/               CreateTransferUseCase.php
│   ├── Services/               AuthorizerService.php, NotificationService.php
│   └── DTOs/                   TransferDTO.php, TransferResultDTO.php
│
├── Infrastructure/             # Implementações concretas (DB, Redis, HTTP, Fila)
│   ├── Repositories/           UserRepository.php, WalletRepository.php, TransferRepository.php
│   ├── Persistence/Models/     UserModel.php, WalletModel.php, TransferModel.php
│   ├── Cache/                  WalletBalanceCache.php
│   ├── Http/                   AuthorizerClient.php, NotifierClient.php
│   ├── Queue/                  TransferNotificationProducer.php, TransferNotificationConsumer.php
│   └── Listeners/              InvalidateWalletCacheListener.php
│
└── Interfaces/                 # Entradas do sistema (HTTP + CLI)
    ├── Http/
    │   ├── Controllers/        TransferController.php, UserController.php,
    │   │                       HealthController.php, DocsController.php
    │   └── Middleware/         IdempotencyMiddleware.php
    └── Console/                SeedUsersCommand.php, SimulateTransferCommand.php,
                                WalletBalanceCommand.php, TransferListCommand.php
```

---

## Como rodar

```bash
# 1. Copiar variáveis de ambiente
cp .env.example .env

# 2. Subir todos os serviços
docker compose up --build -d

# 3. Rodar as migrations
docker compose exec app php bin/hyperf.php migrate

# 4. Criar usuários de teste
docker compose exec app php bin/hyperf.php seed:users --common=5 --merchant=2 --balance=1000

# API disponível em:
# http://localhost:9501
# Swagger UI: http://localhost:9501/docs
```

---

## Endpoints

### `POST /transfer`

Realiza uma transferência financeira entre dois usuários.

**Classe:** `App\Interfaces\Http\Controllers\TransferController::store()`
**Use Case:** `App\Application\UseCases\CreateTransferUseCase::execute()`

#### Headers

| Header | Obrigatório | Descrição |
|---|---|---|
| `Content-Type` | sim | `application/json` |
| `X-Idempotency-Key` | não | UUID único para evitar reprocessamento em retries |

#### Body

```json
{
  "value": 150.00,
  "payer": 1,
  "payee": 2
}
```

| Campo | Tipo | Regra |
|---|---|---|
| `value` | `float` | Obrigatório, mínimo `0.01` |
| `payer` | `integer` | Obrigatório, deve ser usuário `common` |
| `payee` | `integer` | Obrigatório, diferente de `payer` |

#### Respostas

**`201 Created`** — Transferência concluída

```json
{
  "message": "Transfer completed successfully.",
  "data": {
    "id": 42,
    "payer_id": 1,
    "payee_id": 2,
    "amount": 150.00,
    "status": "completed",
    "created_at": "2024-06-22 10:30:00"
  }
}
```

**`403 Forbidden`** — Pagador é lojista

```json
{
  "message": "User type \"merchant\" is not allowed to send transfers."
}
```

**`409 Conflict`** — Transferência em andamento para o mesmo pagador (lock distribuído ativo)

```json
{
  "message": "Another transfer is already in progress for this payer. Please try again."
}
```

**`422 Unprocessable Entity`** — Validação falhou, saldo insuficiente ou serviço externo negou

```json
{
  "message": "Validation failed.",
  "errors": {
    "value": ["The value field must be at least 0.01."],
    "payer": ["The payer field is required."]
  }
}
```

```json
{
  "message": "Insufficient balance. Available: 50.00, Requested: 150.00"
}
```

```json
{
  "message": "Transfer was not authorized by the external service."
}
```

---

### `GET /transfer/{id}`

Consulta os detalhes de uma transferência pelo ID.

**Classe:** `App\Interfaces\Http\Controllers\TransferController::show()`

#### Parâmetros

| Param | Tipo | Descrição |
|---|---|---|
| `id` | `integer` | ID da transferência |

#### Respostas

**`200 OK`**

```json
{
  "data": {
    "id": 42,
    "payer_id": 1,
    "payee_id": 2,
    "amount": 150.00,
    "status": "completed",
    "created_at": "2024-06-22 10:30:00"
  }
}
```

**`404 Not Found`**

```json
{
  "message": "Transfer not found."
}
```

---

### `GET /users/{id}/transfers`

Lista o histórico paginado de transferências do usuário (como pagador ou recebedor).

**Classe:** `App\Interfaces\Http\Controllers\UserController::transfers()`

#### Parâmetros

| Param | Onde | Tipo | Descrição |
|---|---|---|---|
| `id` | path | `integer` | ID do usuário |
| `page` | query | `integer` | Página (default: `1`) |

#### Resposta `200 OK`

```json
{
  "data": [
    {
      "id": 42,
      "payer_id": 1,
      "payee_id": 2,
      "amount": 150.00,
      "status": "completed",
      "created_at": "2024-06-22 10:30:00"
    }
  ],
  "meta": {
    "page": 1
  }
}
```

---

### `GET /users/{id}/wallet`

Retorna o saldo atual da carteira do usuário. Lê do cache Redis antes de consultar o banco.

**Classe:** `App\Interfaces\Http\Controllers\UserController::wallet()`
**Cache:** `App\Infrastructure\Cache\WalletBalanceCache` — chave `wallet:balance:{id}`, TTL 60s

#### Resposta `200 OK`

```json
{
  "data": {
    "user_id": 1,
    "balance": 850.00,
    "from_cache": true
  }
}
```

> `from_cache: true` indica que o dado veio do Redis. `false` indica leitura do banco (e o cache é repovoado automaticamente).

---

### `GET /health`

Verifica o estado dos serviços dependentes.

**Classe:** `App\Interfaces\Http\Controllers\HealthController`

#### Resposta `200 OK` — todos operacionais

```json
{
  "status": "ok",
  "checks": {
    "database": true,
    "redis": true
  }
}
```

#### Resposta `503 Service Unavailable` — degradado

```json
{
  "status": "degraded",
  "checks": {
    "database": false,
    "redis": true
  }
}
```

---

## Regras de negócio

| Regra | Onde é aplicada |
|---|---|
| Apenas usuários `common` podem enviar | `User::assertCanTransfer()` → `UserType::canSend()` |
| Usuários `merchant` só recebem | `UserType::MERCHANT` — `canSend()` retorna `false` |
| Saldo deve ser suficiente | `Wallet::debit()` lança `InsufficientBalanceException` |
| Transferência exige autorização externa | `AuthorizerService` → `AuthorizerClient` (DeviTools mock) |
| Notificação é assíncrona | `TransferCreated` event → RabbitMQ → `TransferNotificationConsumer` |
| CPF/CNPJ e e-mail são únicos | `unique index` na tabela `users` |
| Saldo armazenado como `DECIMAL(15,2)` | Migration `wallets` |

---

## Concorrência e Idempotência

### Distributed Lock (Redis)

Antes de processar, o use case adquire um lock por pagador:

```
SET transfer:lock:{payerId} 1 NX EX 10
```

Se o lock não for obtido, retorna `409 Conflict`. O lock é liberado no `finally`.

**Classe:** `App\Application\UseCases\CreateTransferUseCase`

### Pessimistic Lock (MySQL)

Dentro da transação, as carteiras são travadas em ordem crescente de `user_id` para evitar deadlock:

```sql
SELECT * FROM wallets WHERE user_id = ? FOR UPDATE;
```

**Classe:** `App\Infrastructure\Repositories\WalletRepository::findByUserIdForUpdate()`

### Idempotency Key

O middleware extrai o header `X-Idempotency-Key` e verifica no Redis:

```
GET idem:{key}   → HIT: retorna resposta cacheada (header X-Idempotent-Replayed: true)
                 → MISS: processa e salva com SETEX 86400
```

Apenas respostas com status `< 500` são cacheadas. TTL: **24 horas**.

**Classe:** `App\Interfaces\Http\Middleware\IdempotencyMiddleware`

### Circuit Breaker (Autorizador)

O cliente HTTP do autorizador usa `#[CircuitBreaker]` do Hyperf:

- Abre após **3 falhas consecutivas**
- Permanece aberto por **30 segundos**
- Fallback: nega a transferência e loga o erro

**Classe:** `App\Infrastructure\Http\AuthorizerClient`

---

## Cache

| Chave | TTL | Invalidação | Classe |
|---|---|---|---|
| `wallet:balance:{userId}` | 60s | Após qualquer transferência | `WalletBalanceCache` |
| `idem:{key}` | 86400s (24h) | Nunca (expira) | `IdempotencyMiddleware` |

A invalidação do saldo ocorre no listener do evento `TransferCreated`, que invalida as carteiras do pagador e do recebedor atomicamente.

**Classe:** `App\Infrastructure\Listeners\InvalidateWalletCacheListener`

---

## Mapa de classes

### Domain

| Classe | Responsabilidade |
|---|---|
| `Domain\User\Entities\User` | Entidade usuário; valida se pode transferir |
| `Domain\User\Entities\Wallet` | Entidade carteira; encapsula débito/crédito |
| `Domain\User\Enums\UserType` | `COMMON` (envia e recebe) / `MERCHANT` (só recebe) |
| `Domain\Transfer\Entities\Transfer` | Entidade transferência com ciclo de vida (status) |
| `Domain\Transfer\Enums\TransferStatus` | `pending → authorized → completed / failed` |
| `Domain\Transfer\Events\TransferCreated` | Evento disparado após commit da transação |

### Application

| Classe | Responsabilidade |
|---|---|
| `Application\UseCases\CreateTransferUseCase` | Orquestra todo o fluxo: lock → validar → autorizar → transação → evento |
| `Application\Services\AuthorizerService` | Chama o cliente HTTP e lança exceção se negado |
| `Application\Services\NotificationService` | Enfileira notificação via RabbitMQ |
| `Application\DTOs\TransferDTO` | Dados de entrada da transferência |
| `Application\DTOs\TransferResultDTO` | Dados de saída (response) |

### Infrastructure

| Classe | Responsabilidade |
|---|---|
| `Infrastructure\Repositories\UserRepository` | Busca usuário com carteira do banco |
| `Infrastructure\Repositories\WalletRepository` | `SELECT … FOR UPDATE` + persist |
| `Infrastructure\Repositories\TransferRepository` | Persiste e consulta transferências |
| `Infrastructure\Cache\WalletBalanceCache` | Abstração do Redis para saldo |
| `Infrastructure\Http\AuthorizerClient` | HTTP para DeviTools com `#[CircuitBreaker]` + `#[Retry]` |
| `Infrastructure\Http\NotifierClient` | HTTP para serviço de notificação |
| `Infrastructure\Queue\TransferNotificationProducer` | Publica mensagem no RabbitMQ |
| `Infrastructure\Queue\TransferNotificationConsumer` | Consome e envia notificação; requeue em falha |
| `Infrastructure\Listeners\InvalidateWalletCacheListener` | Ouve `TransferCreated` → invalida cache + enfileira notificação |

### Interfaces

| Classe | Responsabilidade |
|---|---|
| `Interfaces\Http\Controllers\TransferController` | `POST /transfer`, `GET /transfer/{id}` |
| `Interfaces\Http\Controllers\UserController` | `GET /users/{id}/transfers`, `GET /users/{id}/wallet` |
| `Interfaces\Http\Controllers\HealthController` | `GET /health` |
| `Interfaces\Http\Controllers\DocsController` | `GET /docs` (Swagger UI), `GET /docs/openapi.yaml` |
| `Interfaces\Http\Middleware\IdempotencyMiddleware` | Intercepta POST, verifica/grava chave de idempotência |

---

## CLI Commands

```bash
# Criar usuários seed
php bin/hyperf.php seed:users --common=5 --merchant=2 --balance=1000

# Simular N transferências concorrentes (útil para demonstrar lock)
php bin/hyperf.php transfer:simulate --count=20 --concurrent=5

# Consultar saldo (--fresh ignora cache)
php bin/hyperf.php wallet:balance 1
php bin/hyperf.php wallet:balance 1 --fresh

# Listar histórico de transferências
php bin/hyperf.php transfer:list 1
```

| Comando | Classe |
|---|---|
| `seed:users` | `Interfaces\Console\SeedUsersCommand` |
| `transfer:simulate` | `Interfaces\Console\SimulateTransferCommand` |
| `wallet:balance` | `Interfaces\Console\WalletBalanceCommand` |
| `transfer:list` | `Interfaces\Console\TransferListCommand` |

---

## Testes

```bash
# Todos os testes
vendor/bin/phpunit

# Apenas unitários
vendor/bin/phpunit --testsuite=Unit

# Com cobertura
vendor/bin/phpunit --coverage-html=coverage/

# Análise estática
vendor/bin/phpstan analyse

# Padrão de código
vendor/bin/ecs check
```

| Suite | O que cobre |
|---|---|
| `Unit\Domain\WalletTest` | Débito, crédito, saldo insuficiente, validações de valor |
| `Unit\Domain\UserTest` | `assertCanTransfer()` para `common` e `merchant` |
| `Unit\Domain\TransferTest` | Ciclo de vida do status, validações de criação |
| `Unit\Application\CreateTransferUseCaseTest` | Merchant bloqueado, autorizador negando, lock distribuído |
| `Feature\TransferApiTest` | Validação de campos, idempotência, health check |
