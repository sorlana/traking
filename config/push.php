<?php
/**
 * Настройки Web Push (VAPID)
 * 
 * Для генерации ключей используй: https://vapidkeys.com/
 * Или: openssl ecparam -genkey -name prime256v1 -out private_key.pem
 */
return [
    // VAPID public key (base64url) — отдаётся клиенту
    'public_key' => 'ВСТАВЬ_СВОЙ_PUBLIC_KEY',
    
    // VAPID private key (base64url) — хранится на сервере
    'private_key' => 'ВСТАВЬ_СВОЙ_PRIVATE_KEY',
    
    // Контактный email для push-провайдера
    'subject' => 'mailto:admin@unique-style.ru',
];
