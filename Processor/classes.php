<?php

class Table
{
    public $tablecolumns;
    public $tableinfo;
    public $lastrow;
    public $lastcolumn;

    /**
     * Class initializer.
     *
     * @param void
     *
     * @return void
     */
    function __construct()
    {
        $this->tablecolumns = array();
        $this->tableinfo = array();
        $this->lastrow = -1;
        $this->lastcolumn = -1;
    }

    /**
     * Finds the column associated with the column number.
     *
     * @param int $var The tab position / column number.
     *
     * @return array
     */
    public function get_column_by_tabpos($var)
    {
        $column_to_return = null;
        foreach ($this->tablecolumns as $column)
        {
            if ($column->tabpos == $var)
            {
                $column_to_return = $column;
            }
        }
        return $column_to_return;
    }

    /**
     * Adds a new column to this table.
     *
     * @param string $hpv_id The name of the column.
     * @param int $tabpos The tab position / column number.
     * @param Table $thistable The table that the column should be
     *     added to.
     *
     * @return Column
     */
    public function add_column($hpv_id, $tabpos, $thistable)
    {
        $new_column = new Column($hpv_id, $tabpos, $thistable);
        foreach ($this->tablecolumns as $mycolumn)
        {
            if ($new_column->tabpos <= $mycolumn->tabpos)
            {
                $mycolumn->tabpos++;
            }
        }
        $this->tablecolumns[] = $new_column;
        
        return $new_column;
    }

    /**
     * Updates the table dimensions (rows and columns).
     *
     * @param void
     *
     * @return void
     */
    public function update_table_dimensions()
    {
        $highest_column_pos = $this->lastcolumn;
        $highest_row_pos = $this->lastrow;
        
        foreach($this->tablecolumns as $column)
        {
            if($column->tabpos > $highest_column_pos)
            {
                $highest_column_pos = $column->tabpos;
            }
            
            foreach($column->cellarray as $mycell)
            {
                if($mycell->rownumber > $highest_row_pos)
                {
                    $highest_row_pos = $mycell->rownumber;
                }
            }
        }
        
        $this->lastcolumn = $highest_column_pos;
        $this->lastrow = $highest_row_pos;
    }

    /**
     * Adds information about this table.
     *
     * @param string $info_row
     *
     * @return void
     */
    public function add_info($info_row)
    {
        $this->tableinfo[] = $info_row;
    }

    /**
     * Sorts the table columns based on tab position / column number.
     *
     * @param void
     *
     * @return void
     */
    public function sort_table_columns()
    {
        $this->tablecolumns[] = usort($this->tablecolumns,
                array($this, "sort_table_columns_by_tabpos"));
    }

    /**
     * Sorts the supplied Columns based on column number if at leas one
     * of them are info columns or name if none of them are.
     * @param Column $a The first column that is to be compared.
     * @param Column $b The second column that is to be compared.
     *
     * @return int Zero if they are considered equal, negative if a<b and
     *     positive if a>b.
     */
    function sort_table_columns_by_tabpos($a, $b)
    {
        if ($a->columntype == Column::$columntypes["infocolumn"]
                || $b->columntype == Column::$columntypes["infocolumn"])
        { // sort by column number
            if ($a->tabpos < $b->tabpos)
            {
                return -1;
            }
            elseif ($a->tabpos > $b->tabpos)
            {
                return 1;
            }
            else
            {
                return 0;
            }   
        }
        else
        { // sort by name
            preg_match("/([A-Za-z]+)(\d+)/", $a->name, $arr);
            
            $aplha1 = $arr[1];
            $number1 = $arr[2];
            
            preg_match("/([A-Za-z]+)(\d+)/", $b->name, $arr);
            
            $aplha2 = $arr[1];
            $number2 = $arr[2];
            
            if (strtolower($aplha2) != strtolower($aplha1))
            {
                return strcmp($aplha1, $aplha2);
            }
            elseif ($number1<$number2)
            {
                return -1;
            }
            elseif ($number1>$number2)
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }
    }

    /**
     * Finds the column associated wit the supplied name. If no
     * column is associated with the name the function returns null.
     *
     * @param string $columnname The name of the column.
     */
    function get_column_by_name($columnname)
    {
        $column_to_return = null;
        foreach ($this->tablecolumns as $columni)
        {
            if (strtolower($columni->name) == strtolower($columnname))
            {
                $column_to_return = $columni;
            }
        }
        return $column_to_return;
    }
}

class Column
{
    public $name;
    public $tabpos;
    public $cellarray;
    public $SDH2O;
    public $meanH2O;
    public $cutoff;
    public $greyzone;
    public $columntype;
    public $cutoffmod;
    public static $columntypes = array(
        "samplecolumn"=>"samplecolumn",
        "infocolumn"=>"infocolumn",
        "controlcolumn"=>"controlcolumn"
    );
    public $myTable;

    /**
     * Class initializer.
     *
     * @param string $_name Column name.
     * @param int $_tabpos Tab position.
     * @param Table $_myTable The cell table.
     *
     * @return void
     */
    public function __construct($_name, $_tabpos, $_myTable)
    {
        $this->name = $_name;
        $this->tabpos = $_tabpos;
        $this->cellarray = array();
        $this->myTable = $_myTable;
        $this->cutoffmod = set_cutoff_modification($this->name);
    }

