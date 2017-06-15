# HPV-Luminex-Calculator
This is an updated version of the Luminex Processor.
The __HPV-Luminex-Calculator__ converts data from a tab-separated text file into an xml file that can be viewed using LibreOffice Calc, Microsoft Excel or similar.

## Updates
* Now works for PHP 7 (Tested using version 7.1.5)
* Functions and methods are documented
* Variables and functions follow same naming convention (underscore e.g. variable_name and function_name)

## Prerequisits
* [PHP](http://php.net/) (Tested and works for version 5.2.9)

## Usage
You can test the program by converting _sample\_data.txt_ into an xml file.
Instructions for starting the program for Windows, Mac and GNU+Linux can be found below. The procedure for converting _sample\_data.txt_ into _sample\_data.xml_ can be seen in the image below.
<p align="center">
  <img src="https://github.com/marmalmstudent/HPV-Luminex-Calculator/blob/master/usage.png" alt="HPV Luminex Calculator">
</p>
### Windows
* Start the program by double-clicking on _luminexprocessor 20100614.php_
### Mac & GNU+Linux
* Open a terminal emulator in the __HPV-Luminex-Calculator__ directory (navigate by typing `cd /path/to/directory`)
* Start the program by typing `php "luminexprocessor 20100614.php"` (alternatively `php luminexprocessor\ 20100614.php`)
