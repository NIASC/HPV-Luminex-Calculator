#!C:\program\php\php.exe -q 
<?php

error_reporting(E_ALL|E_STRICT);
$researchversion = 1;

include("classes.php");
include("print_functions.php");
include("mod_data_functions.php");

print "----------------------------------------\n";
print " Luminex Processor version 2010-02-26 \n";
print " For info contact olaf.larsson@skane.se \n";
print "----------------------------------------\n";
print "\n";
print "\n";
print "\n";


print "Input name of infile (must be tab separated text-file):\n";
$in_filename = trim(fgets(STDIN));

print "Input name of outfile (must end with '.xml'):\n";
$out_filename = trim(fgets(STDIN));

$settings_filename = get_settings_filename();

print "Settingsfile name is: $settings_filename\n";

$big_array = extract_settings($settings_filename);
$CRarray = $big_array[0]; // cross reactions
$cutoffmodarr = $big_array[1];

$myTable = new Table();
$myTable->add_info("Settingsfile: $settings_filename");

$file_lines = read_data($in_filename);

populate_table_with_data($file_lines);
$var = count($myTable->tablecolumns);

$var = count($CRarray);
$var2 = count($cutoffmodarr);

$crossreactions = set_cross_reactions($CRarray);

modify_table_data();

$myTable->sort_table_columns();
print_table();


/**
 * Finds all settings files and sorts them in reverse order.
 * 
 * This function locates any files ending with '.settings' (and
 * sorts them reversely in alphabetical order). The returned array
 * contains the settings filenames.
 * 
 * @return array
 */
function list_of_settings_files()
{    
    $array = glob("*.settings"); // files in current directory
    $arraytoreturn = array();
    $var = 1;
    rsort($array); // reverse sort based on file name

    foreach ($array as $filename)
    {
        $arraytoreturn[$var]=$filename;
        $var++;
    }
    
    return $arraytoreturn;
}

/**
 * Returns the filename of the settings file.
 * 
 * This function chooses a settings file from current directory and
 * returns the filename.
 * If more than one settings files are available the user must choose
 * which settings file to user.
 * 
 * @param void
 *
 * @return string
 */
function get_settings_filename()
{
    $losf = list_of_settings_files();
    $settings_filename = "";

    if (count($losf) == 1)
    {
        $settings_filename = $losf[1];
    }
    elseif (count($losf) == 0)
    {
        print "Error: No settingfile present\n";
        sleep(5);
        exit;
    }
    elseif (count($losf) > 1)
    { // user must choose which settings to use
        print "There are more then one settingfile present\n"; 
        print "Choose which settingfile to use:\n\n";
        
        foreach ($losf as $key =>$value)
        {
            print "$key: $value\n";
        }
        
        while ($settings_filename=="")
        {
            print "\n Your choice (input a number):\n";
            $choice = trim(fgets(STDIN));
            
            if (array_key_exists($choice, $losf))
            {
                $settings_filename = $losf[$choice];
            }
            else
            {
                print "I dont understand what you mean try again.\n\n";
            }
        }
    }
    else
    {
        print "Error: Out of if-else loop\n";
        sleep(5);
        exit;
    }

    return $settings_filename;
}

/**
 * Sets the table data.
 *
 * @param void
 *
 * @return void
 */
function modify_table_data()
{
    global $researchversion;
    global $myTable;
    
    set_header_cells();
    set_column_types();
    set_water_cells();
    
    add_result_column();
    if ($researchversion != 0)
    { // version is research
        add_grey_zone_column();
    }
    
    create_summary_cells();
    add_result_negative();
    set_cell_results();
    evaluate_cross_reactions();
    
    if ($researchversion != 0)
    {
        populate_results_column_research();
    }
    else
    {
        populate_results_column_clinic();
    }
}

/**
 * Puts the data in the supplied file in an array.
 * 
 * This function reads the supplied file line by line and puts
 * the data in an array and returns the array.
 *
 * @param string $in_filename The path to the input file.
 *
 * @return array
 */
function read_data($in_filename)
{
    global $in_filename;
    
    $file_lines = array();
    $infile = fopen($in_filename, 'r') or exit;
    
    while (!feof($infile))
    {
        $line = fgets($infile);
        $file_lines[] = $line;
    }
    fclose($infile);
    
    return $file_lines;
}

/**
 * Writes $myTable to the output file.
 *
 * This function writes the information contained in the global
 * table $mytable into the outputfile using the subroutines in
 * q_print_functions.
 *
 * @param void
 *
 * @return void
 */
function print_table()
{
    global $out_filename;
    global $myTable;
    
  
    $of = fopen($out_filename, 'w+');
    print_header($of);
    $truerow = print_data($of);
    print_footer($of, $truerow);
    
    fclose($of);
}

/**
 * Create columns in the table and and cells in the columns.
 *
 * This function puts the data from the supplied array into the global
 * table $myTable.
 *
 * @param array $file_lines An array of strings representing the lines
 *     of a data file.
 *
 * @return void
 */
