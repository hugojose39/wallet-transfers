# Wallet Transfers — Documentação da API

API REST para transferências financeiras entre usuários e lojistas, construída com **Hyperf 3.x + Swoole** seguindo arquitetura **DDD Light (Monólito Modular)**.

---

## Índice

- [Stack](#stack)
- [Arquitetura](#arquitetura)
- [Como rodar](#como-rodar)
- [Postman](#postman)
- [Endpoints](#endpoints)
  - [POST /users](#post-users)
  - [POST /users/{id}/wallet/deposit](#post-usersidwalletdeposit)
  - [POST /transfer](#post-transfer)
  - [GET /transfer/{id}](#get-transferid)
  - [GET /users/{id}/transfers](#get-usersidtransfers)
  - [GET /users/{id}/wallet](#get-usersidwallet)
  - [GET /health](#get-health)
- [Regras de negócio](#regras-de-negócio)
- [Concorrência](#concorrência)
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
│   ├── UseCases/               CreateTransferUseCase.php, CreateUserUseCase.php
│   ├── Services/               AuthorizerService.php
│   └── DTOs/                   TransferDTO.php, TransferResultDTO.php
│
├── Infrastructure/             # Implementações concretas (DB, Redis, HTTP, Fila)
│   ├── Repositories/           UserRepository.php, WalletRepository.php, TransferRepository.php
│   ├── Persistence/Models/     UserModel.php, WalletModel.php, TransferModel.php
│   ├── Cache/                  WalletBalanceCache.php
│   ├── Http/                   AuthorizerClient.php, NotifierClient.php
│   └── Queue/                  TransferNotificationProducer.php, TransferNotificationConsumer.php
│
└── Interfaces/                 # Entradas do sistema (HTTP + CLI)
    ├── Http/
    │   ├── Controllers/        TransferController.php, UserController.php,
    │   │                       HealthController.php, DocsController.php
    │   ├── Exceptions/         ValidationExceptionHandler.php, UnhandledExceptionHandler.php
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
docker compose exec app php bin/hyperf.php seed:users --common=5 --merchant=2 --balance=1000000

# API disponível em:
# http://localhost:9501
# Swagger UI: http://localhost:9501/docs

```

---

## Postman

### Workspace público

Acesse a collection diretamente pelo link abaixo — sem precisar importar nenhum arquivo:

[**Abrir no Postman**](https://www.postman.com/hugo-jose-ferreira-moreira-227664/workspace/hugo-jos-s-workspace/collection/56183644-b549b7fa-c440-476b-8a5a-47c6a0760107?action=share&creator=56183644&active-environment=56183644-26db8a02-c738-4d04-a25d-401cbd4b39de)

### Importar pelos arquivos do repositório

Os arquivos estão em `postman/`:

```
postman/
├── wallet-transfers.collection.json   # Todos os endpoints com exemplos
└── wallet-transfers.environment.json  # Variáveis: base_url, user_id, payee_id, transfer_id
```

**Passos:**

1. Abra o Postman
2. Clique em **Import** (canto superior esquerdo)
3. Arraste os dois arquivos ou clique em **Upload Files** e selecione ambos
4. Na aba **Environments**, selecione **Wallet Transfers — Local**
5. Ajuste `base_url` se necessário (padrão: `http://localhost:9501`)

### Variáveis de ambiente

| Variável | Valor padrão | Descrição |
|---|---|---|
| `base_url` | `http://localhost:9501` | URL base da API |
| `user_id` | `1` | ID do usuário pagador (tipo `common`) |
| `payee_id` | `2` | ID do recebedor (tipo `merchant`) |
| `transfer_id` | `1` | ID de uma transferência existente |

> Os requests de **Criar Usuário** e **Realizar Transferência** salvam automaticamente os IDs retornados nas variáveis `user_id`, `payee_id` e `transfer_id` via scripts de teste.

---

## Endpoints

### `POST /users`

Cria um novo usuário e inicializa sua carteira com saldo zero.

**Classe:** `App\Interfaces\Http\Controllers\UserController::store()`
**Use Case:** `App\Application\UseCases\CreateUserUseCase`

#### Body

```json
{
  "name": "João Silva",
  "document": "12345678901",
  "email": "joao@example.com",
  "password": "secret123",
  "type": "common"
}
```

| Campo | Tipo | Regra |
|---|---|---|
| `name` | `string` | Obrigatório |
| `document` | `string` | Obrigatório, máximo 18 caracteres, único |
| `email` | `string` | Obrigatório, formato e-mail, único |
| `password` | `string` | Obrigatório, mínimo 8 caracteres |
| `type` | `string` | Obrigatório, `common` ou `merchant` |

#### Respostas

**`201 Created`** — Usuário criado

```json
{
  "data": {
    "id": 1,
    "name": "João Silva",
    "document": "12345678901",
    "email": "joao@example.com",
    "type": "common"
  }
}
```

**`422 Unprocessable Entity`** — Validação falhou ou documento/e-mail já em uso

```json
{
  "message": "Validation failed.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

```json
{
  "message": "The document is already in use."
}
```

---

### `POST /users/{id}/wallet/deposit`

Adiciona saldo à carteira do usuário. O valor é informado em **reais** (float) e armazenado internamente em **centavos** (BIGINT).

**Classe:** `App\Interfaces\Http\Controllers\UserController::deposit()`

#### Parâmetros

| Param | Onde | Tipo | Descrição |
|---|---|---|---|
| `id` | path | `integer` | ID do usuário |

#### Body

```json
{
  "amount": 100.00
}
```

| Campo | Tipo | Regra |
|---|---|---|
| `amount` | `float` | Obrigatório, mínimo `0.01` (em reais) |

#### Respostas

**`200 OK`** — Depósito realizado

```json
{
  "data": {
    "user_id": 1,
    "balance": 950.00,
    "from_cache": false
  }
}
```

**`404 Not Found`** — Usuário não encontrado

**`422 Unprocessable Entity`** — Validação falhou

---

### `POST /transfer`

Realiza uma transferência financeira entre dois usuários.

**Classe:** `App\Interfaces\Http\Controllers\TransferController::store()`
**Use Case:** `App\Application\UseCases\CreateTransferUseCase::execute()`

#### Headers

| Header | Obrigatório | Descrição |
|---|---|---|
| `Content-Type` | sim | `application/json` |

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

O saldo é armazenado internamente como **BIGINT (centavos)** e retornado como **float (reais)** — `balance = centavos / 100`.

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
    "redis": true
  }
}
```

#### Resposta `503 Service Unavailable` — degradado

```json
{
  "status": "degraded",
  "checks": {
    "redis": false
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
| documento (`document`) e e-mail são únicos | `unique index` na tabela `users` |
| Saldo armazenado como `BIGINT` (centavos) | Migration `wallets` |

---

## Concorrência

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

---

## Cache

| Chave | TTL | Invalidação | Classe |
|---|---|---|---|
| `wallet:balance:{userId}` | 60s | Após qualquer transferência | `WalletBalanceCache` |

A invalidação ocorre diretamente no `CreateTransferUseCase` após o commit da transação.

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
| `Domain\Shared\Exceptions\DomainException` | Base para todas as exceções de domínio |
| `Domain\Shared\Exceptions\InsufficientBalanceException` | Saldo insuficiente para a transferência |
| `Domain\Shared\Exceptions\UnauthorizedTransferException` | Tipo de usuário não permitido para envio |
| `Domain\Shared\Exceptions\TransferNotAuthorizedException` | Serviço externo negou a autorização |
| `Domain\Shared\Exceptions\DuplicateDocumentException` | Documento (`CPF`/`CNPJ`) já cadastrado |
| `Domain\Shared\Exceptions\DuplicateEmailException` | E-mail já cadastrado |
| `Domain\Shared\Exceptions\UserNotFoundException` | Usuário não encontrado no repositório |

### Application

| Classe | Responsabilidade |
|---|---|
| `Application\UseCases\CreateTransferUseCase` | Orquestra todo o fluxo: lock → validar → autorizar → transação → notificação async |
| `Application\UseCases\CreateUserUseCase` | Valida unicidade de document/email, cria usuário com carteira zerada |
| `Application\Services\AuthorizerServiceInterface` | Contrato do serviço de autorização (injeção de dependência) |
| `Application\Services\AuthorizerService` | Chama o cliente HTTP e lança exceção se negado |
| `Application\DTOs\CreateUserDTO` | Dados de entrada para criação de usuário |
| `Application\DTOs\TransferDTO` | Dados de entrada para criação de transferência |
| `Application\DTOs\TransferResultDTO` | Dados de saída da transferência (response) |

### Infrastructure

| Classe | Responsabilidade |
|---|---|
| `Infrastructure\Repositories\UserRepository` | Busca usuário com carteira do banco |
| `Infrastructure\Repositories\WalletRepository` | `SELECT … FOR UPDATE` + persist |
| `Infrastructure\Repositories\TransferRepository` | Persiste e consulta transferências |
| `Infrastructure\Persistence\Models\UserModel` | Model Eloquent para a tabela `users` |
| `Infrastructure\Persistence\Models\WalletModel` | Model Eloquent para a tabela `wallets` |
| `Infrastructure\Persistence\Models\TransferModel` | Model Eloquent para a tabela `transfers` |
| `Infrastructure\Cache\WalletBalanceCache` | Abstração do Redis para saldo |
| `Infrastructure\Http\AuthorizerClient` | HTTP para DeviTools com `#[Retry]` e backoff exponencial |
| `Infrastructure\Http\NotifierClient` | HTTP para serviço de notificação |
| `Infrastructure\Queue\TransferNotificationProducer` | `ProducerMessage` AMQP — serializa `TransferCreated` para a fila |
| `Infrastructure\Queue\TransferNotificationConsumer` | Consome fila `transfer`, chama notificador HTTP; requeue em falha |

### Interfaces

| Classe | Responsabilidade |
|---|---|
| `Interfaces\Http\Controllers\TransferController` | `POST /transfer`, `GET /transfer/{id}` |
| `Interfaces\Http\Controllers\UserController` | `POST /users`, `POST /users/{id}/wallet/deposit`, `GET /users/{id}/transfers`, `GET /users/{id}/wallet` |
| `Interfaces\Http\Controllers\HealthController` | `GET /health` |
| `Interfaces\Http\Controllers\DocsController` | `GET /docs` (Swagger UI), `GET /docs/openapi.yaml` |
| `Interfaces\Http\Requests\StoreUserRequest` | Validação e parsing do body de `POST /users` |
| `Interfaces\Http\Requests\StoreTransferRequest` | Validação e parsing do body de `POST /transfer` |
| `Interfaces\Http\Requests\StoreDepositRequest` | Validação e parsing do body de `POST /users/{id}/wallet/deposit` |
| `Interfaces\Http\Resources\UserResource` | Transforma `User` entity em array JSON de resposta |
| `Interfaces\Http\Resources\WalletResource` | Transforma `Wallet` entity em array JSON de resposta |
| `Interfaces\Http\Resources\TransferResource` | Transforma `Transfer` entity em array JSON de resposta |
| `Interfaces\Http\Exceptions\DomainExceptionHandler` | Captura `DomainException` e mapeia para HTTP status correto |
| `Interfaces\Http\Exceptions\ValidationExceptionHandler` | Captura `ValidationException`, retorna 422 |
| `Interfaces\Http\Exceptions\UnhandledExceptionHandler` | Catch-all para exceções não tratadas, retorna 500 |

---

## CLI Commands

```bash
# Criar usuários seed (--balance em centavos: 1000000 = R$ 10.000,00)
docker compose exec app php bin/hyperf.php seed:users --common=5 --merchant=2 --balance=1000000

# Simular N transferências concorrentes (útil para demonstrar lock)
docker compose exec app php bin/hyperf.php transfer:simulate --count=20 --concurrent=5

# Consultar saldo (--fresh ignora cache)
docker compose exec app php bin/hyperf.php wallet:balance 1
docker compose exec app php bin/hyperf.php wallet:balance 1 --fresh

# Listar histórico de transferências
docker compose exec app php bin/hyperf.php transfer:list 1
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
docker compose exec app composer test

# Apenas unitários
docker compose exec app composer test -- --testsuite=Unit

# Apenas features
docker compose exec app composer test -- --testsuite=Feature

# Com cobertura (mínimo 90% exigido)
docker compose exec app composer test-coverage
# Relatório HTML: coverage/index.html
# Relatório Clover: coverage/clover.xml

# Análise estática
docker compose exec app composer analyse

# Padrão de código
docker compose exec app vendor/bin/ecs check
```

| Suite | Arquivo | O que cobre |
|---|---|---|
| Unit | `Unit\Domain\WalletTest` | Débito, crédito, saldo insuficiente, validações de valor |
| Unit | `Unit\Domain\UserTest` | `assertCanTransfer()` para `common` e `merchant` |
| Unit | `Unit\Domain\TransferTest` | Ciclo de vida do status, validações de criação |
| Unit | `Unit\Domain\DomainExceptionTest` | Hierarquia de exceções de domínio |
| Unit | `Unit\Domain\TransferCreatedEventTest` | Criação e propriedades do evento |
| Unit | `Unit\Application\CreateTransferUseCaseTest` | Merchant bloqueado, autorizador negando, lock distribuído |
| Unit | `Unit\Application\CreateUserUseCaseTest` | Unicidade de document/email, criação de usuário |
| Unit | `Unit\Application\TransferDTOTest` | Criação e validação do DTO |
| Unit | `Unit\Application\TransferResultDTOTest` | Serialização do resultado |
| Unit | `Unit\Infrastructure\AuthorizerClientTest` | Circuit breaker, retry, resposta do autorizador |
| Unit | `Unit\Infrastructure\AuthorizerServiceTest` | Lança exceção quando negado |
| Unit | `Unit\Infrastructure\WalletBalanceCacheTest` | Set/get/invalidation no Redis |
| Unit | `Unit\Infrastructure\ExceptionHandlersTest` | Mapeamento de exceções para HTTP status |
| Unit | `Unit\Infrastructure\HealthControllerTest` | Health check com Redis up/down |
| Unit | `Unit\Infrastructure\ResourcesTest` | Transformação das entities em arrays de resposta |
| Feature | `Feature\TransferApiTest` | Validação de campos do endpoint de transferência |
| Feature | `Feature\UserApiTest` | Criação de usuário, depósito, carteira e histórico |
| Feature | `Feature\TransferHappyPathTest` | Fluxo completo de transferência (happy path) |
