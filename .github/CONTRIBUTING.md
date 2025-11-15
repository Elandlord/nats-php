# Contributing to NATS PHP

Thank you for considering contributing!  
This project welcomes improvements, bug fixes, documentation updates, tests, and new features.

---

## 🧱 How to Contribute

### 1. Fork the Repository
Click the **Fork** button on GitHub and clone your fork:

```bash
git clone https://github.com/<your-username>/nats-php.git
cd nats-php
```

```bash
git checkout -b feature/my-new-feature
```

**Use descriptive names such as:**
- feature/jetstream-metadata
- fix/reconnect-bug
- docs/improve-readme

## 🔧 Development Setup

```composer install```

## 📐 Coding Standards

- PSR-12 coding style
- PSR-4 autoloading
- Strong typing (declare(strict_types=1);)
- No unnecessary dependencies

🧪 Tests

Every new feature or bug fix must include a test.

Tests are located in:

```bash
/tests
```

## 📝 Commit Messages

Follow the conventional commit style:

- feat: for new features
- fix: for bug fixes
- chore: for internal changes
- docs: for documentation
- test: for test improvements
- refactor: for code restructuring

**Example:**
`feat: add JetStream consumer with durable support`