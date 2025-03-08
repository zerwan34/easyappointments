<?php
/**
 * Exemple d'utilisation de l'API JSON-RPC d'Odoo depuis PHP,
 * avec recherche dynamique du premier utilisateur et du template d'email
 * nommé "BL Facture : Envoi".
 *
 * Ce script réalise les opérations suivantes :
 *   1. Authentification
 *   2. Recherche ou création d'un partenaire
 *   3. Création d'une facture brouillon
 *   4. Validation de la facture (vérification de l'état "posted")
 *   5. Correction du champ user_id de la facture (pour éviter les erreurs)
 *   6. Recherche du template d'email par nom ("BL Facture : Envoi")
 *   7. Envoi de la facture par email via ce template
 */

 require_once __DIR__ . '/config.php';//Chargement du fichier config.php

// -----------------------------------------------------------------------------
// 1) PARAMÈTRES DE CONNEXION
// -----------------------------------------------------------------------------
$odooUrl  = Config::ODOO_URL;
$db       = Config::ODOO_DB;
$username = Config::ODOO_USERNAME;
$password = Config::ODOO_PASSWORD;


// Adresse mail à utiliser pour le partenaire
$email = Config::ODOO_EMAIL;

// -----------------------------------------------------------------------------
// 2) FONCTION D'APPEL JSON-RPC
// -----------------------------------------------------------------------------
function odooJsonRpcCall($url, $payload) {
    $ch = curl_init($url . '/jsonrpc');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if ($response === false) {
        die('Erreur cURL : ' . curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($response, true);
    if (isset($result['error'])) {
        die('Erreur Odoo JSON-RPC : ' . print_r($result['error'], true));
    }
    return $result['result'] ?? null;
}

// -----------------------------------------------------------------------------
// 3) AUTHENTIFICATION
// -----------------------------------------------------------------------------
$loginPayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'common',
        'method'  => 'login',
        'args'    => [$db, $username, $password]
    ],
    'id' => 0
];

$uid = odooJsonRpcCall($odooUrl, $loginPayload);
if (!$uid) {
    die("Échec de l’authentification JSON-RPC.\n");
}
echo "Authentification réussie ! UID = $uid\n";

// -----------------------------------------------------------------------------
// 4) RECHERCHER LE PREMIER UTILISATEUR (res.users)
// -----------------------------------------------------------------------------
$userSearchPayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'object',
        'method'  => 'execute_kw',
        'args'    => [
            $db,
            $uid,
            $password,
            'res.users',
            'search_read',
            [
                []  // Pas de filtre particulier, on récupère tous les utilisateurs
            ],
            ['fields' => ['id', 'name'], 'limit' => 1]
        ]
    ],
    'id' => 1
];
$foundUsers = odooJsonRpcCall($odooUrl, $userSearchPayload);
if (empty($foundUsers)) {
    die("Aucun utilisateur trouvé dans Odoo.\n");
}
$validUserId = $foundUsers[0]['id'];
echo "Premier utilisateur trouvé : ID = $validUserId\n";

// -----------------------------------------------------------------------------
// 5) RECHERCHER OU CRÉER UN PARTENAIRE
// -----------------------------------------------------------------------------
$searchPartnerPayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'object',
        'method'  => 'execute_kw',
        'args'    => [
            $db,
            $uid,
            $password,
            'res.partner',
            'search_read',
            [
                [['email', '=', $email]]
            ],
            ['fields' => ['id', 'name']]
        ]
    ],
    'id' => 3
];

$existingPartners = odooJsonRpcCall($odooUrl, $searchPartnerPayload);

if (!empty($existingPartners)) {
    $partnerId = $existingPartners[0]['id'];
    // Mise à jour du partenaire (par exemple, mise à jour du nom)
    $updatePartnerPayload = [
        'jsonrpc' => '2.0',
        'method'  => 'call',
        'params'  => [
            'service' => 'object',
            'method'  => 'execute_kw',
            'args'    => [
                $db,
                $uid,
                $password,
                'res.partner',
                'write',
                [
                    [$partnerId],
                    ['name' => 'Erwan COGOLUENHES']
                ]
            ]
        ],
        'id' => 4
    ];
    $writeResult = odooJsonRpcCall($odooUrl, $updatePartnerPayload);
    if (!$writeResult) {
        die("Échec de la mise à jour du partenaire.\n");
    }
    echo "Partenaire mis à jour ! ID = $partnerId\n";
} else {
    // Création d'un nouveau partenaire
    $createPartnerPayload = [
        'jsonrpc' => '2.0',
        'method'  => 'call',
        'params'  => [
            'service' => 'object',
            'method'  => 'execute_kw',
            'args'    => [
                $db,
                $uid,
                $password,
                'res.partner',
                'create',
                [
                    [
                        'name'  => 'Test Client JSON-RPC',
                        'email' => $email
                    ]
                ]
            ]
        ],
        'id' => 5
    ];
    $partnerId = odooJsonRpcCall($odooUrl, $createPartnerPayload);
    if (!$partnerId) {
        die("Échec de la création du partenaire.\n");
    }
    echo "Partenaire créé ! ID = $partnerId\n";
}

