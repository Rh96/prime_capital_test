# Investment ledger

Append-only cash and holdings ledger. Staff operate it from a Livewire UI; integrations use the REST API. Both go through `LedgerService`.

Requires PHP 8.3+ and **MySQL 8.0.16+** (`CHECK` constraints are ignored on 5.7 and MariaDB — confirm with `SELECT VERSION();`).

## Setup

```bash
git clone <this-repo>
cd <this-repo>
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Point `.env` at local MySQL (the example file is Sail-oriented):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=investment_ledger
DB_USERNAME=root
DB_PASSWORD=
```

Create both databases:

```sql
CREATE DATABASE investment_ledger CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE investment_ledger_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`phpunit.xml` already uses `investment_ledger_test`. Then:

```bash
php artisan migrate --seed
php artisan serve
```

Open http://localhost:8000 and sign in:

- Email: `staff@example.com`
- Password: `password`

You should see Ana, Boris, Elena, and Ivana. Ana is the spec example: 860 cash, 2 AAPL.

```bash
php artisan test
php artisan ledger:verify
```

### Sail

If you would rather not install PHP/MySQL on the host, keep `DB_HOST=mysql` and `DB_DATABASE=laravel` from `.env.example`, then:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

Create `investment_ledger_test` inside the MySQL container and grant the `sail` user access to it before `./vendor/bin/sail test`. The app is on http://localhost:8000 when `APP_PORT=8000`.

## API

No auth. After seed, Ana is client `1`. Amounts are decimal strings. Buy/sell totals are computed server-side as `quantity × unit_price` — do not send `amount` on a trade.

The GET and 422 examples below are copy-pasteable on a fresh seed. The three 201 bodies are Ana's seeded rows (same numbers as GET history). Do not POST them again on `/clients/1` after seed — that would add extra rows.

### Record a movement

`POST /api/clients/{client}/transactions`

Deposit (this is how Ana's first row was written):

```bash
curl -X POST http://localhost:8000/api/clients/1/transactions \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"type":"deposit","amount":"1000"}'
```

```json
{
  "data": {
    "id": 1,
    "client_id": 1,
    "type": "deposit",
    "amount": "1000.0000",
    "symbol": null,
    "quantity": null,
    "unit_price": null,
    "cash_balance_after": "1000.0000",
    "created_at": "2026-09-04T19:32:36.000000Z"
  }
}
```

Buy:

```bash
curl -X POST http://localhost:8000/api/clients/1/transactions \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"type":"buy","symbol":"AAPL","quantity":5,"unit_price":"100"}'
```

```json
{
  "data": {
    "id": 2,
    "client_id": 1,
    "type": "buy",
    "amount": "500.0000",
    "symbol": "AAPL",
    "quantity": 5,
    "unit_price": "100.0000",
    "cash_balance_after": "500.0000",
    "created_at": "2026-09-04T19:32:36.000000Z"
  }
}
```

Sell:

```bash
curl -X POST http://localhost:8000/api/clients/1/transactions \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"type":"sell","symbol":"AAPL","quantity":3,"unit_price":"120"}'
```

```json
{
  "data": {
    "id": 3,
    "client_id": 1,
    "type": "sell",
    "amount": "360.0000",
    "symbol": "AAPL",
    "quantity": 3,
    "unit_price": "120.0000",
    "cash_balance_after": "860.0000",
    "created_at": "2026-09-04T19:32:36.000000Z"
  }
}
```

### Cash

```bash
curl http://localhost:8000/api/clients/1/cash -H 'Accept: application/json'
```

```json
{
  "data": {
    "client_id": 1,
    "cash_balance": "860.0000"
  }
}
```

### Holdings

```bash
curl http://localhost:8000/api/clients/1/holdings -H 'Accept: application/json'
```

```json
{
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "symbol": "AAPL",
      "quantity": 2
    }
  ]
}
```

### Transaction history

Paginated.

```bash
curl http://localhost:8000/api/clients/1/transactions -H 'Accept: application/json'
```

```json
{
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "type": "deposit",
      "amount": "1000.0000",
      "symbol": null,
      "quantity": null,
      "unit_price": null,
      "cash_balance_after": "1000.0000",
      "created_at": "2026-09-04T19:32:36.000000Z"
    },
    {
      "id": 2,
      "client_id": 1,
      "type": "buy",
      "amount": "500.0000",
      "symbol": "AAPL",
      "quantity": 5,
      "unit_price": "100.0000",
      "cash_balance_after": "500.0000",
      "created_at": "2026-09-04T19:32:36.000000Z"
    },
    {
      "id": 3,
      "client_id": 1,
      "type": "sell",
      "amount": "360.0000",
      "symbol": "AAPL",
      "quantity": 3,
      "unit_price": "120.0000",
      "cash_balance_after": "860.0000",
      "created_at": "2026-09-04T19:32:36.000000Z"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/clients/1/transactions?page=1",
    "last": "http://127.0.0.1:8000/api/clients/1/transactions?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://127.0.0.1:8000/api/clients/1/transactions",
    "per_page": 15,
    "to": 3,
    "total": 3
  }
}
```

### Rejection (overdraft)

```bash
curl -X POST http://localhost:8000/api/clients/1/transactions \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"type":"withdraw","amount":"9999"}'
```

HTTP 422. Ana's cash stays `860.0000`.

```json
{
  "code": "insufficient_funds",
  "message": "Insufficient funds: available 860.0000, requested 9999."
}
```

A malformed payload is also 422, from validation rather than the domain:

```bash
curl -X POST http://localhost:8000/api/clients/1/transactions \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"type":"deposit","amount":"1000","symbol":"AAPL"}'
```

```json
{
  "message": "Deposits and withdrawals cannot include a symbol.",
  "errors": {
    "symbol": ["Deposits and withdrawals cannot include a symbol."]
  }
}
```

Overselling (safe to run on Ana after seed):

```bash
curl -X POST http://localhost:8000/api/clients/1/transactions \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"type":"sell","symbol":"AAPL","quantity":8,"unit_price":"100"}'
```

```json
{
  "code": "insufficient_shares",
  "message": "Insufficient shares of AAPL: held 2, requested 8."
}
```

## Зошто вака

**Леџерот е append-only; cash и holdings се кеш.** Секоја промена е нов ред во `transactions`. Стари редови не се менуваат — ако утре има спор, историјата е целосна. `clients.cash_balance` и `holdings` ги чуваме заклучени за да не собираме цел лог на секој екран и при секој sell. Извор на вистина е логот. `php artisan ledger:verify` го пресметува повторно и излегува со грешка ако кешот лаже.

**`lockForUpdate` во `DB::transaction`.** Две повлекувања во ист момент можат двете да прочитаат 500, двете да поминат проверката, и балансот да оди под нула. Песимистичкиот лок на клиентскиот ред ги серијализира. Кај sell, прво се локира клиентот, потоа холдингот — ист редослед насекаде, инаку има deadlock. Без лок, CHECK на кешот може да спаси негативен баланс, но не и двојно продадени акции.

**Бизнис правилата се во сервис, не во validation rule.** Валидацијата одговара „дали барањето е добро формирано?“. Сервисот одговара „дали е дозволено со тековната состојба?“. Rule не може да држи row lock, па проверка на баланс во `FormRequest` би била трка: две барања минуваат валидацијата пред било кое да запише.

**Симболите се `strtoupper(trim(...))`.** Спецификацијата вели етикетата се чува како што е внесена. Без нормализација, `aapl` и `AAPL` се два холдинга и оператор може да „продаде“ акции што клиентот ги нема под другото пишување. Ги нормализираме. Исто така, вкупниот износ на trade се пресметува на сервер (`quantity × unit_price`) — клиентот не се слуша за тоа.

**Нема табела `instruments`.** Симболот е ознака, не регистар. Немаме ISIN, корпоративни акции, ниту цени што ги одржува фирмата. Таква табела би имплицирала универзум на инструменти што спецификацијата не го бара.

**Зошто има UI, и зошто `users` ≠ `clients`.** Спецификацијата бара API. Livewire е вториот влез во истиот `LedgerService` — два влеза, една логика, нула дупликати. `users` се вработени што се логираат (Breeze). `clients` се луѓе чии пари и хартии ги водиме. Клиентот не е логин.
