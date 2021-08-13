<?php declare(strict_types = 1);

/**
 * A class to generate a collage from 10 images. Output is written in file and/or shown on screen.
 */
final class Collage
{

    /**
     * Minimum required GD version.
     */
    const MIN_PHP_GD_VERSION = '2.0';

    /**
     * Directory where image files are located.
     */
    const ASSET_DIR = __DIR__.'/../assets';

    /**
     * File count in the "assets" folder is strictly 10.
     */
    const ASSET_COUNT = 10;

    /**
     * Assume images always have constant width 362 pixels.
     */
    const ASSET_W = 362;

    /**
     * Assume images always have constant height 544 pixels.
     */
    const ASSET_H = 544;

    /**
     * Horizontal spacing between the images is 10 pixels.
     */
    const SPACING_H = 10;

    /**
     * Vertical spacing between the images is 10 pixels.
     */
    const SPACING_V = 10;

    /**
     * Default directory where output file is generated. The default is where the gen.php script is located. Can be
     * overriden in command-line or URL "name". Where "name" is full path, file name and extension.
     *
     * @var string
     */
    private $out_dir = __DIR__.'/../';

    /**
     * Default output file name. Can be overriden in command-line or URL "name". Where "name" is full path, file name
     * and extension.
     *
     * @var string
     */
    private $out_filename = 'result';

    /**
     * Default extension of output file. The default is PNG, but can be overriden in command-line or URL "name". Where
     * "name" is full path, file name and extension. In case PNG is not supported by library, application will throw an
     * error.
     *
     * @var string
     */
    private $out_ext = 'png';

    /**
     * List of supported image mime types.
     *
     * @var array
     */
    private $supported_types = [];

    /**
     * File with paths as keys and mime types as values.
     *
     * @var array
     */
    private $assets = [];

    /**
     * The main image with transparent background to which other images are applied on top.
     *
     * @var resource
     */
    private $canvas;

	/**
	 * Request type, CLI or something else, for example, apache2handler.
	 *
	 * @var string
	 */
	private $request_type;

    /**
     * Get information about PHP libary. If libary not installed or formats are not allowed, throw and exception.
     * If successful, get the file list from "assets" folder.
     *
     * @param array $options  Accepts input parameters from CLI or URL.
     *
     * @throws Exception  Throws exception if invalid options given, like file name, directory, extension not supported.
     */
    function __construct(array $options)
    {
        $this->checkPrequisites();

		$this->request_type = $options['request_type'];

		// Check the default file extension if no option is provided.
        if (!array_key_exists(self::extToType($this->out_ext), $this->supported_types)) {
            throw new Exception('Error: Invalid output file extension. Change default $out_ext in app/Collage.php');
        }

        if ($options) {
            // Check if the given path exists. If not try to create it.
            if (array_key_exists('filename', $options) && $options['filename'] !== '') {
                // Check the directory, file name and extension.
                $path_parts = pathinfo($options['filename']);

                // Check if directory exists. If not, try to create it. If cannot create, throw an error.
                if ($path_parts['dirname'] !== '.') {
					// Check if drectory alredy exists.
					if (!is_dir($path_parts['dirname'])) {
						if (!mkdir($path_parts['dirname'], 0644, true)) {
							throw new Exception('Error: Cannot create directory');
						}
					}

                    $this->out_dir = $path_parts['dirname'].'/';
                }

                // Check if file name is given.
                if ($path_parts['filename'] === '') {
                    throw new Exception('Error: Invalid file name');
                }

                $this->out_filename = $path_parts['filename'];

                // The if given extension is supported.
                if (!array_key_exists('extension', $path_parts)
                        || !array_key_exists(self::extToType($path_parts['extension']), $this->supported_types)) {
                    throw new Exception(sprintf('Error: Invalid file extension. Supported types: %1$s', implode(', ', array_keys($this->supported_types))));
                }

                $this->out_ext = $path_parts['extension'];
            }
        }

        // If everything is OK, load the file list.
        $this->getFileList();
    }

