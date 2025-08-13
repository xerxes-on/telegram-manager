# PayMe API Integration Documentation

This document explains how to use the PayMe Cabinet API integration in the channel_bot2 project.

## Overview

The PayMe integration consists of two main parts:

1. **Webhook Handler (PaymeService)** - Handles incoming payment notifications from PayMe
2. **Cabinet API Client** - Fetches transaction data from PayMe's merchant cabinet

## Configuration

Add the following environment variables to your `.env` file:

```env
# PayMe Webhook Configuration
PAYME_API_URL=https://checkout.paycom.uz/api
PAYME_API_ID=your_merchant_id
PAYME_API_KEY=your_merchant_key

# PayMe Cabinet API Configuration
PAYME_CABINET_LOGIN=your_phone_number
PAYME_CABINET_PASSWORD=your_password
PAYME_DEVICE_ID=your_device_id
PAYME_DEVICE_KEY=your_device_key
PAYME_BUSINESS_ID=your_business_id
PAYME_MERCHANT_ID=your_merchant_id
```

## Usage Examples

### Fetching Transactions

```php
use App\Telegram\Services\PaymeTransactionFetchService;
use Carbon\Carbon;

$service = new PaymeTransactionFetchService();

// Fetch transactions for a date range
$from = Carbon::parse('2025-08-01');
$to = Carbon::parse('2025-08-10');
$transactions = $service->fetchTransactions($from, $to);

foreach ($transactions as $transaction) {
    echo "Transaction ID: " . $transaction['_id'] . "\n";
    echo "Amount: " . $transaction['amount'] . "\n";
    echo "State: " . $transaction['state'] . "\n";
}
```

### Finding a Specific Transaction

```php
// Find a transaction by its PayMe ID
$paymeTransactionId = '64d5f7e8a1b2c3d4e5f6g7h8';
$transaction = $service->findTransaction($paymeTransactionId);

if ($transaction) {
    echo "Found transaction: " . json_encode($transaction) . "\n";
} else {
    echo "Transaction not found\n";
}
```

### Checking Transaction Status

```php
// Get the status of a transaction
$status = $service->getTransactionStatus($paymeTransactionId);

if ($status !== null) {
    echo "Transaction status: " . $status . "\n";
    // Status codes:
    // 1 - Created
    // 2 - Paid
    // -1 - Cancelled (before payment)
    // -2 - Cancelled (after payment/refunded)
}
```

## Using in Commands

You can create Artisan commands to fetch and process transactions:

```php
namespace App\Console\Commands;

use App\Telegram\Services\PaymeTransactionFetchService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchPaymeTransactions extends Command
{
    protected $signature = 'payme:fetch-transactions {--days=7 : Number of days to fetch}';
    protected $description = 'Fetch PayMe transactions for the specified period';

    public function handle(PaymeTransactionFetchService $service)
    {
        $days = $this->option('days');
        $from = Carbon::now()->subDays($days);
        $to = Carbon::now();
        
        $this->info("Fetching transactions from {$from->toDateString()} to {$to->toDateString()}");
        
        $transactions = $service->fetchTransactions($from, $to);
        
        $this->info("Found {$transactions->count()} transactions");
        
        // Process transactions...
        foreach ($transactions as $transaction) {
            // Your processing logic here
        }
    }
}
```

## Transaction States

PayMe uses the following transaction states:

- `1` - Transaction created (waiting for payment)
- `2` - Transaction paid successfully
- `-1` - Transaction cancelled before payment
- `-2` - Transaction cancelled after payment (refunded)

## Error Handling

The service handles errors gracefully and logs them. Check your Laravel logs for any API errors:

```bash
tail -f storage/logs/laravel.log
```

## Security Notes

1. Keep your PayMe credentials secure and never commit them to version control
2. Use environment variables for all sensitive configuration
3. The device ID and device key are obtained from PayMe when setting up API access
4. Ensure your webhook endpoint is protected with proper authentication

## Testing

You can test the integration using Tinker:

```bash
php artisan tinker
```

```php
$service = new \App\Telegram\Services\PaymeTransactionFetchService();
$transactions = $service->fetchTransactions(\Carbon\Carbon::yesterday(), \Carbon\Carbon::today());
dd($transactions->toArray());
```