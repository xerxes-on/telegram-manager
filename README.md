# Telegram Subscription Bot

A Laravel-based Telegram bot for managing paid channel subscriptions with Payme payment integration.

## Features

- **Multi-language Support**: Uzbek (Latin & Cyrillic), Russian, and English
- **Subscription Management**: Multiple subscription plans (weekly, monthly, yearly)
- **Payment Integration**: Payme payment gateway with card tokenization
- **Automatic Renewal**: Retry failed payments up to 3 times
- **Channel Management**: Automatic user invite/removal based on subscription status
- **Admin Panel**: Filament-based admin interface
- **Free Trial**: One-time free trial option with card verification

## Requirements

- PHP 8.2+
- Composer
- MySQL/MariaDB
- Redis (optional, for queue processing)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd channel_bot2
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Configure your `.env` file:
```env
# Database
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=channel_bot2
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token
CHANNEL_ID=@your_channel_id

# Payme Payment Gateway
PAYME_API_URL=https://checkout.paycom.uz/api
PAYME_API_ID=your_merchant_id
PAYME_API_KEY=your_api_key

# Bot Settings
DEFAULT_LANGUAGE=uz
SUPPORTED_LANGUAGES=uz,ru,oz
MAX_PAYMENT_RETRIES=3
PAYMENT_RETRY_INTERVAL_HOURS=24
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Run migrations:
```bash
php artisan migrate
```

7. Set up webhook for your Telegram bot:
```bash
php artisan telegraph:set-webhook
```

## Usage

### Bot Commands

- `/start` - Initialize bot and registration process
- Payment button - View and select subscription plans
- Subscription status - Check current subscription status
- Help - Get support information
- Change language - Switch between supported languages
- My card - Manage payment cards

### Admin Panel

Access the admin panel at `/admin` to:
- View and manage clients
- Create and edit subscription plans
- Monitor transactions
- Send announcements
- View subscription statistics

### Scheduled Jobs

The bot uses three scheduled jobs that should be configured in your cron:

```cron
# Send subscription reminders (daily)
0 9 * * * cd /path/to/project && php artisan app:send-subscription-reminder-job

# Check and expire subscriptions (daily at 18:00)
0 18 * * * cd /path/to/project && php artisan app:check-subscriptions-job

# Attempt subscription renewals (twice daily)
0 6,18 * * * cd /path/to/project && php artisan app:renew-subscriptions-job
```

Or use Laravel's scheduler:
```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Project Structure

```
app/
├── Telegram/
│   ├── Handler.php          # Main bot handler
│   ├── Services/            # Bot services
│   │   ├── PaymeService.php
│   │   ├── PaycomSubscriptionService.php
│   │   └── HandleChannel.php
│   └── Traits/              # Bot functionality traits
├── Models/                  # Eloquent models
├── Jobs/                    # Background jobs
├── Services/                # Application services
└── Filament/               # Admin panel resources

resources/
└── lang/                   # Translations
    ├── uz/                 # Uzbek (Latin)
    ├── ru/                 # Russian
    ├── en/                 # English
    └── oz/                 # Uzbek (Cyrillic)
```

## Security

- All payment card data is tokenized through Payme
- User authentication via Telegram ID
- Admin panel protected by authentication
- Environment variables for sensitive configuration

## Testing

Run the test suite:
```bash
php artisan test
```

## Troubleshooting

### Bot not responding
- Check webhook is properly set: `php artisan telegraph:webhook-info`
- Verify bot token in `.env`
- Check Laravel logs in `storage/logs/`

### Payment issues
- Verify Payme credentials
- Check transaction logs in admin panel
- Ensure webhook URL is accessible

### Channel management issues
- Bot must be admin in the channel
- Channel ID must include @ symbol
- Check bot permissions in channel settings

## License

This project is proprietary software. All rights reserved.