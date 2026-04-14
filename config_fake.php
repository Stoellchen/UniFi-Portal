<?php
// config_fake.php - needs to be changed to config.php and filled with real data, then do NOT upload to GitHub

return [
    'controller' => [
        'user'     => 'ui XXXXXXX 1',
        'password' => 'S XXXXXXXX 4',
        'url'      => 'https://10.XX.XX.XX:XXX43',
        'site_id'  => 's XXXX 1',
    ],
    'profiles' => [ // Hier nur den Namen 'profiles' verwenden, kein $
        'VIP' => [
            'passwords' => ['R XXXXXX 4', 'O XXXXXX l', 'V XXXXXX e'],
            'duration'  => 10080,
            'speed_down' => 100000,
            'speed_up'   => 100000,
            'label'      => 'VIP',
            'welcome'    => "Bienvenue, cher VIP %s !"
        ],
        'FRIEND' => [
            'passwords' => ['A XXXXXX e', 'B XXXXXX 6', 'W XXXXXX d'],
            'duration'  => 4320,
            'speed_down' => 75000,
            'speed_up'   => 75000,
            'label'      => 'Friend',
            'welcome'    => "Ravi de vous voir, %s !"
        ],
        'GUEST' => [
            'passwords' => ['G XXXXXX e', 'B XXXXXX 6', 'V XXXXXX 6', 'W XXXXXX t'],
            'duration'  => 1440,
            'speed_down' => 50000,
            'speed_up'   => 50000,
            'label'      => 'Guest',
            'welcome'    => "Bienvenue à la Résidence, %s !"
        ],
        'STANDARD' => [
            'passwords' => [],
            'duration'  => 480,
            'speed_down' => 25000,
            'speed_up'   => 25000,
            'label'      => 'Standard',
            'welcome'    => "Merci %s !"
        ]
    ] // Hier war das Semikolon zu viel
];



?>