<?php

return [

    'page_title' => 'Settings',

    'selector_hint_label' => 'Per-company settings',
    'selector_hint' => 'Each company has its own site, SEO plugin and content settings.',

    'site_section_title' => 'Site & SEO',

    'seo_plugin_label' => 'SEO plugin',
    'seo_plugin_hint' => 'Which SEO plugin is installed on your WordPress site. We write the meta title, description and focus keyword into that plugin\'s fields.',
    'seo_plugin_yoast' => 'Yoast SEO',
    'seo_plugin_yoast_hint' => 'The most widely used SEO plugin for WordPress.',
    'seo_plugin_rank_math' => 'Rank Math',
    'seo_plugin_rank_math_hint' => 'A lighter alternative with built-in schema support.',

    'wordpress_section_title' => 'WordPress connection',

    'app_password_label' => 'WordPress Application Password',
    'app_password_hint' => 'Paste the password exactly as WordPress shows it, spaces included.',
    'app_password_placeholder' => 'xxxx xxxx xxxx xxxx xxxx xxxx',

    'app_password_security_title' => 'Stored encrypted',
    'app_password_security_body' => 'The password is encrypted before it is saved and is never shown to anyone else. Revoke it from your WordPress profile at any time to cut off access immediately.',

    'app_password_steps_title' => 'How to generate an Application Password',
    'app_password_steps_intro' => 'An Application Password lets us publish to your site without ever knowing your real WordPress password.',

    'app_password_requirements_title' => 'Before you start',
    'app_password_requirement_wordpress_version' => 'Your site runs WordPress 5.6 or newer — no extra plugin is required.',
    'app_password_requirement_admin_access' => 'You can sign in to that site as an administrator.',
    'app_password_requirement_https' => 'The site is served over HTTPS; WordPress hides this feature on insecure sites.',

    'app_password_step_1_title' => 'Sign in to your WordPress admin',
    'app_password_step_1_body' => 'Open yoursite.com/wp-admin and log in with your administrator account.',
    'app_password_step_2_title' => 'Go to Users → Profile',
    'app_password_step_2_body' => 'In the sidebar choose Users, then Profile — or Users → All Users and click your own name.',
    'app_password_step_3_title' => 'Find the Application Passwords section',
    'app_password_step_3_body' => 'Scroll to the bottom of the profile page, where the Application Passwords box sits below Account Management.',
    'app_password_step_4_title' => 'Name it and click Add New',
    'app_password_step_4_body' => 'Type a name you will recognise later, for example Viravach, then press Add New Application Password.',
    'app_password_step_5_title' => 'Copy the password and paste it here',
    'app_password_step_5_body' => 'WordPress shows the 24-character password only once. Copy it, paste it into the field on the left, and save.',

    'wp_username_label' => 'WordPress username',
    'wp_username_hint' => 'The login of the WordPress account the Application Password was generated for — not your email address.',
    'wp_username_placeholder' => 'admin',

    'wp_test_title' => 'Test the connection',
    'wp_test_hint' => 'Checks the saved settings against your site. Save first if you just changed something.',
    'wp_test_button' => 'Test connection',
    'wp_testing' => 'Testing...',

    'wp_status_not_tested' => 'Not tested yet',
    'wp_status_connected' => 'Connected',
    'wp_status_failed' => 'Connection failed',
    'wp_last_checked' => 'Last checked :date',

    'wp_test_unsaved_changes_title' => 'Save your changes first',
    'wp_test_unsaved_changes' => 'You have edited the WordPress settings without saving them. The test always runs against the saved values, so save first and then test the connection.',

    'wp_test_failed_title' => 'We could not connect',
    'wp_test_failed_incomplete_settings' => 'Fill in the site address, the WordPress username and the Application Password, save, and try again.',
    'wp_test_failed_not_wordpress' => 'That address answered, but it is not a WordPress site — its REST API was not found. Check the address, and make sure the REST API is not disabled by a plugin.',
    'wp_test_failed_unreachable' => 'We could not reach that address at all. Check that the site is online and that its domain and HTTPS certificate are valid.',
    'wp_test_failed_authentication_failed' => 'WordPress refused the credentials. Check the username and generate a fresh Application Password — a revoked one stops working immediately.',
    'wp_test_failed_request_failed' => 'The site is WordPress, but it answered with an unexpected error. Try again in a moment; if it persists, check your site\'s error log.',

    'wp_test_success_title' => 'Connected successfully',
    'wp_connected_as' => 'Signed in as :user on :site.',
    'wp_recent_posts_title' => 'Latest posts on that site',
    'wp_no_posts' => 'The connection works, but that site has no published posts yet.',
    'wp_seo_meta_not_writable' => 'A published article\'s SEO title and description could not be set on this site. Your SEO plugin needs a small helper plugin installed to accept them — until then, WordPress will generate its own from the post content.',
    'wp_post_untitled' => '(no title)',

    'content_section_title' => 'Content generation',

    'content_mode_label' => 'How topics are chosen',
    'content_mode_hint' => 'This decides what the generated articles for this company are about.',
    'content_mode_industry' => 'Specialised to the company\'s industry',
    'content_mode_industry_hint' => 'Topics are drawn from your own field of business and product range.',
    'content_mode_trending' => 'Trending Google topics',
    'content_mode_trending_hint' => 'Topics follow what people are currently searching for on Google.',

    'content_language_label' => 'Content language',
    'content_language_hint' => 'The language every generated article for this company is written in.',

    'save_button' => 'Save settings',
    'saving' => 'Saving...',
    'saved_successfully' => 'Settings saved successfully.',

];
