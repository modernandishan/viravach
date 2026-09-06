<?php

return [

    'page_title' => 'تنظیمات',

    'selector_hint_label' => 'تنظیمات هر شرکت',
    'selector_hint' => 'هر شرکت وب‌سایت، افزونه سئو و تنظیمات محتوای مخصوص خود را دارد.',

    'site_section_title' => 'وب‌سایت و سئو',

    'seo_plugin_label' => 'افزونه سئو',
    'seo_plugin_hint' => 'افزونه سئویی که روی سایت وردپرسی شما نصب است. عنوان، توضیحات متا و کلمه کلیدی در فیلدهای همان افزونه نوشته می‌شود.',
    'seo_plugin_yoast' => 'Yoast SEO',
    'seo_plugin_yoast_hint' => 'پرکاربردترین افزونه سئو برای وردپرس.',
    'seo_plugin_rank_math' => 'Rank Math',
    'seo_plugin_rank_math_hint' => 'گزینه‌ای سبک‌تر با پشتیبانی داخلی از اسکیما.',

    'wordpress_section_title' => 'اتصال به وردپرس',

    'app_password_label' => 'رمز کاربردی وردپرس (Application Password)',
    'app_password_hint' => 'رمز را دقیقاً همان‌طور که وردپرس نمایش می‌دهد، همراه با فاصله‌ها، وارد کنید.',
    'app_password_placeholder' => 'xxxx xxxx xxxx xxxx xxxx xxxx',

    'app_password_security_title' => 'به‌صورت رمزنگاری‌شده ذخیره می‌شود',
    'app_password_security_body' => 'این رمز پیش از ذخیره رمزنگاری می‌شود و به هیچ‌کس نمایش داده نمی‌شود. هر زمان بخواهید می‌توانید آن را از پروفایل وردپرس خود باطل کنید تا دسترسی فوراً قطع شود.',

    'app_password_steps_title' => 'چگونه یک رمز کاربردی بسازیم',
    'app_password_steps_intro' => 'رمز کاربردی به ما اجازه می‌دهد روی سایت شما منتشر کنیم، بدون آنکه رمز اصلی وردپرس شما را بدانیم.',

    'app_password_requirements_title' => 'پیش از شروع',
    'app_password_requirement_wordpress_version' => 'سایت شما وردپرس ۵.۶ یا بالاتر باشد — به هیچ افزونه‌ای نیاز نیست.',
    'app_password_requirement_admin_access' => 'بتوانید با نقش مدیر کل وارد آن سایت شوید.',
    'app_password_requirement_https' => 'سایت روی HTTPS اجرا شود؛ وردپرس این قابلیت را در سایت‌های ناامن پنهان می‌کند.',

    'app_password_step_1_title' => 'وارد پیشخوان وردپرس شوید',
    'app_password_step_1_body' => 'آدرس yoursite.com/wp-admin را باز کنید و با حساب مدیر وارد شوید.',
    'app_password_step_2_title' => 'به کاربران ← شناسنامه بروید',
    'app_password_step_2_body' => 'از منوی کناری «کاربران» و سپس «شناسنامه» را انتخاب کنید — یا کاربران ← همه کاربران و روی نام خودتان کلیک کنید.',
    'app_password_step_3_title' => 'بخش Application Passwords را پیدا کنید',
    'app_password_step_3_body' => 'تا پایین صفحه شناسنامه اسکرول کنید؛ جعبه رمزهای کاربردی درست زیر بخش مدیریت حساب قرار دارد.',
    'app_password_step_4_title' => 'یک نام بگذارید و «افزودن» را بزنید',
    'app_password_step_4_body' => 'نامی بنویسید که بعداً آن را بشناسید، مثلاً Viravach، سپس دکمه افزودن رمز کاربردی جدید را بزنید.',
    'app_password_step_5_title' => 'رمز را کپی و اینجا وارد کنید',
    'app_password_step_5_body' => 'وردپرس این رمز ۲۴ کاراکتری را فقط یک بار نشان می‌دهد. آن را کپی کنید، در فیلد سمت مقابل بگذارید و ذخیره کنید.',

    'wp_username_label' => 'نام کاربری وردپرس',
    'wp_username_hint' => 'نام کاربری همان حساب وردپرسی که رمز کاربردی برای آن ساخته شده است — نه نشانی ایمیل شما.',
    'wp_username_placeholder' => 'admin',

    'wp_test_title' => 'آزمایش اتصال',
    'wp_test_hint' => 'تنظیمات ذخیره‌شده را روی سایت شما بررسی می‌کند. اگر چیزی را تغییر داده‌اید، ابتدا ذخیره کنید.',
    'wp_test_button' => 'آزمایش اتصال',
    'wp_testing' => 'در حال آزمایش...',

    'wp_status_not_tested' => 'هنوز آزمایش نشده',
    'wp_status_connected' => 'متصل',
    'wp_status_failed' => 'اتصال ناموفق',
    'wp_last_checked' => 'آخرین بررسی: :date',

    'wp_test_unsaved_changes_title' => 'ابتدا تغییرات را ذخیره کنید',
    'wp_test_unsaved_changes' => 'تنظیمات وردپرس را تغییر داده‌اید اما ذخیره نکرده‌اید. آزمایش همیشه روی مقادیر ذخیره‌شده انجام می‌شود؛ ابتدا ذخیره کنید و سپس اتصال را بیازمایید.',

    'wp_test_failed_title' => 'اتصال برقرار نشد',
    'wp_test_failed_incomplete_settings' => 'نشانی سایت، نام کاربری وردپرس و رمز کاربردی را وارد کنید، ذخیره کنید و دوباره تلاش کنید.',
    'wp_test_failed_not_wordpress' => 'آن نشانی پاسخ داد، اما سایت وردپرسی نیست — REST API آن پیدا نشد. نشانی را بررسی کنید و مطمئن شوید افزونه‌ای REST API را غیرفعال نکرده باشد.',
    'wp_test_failed_unreachable' => 'اصلاً نتوانستیم به آن نشانی دسترسی پیدا کنیم. بررسی کنید سایت در دسترس باشد و دامنه و گواهی HTTPS آن معتبر باشد.',
    'wp_test_failed_authentication_failed' => 'وردپرس این اطلاعات ورود را نپذیرفت. نام کاربری را بررسی کنید و یک رمز کاربردی تازه بسازید — رمز باطل‌شده بلافاصله از کار می‌افتد.',
    'wp_test_failed_request_failed' => 'سایت وردپرسی است، اما با خطای غیرمنتظره‌ای پاسخ داد. کمی بعد دوباره تلاش کنید؛ اگر ادامه داشت، گزارش خطای سایت خود را بررسی کنید.',

    'wp_test_success_title' => 'اتصال با موفقیت برقرار شد',
    'wp_connected_as' => 'با کاربر :user در :site وارد شدیم.',
    'wp_recent_posts_title' => 'آخرین نوشته‌های آن سایت',
    'wp_no_posts' => 'اتصال درست کار می‌کند، اما هنوز نوشته منتشرشده‌ای در آن سایت نیست.',
    'wp_seo_meta_not_writable' => 'عنوان و توضیحات سئوی مقاله منتشرشده روی این سایت قابل تنظیم نبود. افزونه سئوی شما برای پذیرفتن این مقادیر به یک افزونه کمکی کوچک نیاز دارد؛ تا آن زمان وردپرس این مقادیر را خودش از محتوای مطلب می‌سازد.',
    'wp_post_untitled' => '(بدون عنوان)',

    'content_section_title' => 'تولید محتوا',

    'content_mode_label' => 'شیوه انتخاب موضوع',
    'content_mode_hint' => 'این گزینه تعیین می‌کند مقاله‌های تولیدشده برای این شرکت درباره چه چیزی باشند.',
    'content_mode_industry' => 'تخصصی در حوزه فعالیت شرکت',
    'content_mode_industry_hint' => 'موضوع‌ها از حوزه کاری و سبد محصولات خودتان انتخاب می‌شوند.',
    'content_mode_trending' => 'موضوعات پرجستجوی گوگل',
    'content_mode_trending_hint' => 'موضوع‌ها بر اساس آنچه مردم هم‌اکنون در گوگل جستجو می‌کنند انتخاب می‌شوند.',

    'content_language_label' => 'زبان محتوا',
    'content_language_hint' => 'زبانی که همه مقاله‌های تولیدشده برای این شرکت با آن نوشته می‌شوند.',

    'intro_video_section_title' => 'ویدئوی معرفی',
    'intro_video_label' => 'ویدئوی معرفی',
    'intro_video_hint' => 'MP4، WebM، MOV، AVI یا MKV — حداکثر ۲۵۶ مگابایت و ۱۰ دقیقه.',
    'intro_video_upsell' => 'کسب‌وکارتان را با ویدئو معرفی کنید. قابلیت ویدئوی معرفی در پلن Pro فعال می‌شود.',
    'intro_video_upsell_cta' => 'مشاهده پلن‌ها',
    'intro_video_remove' => 'حذف ویدئو',
    'intro_video_save_button' => 'بارگذاری ویدئو',
    'intro_video_saving' => 'در حال بارگذاری...',
    'intro_video_saved' => 'ویدئوی معرفی با موفقیت بارگذاری شد.',
    'intro_video_removed' => 'ویدئوی معرفی حذف شد.',
    'intro_video_ineligible' => 'قابلیت ویدئوی معرفی در پلن فعلی این شرکت فعال نیست.',
    'intro_video_validation_duration' => 'طول ویدئو باید حداکثر ۱۰ دقیقه باشد.',

    'save_button' => 'ذخیره تنظیمات',
    'saving' => 'در حال ذخیره...',
    'saved_successfully' => 'تنظیمات با موفقیت ذخیره شد.',

];
