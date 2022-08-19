<?php

$users = [];

foreach (explode(PHP_EOL, file_get_contents(__DIR__ . '/config/users.txt')) as $line) {
    $credentials = explode(':', $line);

    if (2 !== count($credentials)) {
        continue;
    }

    $users[$credentials[0]] = $credentials[1];
}

$user = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

$validated = !empty($user) && array_key_exists($user, $users) && ($password === $users[$user]);

if (!$validated) {
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    die ("Not authorized");
}