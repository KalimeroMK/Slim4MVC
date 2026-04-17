# Slim4MVC Agent Instructions

This is a modular **Slim 4 MVC Starter Kit** built with PHP 8.4, Eloquent ORM, PHP-DI, BladeOne, and Tailwind CSS.

## Before You Start

ALWAYS read these skill files first before generating code:

- `.claude/skills/backend.md` — Backend architecture, Action/DTO/Repository/Request patterns
- `.claude/skills/frontend.md` — Blade + Tailwind CSS rules, view layer constraints
- `.claude/skills/docker.md` — Docker multi-stage build and service rules

## Core Principles

1. **Plan first, code second.** Analyze the existing module structure and stubs before writing any code.
2. **Preserve existing module structure.** New modules MUST follow the exact directory layout in `app/Modules/{Module}/`.
3. **Use PHP 8.4 features:** `final readonly` classes, constructor property promotion, named arguments, match expressions.
4. **Business logic belongs in Action classes.** Controllers are thin; Blade views contain zero PHP logic.
5. **Register everything:** New modules go into `bootstrap/modules-register.php`; new Action interfaces go into `bootstrap/dependencies.php`.
6. **Write tests:** Every Action, DTO, Repository, and Controller MUST have PHPUnit tests.
7. **Run the test suite:** Execute `vendor/bin/phpunit` before finishing any task.
8. **Code quality:** Ensure code passes PHPStan level 8 and Laravel Pint formatting.
