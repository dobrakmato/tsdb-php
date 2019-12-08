<?php
require '../vendor/autoload.php';

use TSDB\Client;

$tsdb = new Client("mrpi", 9087);

// Create series with name "temperature".
$tsdb->createSeries("temperature");

// Insert value "25.0" into existing series "temperature".
$tsdb->insertValue("temperature", 25.0);
$tsdb->insertValue("temperature", 26.0);
$tsdb->insertValue("temperature", 24.0);
$tsdb->insertValue("temperature", 24.0);

// Select all data-points from last 5 minutes from series "temperature".
$points = $tsdb->select("temperature", strtotime('-5 minutes'));

foreach ($points as $point) {
    $formatted = date('Y-m-d H:i:s', $point[0]);
    echo "At $formatted the value was $point[1]" . PHP_EOL;
}

