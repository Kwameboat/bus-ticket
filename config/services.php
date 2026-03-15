<?php
return [
    'mailgun'  => ['domain'=>env('MAILGUN_DOMAIN'),'secret'=>env('MAILGUN_SECRET'),'endpoint'=>env('MAILGUN_ENDPOINT','api.mailgun.net'),'scheme'=>'https'],
    'postmark' => ['token'=>env('POSTMARK_TOKEN')],
    'ses'      => ['key'=>env('AWS_ACCESS_KEY_ID'),'secret'=>env('AWS_SECRET_ACCESS_KEY'),'region'=>env('AWS_DEFAULT_REGION','us-east-1')],

    'paystack' => [
        'public_key'       => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key'       => env('PAYSTACK_SECRET_KEY'),
        'payment_url'      => env('PAYSTACK_PAYMENT_URL','https://api.paystack.co'),
        'merchant_email'   => env('PAYSTACK_MERCHANT_EMAIL'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model'   => env('OPENAI_MODEL','gpt-4o-mini'),
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'model'   => env('CLAUDE_MODEL','claude-haiku-4-5-20251001'),
    ],
];