function populate_table_with_data($file_lines)
{
    global $myTable;
    
    $line_number = 0;
    $table_start = false;
    $after_table = false;
    $number_of_columns_to_create;
   
    foreach ($file_lines as $line)
    {
        $line = trim($line);
        $array = explode("\t", $line);

        if (preg_match("/Well\tType/i", $line)) 
        { // line is header header
            $number_of_columns_to_create = sizeof($array); 
            $table_start = true;
            $line_number = 0;
        }
        else if (!preg_match("/.+\t.+\t.+\t.+/", $line))
        { // line contains data
            $table_start = false;
            if (!$after_table)
            {
                $myTable->add_info($line);
            }
        }
        
        if ($table_start)
        { // create new header
            for ($i = 0; $i < $number_of_columns_to_create; $i++)
            {
                $cellvalue = "";
                if (isset($array[$i]))
                {
                    $cellvalue = preg_replace("/\s|\(\d+\)/", "", $array[$i]);
                    if (preg_match("/\*\*\*/", $cellvalue))
                    {
                        $cellvalue = "0";
                    }
                }
                if ($myTable->get_column_by_tabpos($i) == null)
                {
                    $newcolumn = $myTable->add_column($cellvalue, $i, $myTable);
                }
                
                if ($cellvalue != "")
                {
                    if ($line_number == 0)
                    {
                        $myTable->get_column_by_tabpos($i)->add_cell(
                            $cellvalue, $line_number, Cell::$celltypes["headercell"],
                            Cell::$cellstyles["textboldcell"]);
                    }
                    else
                    {  
                        $myTable->get_column_by_tabpos($i)->add_cell(
                            $cellvalue, $line_number, 0, 0);
                    }
                }
            } // end for
        } // end if
        $line_number++;
    }
}

/**
 * Creates an array of CrossReactions.
 *
 * This function creates an array of cross reactions from the data
 * contained in the supplied array.
 *
 * @param array $CRarray A 2-dimensional array where each element
 *     contains the crossreaction data on the form
 *     [<reactant1>, <reactant2>, <value>], e.g. [HPV16, HPV31, 1.09].
 *
 * @return array
 */
function set_cross_reactions($CRarray)
{
    $cross_reactions = array();
    for ($i = 0; $i<count($CRarray); $i++)
    {
        $cross_reactions[] = new CrossReaction($CRarray[$i][0], $CRarray[$i][1],
                $CRarray[$i][2]);
    }
    return $cross_reactions;
}

/**
 * finds the cutoff modification associated with the column name.
 *
 * This function finds the cutoff modifcations associated with
 * the supplied column name. If the column name does not exist in
 * the global variable $cutoffmodarr, the function returns 1.0.
 *
 * @param string $column_name The name of the column.
 *
 * @return float
 */
function set_cutoff_modification($column_name)
{
    $cutoff_mod = 1.0;
    global $cutoffmodarr;
    foreach ($cutoffmodarr as $key => $value)
    {
        if (strtolower($column_name) == strtolower($key)) 
        {
            $cutoff_mod = $cutoffmodarr[$key];
        }
    }
    return $cutoff_mod;
}

/**
 * Extracts the settings contained in the settings file.
 *
 * This function extracts the settings contained in the supplied
 * settings file path and places them in the returned array.
 *
 * @param string $settings_filename The path to the settings file.
 *
 * @return array
 */
function extract_settings($settings_filename)
{
    global $variant;
    global $researchversion;

    $fh = fopen($settings_filename, 'r');
    
    $cross_array = array();
    $cutoff_array = array();
    $big_array = array();
    
    $var = 0;

    while (!feof($fh))
    { // file contains more data
        $line = trim(fgets($fh));

        if (preg_match("/variant=(\w)/i", $line, $matches))
        { // variant (clinic/research)
            $variant = $matches[1];
            
            if($variant == 'K')
            { // clinic
                $researchversion = 0;
            }
            elseif($variant == 'F')
            { // research
                $researchversion = 1;
            }
            else
            {
                print "Error: No variant code\n";
            }
        }
        else if (preg_match("/.+,.+,.+/", $line))
        { // crossreations: var1, var2, val
            $splitarray = explode(",", $line);
            for ($i = 0; $i < count($splitarray); $i++)
            { // remove whitespace
                $splitarray[$i] = preg_replace('/\s/', '', $splitarray[$i]);
                $cross_array[$var][] = $splitarray[$i];
            }
            $var++;
        }
        else if (preg_match("/[\w\s]+=\d+/", $line))
        { // cutoffmodifications
            $splitarray = explode("=", $line);
            
            for($i = 0; $i < count($splitarray); $i++)
            { // remove whitespace
                $splitarray[$i] = preg_replace('/\s/', '', $splitarray[$i]);
                $cutoff_array[$splitarray[0]] = $splitarray[1];
            }
        }
    }
    
    fclose($fh);
    
    $big_array[] = $cross_array;
    $big_array[] = $cutoff_array;
    
    return $big_array;
}

?>