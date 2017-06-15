<?php

/**
 * Checks if the column number $tabpos_to_check exists.
 *
 * This function checks if the supplied column exists
 * in the global variable $array_of_columns
 *
 * @param int $tabpos_to_check The tab position / clumn number.
 *
 * @return boolean Whether or not the column exists.
 */
function column_existst($tabpos_to_check)
{
    global $array_of_columns;  
    $column_exists = false;
  
    foreach($column as $array_of_columns)
    {
        if($column->$tabpos == $tabpos_to_check)
        {
            $column_exists = true;
        }
    }
    return $column_exists;
}

/**
 * Extracts rows containing water.
 *
 * This function extracts the number of rows containing
 * water from the global $myTable.
 *
 * @param void
 *
 * @return array An array containing the row number of the rows
 *     containing water.
 */
function get_water_rows()
{
    global $myTable;
    $column_name = "Description";
    $water_rows = array();
    $description_column = $myTable->get_column_by_name($column_name);
    
    if ($description_column == null)
    {
        print "Error: Could not get description column\n";
    }
    
    foreach ($description_column->cellarray as $my_cell)
    {
		if (preg_match("/h2o/i", $my_cell->cellvalue))
        {
            $water_rows[] = $my_cell->rownumber;
        }
    }
    return $water_rows;
}

/**
 * Sets the water cells to type "watercell."
 *
 * This function sets the water cells in the global $myTable
 * to the type "watercell."
 *
 * @param void
 *
 * @return void
 */
function set_water_cells()
{
    global $myTable;
    $water_rows = get_water_rows();
    foreach ($myTable->tablecolumns as $my_column)
    {
        if ($my_column->columntype == Column::$columntypes["samplecolumn"]
                || $my_column->columntype == Column::$columntypes["controlcolumn"])
        {
            foreach ($my_column->cellarray as $my_cell)
            {
                foreach ($water_rows as $water_row)
                {
                    if ($water_row == $my_cell->rownumber)
                    {
                        $my_cell->celltype = Cell::$celltypes["watercell"];
                        $my_cell->cellstyle = Cell::$cellstyles["aquacell"];
                    }
                }
                if ($my_cell->celltype != Cell::$celltypes["watercell"]
                   && $my_cell->celltype != Cell::$celltypes["headercell"])
                {
                    $my_cell->celltype = Cell::$celltypes["samplecell"];
                }
            }
        }
    }
}

/**
 * Sets the type of the first cell in a column to a "headercell"
 *
 * @param void
 *
 * @return void
 */
function set_header_cells()
{
    global $myTable;
    foreach ($myTable->tablecolumns as $my_column)
    {
        foreach ($my_column->cellarray as $my_cell)
        { 
            if ($my_cell->rownumber == 0)
            {
                $my_cell->celltype = Cell::$celltypes["headercell"];
            }
        }
    }
}

/**
 * Sets the column type to celltype "control," "sample" or "info"
 * based on the column names.
 *
 * This function sets columns
 * - containing HPV (case insensitive) in their names to "samplecolumn."
 * - starts with u/uni/universal followed by blanks and a number to
 *   "controlcolumn."
 * - if none of the above are satisfied to "infocolumn".
 *
 * @param void
 *
 * @return void
 */
function set_column_types()
{
    global $myTable;
    global $celltypearray;
    global $columntypearray;
    
    foreach ($myTable->tablecolumns as $my_column)
    {
        if (preg_match("/hpv/i", $my_column->name))
        {
            $my_column->columntype = Column::$columntypes["samplecolumn"];
        }
        elseif (preg_match("/^u\s*\d$/i", $my_column->name)
                    || preg_match("/^uni\s*\d$/i", $my_column->name)
                    || preg_match("/^universal\s*\d$/i", $my_column->name))
        {
            $my_column->columntype = Column::$columntypes["controlcolumn"];
        }
        else
        {
            $my_column->columntype = Column::$columntypes["infocolumn"];
        }
    }
}

