<?php
/**
 * Menu endpoint - returns menu structure based on user permissions and menu type
 */

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Get menu type from request body
$menuType = $data['type'] ?? 'protected';

// Get Authorization header to extract user info (in real API, this would decode the JWT)
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// Mock user permissions based on token (in real API, this would be extracted from JWT)
// For testing, we'll return different menus based on the type parameter
$userPermissions = ['user', 'admin']; // Mock permissions

// Define menu structures
$menus = [
    'protected' => [
        [
            'label' => 'Egyenleglekérdezés',
            'href' => '/',
            'icon' => 'wallet'
        ],
        [
            'label' => 'SZJA-kalkulátor',
            'href' => '/szja-kalkulator',
            'icon' => 'calculator'
        ],
        [
            'label' => 'Pénzügyek',
            'href' => '/penzugyek',
            'icon' => 'document',
            'children' => [
                [
                    'label' => '24 hónapos lekötés',
                    'href' => '/penzugyek/lekotes',
                    'icon' => 'lock'
                ],
                [
                    'label' => 'Folyószámla-kimutatás',
                    'href' => '/penzugyek/folyoszamla',
                    'icon' => 'document'
                ],
                [
                    'label' => 'Számlafeltöltés',
                    'href' => '/penzugyek/szamlafeltoltes',
                    'icon' => 'upload'
                ]
            ]
        ],
        [
            'label' => 'Kártyák',
            'href' => '/kartyak',
            'icon' => 'card',
            'children' => [
                [
                    'label' => 'Kártyáim',
                    'href' => '/kartyak/kartyaim',
                    'icon' => 'card'
                ],
                [
                    'label' => 'Mesterkártya',
                    'href' => '/kartyak/mesterkartya',
                    'icon' => 'star'
                ],
                [
                    'label' => 'Állandó bankkártyás megbízás',
                    'href' => '/kartyak/bankkartyás-megbizas',
                    'icon' => 'repeat'
                ]
            ]
        ],
        [
            'label' => 'Szolgáltatások',
            'href' => '/szolgaltatasok',
            'icon' => 'gift',
            'children' => [
                [
                    'label' => 'ÉnPénztáram hűségprogram',
                    'href' => '/szolgaltatasok/husegprogram',
                    'icon' => 'gift'
                ],
                [
                    'label' => 'Feliratkozom e-ügyintézésre',
                    'href' => '/szolgaltatasok/e-ugyintezes',
                    'icon' => 'mail'
                ],
                [
                    'label' => 'E-irat-feliratkozás',
                    'href' => '/szolgaltatasok/e-irat',
                    'icon' => 'file-text'
                ]
            ]
        ],
        [
            'label' => 'Dokumentumok',
            'href' => '/dokumentumok',
            'icon' => 'file-upload',
            'children' => [
                [
                    'label' => 'Dokumentumfeltöltés',
                    'href' => '/dokumentumok/feltoltes',
                    'icon' => 'file-upload'
                ],
                [
                    'label' => 'Elutasított számla',
                    'href' => '/dokumentumok/elutasitott-szamla',
                    'icon' => 'x-circle'
                ]
            ]
        ],
        [
            'label' => 'Egyéb',
            'href' => '/egyeb',
            'icon' => 'heart',
            'children' => [
                [
                    'label' => 'Kedvezményezettjeim',
                    'href' => '/egyeb/kedvezmenyezettek',
                    'icon' => 'users'
                ],
                [
                    'label' => 'Patikakártyával fizetett szolgáltatás',
                    'href' => '/egyeb/patikakartya',
                    'icon' => 'heart'
                ]
            ]
        ]
    ],
    'admin' => [
        [
            'label' => 'Dashboard',
            'href' => '/admin',
            'icon' => 'dashboard'
        ],
        [
            'label' => 'Felhasználók',
            'href' => '/admin/users',
            'icon' => 'users',
            'icon_type' => 'lucide'
        ],
        [
            'label' => 'Statisztikák',
            'href' => '/admin/stats',
            'icon' => 'chart'
        ],
        [
            'label' => 'Rendszer',
            'href' => '/admin/system',
            'icon' => 'settings',
            'children' => [
                [
                    'label' => 'Hibák',
                    'href' => '/admin/errors',
                    'icon' => 'alert',
                    'icon_type' => 'lucide'
                ],
                [
                    'label' => 'Beállítások',
                    'href' => '/admin/settings',
                    'icon' => 'cog'
                ],
                [
                    'label' => 'Naplók',
                    'href' => '/admin/logs',
                    'icon' => 'file-text'
                ]
            ]
        ],
        [
            'label' => 'Debug',
            'href' => '#',
            'icon' => 'debug',
            'children' => [
                [
                    'label' => 'Teszt hibák',
                    'href' => '/admin/test-errors',
                    'icon' => 'bug'
                ]
            ]
        ]
    ]
];

// Return the appropriate menu based on type
$menuItems = $menus[$menuType] ?? [];

echo json_encode([
    'success' => true,
    'items' => $menuItems
]);
