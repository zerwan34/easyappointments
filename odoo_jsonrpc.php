<?php
/**
 * Exemple d'utilisation de l'API JSON-RPC d'Odoo depuis PHP.
 *
 * Ce script recherche un partenaire via son adresse mail. S'il existe, il met à jour
 * ses données (par exemple le nom). Sinon, il crée un nouveau partenaire.
 */

// -----------------------------------------------------------------------------
// 1) PARAMÈTRES DE CONNEXION
// -----------------------------------------------------------------------------
$odooUrl  = 'http://test.dnsinfo.fr:8069';  // URL d'Odoo
$db       = 'odoo3';                        // Nom de la base de données Odoo
$username = 'admin';                        // Identifiant utilisateur Odoo
$password = 'admin';                        // Mot de passe (ou token) Odoo

// Adresse mail à utiliser pour le partenaire
$email = 'jsonrpc@example.com';

// -----------------------------------------------------------------------------
// 2) FONCTION GLOBALE D'APPEL JSON-RPC
// -----------------------------------------------------------------------------
function odooJsonRpcCall($url, $payload) {
    // Initialisation cURL
    $ch = curl_init($url . '/jsonrpc');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Exécution
    $response = curl_exec($ch);
    if ($response === false) {
        die('Erreur cURL : ' . curl_error($ch));
    }
    curl_close($ch);

    // Décodage JSON
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
// 4) RECHERCHER OU METTRE À JOUR UN PARTENAIRE
// -----------------------------------------------------------------------------

// Rechercher un partenaire existant avec l'adresse mail
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
            // Domaine de recherche : on filtre sur le champ email
            [[['email', '=', $email]]],
            // On récupère les champs utiles (ici id et name, par exemple)
            ['fields' => ['id', 'name']]
        ]
    ],
    'id' => 3
];

$existingPartners = odooJsonRpcCall($odooUrl, $searchPartnerPayload);

if (!empty($existingPartners)) {
    // Le partenaire existe déjà, on récupère son ID
    $partnerId = $existingPartners[0]['id'];
    // Mise à jour du partenaire (par exemple, actualiser le nom ou d'autres données)
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
                    [$partnerId], // Liste des IDs à mettre à jour
                    [
                        'name'  => 'Erwan COGOLUENHES', // Par exemple, mise à jour du nom
                        // Vous pouvez ajouter ici d'autres champs à mettre à jour
                    ]
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
    // Le partenaire n'existe pas, on le crée
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
// 5) CRÉER UNE FACTURE BROUILLON AVEC UNE LIGNE
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
                    'move_type' => 'out_invoice', // Facture client
                    'partner_id' => $partnerId,
                    'invoice_line_ids' => [
                        // La syntaxe [(0, 0, {vals})] pour créer une ligne
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
// FIN DU SCRIPT
// -----------------------------------------------------------------------------
echo "Terminé.\n";
?>