/**
 * Adds summary cells to the bottom if the "Result" column (clinic version)
 * or "Greyzone" column (research version).
 *
 * @param void
 *
 * @return void
 */
function create_summary_cells()
{
    global $myTable;
    global $researchversion;
    
    $last_row_in_table = $myTable->lastrow;
    
    foreach ($myTable->tablecolumns as $column)
    {
        if ($column->columntype == Column::$columntypes["samplecolumn"]
                || $column->columntype == Column::$columntypes["controlcolumn"])
        {
            $column->set_params();
            $column->add_param_cells($last_row_in_table);
        }
    }
    
    $column_name = "Result";
    if($researchversion == 1)
    {
        $column_name = "Greyzone";
    }
    
    $description_columns = $myTable->get_column_by_name($column_name);
    
    $description_columns->add_cell(
            "Mean H2O", $last_row_in_table + 2, Cell::$celltypes["infocell"],
            Cell::$cellstyles["textboldcell"]);
    $description_columns->add_cell(
            "SD H2O", $last_row_in_table + 3, Cell::$celltypes["infocell"],
            Cell::$cellstyles["textboldcell"]);
    $description_columns->add_cell(
            "SD H2O*5", $last_row_in_table + 4, Cell::$celltypes["infocell"],
            Cell::$cellstyles["textboldcell"]);
    $description_columns->add_cell(
            "Cutoff", $last_row_in_table + 5, Cell::$celltypes["infocell"],
            Cell::$cellstyles["textboldcell"]);
    $description_columns->add_cell(
            "Greyzone", $last_row_in_table + 6, Cell::$celltypes["infocell"],
            Cell::$cellstyles["textboldcell"]);
    $description_columns->add_cell(
            "Cutoff mod.", $last_row_in_table + 7, Cell::$celltypes["infocell"],
            Cell::$cellstyles["textboldcell"]);
}

/**
 * Adds a result column to global $myTable.
 *
 * @param void
 *
 * @return void
 */
function add_result_column()
{
    global $myTable;
    
    $newcolumn = $myTable->add_column("Result", 3, $myTable);
    $newcolumn->columntype = Column::$columntypes["infocolumn"];
    $newcolumn->add_cell("Result", 0, Cell::$celltypes["headercell"],
            Cell::$cellstyles["textboldcell"]);
}

/**
 * Adds a Greyzone column to global $myTable.
 *
 * @param void
 *
 * @return void
 */
function add_grey_zone_column()
{
    global $myTable;
    
    $newcolumn = $myTable->add_column("Greyzone", 4, $myTable);
    $newcolumn->columntype = Column::$columntypes["infocolumn"];
    $newcolumn->add_cell("Greyzone", 0, Cell::$celltypes["headercell"],
            Cell::$cellstyles["textboldcell"]);
}

/**
 * Sets all of the results to negative.
 *
 * @param void
 *
 * @return void
 */
function add_result_negative()
{
    global $myTable;
    global $researchversion;
    
    $rows_to_create = array();
    $extracted_rows_to_create = array();
    
    foreach ($myTable->tablecolumns as $column)
    { // loop through columns in table
        if ($column->columntype == Column::$columntypes["samplecolumn"]
                || $column->columntype == Column::$columntypes["controlcolumn"])
        {
            foreach ($column->cellarray as $cell)
            { // loop through cells in column
                if ($cell->celltype == Cell::$celltypes["samplecell"]
                        || $cell->celltype == Cell::$celltypes["watercell"])
                {
                    $rows_to_create[] = $cell->rownumber;
                }
            }
        }
    }
    
    $extracted_rows_to_create = array_count_values($rows_to_create);
    
    foreach ($extracted_rows_to_create as $key => $value)
    {
        $myTable->get_column_by_name("Result")->add_cell("neg", $key,
                Cell::$celltypes["infocell"], 0);
        
        if ($researchversion!=0)
        {
            $myTable->get_column_by_name("Greyzone")->add_cell("", $key,
                    Cell::$celltypes["infocell"], 0);
        }
    }
}

