<?php

return [
    'methods' => [
        'stripe' => [
            'name' => 'Credit Card (Stripe)',
            'icon' => 'credit_card',
            'handler' => 'stripe',
            'enabled' => true,
            'requires_redirect' => true,
        ],
        'bank_transfer' => [
            'name' => 'Bank Transfer',
            'icon' => 'account_balance',
            'handler' => 'bank_transfer',
            'enabled' => true,
            'requires_redirect' => false,
        ],
    ],

    'default' => 'stripe',

    'validation' => [
        'checkout' => ['stripe', 'bank_transfer'],
        'subscription' => ['card', 'stripe', 'bank_transfer'],
    ],
];
