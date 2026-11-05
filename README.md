# منصة إبتزاز

Current release: **v1.13.0**. See [CHANGELOG.md](CHANGELOG.md) for changes and upgrade instructions. The release number is recorded in [VERSION](VERSION).

تذييل لوحة الإدارة يعرض `VERSION`. قسم «النظام ← إصدار التطبيق والتحديثات» متاح للمدير لقراءة آخر إصدار مستقر من GitHub. للمستودع الخاص أضف `GITHUB_UPDATE_TOKEN` إلى بيئة الخادم برمز Fine-grained محدود بمستودع `OmanGhost/Blackmail` وصلاحية `Contents: read`، ثم نفّذ `php artisan config:clear`. لا تستخدم رمز النشر واسع الصلاحيات داخل التطبيق. الفحص تلقائي عند فتح لوحة المدير ومخزّن 12 ساعة، مع زر فحص يدوي محدود بدقيقة. أمر `php artisan application:check-updates` مُجدول مرتين يوميًا عند تشغيل Laravel Scheduler. الفحص لا يثبت التحديثات تلقائيًا.

مسار الإدارة يُحدد في `.env` عبر `ADMIN_PATH` (مقطع واحد من 8 إلى 64 حرفًا إنجليزيًا أو رقمًا أو شرطة، يبدأ بحرف). اختر قيمة خاصة بكل بيئة، ثم نفّذ `php artisan optimize:clear`. رابط الدخول هو `/<ADMIN_PATH>/login`، أما `/admin` ومساراته فتحوّل إلى الرئيسية. تغيير المسار طبقة إضافية ولا يغني عن الصلاحيات والمصادقة الثنائية.

تشغيل XAMPP دون `public` في الرابط: يتضمن `deployment/apache/blackmail-xampp.conf` مثال Alias آمنًا يخدم `public/` فقط من `http://localhost/blackmail`. اضبط المسارات المطلقة، وأدرجه في إعداد Apache، ثم اختبر `httpd -t` وأعد تشغيل Apache. اضبط `APP_URL` للرابط الجديد. في الإنتاج وجّه DocumentRoot إلى مجلد `public` مباشرة؛ لا تنقل ملفات Laravel أو `.env` إلى جذر الويب.

التحديث الأمني v1.7.1 يلغي الجلسات السابقة ويطلب تسجيل الدخول مجددًا. دخول الإدارة مستقل عبر مسار `ADMIN_PATH` ويستمر في طلب المصادقة الثنائية. اعتماد بيانات الدول والجهات متاح للمدير والمراجع القانوني فقط، ويسجل النظام المراجع والتاريخ تلقائيًا. تعديل بيانات اتصال متحققة يعيدها للمراجعة ويوقف نشرها إلى حين الاعتماد مجددًا. يعيد الترحيل الجهات القديمة غير الموثقة والدول التي تفتقد مراجعًا أو تاريخ تحقق إلى «قيد المراجعة» دون حذف بياناتها.

رقم الهاتف في رأس الموقع: يعرض النظام رقم أول جهة إبلاغ متحققة ومنشورة (ولها رقم هاتف) في دولة الزائر المتحققة، وإلا يعرض رقم المنصة `78444499` داخل عُمان و`+968-78444499` خارجها (قابل للتغيير عبر `PLATFORM_HOTLINE_LOCAL` و`PLATFORM_HOTLINE_INTERNATIONAL`). تُحدد الدولة محليًا من قاعدة DB-IP Lite المجانية دون إرسال عنوان IP لأي خدمة ودون حفظه؛ نفّذ `php artisan geoip:update` مرة بعد التثبيت، ويتحدث تلقائيًا شهريًا عبر المجدول. خلف Cloudflare يمكن ضبط `GEO_COUNTRY_HEADER=CF-IPCountry`. يستطيع الزائر تغيير الدولة يدويًا من الرأس. لتغيير الرقم المعروض لدولة: الإدارة ← الدليل القانوني ← جهات الإبلاغ.

لإدارة الورش والمحاضرات: الإدارة ← المحتوى ← الفعاليات. أضف العنوان والوصف والمواعيد بتوقيت عُمان وطريقة الحضور والمكان والمحاضر وصورة الغلاف وفقرات البرنامج ورابط التسجيل الخارجي اختياريًا. اختر «منشورة» لإظهار الفعالية في `/events`، أو «مسودة» لإخفائها، أو «ملغاة» لعرض إشعار الإلغاء وتعطيل التسجيل. الفعاليات المنتهية تنتقل تلقائيًا إلى الأرشيف.

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
