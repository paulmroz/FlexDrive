# DriveAgency

DriveAgency is a car subscription platform. The backend is built using Symfony 7, implementing a modular monolith structure with domain-driven design, ports and adapters, and CQRS patterns. The frontend is built using React 19, TypeScript, and Tailwind CSS.

## Architecture

* **Modular Monolith**: Business logic is separated into independent domain modules (Car, User, Subscription) inside the backend.
* **CQRS**: Commands and queries are handled separately using the Symfony Messenger component.
* **Concurrency Control**: Concurrency is managed at the database level using pessimistic write locks.
* **Ports and Adapters**: Domain logic is decoupled from framework adapters using interface ports.

## Tech Stack

* **Backend**: PHP 8.3, Symfony 7.4, FrankenPHP, PostgreSQL, Redis
* **Frontend**: React 19, TypeScript, Vite, Tailwind CSS v4, Zustand

## Setup

### Prerequisites
* Docker and Docker Compose

### Running the Project

1. **Start the containers**:
   ```bash
   docker compose up -d --build
   ```

2. **Generate JWT keys**:
   ```bash
   docker compose exec php bin/console lexik:jwt:generate-keypair
   ```

3. **Run database migrations**:
   ```bash
   docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. **Run backend tests**:
   ```bash
   docker compose exec php bin/phpunit
   ```

5. **Run the frontend**:
   Navigate to the `frontend` directory and start the Vite development server:
   ```bash
   npm install
   npm run dev
   ```
   The development server will be available at http://localhost:5173/.