/**
 * Sets the cell results to positive or grayzone.
 *
 * This function sets the cell results to positive if >= the
 * grayzone level or grayzone if they are >= the cutoff value but
 * < than the grayzone level. It is assumed that all results have
 * been set to negative before this function is called.
 *
 * @param void
 *
 * @result void
 */
function set_cell_results()
{
    global $myTable;
    
    foreach ($myTable->tablecolumns as $column)
    { // loop through columns in table
        if ($column->columntype == Column::$columntypes["samplecolumn"]
                || $column->columntype == Column::$columntypes["controlcolumn"] )
        {
            foreach ($column->cellarray as $cell)
            { // loop through cells in column
                if ($cell->celltype == Cell::$celltypes["samplecell"]
                        || $cell->celltype == Cell::$celltypes["watercell"])
                {
                    if ($cell->cellvalue >= $column->greyzone)
                    { // positive
                        $cell->cellstyle = Cell::$cellstyles["yellowcell"];
                        $cell->cellresult = Cell::$cellresults["positive"];
                    }
                    elseif ($cell->cellvalue >= $column->cutoff
                                && $cell->cellvalue<$column->greyzone)
                    { // grayzone
                        $cell->cellstyle = Cell::$cellstyles["greycell"];
                        $cell->cellresult = Cell::$cellresults["greyzone"];
                    }
                }
            }
        }
    }
}

/**
 * Appends results to columns, research version.
 *
 * This function appends results to columns of type "samplecolumn" and
 * "controlcolumn"
 *
 * @param void
 *
 * @return void
 */
function populate_results_column_research()
{
    global $myTable;
    
    for ($i = 0; $i <= $myTable->lastrow; $i++)
    { 
        $allisnegative = true;
        foreach ($myTable->tablecolumns as $column)
        {
            if ($column->columntype == Column::$columntypes["samplecolumn"]
                    || $column->columntype == Column::$columntypes["controlcolumn"])
            {
                $cell = $column->get_cell_by_row_number($i);
                if ($cell != null)
                {
                    if ($cell->cellresult == Cell::$cellresults["positive"]
                            || $cell->cellresult == Cell::$cellresults["greyzone"])
                    { 
                        append_result_research($column, $cell);
                        $allisnegative = false;
                    }
                }
            }
        }
    }
}

/**
 * Appends results to columns, clinic version.
 *
 * This function appends results to columns of type "samplecolumn" and
 * "controlcolumn"
 *
 * @param void
 *
 * @return void
 */
function populate_results_column_clinic()
{
    global $myTable;
    
    for ($i = 0; $i <= $myTable->lastrow; $i++)
    {
        $allisnegative = true;
        foreach ($myTable->tablecolumns as $column)
        {
            if ($column->columntype == Column::$columntypes["samplecolumn"])
            {
                $cell = $column->get_cell_by_row_number($i);
                if ($cell != null)
                {
                    if ($cell->cellresult == Cell::$cellresults["positive"]
                            || $cell->cellresult == Cell::$cellresults["greyzone"])
                    {
                        append_result_clinic($column, $cell);
                        $allisnegative = false;
                    }
                }
            }
        }
        
        /* It seems programatically equivalent to remove $allisnegative and
           the proceeding foreach-block and instead just use
             if ($column->columntype == Column::$columntypes["samplecolumn"]
                     || $column->columntype == Column::$columntypes["controlcolumn"])
           in the above if-statement.
         */
        foreach($myTable->tablecolumns as $column)
        {
            if ($column->columntype == Column::$columntypes["controlcolumn"]
                    && $allisnegative == true)
            {
                $cell = $column->get_cell_by_row_number($i);
                if ($cell != null)
                {
                    if ($cell->cellresult == Cell::$cellresults["positive"]
                            || $cell->cellresult == Cell::$cellresults["greyzone"])
                    {
                        append_result_clinic($column, $cell);
                    }
                }
            }
        }
    }
}

