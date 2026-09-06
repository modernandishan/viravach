<?php

return [

    'page_title' => 'Настройки',

    'selector_hint_label' => 'Настройки для каждой компании',
    'selector_hint' => 'У каждой компании свой сайт, SEO-плагин и настройки контента.',

    'site_section_title' => 'Сайт и SEO',

    'seo_plugin_label' => 'SEO-плагин',
    'seo_plugin_hint' => 'Какой SEO-плагин установлен на вашем сайте WordPress. Мы записываем meta-заголовок, описание и ключевое слово в поля именно этого плагина.',
    'seo_plugin_yoast' => 'Yoast SEO',
    'seo_plugin_yoast_hint' => 'Самый распространённый SEO-плагин для WordPress.',
    'seo_plugin_rank_math' => 'Rank Math',
    'seo_plugin_rank_math_hint' => 'Более лёгкая альтернатива со встроенной поддержкой schema.',

    'wordpress_section_title' => 'Подключение к WordPress',

    'app_password_label' => 'Пароль приложения WordPress',
    'app_password_hint' => 'Вставьте пароль ровно в том виде, в котором его показывает WordPress, включая пробелы.',
    'app_password_placeholder' => 'xxxx xxxx xxxx xxxx xxxx xxxx',

    'app_password_security_title' => 'Хранится в зашифрованном виде',
    'app_password_security_body' => 'Пароль шифруется перед сохранением и никому не показывается. Вы можете в любой момент отозвать его в своём профиле WordPress, чтобы немедленно прекратить доступ.',

    'app_password_steps_title' => 'Как создать пароль приложения',
    'app_password_steps_intro' => 'Пароль приложения позволяет нам публиковать материалы на вашем сайте, не зная вашего настоящего пароля WordPress.',

    'app_password_requirements_title' => 'Перед началом',
    'app_password_requirement_wordpress_version' => 'На сайте работает WordPress 5.6 или новее — дополнительный плагин не нужен.',
    'app_password_requirement_admin_access' => 'Вы можете войти на этот сайт как администратор.',
    'app_password_requirement_https' => 'Сайт работает по HTTPS: на незащищённых сайтах WordPress скрывает эту возможность.',

    'app_password_step_1_title' => 'Войдите в админку WordPress',
    'app_password_step_1_body' => 'Откройте yoursite.com/wp-admin и войдите под учётной записью администратора.',
    'app_password_step_2_title' => 'Перейдите в Пользователи → Профиль',
    'app_password_step_2_body' => 'В боковом меню выберите «Пользователи», затем «Профиль» — или Пользователи → Все пользователи и нажмите на своё имя.',
    'app_password_step_3_title' => 'Найдите раздел Application Passwords',
    'app_password_step_3_body' => 'Прокрутите страницу профиля вниз: блок паролей приложений находится под разделом управления учётной записью.',
    'app_password_step_4_title' => 'Задайте имя и нажмите «Добавить»',
    'app_password_step_4_body' => 'Введите имя, которое вы узнаете позже, например Viravach, затем нажмите «Добавить новый пароль приложения».',
    'app_password_step_5_title' => 'Скопируйте пароль и вставьте его здесь',
    'app_password_step_5_body' => 'WordPress показывает 24-символьный пароль только один раз. Скопируйте его, вставьте в поле рядом и сохраните.',

    'wp_username_label' => 'Имя пользователя WordPress',
    'wp_username_hint' => 'Логин той учётной записи WordPress, для которой создан пароль приложения, — не адрес электронной почты.',
    'wp_username_placeholder' => 'admin',

    'wp_test_title' => 'Проверка подключения',
    'wp_test_hint' => 'Проверяет сохранённые настройки на вашем сайте. Если вы только что что-то изменили, сначала сохраните.',
    'wp_test_button' => 'Проверить подключение',
    'wp_testing' => 'Проверяем...',

    'wp_status_not_tested' => 'Ещё не проверялось',
    'wp_status_connected' => 'Подключено',
    'wp_status_failed' => 'Не удалось подключиться',
    'wp_last_checked' => 'Последняя проверка: :date',

    'wp_test_unsaved_changes_title' => 'Сначала сохраните изменения',
    'wp_test_unsaved_changes' => 'Вы изменили настройки WordPress, но не сохранили их. Проверка всегда выполняется по сохранённым значениям, поэтому сначала сохраните, а затем проверяйте подключение.',

    'wp_test_failed_title' => 'Подключиться не удалось',
    'wp_test_failed_incomplete_settings' => 'Укажите адрес сайта, имя пользователя WordPress и пароль приложения, сохраните и попробуйте снова.',
    'wp_test_failed_not_wordpress' => 'Этот адрес ответил, но это не сайт на WordPress — его REST API не найден. Проверьте адрес и убедитесь, что REST API не отключён плагином.',
    'wp_test_failed_unreachable' => 'Мы вообще не смогли достучаться до этого адреса. Проверьте, что сайт доступен, а домен и сертификат HTTPS действительны.',
    'wp_test_failed_authentication_failed' => 'WordPress отклонил учётные данные. Проверьте имя пользователя и создайте новый пароль приложения — отозванный перестаёт работать сразу.',
    'wp_test_failed_request_failed' => 'Сайт работает на WordPress, но ответил неожиданной ошибкой. Повторите попытку позже, а если ошибка повторяется — посмотрите журнал ошибок сайта.',

    'wp_test_success_title' => 'Подключение установлено',
    'wp_connected_as' => 'Вход выполнен как :user на :site.',
    'wp_recent_posts_title' => 'Последние записи этого сайта',
    'wp_no_posts' => 'Подключение работает, но на этом сайте пока нет опубликованных записей.',
    'wp_seo_meta_not_writable' => 'Не удалось задать SEO-заголовок и описание опубликованной статьи на этом сайте. Вашему SEO-плагину нужен небольшой вспомогательный плагин, чтобы принимать эти значения; до тех пор WordPress будет формировать их сам из содержимого записи.',
    'wp_post_untitled' => '(без заголовка)',

    'content_section_title' => 'Генерация контента',

    'content_mode_label' => 'Как выбираются темы',
    'content_mode_hint' => 'Этот выбор определяет, о чём будут статьи, создаваемые для этой компании.',
    'content_mode_industry' => 'Специализированно по отрасли компании',
    'content_mode_industry_hint' => 'Темы берутся из вашей сферы деятельности и ассортимента продукции.',
    'content_mode_trending' => 'Популярные темы Google',
    'content_mode_trending_hint' => 'Темы следуют за тем, что люди сейчас ищут в Google.',

    'content_language_label' => 'Язык контента',
    'content_language_hint' => 'Язык, на котором пишутся все сгенерированные статьи для этой компании.',

    'save_button' => 'Сохранить настройки',
    'saving' => 'Сохранение...',
    'saved_successfully' => 'Настройки успешно сохранены.',

];
