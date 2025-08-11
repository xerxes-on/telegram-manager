<?php

return [
    'resources' => [
        'announcement' => [
            'label' => 'E\'lon',
            'plural' => 'E\'lonlar',
        ],
        'client' => [
            'label' => 'Mijoz',
            'plural' => 'Mijozlar',
        ],
        'subscription' => [
            'label' => 'Obuna',
            'plural' => 'Obunalar',
        ],
        'plan' => [
            'label' => 'Tarif',
            'plural' => 'Tariflar',
        ],
        'transaction' => [
            'label' => 'Tranzaksiya',
            'plural' => 'Tranzaksiyalar',
        ],
        'subscription_transaction' => [
            'label' => 'Obuna tranzaksiyasi',
            'plural' => 'Obuna tranzaksiyalari',
        ],
        'card' => [
            'label' => 'Karta',
            'plural' => 'Kartalar',
        ],
    ],
    
    'fields' => [
        // Common fields
        'id' => 'ID',
        'created_at' => 'Yaratilgan',
        'updated_at' => 'Yangilangan',
        'status' => 'Holat',
        'active' => 'Faol',
        
        // Client fields
        'first_name' => 'Ism',
        'last_name' => 'Familiya',
        'telegram_id' => 'Telegram ID',
        'username' => 'Foydalanuvchi nomi',
        'phone_number' => 'Telefon raqami',
        'chat_id' => 'Chat ID',
        'lang' => 'Til',
        'state' => 'Holat',
        'subscription_count' => 'Obunalar',
        'has_active_subscription' => 'Faol obuna bor',
        
        // Subscription fields
        'client' => 'Mijoz',
        'client_name' => 'Mijoz ismi',
        'plan' => 'Tarif',
        'plan_name' => 'Tarif nomi',
        'price' => 'Narx',
        'receipt_id' => 'Chek ID',
        'expires_at' => 'Tugash sanasi',
        'payment_retry_count' => 'To\'lov urinishlari',
        'last_payment_attempt' => 'Oxirgi to\'lov urinishi',
        'last_payment_error' => 'Oxirgi to\'lov xatosi',
        'previous_subscription_id' => 'Oldingi obuna',
        'is_renewal' => 'Yangilash',
        'reminder_sent_at' => 'Eslatma yuborilgan',
        'reminder_count' => 'Yuborilgan eslatmalar',
        'days_until_expiry' => 'Tugashiga qolgan kunlar',
        
        // Plan fields
        'name' => 'Nomi',
        'days' => 'Davomiyligi (kunlarda)',
        
        // Transaction fields
        'paycom_transaction_id' => 'Paycom tranzaksiya ID',
        'amount' => 'Summa',
        'state' => 'Holat',
        'order_id' => 'Buyurtma ID',
        'perform_time' => 'Bajarilgan',
        'cancel_time' => 'Bekor qilingan',
        
        // Subscription Transaction fields
        'card_id' => 'Karta',
        'subscription_id' => 'Obuna',
        'transaction_id' => 'Tranzaksiya ID',
        'type' => 'Turi',
        'error_message' => 'Xato xabari',
        
        // Card fields
        'masked_number' => 'Karta raqami',
        'token' => 'Token',
        'expire' => 'Amal qilish muddati',
        'verified' => 'Tasdiqlangan',
        'is_main' => 'Asosiy karta',
    ],
    
    'filters' => [
        'language' => 'Til',
        'has_subscription' => 'Obuna bor',
        'active_subscription' => 'Faol obuna',
        'active_status' => 'Faol holat',
        'expired' => 'Muddati tugagan',
        'expiring_soon' => 'Tez tugaydi (3 kun)',
        'plan' => 'Tarif',
        'state' => 'Holat',
        'verified' => 'Tasdiqlangan',
        'is_renewal' => 'Faqat yangilashlar',
        'has_reminder' => 'Eslatma yuborilgan',
        'date_range' => 'Sana oralig\'i',
        'type' => 'Tranzaksiya turi',
    ],
    
    'actions' => [
        'view' => 'Ko\'rish',
        'edit' => 'Tahrirlash',
        'delete' => 'O\'chirish',
        'create' => 'Yaratish',
        'send' => 'Yuborish',
        'send_reminder' => 'Eslatma yuborish',
        'renew' => 'Yangilash',
        'cancel' => 'Bekor qilish',
    ],
    
    'transaction_states' => [
        'created' => 'Yaratilgan',
        'performed' => 'Bajarilgan',
        'cancelled' => 'Bekor qilingan',
        'cancelled_after_perform' => 'Bajarilgandan keyin bekor qilingan',
    ],
    
    'transaction_types' => [
        'subscription' => 'Yangi obuna',
        'renewal' => 'Yangilash',
        'plan_change' => 'Tarif o\'zgartirish',
    ],
    
    'widgets' => [
        'active_subscriptions' => 'Faol obunalar',
        'churned_subscriptions' => 'Bekor qilingan obunalar',
        'subscription_distribution' => 'Obunalar taqsimoti',
        'top_plans' => 'Ommabop tariflar',
        'user_stats' => 'Foydalanuvchilar statistikasi',
        'new_users_this_month' => 'Bu oyda yangi foydalanuvchilar',
        'transaction_stats' => 'Tranzaksiyalar statistikasi',
        'revenue_today' => 'Bugungi daromad',
        'revenue_this_month' => 'Bu oydagi daromad',
        'revenue_all_time' => 'Umumiy daromad',
        'renewal_rate' => 'Yangilash foizi',
        'upcoming_renewals' => 'Kelgusi yangilashlar',
    ],
    
    'messages' => [
        'no_active_subscription' => 'Faol obuna yo\'q',
        'subscription_renewed' => 'Obuna muvaffaqiyatli yangilandi',
        'reminder_sent' => 'Eslatma muvaffaqiyatli yuborildi',
        'subscription_cancelled' => 'Obuna bekor qilindi',
        'payment_failed' => 'To\'lov xatosi',
        'card_added' => 'Karta muvaffaqiyatli qo\'shildi',
        'card_removed' => 'Karta o\'chirildi',
        'card_set_as_main' => 'Karta asosiy sifatida belgilandi',
    ],
    
    'announcement' => [
        'title' => 'Sarlavha',
        'message' => 'Xabar',
        'has_attachment' => 'Ilova bor',
        'file_path' => 'Fayl yo\'li',
        'status' => 'Holat',
        'user_id' => 'Foydalanuvchi',
        'created_at' => 'Yaratilgan',
        'updated_at' => 'Yangilangan',
        'messages' => [
            'created' => 'E\'lon muvaffaqiyatli yaratildi.',
            'attachment' => 'Ilova',
            'send_success' => 'E\'lon :id muvaffaqiyatli yuborildi.',
            'send_error' => 'E\'lon :id yuborishda xatolik yuz berdi.',
            'send_error_client' => 'Mijoz :chat_id ga e\'lon yuborishda xatolik yuz berdi.',
        ],
    ],
]; 