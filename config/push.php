<?php
/**
 * Настройки Web Push (VAPID)
 * 
 * Для генерации ключей используй: https://vapidkeys.com/
 * Или: openssl ecparam -genkey -name prime256v1 -out private_key.pem
 */
return [
    // VAPID public key (base64url) — отдаётся клиенту
    'public_key' => 'BAdElViMdhGhIG4pzM2ERfsBrNzisoEqGHSw1pO0sxpVNqwHqpoz8-lombFZOOf3vdr_owH55Wudy4MVMCJHGio',
    
    // VAPID private key (base64url) — хранится на сервере
    'private_key' => 'cwfhS5jPfaKtZWNcbzHE-Nz8IAZGH3UQDUjyZ1VJwNw',
    
    // Контактный email для push-провайдера
    'subject' => 'mailto:sorlana@yandex.ru',
];
