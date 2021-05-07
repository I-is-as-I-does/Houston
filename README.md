# Houston
Call Houston when you have a problem. Houston is a simple php error handler, with configurable profiles.

## Getting started

````bash
$ composer require exoproject/houston
````

## Quick Start

````php
require_once(dirname(__DIR__).'\\vendor\\autoload.php');

use ExoProject\Houston\Houston;

$config = json_decode(file_get_contents(dirname(__DIR__).'\\config\\houston.json'), true);
$datatolog = "I think we have a problem";
$origin = __FILE__;
$lvl =2;
$houston = new Houston($datatolog, $origin, $lvl, $config);
````

## Contributing

Sure! :raised_hands:
You can take a loot at [CONTRIBUTING](CONTRIBUTING.md).  
This repo also features a [Discussions](https://github.com/I-is-as-I-does/Houston/discussions) tab.

## License

This project is under the MIT License; cf. [LICENSE](LICENSE) for details.