# FlexDrive — Enterprise Car Subscription Platform

FlexDrive is a high-performance, modular monolith backend built in Symfony 7. It demonstrates enterprise software engineering patterns, including Domain-Driven Design (DDD), Hexagonal Architecture (Ports & Adapters), and CQRS command/query buses with pessimistic database locking for concurrency control.

---

## 🏗️ Architectural Overview

The application is structured as a **Modular Monolith** located in the [src](file:///home/pawel/DriveAgency/backend/src) directory, separating distinct business domains while keeping them within a single repository for development velocity:

* **Domain-Driven Design (DDD)**: Business logic is encapsulated in Rich Domain Entities and Value Objects with guarded state transitions.
* **Ports & Adapters (Hexagonal)**: High-level domain layers are isolated from low-level infrastructure dependencies (database adapters, HTTP controllers, CLI commands) using clear Interfaces (Ports).
* **CQRS (Command Query Responsibility Segregation)**: Reads and writes are segregated into separate Command and Query paths using Symfony Messenger buses.
* **Concurrency Control**: Booking collisions are prevented at the database layer using Doctrine pessimistic write locks (`LockMode::PESSIMISTIC_WRITE`) running inside transaction middleware.

---

## 🚀 Tech Stack

* **Core Framework**: PHP 8.3 + Symfony 7.4
* **Application Server**: FrankenPHP 1.3 (Caddy-based worker mode)
* **Database**: PostgreSQL 16
* **Cache & Store**: Redis 7
* **Testing**: PHPUnit 9

---

## 🛠️ Getting Started

### Prerequisites
* Docker and Docker Compose installed locally.

### Setup Instructions

1. **Start Services**:
   Spin up the application server, PostgreSQL, and Redis containers defined in [docker-compose.yaml](file:///home/pawel/DriveAgency/docker-compose.yaml):
   ```bash
   docker compose up -d --build
   ```

2. **Generate JWT Keypairs**:
   Generate the public and private key pairs required for lexik stateless JWT authentication:
   ```bash
   docker compose exec php bin/console lexik:jwt:generate-keypair
   ```

3. **Run Database Migrations**:
   Run migration scripts to construct the database schema:
   ```bash
   docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. **Execute Tests**:
   Run the test suite (unit, functional, integration, and parallel locking tests) inside the PHP container environment:
   ```bash
   docker compose exec php bin/phpunit
   ```
