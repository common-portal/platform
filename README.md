# xramp.io

A private fork of the common-portal-platform, customized for xramp.io.

## About

This project is based on the [common-portal-platform](https://github.com/common-portal/platform) and has been configured as an independent local project.

## Technology Stack

- **Backend**: Laravel (PHP)
- **Frontend**: Vite + TailwindCSS
- **Database**: PostgreSQL (via Docker)
- **Containerization**: Docker & Docker Compose

## Getting Started

### Prerequisites

- Docker and Docker Compose
- PHP 8.2+
- Node.js 18+

### Installation

1. Copy environment configuration:
```bash
cp .env.example .env
```

2. Start the development environment:
```bash
docker-compose up -d
```

3. Install dependencies:
```bash
cd src
composer install
npm install
```

4. Run migrations:
```bash
php artisan migrate
```

5. Start the development server:
```bash
npm run dev
```

## Project Structure

See `COMMON-PORTAL-DIRECTORY-INDEX-002.md` for detailed directory structure.

## Documentation

- Development Roadmap: `COMMON-PORTAL-DEVELOPMENT-ROADMAP-002.md`
- Database Schema: `COMMON-PORTAL-DATABASE-SCHEMA-002.md`
- Framework README: `COMMON-PORTAL-FRAMEWORK-README-002.md`
- Feature Wishlist: `COMMON-PORTAL-BRAINSTORMING-WISH-LIST-003.md`

## License

See `LICENSE` file for details.

---

*Forked from common-portal-platform on January 27, 2026*
