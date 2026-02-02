<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HifzMaal Configuration
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'HifzMaal'),
    
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    */
    'currencies' => [
        'PKR' => 'Pakistani Rupee',
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'SAR' => 'Saudi Riyal',
        'AED' => 'UAE Dirham',
        'INR' => 'Indian Rupee',
        'BDT' => 'Bangladeshi Taka',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('DEFAULT_CURRENCY', 'PKR'),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    */
    'locales' => [
        'en' => 'English',
        'ur' => 'اردو (Urdu)',
        'hi' => 'हिन्दी (Hindi)',
        'bn' => 'বাংলা (Bangla)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    */
    'default_locale' => env('DEFAULT_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Zakat Configuration
    |--------------------------------------------------------------------------
    */
    'zakat' => [
        'rate' => 0.025, // 2.5%
        'gold_nisab_grams' => 87.48, // 7.5 tola
        'silver_nisab_grams' => 612.36, // 52.5 tola
    ],

    /*
    |--------------------------------------------------------------------------
    | Family Settings
    |--------------------------------------------------------------------------
    */
    'family' => [
        'max_members' => 50,
        'max_accounts' => 20,
        'default_spending_limit' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    */
    'transaction' => [
        'max_receipt_size' => 5120, // KB (5MB)
        'allowed_receipt_types' => ['image/jpeg', 'image/png', 'application/pdf'],
        'auto_approval_threshold' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Budget Settings
    |--------------------------------------------------------------------------
    */
    'budget' => [
        'default_alert_threshold' => 80, // percentage
        'periods' => ['weekly', 'monthly', 'yearly'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bill Settings
    |--------------------------------------------------------------------------
    */
    'bill' => [
        'default_reminder_days' => 3,
        'types' => [
            'electricity',
            'gas',
            'water',
            'internet',
            'mobile',
            'rent',
            'school_fees',
            'other',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Savings Goal Types
    |--------------------------------------------------------------------------
    */
    'savings_goal_types' => [
        'hajj' => 'Hajj',
        'umrah' => 'Umrah',
        'education' => 'Education',
        'marriage' => 'Marriage',
        'emergency' => 'Emergency Fund',
        'business' => 'Business',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Zakat Recipient Categories
    |--------------------------------------------------------------------------
    */
    'zakat_categories' => [
        'fuqara' => 'The Poor (الفقراء)',
        'masakin' => 'The Needy (المساكين)',
        'amilin' => 'Zakat Administrators (العاملين عليها)',
        'muallaf' => 'New Muslims (المؤلفة قلوبهم)',
        'riqab' => 'Freeing Slaves (في الرقاب)',
        'gharimin' => 'Those in Debt (الغارمين)',
        'fisabilillah' => 'In the Cause of Allah (في سبيل الله)',
        'ibnus_sabil' => 'Stranded Travelers (ابن السبيل)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Islamic Calendar Settings
    |--------------------------------------------------------------------------
    */
    'hijri' => [
        'start_year' => 1400,
        'end_year' => 1500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => ['mail', 'database'],
        'budget_alert_enabled' => true,
        'bill_reminder_enabled' => true,
        'zakat_reminder_enabled' => true,
        'transaction_approval_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Settings
    |--------------------------------------------------------------------------
    */
    'reports' => [
        'monthly_trend_months' => 6,
        'export_formats' => ['pdf', 'excel', 'csv'],
    ],
];