    /**
     * Adds a new cell to the cellarray.
     *
     * This function creates a new cell with the specified properies and
     * adds it to the cell array.
     *
     * @param object $cellvalue The value of the cell (i.e. what is written
     *     in the cell).
     * @param int $cellrow The row nuber.
     * @param string $celltype The type of cell, which can be found in the
     *     Cell::$cellstypes array.
     * @param string $cellstyle The style of the cell, which can be found in
     *     the Cell::$cellstyles array.
     *
     * @return void
     */
    public function add_cell($cellvalue, $cellrow, $celltype, $cellstyle)
    {
        $newcell = new Cell($cellvalue, $cellrow);
        
        if ($celltype != null)
        {
            $newcell->celltype = $celltype;
        }
        
        if($cellstyle != null)
        {
            $newcell->cellstyle = $cellstyle;
        }
        else
        {
            $newcell->cellstyle = Cell::$cellstyles["whitecell"];
        }
        
        $this->cellarray[] = $newcell;
        $this->myTable->update_table_dimensions();
    }

    /**
     * Returns the cell at the supplied row.
     *
     * This function searches the cell array for the cell with
     * supplied row number. If the cell can not be found the function
     * returns null.
     *
     * @param int $i The row number.
     *
     * @return Cell
     */
    public function get_cell_by_row_number($i)
    {
        $celltoreturn = null;
    
        foreach ($this->cellarray as $mycell)
        {
            if ($mycell->rownumber == $i)
            {
                $celltoreturn = $mycell;
            }
        }
        return $celltoreturn;
    }

    /**
     * Sets the parameters SDH2O, meanH2O, cutoff, greyzone.
     *
     * @param void
     *
     * @return void
     */
    public function set_params()
    {
        $standard_deviation = 0;  // standard deviation
        $control_values = array();    
    
        foreach ($this->cellarray as $mycell)
        {
            if ($mycell->celltype == Cell::$celltypes["watercell"])
            {
                $control_values[] = $mycell->cellvalue;
            }
        }
        $mean = Mean($control_values);
    
        $standard_deviation = standard_deviation($control_values, $mean);
    
        if($standard_deviation < 1)
        {
            $standard_deviation = 1;
        }
    
        $this->SDH2O = $standard_deviation;
        $this->meanH2O = $mean;
        $this->cutoff = ($mean + 5*$standard_deviation) * $this->cutoffmod;
        $this->greyzone = $this->cutoff * 2;
    }

    /**
     * Adds the parameter cells to the cell array.
     *
     * @param int $lastrowintable The last row in the table.
     *
     * @return void
     */
    public function add_param_cells($lastrowintable)
    { 
        $this->add_cell($this->meanH2O, $lastrowintable + 2,
                Cell::$celltypes["infocell"], 0);
        $this->add_cell($this->SDH2O, $lastrowintable + 3,
                Cell::$celltypes["infocell"], 0);
        $this->add_cell($this->SDH2O * 5, $lastrowintable + 4,
                Cell::$celltypes["infocell"], 0);
        $this->add_cell($this->cutoff, $lastrowintable + 5,
                Cell::$celltypes["infocell"], 0);
        $this->add_cell($this->cutoff * 2, $lastrowintable + 6,
                Cell::$celltypes["infocell"], 0);
        $this->add_cell($this->cutoffmod, $lastrowintable + 7,
                Cell::$celltypes["infocell"], 0);
    }
}

class Cell
{
    public $cellvalue;
    public $cellstyle;
    public $rownumber;
    public $celltype;
    public $cellresult;
    
    public static $cellstyles = array(
        "whitecell" => "whitecell",
        "textboldcell" => "textboldcell",
        "greycell" => "greycell",
        "yellowcell" => "yellowcell",
        "orangecell" => "orangecell",
        "greencell" => "greencell",
        "aquacell" => "aquacell"
    );
    
    public static $celltypes = array(
        "headercell" => "headercell",
        "samplecell" => "samplecell",
        "watercell" => "watercell",
        "infocell" => "infocell"
    );
    
    public static $cellresults = array(
        "positive" => "positive",
        "greyzone" => "greyzone",
        "neg" => "neg"
    );

    /**
     * Class initializer.
     *
     * @param object $_cellvalue The value if the cell.
     * @param int $_rownumber The row number.
     *
     * @return void
     */
    public function __construct($_cellvalue, $_rownumber)
    {
        $this->cellvalue = $_cellvalue;
        $this->rownumber = $_rownumber;
        $this->cellresult = Cell::$cellresults["neg"];
    }    
}

class CrossReaction
{
    public $falsePositive;
    public $truePositive;
    public $crossvar;

    /**
     * Class initializer.
     *
     * @param string $_falsePositive Shows as positive but it should not.
     * @param string $_truePositive Shows as positive and it should.
     * @param float $_crossvar The crossreaction variable
     *
     * @return void
     */
    public function __construct($_falsePositive, $_truePositive, $_crossvar)
    {
        $this->falsePositive=$_falsePositive;
        $this->truePositive=$_truePositive;
        $this->crossvar=$_crossvar;
    }

}

?>