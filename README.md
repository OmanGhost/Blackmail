# منصة إبتزاز

Current release: **v1.6.0**. See [CHANGELOG.md](CHANGELOG.md) for changes and upgrade instructions. The release number is recorded in [VERSION](VERSION).

للنشر في المدونة: افتح الإدارة ← المحتوى ← المدونة ← إنشاء، ثم اختر النوع (مقال/خبر/فيديو) والتصنيف والكاتب وأضف صورة الغلاف والمحتوى والترجمات داخل النموذج نفسه. استخدم رابطًا مختصرًا فريدًا بالحروف الإنجليزية والأرقام والشرطات. للفيديو أضف رابط المشاهدة ووصفه. اختر «منشور» وحدد وقت النشر؛ الموعد المستقبلي يجدول ظهور المنشور. احتفظ بالمسودة إذا لم يكن المحتوى جاهزًا.

Arabic-first, privacy-conscious anti-blackmail education and guidance platform built with Laravel 12, Livewire 3, Filament 4, Tailwind CSS 4, and Alpine.js.

## Local setup

Requires PHP 8.3+ with `fileinfo`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, and `zip`; Composer; Node.js; MySQL 8. Redis is recommended for cache, sessions, queues, and rate limiting.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Seeded countries are demonstration records marked **unverified and unpublished**. The seeder intentionally creates no administrator with a known password. Create the first operator locally with `php artisan make:filament-user`, assign the `super-admin` role, and require MFA before production use.

## Routes and architecture

- `/` Arabic RTL public homepage with language and dark-mode controls.
- `/help` anonymous Livewire guidance wizard; answers are not persisted.
- `/admin` authenticated Filament panel foundation.
- Normalized content, translations, editorial states, countries, legal review, consultation, RBAC, settings, and privacy-aware audit schema.

See [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) and [SECURITY.md](SECURITY.md).

## Production

Deploy on Ubuntu 24.04 with NGINX, PHP-FPM 8.3+, MySQL 8, Redis, TLS, and Supervisor. Set `APP_ENV=production`, `APP_DEBUG=false`, encrypted secure cookies, and real secrets through the host secret manager. Run `php artisan optimize`, a persistent `queue:work`, and the scheduler every minute. Use a restricted OS user, encrypted backups, and tested restore procedures. Complete retention/deletion jobs and all security gates before accepting consultation cases.
