# How to use
## Prequisites
- Have a decent version of PHP installed like 7.3 at least;
- have PHP GD extension installed. If not, application will display and error;
- make sure at least PNG files are supported;
- have PHP EXIF extension installed. If not, application will display and error;
- have `assets` folder with 10 valid image files.

If in `assets` folder you have folders, text files, non-image png, gif or other files, they will be ignored. Only valid
image files are counted. For example TIFF files are not supported, but they can exist in folder. Preferably if the image
size is 362x544 pixels. Settings can be changed in the code.

## CLI
- Open command line and excute the script. For example:

$ `php gen.php`

If no parameters are given and all the necessary prequisites are met, you will see a success message with
file name and path where the file was generated. The default format is `PNG` and default file name is `result`. The
default location is the same where `gen.php` is located.

- you can also set output path, file name and extension. For example:

$ `php gen.php ./subfolder1/customname.gif`

If it was a success, the folder was created and file generated, you will see a success message with file name and path
where the file was generated. If you have given invalid extension, you will see an error message and supported mime
types. To make sure background is transparent, use `GIF` on `PNG`.

## Browser
- Oper web browser and type the location of script. For example:

`http://localhost/gen.php`

If no parameters are given and all the necessary prequisites are met, you will see the image on screen which you can
save later. The application will also generate the output file. The default format is `PNG` and default file name
is `result`. The default location is the same where `gen.php` is located.

- You can also give the file name and folder just like in CLI. To do so, add "name" parameter. For example:

`http://localhost/gen.php?name=./subfolder1/customname.gif`

If it was a success you will see the image on screen which you can save later. The application will also generate the
output file in the designated location and have the name of given file. If you have given invalid extension, you will
see an error message and supported mime types. To make sure background is transparent, use `GIF` on `PNG`.

## Out of scope, known issues, things to improve
1. Application assumes a lot. Like constant asset count. Not possible to make a collage with more or less than 10
images;
2. not possible to output desired size of the image;
3. asset image size is strictly 362x544 pixels. No image cropping, no rezing. So is the spacing between images and so is
the background - always transparent (if `GIF` on `PNG`); could add more options. Or maybe more image types;
4. everything runs in one class; Could some more to split some logic. For example take user input, check prequisites in
a class like `Setup` or maybe add image reading in separate class like `ImageReader`, which then `Collage` class could
use;
5. no unit tests. Could be added in the future.
