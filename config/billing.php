<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Billing currency
    |--------------------------------------------------------------------------
    |
    | HifzMaal is sold as a SaaS in US dollars regardless of the currency a
    | family chooses for its own budgets/transactions (see families.currency).
    | This must match CASHIER_CURRENCY in .env.
    |
    */
    'currency' => 'usd',

    /*
    |--------------------------------------------------------------------------
    | Free trial
    |--------------------------------------------------------------------------
    |
    | New accounts get this many days of full Pro access with no card on
    | file (Cashier's "generic trial", backed by users.trial_ends_at).
    |
    */
    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Free plan limits
    |--------------------------------------------------------------------------
    |
    | Enforced once a user is neither subscribed nor on trial. Pro (an
    | active Stripe subscription) removes these limits entirely.
    |
    */
    'free' => [
        'max_families' => (int) env('BILLING_FREE_MAX_FAMILIES', 1),
        'max_members_per_family' => (int) env('BILLING_FREE_MAX_MEMBERS', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Price IDs
    |--------------------------------------------------------------------------
    |
    | Create a single "HifzMaal Pro" product in the Stripe Dashboard with a
    | monthly and a yearly recurring price (USD), then paste their IDs here.
    |
    */
    'plans' => [
        'pro_monthly' => [
            'price_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'label' => 'Pro — Monthly',
        ],
        'pro_yearly' => [
            'price_id' => env('STRIPE_PRICE_PRO_YEARLY'),
            'label' => 'Pro — Yearly',
        ],
    ],

];