/**
 * Appends column name to cell value, research version.
 *
 * This function appends (comma separated) the column name to the cell value.
 * If the cell result is "greyzone" it will remove any negative value in the
 * "Result" column.
 * If the cell result is not "greyzone" it will remove any negative value in
 * the "Result" column and append to the "Result" column.
 *
 * @param Column $column
 *
 * @param Cell $cell
 */
function append_result_research(Column $column, Cell $cell)
{
    global $myTable;
    global $researchversion;
    
    $result_to_append = preg_replace("/hpv/i", "", $column->name);
    
    if ($cell->cellresult == Cell::$cellresults["greyzone"])
    {
        /* cell result is grayzone so append it and remove
           value from result column */
        if ($myTable->get_column_by_name("Greyzone")->get_cell_by_row_number(
                    $cell->rownumber)->cellvalue == "")
        {
            $myTable->get_column_by_name("Greyzone")->get_cell_by_row_number(
                    $cell->rownumber)->cellvalue = $result_to_append;
            
            if ($myTable->get_column_by_name("Result")->get_cell_by_row_number(
                        $cell->rownumber)->cellvalue == "neg")
            {
                $myTable->get_column_by_name("Result")->get_cell_by_row_number(
                        $cell->rownumber)->cellvalue = "";
            }
        }
        else
        { // not empty; append
            $result_to_append = ", ".$result_to_append;
            $myTable->get_column_by_name("Greyzone")->get_cell_by_row_number(
                    $cell->rownumber)->cellvalue .= $result_to_append;
        }
    }
    else
    { // not grayzone
        if($myTable->get_column_by_name("Result")->get_cell_by_row_number(
                   $cell->rownumber)->cellvalue == "neg"
               || $myTable->get_column_by_name("Result")->get_cell_by_row_number(
                       $cell->rownumber)->cellvalue == "")
        {
            $myTable->get_column_by_name("Result")->get_cell_by_row_number(
                    $cell->rownumber)->cellvalue = $result_to_append;
        }
        else
        {
            $result_to_append = ", ".$result_to_append;
            $myTable->get_column_by_name("Result")->get_cell_by_row_number(
                    $cell->rownumber)->cellvalue .= $result_to_append;
        }
    }
}

/**
 * Appends column name to cell value, clinic version.
 *
 * This function appends (comma separated) the column name to the cell
 * value in the "Result" column. Any negative value already written
 * there will be removed.
 * If the cell result is "greyzone" it will prepend "gr" the column name
 * before it is written to the cell value.
 *
 * @param Column $column
 *
 * @param Cell $cell
 */
function append_result_clinic(Column $column, Cell $cell)
{
    global $myTable;
    
    $result_to_append = preg_replace("/hpv/i", "", $column->name);
    
    if($cell->cellresult == Cell::$cellresults["greyzone"])
    {
        $result_to_append = "gr".$result_to_append;
    }
    if ($myTable->get_column_by_name("Result")->get_cell_by_row_number(
                $cell->rownumber)->cellvalue == "neg")
    {
        $myTable->get_column_by_name("Result")->get_cell_by_row_number(
                $cell->rownumber)->cellvalue = $result_to_append;
    }
    else
    {
        $result_to_append = ", ".$result_to_append;
        $myTable->get_column_by_name("Result")->get_cell_by_row_number(
                $cell->rownumber)->cellvalue .= $result_to_append;
    }
}

/**
 * Finds the rows containing negative results.
 *
 * @param void
 *
 * @result array An array containing the row number of the rows
 *     containing negative value.
 */
function get_negative_rows()
{
    global $myTable;
    
    $negative_results_cell_rows = array();
    
    foreach ($myTable->get_column_by_name("Result")->cellarray as $cell)
    {
        if ($cell->cellvalue == "neg")
        {
            $negative_results_cell_rows[] = $cell->rownumber;
        }
    }
    return $negative_results_cell_rows;
}

