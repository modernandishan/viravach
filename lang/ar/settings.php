<?php

return [

    'page_title' => 'الإعدادات',

    'selector_hint_label' => 'إعدادات خاصة بكل شركة',
    'selector_hint' => 'لكل شركة موقعها الإلكتروني وإضافة السيو وإعدادات المحتوى الخاصة بها.',

    'site_section_title' => 'الموقع والسيو',

    'seo_plugin_label' => 'إضافة السيو',
    'seo_plugin_hint' => 'إضافة السيو المثبتة على موقع ووردبريس الخاص بك. نكتب عنوان الميتا والوصف والكلمة المفتاحية في حقول تلك الإضافة.',
    'seo_plugin_yoast' => 'Yoast SEO',
    'seo_plugin_yoast_hint' => 'إضافة السيو الأكثر استخدامًا في ووردبريس.',
    'seo_plugin_rank_math' => 'Rank Math',
    'seo_plugin_rank_math_hint' => 'بديل أخف مع دعم مدمج للـ Schema.',

    'wordpress_section_title' => 'الاتصال بووردبريس',

    'app_password_label' => 'كلمة مرور التطبيق في ووردبريس',
    'app_password_hint' => 'الصق كلمة المرور تمامًا كما يعرضها ووردبريس، بما في ذلك المسافات.',
    'app_password_placeholder' => 'xxxx xxxx xxxx xxxx xxxx xxxx',

    'app_password_security_title' => 'تُحفظ مشفَّرة',
    'app_password_security_body' => 'تُشفَّر كلمة المرور قبل حفظها ولا تُعرض لأي شخص آخر. يمكنك إبطالها من ملفك الشخصي في ووردبريس في أي وقت لقطع الوصول فورًا.',

    'app_password_steps_title' => 'كيفية إنشاء كلمة مرور تطبيق',
    'app_password_steps_intro' => 'تتيح لنا كلمة مرور التطبيق النشر على موقعك دون معرفة كلمة مرور ووردبريس الحقيقية.',

    'app_password_requirements_title' => 'قبل أن تبدأ',
    'app_password_requirement_wordpress_version' => 'أن يعمل موقعك بووردبريس 5.6 أو أحدث — لا حاجة لأي إضافة.',
    'app_password_requirement_admin_access' => 'أن تتمكن من تسجيل الدخول إلى الموقع بصلاحية مدير.',
    'app_password_requirement_https' => 'أن يعمل الموقع عبر HTTPS؛ فووردبريس يخفي هذه الميزة على المواقع غير الآمنة.',

    'app_password_step_1_title' => 'سجّل الدخول إلى لوحة تحكم ووردبريس',
    'app_password_step_1_body' => 'افتح yoursite.com/wp-admin وسجّل الدخول بحساب المدير.',
    'app_password_step_2_title' => 'اذهب إلى الأعضاء ← ملفي الشخصي',
    'app_password_step_2_body' => 'من القائمة الجانبية اختر «الأعضاء» ثم «ملفي الشخصي» — أو الأعضاء ← كل الأعضاء واضغط على اسمك.',
    'app_password_step_3_title' => 'ابحث عن قسم Application Passwords',
    'app_password_step_3_body' => 'انزل إلى أسفل صفحة الملف الشخصي، حيث يوجد صندوق كلمات مرور التطبيقات أسفل قسم إدارة الحساب.',
    'app_password_step_4_title' => 'اختر اسمًا واضغط «إضافة»',
    'app_password_step_4_body' => 'اكتب اسمًا تتعرف عليه لاحقًا، مثل Viravach، ثم اضغط زر إضافة كلمة مرور تطبيق جديدة.',
    'app_password_step_5_title' => 'انسخ كلمة المرور والصقها هنا',
    'app_password_step_5_body' => 'يعرض ووردبريس كلمة المرور المكوّنة من 24 حرفًا مرة واحدة فقط. انسخها والصقها في الحقل المقابل ثم احفظ.',

    'wp_username_label' => 'اسم المستخدم في ووردبريس',
    'wp_username_hint' => 'اسم دخول حساب ووردبريس الذي أُنشئت له كلمة مرور التطبيق — وليس بريدك الإلكتروني.',
    'wp_username_placeholder' => 'admin',

    'wp_test_title' => 'اختبار الاتصال',
    'wp_test_hint' => 'يفحص الإعدادات المحفوظة مقابل موقعك. احفظ أولاً إن كنت قد غيّرت شيئاً للتو.',
    'wp_test_button' => 'اختبار الاتصال',
    'wp_testing' => 'جارٍ الاختبار...',

    'wp_status_not_tested' => 'لم يُختبر بعد',
    'wp_status_connected' => 'متصل',
    'wp_status_failed' => 'فشل الاتصال',
    'wp_last_checked' => 'آخر فحص: :date',

    'wp_test_unsaved_changes_title' => 'احفظ تغييراتك أولاً',
    'wp_test_unsaved_changes' => 'لقد عدّلت إعدادات ووردبريس دون حفظها. يجري الاختبار دائماً على القيم المحفوظة، لذا احفظ أولاً ثم اختبر الاتصال.',

    'wp_test_failed_title' => 'تعذّر الاتصال',
    'wp_test_failed_incomplete_settings' => 'أدخل عنوان الموقع واسم المستخدم في ووردبريس وكلمة مرور التطبيق، ثم احفظ وحاول مرة أخرى.',
    'wp_test_failed_not_wordpress' => 'استجاب ذلك العنوان، لكنه ليس موقع ووردبريس — لم يُعثر على واجهة REST الخاصة به. تحقق من العنوان وتأكد من أن أي إضافة لم تعطّل واجهة REST.',
    'wp_test_failed_unreachable' => 'لم نتمكن من الوصول إلى ذلك العنوان إطلاقاً. تأكد من أن الموقع يعمل وأن نطاقه وشهادة HTTPS صالحان.',
    'wp_test_failed_authentication_failed' => 'رفض ووردبريس بيانات الاعتماد. تحقق من اسم المستخدم وأنشئ كلمة مرور تطبيق جديدة — الكلمة الملغاة تتوقف عن العمل فوراً.',
    'wp_test_failed_request_failed' => 'الموقع يعمل بووردبريس، لكنه ردّ بخطأ غير متوقع. أعد المحاولة بعد قليل، وإن استمر فراجع سجل أخطاء موقعك.',

    'wp_test_success_title' => 'تم الاتصال بنجاح',
    'wp_connected_as' => 'تم تسجيل الدخول باسم :user على :site.',
    'wp_recent_posts_title' => 'أحدث المقالات على ذلك الموقع',
    'wp_no_posts' => 'الاتصال يعمل، لكن لا توجد مقالات منشورة على ذلك الموقع بعد.',
    'wp_seo_meta_not_writable' => 'تعذّر ضبط عنوان ووصف السئو الخاصين بالمقال المنشور على هذا الموقع. تحتاج إضافة السئو لديك إلى إضافة مساعدة صغيرة لتقبل هذه القيم؛ وإلى أن يتم ذلك سيولّد ووردبريس هذه القيم بنفسه من محتوى المقال.',
    'wp_post_untitled' => '(بدون عنوان)',

    'content_section_title' => 'توليد المحتوى',

    'content_mode_label' => 'طريقة اختيار المواضيع',
    'content_mode_hint' => 'يحدد هذا الخيار موضوعات المقالات التي تُولَّد لهذه الشركة.',
    'content_mode_industry' => 'متخصص في مجال عمل الشركة',
    'content_mode_industry_hint' => 'تُختار المواضيع من مجال عملك وتشكيلة منتجاتك.',
    'content_mode_trending' => 'المواضيع الرائجة في جوجل',
    'content_mode_trending_hint' => 'تتبع المواضيع ما يبحث عنه الناس حاليًا في جوجل.',

    'content_language_label' => 'لغة المحتوى',
    'content_language_hint' => 'اللغة التي تُكتب بها جميع المقالات المُنشأة لهذه الشركة.',

    'save_button' => 'حفظ الإعدادات',
    'saving' => 'جارٍ الحفظ...',
    'saved_successfully' => 'تم حفظ الإعدادات بنجاح.',

];