    /**
     * Create a collage.
     */
    public function create(): void
    {
        $this->createCanvas();

        $pos_x = 0;
        $pos_y = 0;

        $col = 1;
        $row = 1;

        // Split images into 5 columns.
        $cols_max = 5;
        $rows_max = ceil(self::ASSET_COUNT / $cols_max);

        foreach ($this->assets as $file => $mime) {
            // Load the image and add it to canvas.
            $image = $this->loadImage($file, $mime);
            imagecopy($this->canvas, $image, $pos_x, $pos_y, 0, 0, self::ASSET_W, self::ASSET_H);

            // If this is not last column, add horizontal spacing +10px.
            if ($col != $cols_max) {
                $pos_x += self::SPACING_H;
            }

            // Set the horizontal postion of the next image.
            $pos_x += self::ASSET_W;

            // If this is last column, add vertial spacing +10px, reset the vertical position of next image.
            if ($col == $cols_max && $row != $rows_max) {
                $pos_y += self::ASSET_H;
                $pos_y += self::SPACING_V;
                $col = 0;
                $pos_x = 0;
                $row++;
            }

            imagedestroy($image);

            $col++;
        }

        $this->draw();
    }

    /**
     * Create canvas resource depending on image count and sizes. Then fill it with transparent background.
     */
    private function createCanvas(): void
    {
        $size = $this->calcCanvasSize();

        $this->canvas = imagecreatetruecolor((int) $size['width'], (int) $size['height']);
        imagesavealpha($this->canvas, true);

        $transp_color = imagecolorallocatealpha($this->canvas, 255, 255, 255, 127);
        imagefill($this->canvas, 0, 0, $transp_color);

		if ($this->out_ext === 'gif') {
			imagecolortransparent($this->canvas, $transp_color);
		}
    }

    /**
     * Calculate the size of the canvas that images are going to be applied to. Canvas size depends on image size and
     * spacing.
     *
     * @return array
     */
    private function calcCanvasSize(): array
    {
        $cols_max = 5;
        $rows_max = ceil(self::ASSET_COUNT / $cols_max);

        $width = self::ASSET_W * $cols_max + self::SPACING_H * ($cols_max - 1);
        $height = self::ASSET_H * $rows_max + self::SPACING_V * ($rows_max - 1);

        return [
            'width' => $width,
            'height' => $height
        ];
    }

    /**
     * Load the image depending on mime type.
     *
     * @param string $file  Path with file name.
     * @param string $mime  Mime type of the image.
     *
     * @return resource
     */
    private function loadImage(string $file, string $mime)
    {
        switch (self::mimeToType($mime))  {
            case 'jpeg':
                return imagecreatefromjpeg($file);

            case 'png':
                return imagecreatefrompng($file);

            case 'bmp':
                return imagecreatefrombmp($file);

            case 'gif':
                return imagecreatefromgif($file);

            case 'vnd.wap.wbmp':
                return imagecreatefromwbmp($file);
        }
    }

    /**
     * Loads the files into an asset array. Keys contain file paths and file names and values contain mime types.
     *
     * @throws Exception  Throws exception if asset count is not 10.
     */
    function getFileList(): void
    {
        if (is_dir(self::ASSET_DIR)) {
            if ($handle = opendir(self::ASSET_DIR)) {
                while (($file = readdir($handle)) !== false) {
                    // Skip directories.
                    if ($file !== '.' && $file !== '..' && is_file(self::ASSET_DIR.'/'.$file)) {
                        // Image has to be at least 11 bytes to be able to read without errors.
                        if (filesize(self::ASSET_DIR.'/'.$file) > 11) {
                            $exif = exif_imagetype(self::ASSET_DIR.'/'.$file);

                            // Check if the file is an image  and not just a file with image extension.
                            if ($exif !== false) {
                                $mime = image_type_to_mime_type($exif);

                                // Ignores unallowed file types.
                                if (array_key_exists(self::mimeToType($mime), $this->supported_types)) {
                                    $this->assets[self::ASSET_DIR.'/'.$file] = $mime;
                                }
                            }
                        }
                    }
                }

                closedir($handle);
            }
        }

        /*
         * If file count in folder exceeds asset count, we no longer know which files are valid and which are not.
         * Otherwise if there are not enough valid image files, we cannot make a collage.
         */
        $asset_count = count($this->assets);
        if ($asset_count != self::ASSET_COUNT) {
            throw new Exception(sprintf('Error: Invalid asset count "%1$s"', $asset_count));
        }

        // Sort the file list.
        uksort($this->assets, 'strnatcmp');
    }

