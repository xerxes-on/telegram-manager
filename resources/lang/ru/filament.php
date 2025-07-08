<?php

return [
    'resources' => [
        'announcement' => [
            'label' => 'Объявление',
            'plural' => 'Объявления',
        ],
    ],
    'announcement' => [
        'title' => 'Заголовок',
        'message' => 'Сообщение',
        'has_attachment' => 'Есть вложение',
        'file_path' => 'Путь к файлу',
        'status' => 'Статус',
        'user_id' => 'Пользователь',
        'created_at' => 'Создано',
        'updated_at' => 'Обновлено',
        'messages' => [
            'created' => 'Объявление успешно создано.',
            'attachment' => 'Вложение',
            'send_success' => 'Объявление :id успешно отправлено.',
            'send_error' => 'Не удалось отправить объявление :id.',
            'send_error_client' => 'Не удалось отправить объявление клиенту :chat_id.',
        ],
    ],
]; 