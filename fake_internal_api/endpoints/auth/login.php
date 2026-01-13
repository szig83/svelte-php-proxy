<?php
$response = [
    "access_token" => "aaa",
    "refresh_token" => "bbb",
    "expires_in" => 3600,
    "user" => [
        "id" => 1,
        "email" => "valami@valami.hu",
        "name" => "Béla",
        "permissions" => ["user","admin"]
    ]
];

header('Content-type: application/json');
echo json_encode($response);
