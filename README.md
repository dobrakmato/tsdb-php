tsdb-php
-----------

Basic pure PHP client for `tsdb`.

## Installing

Add the repository and require line to your `composer.json` file:
```
  "repositories": [
    {
      "url": "https://github.com/dobrakmato/tsdb-php",
      "type": "vcs"
    }
  ],
  "require": {
    "dobrakmato/tsdb-php": "dev-master"
  }
```

## Usage

### Connecting to server
You connect to TSDB server by creating a new instance of Client class.

```php
use TSDB\Client;

$tsdb = new Client("mrpi", 9087);
```

### Create new series
To create new series object call `createSeries` method on client object.

```php
// Create series with name "temperature".
$tsdb->createSeries("temperature");
```

### Inserting values into series
To insert values into series call `insertValue` method on client object. Make sure to 
not pass incorrect types as value parameter.
```php
// Insert value "25.0" into existing series "temperature".
$tsdb->insertValue("temperature", 25.0);
$tsdb->insertValue("temperature", 26.0);
$tsdb->insertValue("temperature", 24.0);
$tsdb->insertValue("temperature", 24.0);
```

### Selecting values from series
To select values from series call `select` method on client object. You can pass additional 
arguments `fromTimestamp` and `toTimestamp` which represent the range of values you are interesed
in. In this example we retrieve all data-points that occurred in last 5 minutes.

The function will return array of data-points. Each data-point is currently represented as two
element array. First element is UNIX timestamp of the data-point, second element is value.
```php
// Select all data-points from last 5 minutes from series "temperature".
$points = $tsdb->select("temperature", strtotime('-5 minutes'));

foreach ($points as $point) {
    $formatted = date('Y-m-d H:i:s', $point[0]);
    echo "At $formatted the value was $point[1]" . PHP_EOL;
}
```
