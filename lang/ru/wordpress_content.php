<?php

return [

    'page_title' => 'Контент для WordPress',

    'schedule_section_title' => 'Автоматические статьи',

    'guard_not_connected' => 'Подключение к WordPress ещё не подтверждено.',
    'guard_go_to_settings' => 'Перейдите в настройки, чтобы проверить подключение',
    'guard_generation_disabled' => 'Генерация контента сейчас отключена. Повторите попытку позже.',
    'guard_quota_exhausted' => 'Лимит на этот месяц исчерпан.',
    'guard_already_running' => 'Для этой компании уже создаётся статья.',
    'guard_no_topic_available' => 'Не удалось найти популярную тему. Повторите попытку позже.',

    'quota_used' => 'Использовано :used из :limit в этом месяце',
    'quota_resets_at' => 'Обновится :date',

    'schedule_in_progress' => 'Прямо сейчас создаётся статья.',
    'schedule_quota_reached' => 'Лимит этого месяца исчерпан. Обновится :date.',
    'schedule_next_at' => 'Следующая статья запланирована на :date.',
    'schedule_tonight' => 'Следующая статья выйдет с ночным запуском по расписанию.',

    'history_title' => 'Созданные статьи',
    'history_empty' => 'Пока не создано ни одной статьи.',

    'status_queued' => 'В очереди',
    'status_generating' => 'Создаётся',
    'status_published' => 'Опубликовано',
    'status_failed' => 'Ошибка',

    'publish_failed_incomplete_settings' => 'Настройки WordPress заполнены не полностью.',
    'publish_failed_unreachable' => 'В момент публикации сайт был недоступен.',
    'publish_failed_authentication_failed' => 'WordPress отклонил сохранённые учётные данные.',
    'publish_failed_not_permitted' => 'Учётной записи WordPress не разрешено публиковать записи или загружать файлы.',
    'publish_failed_media_upload_failed' => 'Не удалось загрузить изображение записи в WordPress.',
    'publish_failed_post_creation_failed' => 'WordPress отказался создать запись.',

    'trend_failed_not_configured' => 'Популярные темы недоступны на этой установке.',
    'trend_failed_no_seed_topic' => 'Чтобы найти популярную тему, у компании должна быть категория или название.',
    'trend_failed_request_failed' => 'Не удалось обратиться к сервису популярных тем.',
    'trend_failed_no_candidates' => 'Сейчас не найдено популярных тем в сфере деятельности этой компании.',

];
