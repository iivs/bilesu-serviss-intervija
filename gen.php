<?php declare(strict_types = 1);

/**
 * Set the application directory to autoload classes.
 *
 * @var string
 */
$dir = 'app';

/**
 * Options to pass for Collage constructor.
 *
 * @var array
 */
$options = [
	'request_type' => php_sapi_name()
];

if ($options['request_type'] === 'cli') {
	/*
	 * Accept command-line arguments. Possible to set output file path and name. If no path or name is given, use
	 * application default name and location.
	 */
	if ($argc > 2) {
		echo 'Error: Too many arguments'."\n";
		exit(1);
	}

	foreach ($argv as $i => $arg) {
		// Skip the script name.
		if ($i == 0) {
			continue;
		}

		// Second and only argument is path and filename. For exaple ./folder/image.png
		$options['filename'] = $arg;
	}
}
else {
	// Accept URL parameter "name". For example gen.php?name=./folder/image.png
	if (array_key_exists('name', $_REQUEST)) {
		$options['filename'] = $_REQUEST['name'];
	}
}

// Autoload the class.
spl_autoload_register(function ($name) use ($dir) {
    include __DIR__.'/'.$dir.'/'.$name.'.php';
});

/*
 * Start the Collage generation. If CLI is used, it will generate a file. If web browser is used, it will generate a
 * file and also display the image on screen.
 */
try {
    $collage = new Collage($options);
    $collage->create();
}
catch (Exception $e) {
    echo $e->getMessage(), "\n";
}
