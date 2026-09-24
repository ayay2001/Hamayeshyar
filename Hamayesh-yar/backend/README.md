# Backend همایش‌یار — PHP + MySQL

## نصب
1. کل پوشه پروژه را داخل `htdocs` قرار دهید.
2. در phpMyAdmin دیتابیس `hamayesh_yar` را بسازید.
3. فایل `database/schema.sql` را اجرا کنید.
4. در صورت نیاز برای حساب‌های تست، `database/seed_demo.sql` را اجرا کنید. رمز هر سه حساب `123` است.
5. مشخصات دیتابیس را در `config/database.php` تنظیم کنید.
6. پروژه را از طریق Apache/PHP باز کنید؛ اجرای مستقیم HTML با Live Server برای API و Session مناسب نیست.

## API احراز هویت
- `POST backend/api/auth/register.php`
- `POST backend/api/auth/login.php`
- `GET backend/api/auth/me.php`
- `POST backend/api/auth/logout.php`

## امنیت
- رمزها با `password_hash()` ذخیره می‌شوند.
- ورود با Session سمت PHP انجام می‌شود.
- نقش عمومی ثبت‌نام فقط `author` است.
- حساب `reviewer` و `secretariat` باید توسط دبیرخانه/مدیر ایجاد شود.

## API مقالات
- `GET backend/api/articles/list.php` — لیست مقالات نویسنده جاری
- `POST multipart/form-data backend/api/articles/submit.php` — ثبت مقاله جدید (`title`, `abstract`, `keywords`, `file`, اختیاری: `event_id`)
- `POST multipart/form-data backend/api/articles/revision.php` — ارسال اصلاحیه (`article_id`, `file`)
- اندازه فایل PDF: حداکثر ۵ مگابایت

## مدیریت داورها

- `GET api/admin/reviewers.php` — فهرست داوران فعال/غیرفعال (نیازمند نقش دبیرخانه یا مدیر)
- `POST api/admin/reviewers.php` — ایجاد حساب داور با رمز عبور هش‌شده
- `PATCH api/admin/reviewers.php` — تغییر وضعیت داور به `active`، `inactive` یا `blocked`

ثبت داور عمومی نیست و فقط از پنل دبیرخانه/مدیر انجام می‌شود.

### Events API (مرحله ۶)
- `GET backend/api/events.php` — همایش‌های عمومی منتشرشده
- `GET backend/api/events.php?id=1` — جزئیات یک همایش
- `GET backend/api/events.php?admin=1` — فهرست مدیریتی (secretariat/admin)
- `POST backend/api/events.php` — ایجاد همایش
- `PATCH backend/api/events.php` — ویرایش همایش با `id`
- `DELETE backend/api/events.php` — حذف همایش فقط وقتی وابستگی ندارد
- `events-admin.html` — پنل مدیریت همایش‌ها
- `database/seed_events.sql` — داده‌های نمونه همایش‌ها


## مرحله ۷ — ثبت‌نام همایش‌ها
برای دیتابیس موجود، یک‌بار `database/migration_step7.sql` را اجرا کنید.
جدول `registrations` اکنون اطلاعات سازمان و توضیحات را نیز نگه می‌دارد.
API ثبت‌نام در `api/registrations.php` قرار دارد و از `GET/POST/PATCH` پشتیبانی می‌کند.
ثبت‌نام فعال فقط برای همایش‌های `upcoming` و `ongoing` انجام می‌شود و ظرفیت و ثبت‌نام تکراری در سمت سرور کنترل می‌شوند.

## مرحله ۹ — نظرسنجی و گزارش‌ها
- `GET backend/api/surveys.php` — فهرست نظرسنجی‌های فعال
- `POST backend/api/surveys.php` — ثبت پاسخ نظرسنجی؛ ثبت تکراری با یک ایمیل برای همان نظرسنجی رد می‌شود
- `GET backend/api/surveys.php?action=stats` — آمار نظرسنجی برای دبیرخانه/مدیر
- `PATCH backend/api/surveys.php` — فعال/غیرفعال‌سازی یا ویرایش نظرسنجی توسط دبیرخانه/مدیر
- `GET backend/api/admin/reports.php` — گزارش مقالات، ثبت‌نام‌ها و نظرسنجی‌ها با فیلتر همایش
- اجرای یک‌باره `database/migration_step9.sql` برای دیتابیس‌های مرحله قبل
- `survey.html` اکنون پاسخ‌ها را واقعاً در MySQL ثبت می‌کند.
- `reports.html` اکنون آمار را از API می‌گیرد و دکمه خروجی، گزارش چاپی مرورگر را باز می‌کند.

## Step 10 — Schedule Management
- Run `database/migration_step10.sql` once on an existing database.
- Public timetable: `schedule.html?event_id=EVENT_ID`.
- Secretariat/admin timetable manager: `schedule-admin.html`.
- CRUD endpoint: `api/schedules.php`.
