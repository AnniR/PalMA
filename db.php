<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Poll up to 300 s for changes in the database and return its data in
// JSON format. The previous data is passed via URL request (?json=DATA).

// TODO: Use db triggers instead of time based polling.

require_once('DBConnector.class.php');
$dbcon = new DBConnector();

$remote = $_SERVER['REMOTE_ADDR'];
$isAllowed = false;

$newJSON = '{}';
$oldJSON = '';

if (isset($_REQUEST['json'])) {
    $oldJSON = $_REQUEST['json'];
    trace("db old: $oldJSON");
}

for ($t = 0; $t < 300; $t++) {
    //~ echo("waiting for db change...<br>");
    //~ $array = ['username' => 'stweil', 'quux' => 'baz'];
    //~ $newJSON = json_encode($array);

    $database = array();

    $table = $dbcon->query('select * from setting');
    $data = array();
    while ($row = $table->fetchArray(SQLITE3_ASSOC)) {
        array_push($data, $row);
    }
    $database['setting'] = $data;

    $table = $dbcon->query('select * from address');
    $data = array();
    while ($row = $table->fetchArray(SQLITE3_ASSOC)) {
        array_push($data, $row);
        $isAllowed = $isAllowed || ($row['address'] == $remote);
    }
    if (!$isAllowed) {
        // Some unauthorized host tried to read the database.
        // Don't return any data.
        break;
    }
    $database['address'] = $data;

    $table = $dbcon->query('select * from user');
    $data = array();
    while ($row = $table->fetchArray(SQLITE3_ASSOC)) {
        array_push($data, $row);
    }
    $database['user'] = $data;

    $table = $dbcon->query('select * from window');
    $data = array();
    while ($row = $table->fetchArray(SQLITE3_ASSOC)) {
        array_push($data, $row);
    }
    $database['window'] = $data;

    //~ $newJSON = json_encode($database, JSON_PRETTY_PRINT);
    $newJSON = json_encode($database);
    if ($oldJSON != $newJSON) {
        trace("db new: $newJSON");
        break;
    }

    sleep(1);
}

touch("last_activity");

echo($newJSON);

?>
