<?php

return [
    'resources' => [
        'announcement' => [
            'label' => 'Announcement',
            'plural' => 'Announcements',
        ],
    ],
    'announcement' => [
        'title' => 'Title',
        'message' => 'Message',
        'has_attachment' => 'Has Attachment',
        'file_path' => 'File Path',
        'status' => 'Status',
        'user_id' => 'User',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'messages' => [
            'created' => 'Announcement created successfully.',
            'attachment' => 'Attachment',
            'send_success' => 'Announcement :id sent successfully.',
            'send_error' => 'Failed to send announcement :id.',
            'send_error_client' => 'Failed to send announcement to client :chat_id.',
        ],
    ],
]; 