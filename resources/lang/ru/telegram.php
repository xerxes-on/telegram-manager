<?php

return [
    'welcome' => 'Здравствуйте! Добро пожаловать в бот!',
    'ask_phone' => 'Пожалуйста, отправьте свой номер телефона',
    'send_phone_button' => 'Отправить номер телефона',
    'welcome_message' => "Здравствуйте! Добро пожаловать в наш новый проект \"Anvar Abduqayum Full Contact\"!\n\n" .
        "Я постараюсь уделить этому каналу особое время и подойти к нему с удовольствием!\n\n" .
        "В этом канале:\n" .
        "1. Только полезный контент.\n" .
        "Месячная подписка 500 000 сум.",
    'payment_button' => '💳 Оплата',
    'subscription_status_button' => '📋 Статус подписки',
    'help_button' => '🆘 Помощь',
    'misunderstanding' => '🤷‍ Извините, я этого не понял.',
    'thank_you_data_saved' => "Спасибо! Ваши данные сохранены. 🎉",
    'free_plan_used' => "Вы уже использовали бесплатную подписку 🙃",
    'subscription_confirmation' => "Подписка: :plan_name\nЦена: :plan_price\nКарта: :card_number",
    'confirm_button' => '✅ Подтвердить',
    'change_card_button' => "♻︎ Изменить карту",
    'back_button' => "🧐 Назад",
    'subscription_already_active' => "У вас уже есть активная подписка!",
    'command_not_understood' => "🤷‍ Извините, я не понял эту команду.",
    'phone_saved' => 'Номер телефона сохранен.',
    'support_text' => "🙌 Для поддержки, пожалуйста, свяжитесь с администратором: @xerxeson",
    'no_active_subscription' => "У вас нет активной подписки 🙁",
    'subscription_expires_at' => "Ваша подписка действительна до :date 🙃",
    'cancel_button' => '❌ Отменить',
    'confirm_delete' => "Вы уверены, что хотите удалить? 😞",
    'no_button' => '❌',
    'yes_button' => '✅',
    'unknown_card' => 'Неизвестная карта',
    'verification_code_sent' => "📲 Код отправлен на ваш номер +:phone!\nВведите код:",
    'verification_code_send_failed' => "📲 Не удалось отправить код",
    'incorrect_code' => "Неверный код. Пожалуйста, попробуйте еще раз.",
    'card_verification_unexpected_response' => "Неожиданный ответ во время верификации карты.",
    'card_verified' => "Ваша карта верифицирована Payme.",
    'card_not_found' => 'Карта не найдена',
    'no_verified_card' => "Проверенная карта для оплаты не найдена.",
    'payment_failed' => "Платеж не удался",
    'something_went_wrong_support' => "Упс, что-то пошло не так :(\nПожалуйста, свяжитесь с поддержкой 🙃",
    'user_kicked' => "Вы были удалены из канала\nПожалуйста, подпишитесь, мы будем скучать по вам 😢",
    'ask_phone_number' => 'Пожалуйста, отправьте свой номер телефона :)',
    'send_phone_number_button' => '🫣 Отправить номер телефона',
    'your_details' => "Ваши данные:\nИмя: :first_name\nТелефон: :phone_number",
    'subscription_success' => "Хороший выбор! \nВаша подписка до: :date 😇",
    'new_subscription_admin_notification' => "Создана новая подписка 🎉.\nИмя: :first_name \nНомер телефона: :phone_number \nПодписка: :plan_name",
    'invalid_card_number' => "Введённый номер карты неверен. Пожалуйста, введите 16-значный номер карты повторно:",
    'ask_for_card_expiry' => "💳 Пожалуйста, отправьте срок действия карты (например, 10/29):",
    'invalid_expiry_date' => "Введённая дата неверна. Например: 10/30 или 02/28",
    'card_expired' => "Срок действия карты истёк. Пожалуйста, введите действительную дату.",
    'ask_for_card_number' => "💳 Пожалуйста, отправьте номер карты:",
    'select_plan_duration' => 'Выберите срок подписки 👇',
    'one_week_free' => '1 неделя бесплатно',
    'one_month' => '1 месяц',
    'two_months' => '2 месяца',
    'six_months' => '6 месяцев',
    'one_year' => '1 год',
    'home_button' => '🏠 Главная',
    'change_language_button' => '🌐 Изменить язык',
    'choose_lang' => 'Выберите язык:',
    'eng' => '🇺🇸 English',
    'ru' => '🇷🇺 Русский',
    'uz' => '🇺🇿 O\'zbekcha',
    'my_card_button' => '💳 Моя карта',

    // Payme service messages
    'order_not_found' => 'Заказ не найден',
    'incorrect_amount' => 'Неверная сумма',
    'order_payment_processing' => 'Оплата заказа в данный момент обрабатывается',
    'transaction_not_found' => 'Транзакция не найдена',
    'insufficient_privileges' => 'Недостаточно привилегий для выполнения метода',
    'order_canceled' => 'отменен',

    // Subscription messages
    'telegram_channel_subscription' => 'Подписка на Telegram канал',

    // Subscription reminder job messages
    'subscription_reminder_greeting' => 'Здравствуйте, :first_name!',
    'subscription_reminder_message' => 'Напоминание: Ваша подписка истекает через :days_left дней.',
    'subscription_reminder_footer' => 'Не забудьте продлить подписку, чтобы продолжить пользоваться нашими услугами :)',

    // Subscription renewal job messages
    'subscription_renewal_no_card' => 'Уважаемый :name, проверенная карта для автоматического продления подписки не найдена. Пожалуйста, обновите карту.',
    'subscription_renewal_success' => 'Уважаемый :name, ваша подписка успешно продлена.',
    'subscription_renewal_error' => 'Уважаемый :name, произошла ошибка при продлении подписки. Пожалуйста, обновите баланс 😇',

    // Announcement status messages
    'announcement_state' => [
        'in_progress' => 'В процессе',
        'sent' => 'Отправлено',
        'failed' => 'Ошибка',
    ],
    'choose_main_card' => 'Выберите карту, чтобы сделать её основной',
    'card_set_main_success' => 'Карта :card успешно установлена как основная!',
    'add_card_button' => '+ Добавить карту',
]; 