/**
 * Attempts to find $input in $array.
 *
 * @param array $array The array to look in.
 * @param object $input The object to look for.
 *
 * @return boolean If $input is found in $array it returns true
 */
function exists_in_array($array, $input)
{
    $exists = false;
    foreach ($array as $value)
    {
        if ($input == $value)
        {
            $exists = true;
        }
    }
    return $exists;
}

/**
 * Evaluates the cross reactions.
 *
 * This function evaluates the cross reaction for non-negative results
 * of type "samplecell" and "watercell." The evaluation is done by
 * comparing the quotient of the false positive and true positive with
 * the cross variable (from the CrossReaction class). If the quotient
 * is negative the result is negative.
 * For example, comparing the cross reaction [HPV16, HPV31, 1.09]
 * will compare as (16/31 < 1.09) which will yield a negative result.
 *
 * @param void
 *
 * @return void
 */
function evaluate_cross_reactions()
{
    global $crossreactions;
    global $myTable;
    
    foreach ($crossreactions as $cross_reaction)
    {
        $true_positive_column = $myTable->get_column_by_name($cross_reaction->truePositive);
        $false_positive_column = $myTable->get_column_by_name($cross_reaction->falsePositive);
        
        if ($true_positive_column != null
                && $false_positive_column != null)
        {
            foreach ($true_positive_column->cellarray as $true_positive_cell)
            {
                $false_positive_cell = $false_positive_column->get_cell_by_row_number(
                        $true_positive_cell->rownumber);
                
                if ($true_positive_cell->cellvalue >= $true_positive_column->greyzone /* positive */
                        && ($true_positive_cell->celltype == Cell::$celltypes["samplecell"]
                            || $true_positive_cell->celltype == Cell::$celltypes["watercell"])
                        && ($false_positive_cell->cellresult == Cell::$cellresults["positive"]
                                || $false_positive_cell->cellresult == Cell::$cellresults["greyzone"]))
                { // valid for evaluation
                    if($false_positive_cell->cellvalue / $true_positive_cell->cellvalue
                           < $cross_reaction->crossvar)
                    { // does not satisfy evaluation constraint
                        $false_positive_cell->cellstyle = Cell::$cellstyles["greencell"];
                        $false_positive_cell->cellresult = Cell::$cellresults["neg"];
                    }
                }
            }
        }
    }
}

/**
 * Computes the standard deviation of the correct values $array with
 * mean $mean. The mean 
 *
 * This function computes the standard deviation of the correct values
 * in $array which has a mean $mean. The mean of $array should be
 * computed using only correct values in $array, which can be found
 * using the function mean.
 *
 * @param array $array The array that the standard deviation will be
 *     computed for.
 * @param float $mean The average of $array.
 *
 * @return float The standard deviation
 */
function standard_deviation($array, $mean)
{   
    $variance = 0;
    $standard_deviation = 0;
    
    foreach($array as $value)
    {
        $difference = $mean - $value;
        $variance += pow($difference, 2) / (count_correct_values($array) - 1);
    }
    
    $standard_deviation = sqrt($variance);
    
    return $standard_deviation;
}

/**
 * Computes the average of $array.
 *
 * This function computes the average of the correct values in $array.
 * Correct values are values defined in count_correct_values.
 *
 * @param array $array The array that the mean will be computed for.
 *
 * @return float The average of $array
 */
function mean($array)
{
    
    $sum = 0;
    $mean = 0;
    foreach($array as $value)
    {
        $sum += $value;
    }
    $mean = $sum / count_correct_values($array);

    return $mean;
}

/**
 * Computes the number of correct values in $array.
 *
 * This function calculates the number of correct elements in $array.
 * Correct values are values in $array not containing "***".
 *
 * @param array $array
 *
 * @return int The number of correct elements in $array
 */
function count_correct_values($array)
{

    $len = sizeof($array);
    $error_count = 0;
    foreach($array as $value)
    {
        if($value == "***")
        {
            $error_count++;
        }
    }
    $len = $len - $error_count;
    
    return $len;

}

?>