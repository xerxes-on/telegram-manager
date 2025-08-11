<?php

return [
    'resources' => [
        'announcement' => [
            'label' => 'Объявление',
            'plural' => 'Объявления',
        ],
        'client' => [
            'label' => 'Клиент',
            'plural' => 'Клиенты',
        ],
        'subscription' => [
            'label' => 'Подписка',
            'plural' => 'Подписки',
        ],
        'plan' => [
            'label' => 'Тариф',
            'plural' => 'Тарифы',
        ],
        'transaction' => [
            'label' => 'Транзакция',
            'plural' => 'Транзакции',
        ],
        'subscription_transaction' => [
            'label' => 'Транзакция подписки',
            'plural' => 'Транзакции подписок',
        ],
        'card' => [
            'label' => 'Карта',
            'plural' => 'Карты',
        ],
    ],
    
    'fields' => [
        // Common fields
        'id' => 'ID',
        'created_at' => 'Создано',
        'updated_at' => 'Обновлено',
        'status' => 'Статус',
        'active' => 'Активно',
        
        // Client fields
        'first_name' => 'Имя',
        'last_name' => 'Фамилия',
        'telegram_id' => 'Telegram ID',
        'username' => 'Имя пользователя',
        'phone_number' => 'Номер телефона',
        'chat_id' => 'ID чата',
        'lang' => 'Язык',
        'state' => 'Состояние',
        'subscription_count' => 'Подписки',
        'has_active_subscription' => 'Есть активная подписка',
        
        // Subscription fields
        'client' => 'Клиент',
        'client_name' => 'Имя клиента',
        'plan' => 'Тариф',
        'plan_name' => 'Название тарифа',
        'price' => 'Цена',
        'receipt_id' => 'ID чека',
        'expires_at' => 'Истекает',
        'payment_retry_count' => 'Попытки оплаты',
        'last_payment_attempt' => 'Последняя попытка оплаты',
        'last_payment_error' => 'Последняя ошибка оплаты',
        'previous_subscription_id' => 'Предыдущая подписка',
        'is_renewal' => 'Продление',
        'reminder_sent_at' => 'Напоминание отправлено',
        'reminder_count' => 'Отправлено напоминаний',
        'days_until_expiry' => 'Дней до истечения',
        
        // Plan fields
        'name' => 'Название',
        'days' => 'Длительность (дней)',
        
        // Transaction fields
        'paycom_transaction_id' => 'ID транзакции Paycom',
        'amount' => 'Сумма',
        'state' => 'Состояние',
        'order_id' => 'ID заказа',
        'perform_time' => 'Выполнено',
        'cancel_time' => 'Отменено',
        
        // Subscription Transaction fields
        'card_id' => 'Карта',
        'subscription_id' => 'Подписка',
        'transaction_id' => 'ID транзакции',
        'type' => 'Тип',
        'error_message' => 'Сообщение об ошибке',
        
        // Card fields
        'masked_number' => 'Номер карты',
        'token' => 'Токен',
        'expire' => 'Срок действия',
        'verified' => 'Подтверждена',
        'is_main' => 'Основная карта',
    ],
    
    'filters' => [
        'language' => 'Язык',
        'has_subscription' => 'Есть подписка',
        'active_subscription' => 'Активная подписка',
        'active_status' => 'Активный статус',
        'expired' => 'Истекшие',
        'expiring_soon' => 'Истекают скоро (3 дня)',
        'plan' => 'Тариф',
        'state' => 'Состояние',
        'verified' => 'Подтверждена',
        'is_renewal' => 'Только продления',
        'has_reminder' => 'Напоминание отправлено',
        'date_range' => 'Период',
        'type' => 'Тип транзакции',
    ],
    
    'actions' => [
        'view' => 'Просмотр',
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
        'create' => 'Создать',
        'send' => 'Отправить',
        'send_reminder' => 'Отправить напоминание',
        'renew' => 'Продлить',
        'cancel' => 'Отменить',
    ],
    
    'transaction_states' => [
        'created' => 'Создана',
        'performed' => 'Выполнена',
        'cancelled' => 'Отменена',
        'cancelled_after_perform' => 'Отменена после выполнения',
    ],
    
    'transaction_types' => [
        'subscription' => 'Новая подписка',
        'renewal' => 'Продление',
        'plan_change' => 'Смена тарифа',
    ],
    
    'widgets' => [
        'active_subscriptions' => 'Активные подписки',
        'churned_subscriptions' => 'Отмененные подписки',
        'subscription_distribution' => 'Распределение подписок',
        'top_plans' => 'Популярные тарифы',
        'user_stats' => 'Статистика пользователей',
        'new_users_this_month' => 'Новые пользователи за месяц',
        'transaction_stats' => 'Статистика транзакций',
        'revenue_today' => 'Доход за сегодня',
        'revenue_this_month' => 'Доход за месяц',
        'revenue_all_time' => 'Общий доход',
        'renewal_rate' => 'Процент продлений',
        'upcoming_renewals' => 'Предстоящие продления',
    ],
    
    'messages' => [
        'no_active_subscription' => 'Нет активной подписки',
        'subscription_renewed' => 'Подписка успешно продлена',
        'reminder_sent' => 'Напоминание успешно отправлено',
        'subscription_cancelled' => 'Подписка отменена',
        'payment_failed' => 'Ошибка оплаты',
        'card_added' => 'Карта успешно добавлена',
        'card_removed' => 'Карта удалена',
        'card_set_as_main' => 'Карта установлена как основная',
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
            'send_error' => 'Ошибка отправки объявления :id.',
            'send_error_client' => 'Ошибка отправки объявления клиенту :chat_id.',
        ],
    ],
];