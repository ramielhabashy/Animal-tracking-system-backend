<?php return array (
  'barryvdh/laravel-dompdf' => 
  array (
    'aliases' => 
    array (
      'PDF' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
      'Pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
    ),
    'providers' => 
    array (
      0 => 'Barryvdh\\DomPDF\\ServiceProvider',
    ),
  ),
  'laravel/horizon' => 
  array (
    'aliases' => 
    array (
      'Horizon' => 'Laravel\\Horizon\\Horizon',
    ),
    'providers' => 
    array (
      0 => 'Laravel\\Horizon\\HorizonServiceProvider',
    ),
  ),
  'laravel/sail' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Sail\\SailServiceProvider',
    ),
  ),
  'laravel/sanctum' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Sanctum\\SanctumServiceProvider',
    ),
  ),
  'laravel/sentinel' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Sentinel\\SentinelServiceProvider',
    ),
  ),
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nunomaduro/collision' => 
  array (
    'providers' => 
    array (
      0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    ),
  ),
  'nunomaduro/termwind' => 
  array (
    'providers' => 
    array (
      0 => 'Termwind\\Laravel\\TermwindServiceProvider',
    ),
  ),
  'php-mqtt/laravel-client' => 
  array (
    'aliases' => 
    array (
      'MQTT' => 'PhpMqtt\\Client\\Facades\\MQTT',
    ),
    'providers' => 
    array (
      0 => 'PhpMqtt\\Client\\MqttClientServiceProvider',
    ),
  ),
  'sentry/sentry-laravel' => 
  array (
    'aliases' => 
    array (
      'Sentry' => 'Sentry\\Laravel\\Facade',
    ),
    'providers' => 
    array (
      0 => 'Sentry\\Laravel\\ServiceProvider',
      1 => 'Sentry\\Laravel\\Tracing\\ServiceProvider',
    ),
  ),
  'spatie/laravel-permission' => 
  array (
    'providers' => 
    array (
      0 => 'Spatie\\Permission\\PermissionServiceProvider',
    ),
  ),
  'spatie/laravel-translatable' => 
  array (
    'providers' => 
    array (
      0 => 'Spatie\\Translatable\\TranslatableServiceProvider',
    ),
  ),
);