<?php

$filename = 'nicknames.txt';
$nicknames = file($filename);
$nickname = $_GET['nickname'];

header('Content-Type: application/json; charset=utf-8');

if (!in_array("$nickname\n", $nicknames)) {
    file_put_contents($filename, "$nickname\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['unique' => true, 'nicknames' => $nicknames]);
} else {
    echo json_encode(['unique' => false]);
}
