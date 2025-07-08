<?php

return [
    'welcome' => 'Salom! Botga xush kelibsiz!',
    'ask_phone' => 'Iltimos, telefon raqamingizni yuboring',
    'send_phone_button' => 'Telefon raqamni yuborish',
    'welcome_message' => "Assalomu aleykum! Yangi \"Anvar Abduqayum Full Contact\" loyihamizga xush kelibsiz!\n\n" .
        "Ushbu kanal uchun alohida vaqt ajratib zavq bilan yondashishga harakat qilaman!\n\n" .
        "Bu kanalda:\n" .
        "1. Faqat foydali content.\n" .
        "Oylik obuna 500 000 so'm.",
    'payment_button' => "💳 To'lov",
    'subscription_status_button' => "📋 Obuna holati",
    'help_button' => "🆘 Yordam",
    'misunderstanding' => "🤷‍ Kechirasiz, buni tushunmadim.",
    'thank_you_data_saved' => "Rahmat! Ma'lumotlaringiz saqlandi. 🎉",
    'free_plan_used' => "Siz Tekin obunadan allqachon foydalangansiz 🙃",
    'subscription_confirmation' => "Obuna: :plan_name\nNarxi: :plan_price\nKarta: :card_number",
    'confirm_button' => '✅ Tasdiqlash',
    'change_card_button' => "♻︎ Kartani o'zgartirish",
    'back_button' => "🧐 Orqaga",
    'subscription_already_active' => "Sizda obuna allaqachon faol!",
    'command_not_understood' => "🤷‍ Kechirasiz, bu buyruqni tushunmadim.",
    'phone_saved' => 'Telefon raqam saqlandi.',
    'support_text' => "🙌 Qo'llab quvvatlash uchun adminga murojaat qiling: @xerxeson",
    'no_active_subscription' => "Sizda faol obuna yo'q 🙁",
    'subscription_expires_at' => "Obunangiz :date gacha mavjud 🙃",
    'cancel_button' => '❌ Bekor qilish',
    'confirm_delete' => "O'chirishni tasdiqlaysizmi 😞",
    'no_button' => '❌',
    'yes_button' => '✅',

    'unknown_card' => "Noma'lum karta",
    'verification_code_sent' => "📲Kod +:phone raqamingizga yuborildi!\nKodni kiriting:",
    'verification_code_send_failed' => "📲Kod jo'natish o'xshamadi",
    'incorrect_code' => "Notog'ri kod Iltimos yana harakat qilib koring.",
    'card_verification_unexpected_response' => "Karta verifikatsiyasi paytida kutilmagan javob.",
    'card_verified' => "Kartangiz Payme tomonidan tasdiqlandi.",
    'card_not_found' => 'Karta topilmadi',
    'no_verified_card' => "To'lov uchun tasdiqlangan karta topilmadi.",
    'payment_failed' => "To'lov amalga oshmadi",

    'something_went_wrong_support' => "Aah nimadir o'xshamadi :(\nQo'llab-quvvatlashga murojaat qiling 🙃",
    'user_kicked' => "Siz kanaldan chiqarib yuborildingiz\nIltimos obuna bo'ling, sizni sog'inamiz😢",

    'ask_phone_number' => 'Telefon raqamingizni yuboring :)',
    'send_phone_number_button' => '🫣 Telefon raqam yuborish',
    'your_details' => "Sizning ma'lumotlaringiz:\nIsm: :first_name \nTelefon: :phone_number",

    'subscription_success' => "To'g'ri tanlov! \nObunangiz: :date gacha 😇",
    'new_subscription_admin_notification' => "Yangi obuna yaratildi 🎉.\nIsm: :first_name \nTel raqam: :phone_number \nObuna: :plan_name",

    'invalid_card_number' => "Kiritilgan karta raqami noto'g'ri. Iltimos, 16 xonali karta raqamini qayta kiriting:",
    'ask_for_card_expiry' => "💳 Amal qilish muddatini yuboring (masalan, 10/29):",
    'invalid_expiry_date' => "Kiritilgan sana noto'g'ri. Masalan: 10/30 yoki 02/28",
    'card_expired' => "Karta muddati tugagan. Iltimos, amal qiladigan sanani kiriting.",
    'ask_for_card_number' => "💳 Karta raqamini yuboring:",
    'select_plan_duration' => 'Obuna muddatini tanlang 👇',
    'one_week_free' => '1 hafta bepul',
    'one_month' => '1 oy',
    'two_months' => '2 oy',
    'six_months' => '6 oy',
    'one_year' => '1 yil',

    'home_button' => '🏠 Bosh sahifa',
    'change_language_button' => '🌐 Tilni o\'zgartirish',
    'choose_lang' => 'Tilni tanlang:',
    'eng' => '🇺🇸 English',
    'ru' => '🇷🇺 Русский',
    'uz' => '🇺🇿 O\'zbekcha',

    // Payme service messages
    'order_not_found' => 'Buyurtma topilmadi',
    'incorrect_amount' => 'Noto\'g\'ri summa',
    'order_payment_processing' => 'Buyurtma to\'lovi hozirda amalga oshirilmoqda',
    'transaction_not_found' => 'Tranzaksiya topilmadi',
    'insufficient_privileges' => 'Metodni bajarish uchun yetarli huquqlar yo\'q',
    'order_canceled' => 'bekor qilindi',

    // Subscription messages
    'telegram_channel_subscription' => 'Telegram kanal obunasi',

    // Subscription reminder job messages
    'subscription_reminder_greeting' => 'Assalomu alaykum, :first_name!',
    'subscription_reminder_message' => 'Eslatma: Sizning obunangiz :days_left kundan keyin tugaydi.',
    'subscription_reminder_footer' => 'Obunani yangilashni unutmang, xizmatlarimizdan uzluksiz foydalanishingiz uchun :)',

    // Subscription renewal job messages
    'subscription_renewal_no_card' => 'Hurmatli :name, obunangizni avtomatik yangilash uchun tekshirilgan kartangiz topilmadi. Iltimos, kartangizni yangilang.',
    'subscription_renewal_success' => 'Hurmatli :name, obunangiz muvaffaqiyatli yangilandi.',
    'subscription_renewal_error' => 'Hurmatli :name, obunangizni yangilashda xatolik yuz berdi. Iltimos, balansingizni yangilang 😇',

    // Announcement status messages
    'announcement_state' => [
        'in_progress' => 'Jarayonda',
        'sent' => 'Yuborildi',
        'failed' => 'Xatolik',
    ],
]; 