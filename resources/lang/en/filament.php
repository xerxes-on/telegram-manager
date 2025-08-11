<?php

return [
    'resources' => [
        'announcement' => [
            'label' => 'Announcement',
            'plural' => 'Announcements',
        ],
        'client' => [
            'label' => 'Client',
            'plural' => 'Clients',
        ],
        'subscription' => [
            'label' => 'Subscription',
            'plural' => 'Subscriptions',
        ],
        'plan' => [
            'label' => 'Plan',
            'plural' => 'Plans',
        ],
        'transaction' => [
            'label' => 'Transaction',
            'plural' => 'Transactions',
        ],
        'subscription_transaction' => [
            'label' => 'Subscription Transaction',
            'plural' => 'Subscription Transactions',
        ],
        'card' => [
            'label' => 'Card',
            'plural' => 'Cards',
        ],
    ],
    
    'fields' => [
        // Common fields
        'id' => 'ID',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'status' => 'Status',
        'active' => 'Active',
        
        // Client fields
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'telegram_id' => 'Telegram ID',
        'username' => 'Username',
        'phone_number' => 'Phone Number',
        'chat_id' => 'Chat ID',
        'lang' => 'Language',
        'state' => 'State',
        'subscription_count' => 'Subscriptions',
        'has_active_subscription' => 'Has Active Subscription',
        
        // Subscription fields
        'client' => 'Client',
        'client_name' => 'Client Name',
        'plan' => 'Plan',
        'plan_name' => 'Plan Name',
        'price' => 'Price',
        'receipt_id' => 'Receipt ID',
        'expires_at' => 'Expires At',
        'payment_retry_count' => 'Payment Retries',
        'last_payment_attempt' => 'Last Payment Attempt',
        'last_payment_error' => 'Last Payment Error',
        'previous_subscription_id' => 'Previous Subscription',
        'is_renewal' => 'Is Renewal',
        'reminder_sent_at' => 'Reminder Sent At',
        'reminder_count' => 'Reminders Sent',
        'days_until_expiry' => 'Days Until Expiry',
        
        // Plan fields
        'name' => 'Name',
        'days' => 'Duration (days)',
        
        // Transaction fields
        'paycom_transaction_id' => 'Paycom Transaction ID',
        'amount' => 'Amount',
        'state' => 'State',
        'order_id' => 'Order ID',
        'perform_time' => 'Performed At',
        'cancel_time' => 'Cancelled At',
        
        // Subscription Transaction fields
        'card_id' => 'Card',
        'subscription_id' => 'Subscription',
        'transaction_id' => 'Transaction ID',
        'type' => 'Type',
        'error_message' => 'Error Message',
        
        // Card fields
        'masked_number' => 'Card Number',
        'token' => 'Token',
        'expire' => 'Expiry',
        'verified' => 'Verified',
        'is_main' => 'Primary Card',
    ],
    
    'filters' => [
        'language' => 'Language',
        'has_subscription' => 'Has Subscription',
        'active_subscription' => 'Active Subscription',
        'active_status' => 'Active Status',
        'expired' => 'Expired',
        'expiring_soon' => 'Expiring Soon (3 days)',
        'plan' => 'Plan',
        'state' => 'State',
        'verified' => 'Verified',
        'is_renewal' => 'Renewals Only',
        'has_reminder' => 'Reminder Sent',
        'date_range' => 'Date Range',
        'type' => 'Transaction Type',
    ],
    
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'create' => 'Create',
        'send' => 'Send',
        'send_reminder' => 'Send Reminder',
        'renew' => 'Renew',
        'cancel' => 'Cancel',
    ],
    
    'transaction_states' => [
        'created' => 'Created',
        'performed' => 'Performed',
        'cancelled' => 'Cancelled',
        'cancelled_after_perform' => 'Cancelled After Perform',
    ],
    
    'transaction_types' => [
        'subscription' => 'New Subscription',
        'renewal' => 'Renewal',
        'plan_change' => 'Plan Change',
    ],
    
    'widgets' => [
        'active_subscriptions' => 'Active Subscriptions',
        'churned_subscriptions' => 'Churned Subscriptions',
        'subscription_distribution' => 'Subscription Distribution',
        'top_plans' => 'Top Plans',
        'user_stats' => 'User Statistics',
        'new_users_this_month' => 'New Users This Month',
        'transaction_stats' => 'Transaction Statistics',
        'revenue_today' => 'Revenue Today',
        'revenue_this_month' => 'Revenue This Month',
        'revenue_all_time' => 'Total Revenue',
        'renewal_rate' => 'Renewal Rate',
        'upcoming_renewals' => 'Upcoming Renewals',
    ],
    
    'messages' => [
        'no_active_subscription' => 'No active subscription',
        'subscription_renewed' => 'Subscription renewed successfully',
        'reminder_sent' => 'Reminder sent successfully',
        'subscription_cancelled' => 'Subscription cancelled',
        'payment_failed' => 'Payment failed',
        'card_added' => 'Card added successfully',
        'card_removed' => 'Card removed',
        'card_set_as_main' => 'Card set as primary',
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