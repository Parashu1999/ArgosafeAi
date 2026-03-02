<?php

return [
    // Gmail SMTP defaults.
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',

    // Set your sender Gmail address and 16-char app password here.
    // Example app password format: abcd efgh ijkl mnop (spaces optional)
    'username' => 'parashuramparashuram3183@gmail.com',
    'password' => 'ncfmbkourswtxhmv',

    // Optional sender identity override.
    'from_email' => 'parashuramparashuram3183@gmail.com',
    'from_name' => 'AgroSafeAI',

    // Important for mobile verification links.
    // Set this to your LAN IP URL for phone testing on same WiFi.
    // Example: http://192.168.1.10/ArgosafeAi
    'app_url' => '',

    // Use only for local dev if OpenSSL CA validation fails.
    'skip_tls_verify' => true,

    // 0 = off, 2 = verbose SMTP logs.
    'debug' => 0,
];