    /**
     * Check PHP version and GD libary.
     *
     * @throws Exception  If GD extension is not supported or EXIF extension does no exist, throw an exception.
     */
    private function checkPrequisites(): void
    {
		// Check GD extension.
        if (is_callable('gd_info')) {
			$gdinfo = gd_info();
			preg_match('/(\d\.?)+/', $gdinfo['GD Version'], $current);
			$current = $current[0];
		}
		else {
			$current = 'unknown';
		}
		$check = version_compare($current, self::MIN_PHP_GD_VERSION, '>=');

		if ($check === false) {
            throw new Exception('Error: PHP GD extension missing');
        }

		if (!is_callable('exif_imagetype')) {
			throw new Exception('Error: PHP EXIF extension missing');
		}

        // Add extension support. XBM, TIFF and other formats are not supported by this application.
        if (array_key_exists('GIF Read Support', $gdinfo) && $gdinfo['GIF Read Support'] === true
                && array_key_exists('GIF Create Support', $gdinfo) && $gdinfo['GIF Create Support'] === true) {
            $this->supported_types['gif'] = true;
        }
        if (array_key_exists('JPEG Support', $gdinfo) && $gdinfo['JPEG Support'] === true) {
            $this->supported_types['jpeg'] = true;
        }
        if (array_key_exists('PNG Support', $gdinfo) && $gdinfo['PNG Support'] === true) {
            $this->supported_types['png'] = true;
        }
        if (array_key_exists('WBMP Support', $gdinfo) && $gdinfo['WBMP Support'] === true) {
            $this->supported_types['vnd.wap.wbmp'] = true;
        }
        if (array_key_exists('BMP Support', $gdinfo) && $gdinfo['BMP Support'] === true) {
            $this->supported_types['bmp'] = true;
        }
    }

    /**
     * Output to screen when using web browser. Also writes to file.
     */ 
    private function draw(): void
    {
		if ($this->request_type !== 'cli') {
			header('Content-Type: image/'.self::extToType($this->out_ext));

			$this->output();
		}

        $this->write();
    }

    /**
     * Output to file depeding on the chosen and supported extensions.
     */
    private function write(): void
    {
        $file = $this->out_dir.$this->out_filename.'.'.$this->out_ext;

		$this->output($file);
        imagedestroy($this->canvas);

		if ($this->request_type === 'cli') {
			echo 'File generated: '.$file."\n";
		}
    }

	/**
	 * Writes to file or outputs the content depending extension and if file and path are given or not.
	 *
	 * @param string $file  Full file path and name.
	 */
	private function output(string $file = null): void {
		switch ($this->out_ext) {
            case 'jpeg':
            case 'jpg':
            case 'jfif':
            case 'jpe':
                imagejpeg($this->canvas, $file ?? null);
                break;

            case 'png':
                imagepng($this->canvas, $file ?? null);
                break;

            case 'bmp':
                imagebmp($this->canvas, $file ?? null);
                break;

            case 'wbmp':
                imagewbmp($this->canvas, $file ?? null);
                break;

            case 'gif':
                imagegif($this->canvas, $file ?? null);
                break;
        }
	}

    /**
     * Convert mime type to more simplified type.
     *
     * @param string $mime  Image file mime type.
     */
    private static function mimeToType(string $mime): string {
        return substr(strrchr($mime, '/'), 1);
    }

    /**
     * Convert extension to mime type.
     *
     * @param string $ext  Image file extension.
     */
    private static function extToType(string $ext): string {
        switch ($ext) {
            case 'jpeg':
            case 'jpg':
            case 'jfif':
            case 'jpe':
                return 'jpeg';

            case 'png':
                return 'png';

            case 'bmp':
                return 'bmp';

            case 'wbmp':
                return 'vnd.wap.wbmp';

            case 'gif':
                return 'gif';

            default:
                return '';
        }
    }
}
