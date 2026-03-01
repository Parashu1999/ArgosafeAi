<?php

return [
    // Gmail SMTP defaults.
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',

    // Set your sender Gmail address and 16-char app password here.
    // Example app password format: abcd efgh ijkl mnop (spaces optional)
    'username' => 'jbasanagoudra@gmail.com',
    'password' => 'ctgnwujflpvuxrgr',

    // Optional sender identity override.
    'from_email' => 'jbasanagoudra@gmail.com',
    'from_name' => 'AgroSafeAI',

    // Use only for local dev if OpenSSL CA validation fails.
    'skip_tls_verify' => true,

    // 0 = off, 2 = verbose SMTP logs.
    'debug' => 0,
];
