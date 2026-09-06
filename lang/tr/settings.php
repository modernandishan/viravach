<?php

return [

    'page_title' => 'Ayarlar',

    'selector_hint_label' => 'Şirkete özel ayarlar',
    'selector_hint' => 'Her şirketin kendi web sitesi, SEO eklentisi ve içerik ayarları vardır.',

    'site_section_title' => 'Site ve SEO',

    'seo_plugin_label' => 'SEO eklentisi',
    'seo_plugin_hint' => 'WordPress sitenizde hangi SEO eklentisinin kurulu olduğu. Meta başlığı, açıklamayı ve odak anahtar kelimeyi o eklentinin alanlarına yazarız.',
    'seo_plugin_yoast' => 'Yoast SEO',
    'seo_plugin_yoast_hint' => 'WordPress için en yaygın kullanılan SEO eklentisi.',
    'seo_plugin_rank_math' => 'Rank Math',
    'seo_plugin_rank_math_hint' => 'Yerleşik schema desteği olan daha hafif bir alternatif.',

    'wordpress_section_title' => 'WordPress bağlantısı',

    'app_password_label' => 'WordPress Uygulama Parolası',
    'app_password_hint' => 'Parolayı WordPress’in gösterdiği şekilde, boşluklar dahil yapıştırın.',
    'app_password_placeholder' => 'xxxx xxxx xxxx xxxx xxxx xxxx',

    'app_password_security_title' => 'Şifrelenerek saklanır',
    'app_password_security_body' => 'Parola kaydedilmeden önce şifrelenir ve başka kimseye gösterilmez. Erişimi anında kesmek için WordPress profilinizden istediğiniz zaman iptal edebilirsiniz.',

    'app_password_steps_title' => 'Uygulama Parolası nasıl oluşturulur',
    'app_password_steps_intro' => 'Uygulama Parolası, gerçek WordPress parolanızı hiç bilmeden sitenizde yayın yapmamızı sağlar.',

    'app_password_requirements_title' => 'Başlamadan önce',
    'app_password_requirement_wordpress_version' => 'Siteniz WordPress 5.6 veya daha yenisini çalıştırıyor olmalı — ek eklenti gerekmez.',
    'app_password_requirement_admin_access' => 'O siteye yönetici olarak giriş yapabiliyor olmalısınız.',
    'app_password_requirement_https' => 'Site HTTPS üzerinden sunulmalı; WordPress bu özelliği güvensiz sitelerde gizler.',

    'app_password_step_1_title' => 'WordPress yönetim paneline giriş yapın',
    'app_password_step_1_body' => 'yoursite.com/wp-admin adresini açın ve yönetici hesabınızla giriş yapın.',
    'app_password_step_2_title' => 'Kullanıcılar → Profil bölümüne gidin',
    'app_password_step_2_body' => 'Yan menüden «Kullanıcılar», ardından «Profil» seçin — ya da Kullanıcılar → Tüm Kullanıcılar’dan kendi adınıza tıklayın.',
    'app_password_step_3_title' => 'Application Passwords bölümünü bulun',
    'app_password_step_3_body' => 'Profil sayfasının en altına inin; Uygulama Parolaları kutusu Hesap Yönetimi bölümünün hemen altındadır.',
    'app_password_step_4_title' => 'Bir ad verip «Ekle»ye tıklayın',
    'app_password_step_4_body' => 'Sonradan tanıyacağınız bir ad yazın, örneğin Viravach, sonra Yeni Uygulama Parolası Ekle düğmesine basın.',
    'app_password_step_5_title' => 'Parolayı kopyalayıp buraya yapıştırın',
    'app_password_step_5_body' => 'WordPress 24 karakterlik parolayı yalnızca bir kez gösterir. Kopyalayın, yandaki alana yapıştırın ve kaydedin.',

    'wp_username_label' => 'WordPress kullanıcı adı',
    'wp_username_hint' => 'Uygulama Parolasının oluşturulduğu WordPress hesabının kullanıcı adı — e-posta adresiniz değil.',
    'wp_username_placeholder' => 'admin',

    'wp_test_title' => 'Bağlantıyı test edin',
    'wp_test_hint' => 'Kaydedilmiş ayarları sitenizde dener. Az önce bir şey değiştirdiyseniz önce kaydedin.',
    'wp_test_button' => 'Bağlantıyı test et',
    'wp_testing' => 'Test ediliyor...',

    'wp_status_not_tested' => 'Henüz test edilmedi',
    'wp_status_connected' => 'Bağlandı',
    'wp_status_failed' => 'Bağlantı başarısız',
    'wp_last_checked' => 'Son kontrol: :date',

    'wp_test_unsaved_changes_title' => 'Önce değişikliklerinizi kaydedin',
    'wp_test_unsaved_changes' => 'WordPress ayarlarını değiştirdiniz ama kaydetmediniz. Test her zaman kaydedilmiş değerlerle çalışır; önce kaydedin, sonra bağlantıyı test edin.',

    'wp_test_failed_title' => 'Bağlanamadık',
    'wp_test_failed_incomplete_settings' => 'Site adresini, WordPress kullanıcı adını ve Uygulama Parolasını girin, kaydedin ve tekrar deneyin.',
    'wp_test_failed_not_wordpress' => 'Bu adres yanıt verdi ama bir WordPress sitesi değil — REST API\'si bulunamadı. Adresi kontrol edin ve REST API\'nin bir eklenti tarafından kapatılmadığından emin olun.',
    'wp_test_failed_unreachable' => 'Bu adrese hiç ulaşamadık. Sitenin çevrimiçi olduğundan, alan adının ve HTTPS sertifikasının geçerli olduğundan emin olun.',
    'wp_test_failed_authentication_failed' => 'WordPress kimlik bilgilerini kabul etmedi. Kullanıcı adını kontrol edin ve yeni bir Uygulama Parolası oluşturun — iptal edilen parola anında çalışmaz olur.',
    'wp_test_failed_request_failed' => 'Site WordPress ama beklenmedik bir hatayla yanıt verdi. Birazdan tekrar deneyin; sürerse sitenizin hata kaydına bakın.',

    'wp_test_success_title' => 'Bağlantı başarılı',
    'wp_connected_as' => ':site sitesine :user olarak giriş yapıldı.',
    'wp_recent_posts_title' => 'O sitedeki en son yazılar',
    'wp_no_posts' => 'Bağlantı çalışıyor ama o sitede henüz yayımlanmış yazı yok.',
    'wp_seo_meta_not_writable' => 'Yayımlanan makalenin SEO başlığı ve açıklaması bu sitede ayarlanamadı. SEO eklentinizin bu değerleri kabul etmesi için küçük bir yardımcı eklentiye ihtiyacı var; o zamana kadar WordPress bunları yazı içeriğinden kendisi oluşturacak.',
    'wp_post_untitled' => '(başlıksız)',

    'content_section_title' => 'İçerik üretimi',

    'content_mode_label' => 'Konular nasıl seçilir',
    'content_mode_hint' => 'Bu seçim, bu şirket için üretilecek makalelerin neyle ilgili olacağını belirler.',
    'content_mode_industry' => 'Şirketin sektörüne özel',
    'content_mode_industry_hint' => 'Konular kendi faaliyet alanınızdan ve ürün yelpazenizden seçilir.',
    'content_mode_trending' => 'Google’da yükselen konular',
    'content_mode_trending_hint' => 'Konular, insanların şu anda Google’da aradıklarını takip eder.',

    'content_language_label' => 'İçerik dili',
    'content_language_hint' => 'Bu şirket için üretilen tüm makalelerin yazılacağı dil.',

    'save_button' => 'Ayarları kaydet',
    'saving' => 'Kaydediliyor...',
    'saved_successfully' => 'Ayarlar başarıyla kaydedildi.',

];