// -----------------------------------------------------------------------------
// 6) CRÉER UNE FACTURE BROUILLON AVEC UNE LIGNE
// -----------------------------------------------------------------------------
$invoicePayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'object',
        'method'  => 'execute_kw',
        'args'    => [
            $db,
            $uid,
            $password,
            'account.move',
            'create',
            [
                [
                    'move_type' => 'out_invoice',
                    'partner_id' => $partnerId,
                    'invoice_line_ids' => [
                        [0, 0, [
                            'name'       => 'Prestation JSON-RPC',
                            'quantity'   => 1,
                            'price_unit' => 123.45
                        ]]
                    ]
                ]
            ]
        ]
    ],
    'id' => 6
];

$invoiceId = odooJsonRpcCall($odooUrl, $invoicePayload);
if (!$invoiceId) {
    die("Échec de la création de la facture.\n");
}
echo "Facture brouillon créée ! ID = $invoiceId\n";

// -----------------------------------------------------------------------------
// 7) VALIDER LA FACTURE
// -----------------------------------------------------------------------------
$actionPostPayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'object',
        'method'  => 'execute_kw',
        'args'    => [
            $db,
            $uid,
            $password,
            'account.move',
            'action_post',
            [[$invoiceId]]
        ]
    ],
    'id' => 7
];
odooJsonRpcCall($odooUrl, $actionPostPayload);

// Vérifier que la facture est bien validée (state = 'posted')
$checkInvoicePayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'object',
        'method'  => 'execute_kw',
        'args'    => [
            $db,
            $uid,
            $password,
            'account.move',
            'read',
            [
                [$invoiceId],
                ['state']
            ]
        ]
    ],
    'id' => 8
];
$invoiceData = odooJsonRpcCall($odooUrl, $checkInvoicePayload);
if (!empty($invoiceData) && isset($invoiceData[0]['state']) && $invoiceData[0]['state'] === 'posted') {
    echo "Facture validée ! ID = $invoiceId\n";
} else {
    die("Échec de la validation de la facture.\n");
}

// -----------------------------------------------------------------------------
// 8) FORCER user_id POUR ÉVITER LES ERREURS D'UTILISATEUR MANQUANT
// -----------------------------------------------------------------------------
$fixInvoicePayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'object',
        'method'  => 'execute_kw',
        'args'    => [
            $db,
            $uid,
            $password,
            'account.move',
            'write',
            [
                [$invoiceId],
                ['user_id' => $validUserId]
            ]
        ]
    ],
    'id' => 9
];
odooJsonRpcCall($odooUrl, $fixInvoicePayload);

// -----------------------------------------------------------------------------
// 9) RECHERCHER LE TEMPLATE D'EMAIL PAR NOM ("BL Facture : Envoi")
// -----------------------------------------------------------------------------
$templateSearchPayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'object',
        'method'  => 'execute_kw',
        'args'    => [
            $db,
            $uid,
            $password,
            'mail.template',
            'search_read',
            [
                [['name', '=', 'BL Facture : Envoi']]
            ],
            ['fields' => ['id', 'name'], 'limit' => 1]
        ]
    ],
    'id' => 10
];
$foundTemplates = odooJsonRpcCall($odooUrl, $templateSearchPayload);
if (empty($foundTemplates)) {
    die("Aucun template d'email trouvé portant le nom 'BL Facture : Envoi'.\n");
}
$templateId = $foundTemplates[0]['id'];
echo "Template d'email trouvé : ID = $templateId\n";

// -----------------------------------------------------------------------------
// 10) ENVOYER LA FACTURE PAR EMAIL VIA LE TEMPLATE
// -----------------------------------------------------------------------------
$sendMailPayload = [
    'jsonrpc' => '2.0',
    'method'  => 'call',
    'params'  => [
        'service' => 'object',
        'method'  => 'execute_kw',
        'args'    => [
            $db,
            $uid,
            $password,
            'mail.template',
            'send_mail',
            [
                $templateId,
                $invoiceId,
                true  // force_send => envoi immédiat
            ]
        ]
    ],
    'id' => 11
];

$mailResult = odooJsonRpcCall($odooUrl, $sendMailPayload);
if (!$mailResult) {
    die("Échec de l'envoi de la facture par email.\n");
}
echo "La facture a été envoyée par email !\n";

// -----------------------------------------------------------------------------
// FIN DU SCRIPT
// -----------------------------------------------------------------------------
echo "Terminé.\n";
?>
