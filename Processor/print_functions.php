<?php

/**
 * Writes the xml header to the output file.
 *
 * This function prints an xml header for Microsoft office spreadsheet.
 *
 * @param resource $output_file The file to be written to.
 *
 * @return void
 */
function print_header($output_file)
{

$xml=<<<_XML_
<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <LastAuthor>155435</LastAuthor>
  <Created>2009-02-20T12:24:56Z</Created>
  <Version>12.00</Version>
 </DocumentProperties>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>8640</WindowHeight>
  <WindowWidth>18975</WindowWidth>
  <WindowTopX>120</WindowTopX>
  <WindowTopY>30</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  
  <Style ss:ID="whitecell">

  </Style>
  
  <Style ss:ID="textboldcell">
   <Font ss:FontName="Arial" x:Family="Swiss" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#000080" ss:Pattern="Solid"/>
  </Style>
  
  <Style ss:ID="greycell">
   <Interior ss:Color="#C0C0C0" ss:Pattern="Solid"/>
  </Style>
  
  <Style ss:ID="yellowcell">
   <Interior ss:Color="#FFFF00" ss:Pattern="Solid"/>
  </Style>
  
  <Style ss:ID="greencell">
   <Interior ss:Color="#00FF00" ss:Pattern="Solid"/>
  </Style>
  
  <Style ss:ID="aquacell">
   <Interior ss:Color="#00FFFF" ss:Pattern="Solid"/>
  </Style>
  
  
  
 </Styles>
 <Worksheet  ss:Name="LuminexData">\n
_XML_;

fwrite($output_file, $xml);

}

/**
 * Writes the data from global $myTable and global $infoarray
 * to the output file.
 *
 * @param resource $output_file The file to be written to.
 *
 * @return int The row of the output file after writing the data.
 */
function print_data($output_file)
{
    global $myTable;
    global $infoarray;
    
    $truerow = 0;
    
    $xml = "<Table x:FullColumns=\"1\"\n x:FullRows=\"1\" ss:DefaultRowHeight=\"15\">";
    $xml .= "\n";
    
    $xml .= "<Row ss:AutoFitHeight=\"0\"/>\n";
    
    $xml .= "<Row ss:AutoFitHeight=\"0\">\n";
    $xml .= "<Cell ss:StyleID=\"yellowcell\"></Cell>\n";
    $xml .= "<Cell><Data ss:Type=\"String\">Positive</Data></Cell>\n";
    $xml .= "</Row>\n";
    $truerow++;
    
    $xml .= "<Row ss:AutoFitHeight=\"0\">\n";
    $xml .= "<Cell ss:StyleID=\"greycell\"></Cell>\n";
    $xml .= "<Cell><Data ss:Type=\"String\">Grey zone</Data></Cell>\n";
    $xml .= "</Row>\n";
    $truerow++;
    
    $xml .= "<Row ss:AutoFitHeight=\"0\">\n";
    $xml .= "<Cell ss:StyleID=\"greencell\"></Cell>\n";
    $xml .= "<Cell><Data ss:Type=\"String\">Cross reaction</Data></Cell>\n";
    $xml .= "</Row>\n";
    $truerow++;
    
    $xml .= "<Row ss:AutoFitHeight=\"0\"/>\n";
    
    foreach ($myTable->tableinfo as $infoline)
    {
        $xml .= "<Row ss:AutoFitHeight=\"0\">\n";
        $xml .= "<Cell><Data ss:Type=\"String\">$infoline</Data></Cell>\n";
        $xml .= "</Row>\n";
        $truerow++;
    }
    
    for ($i = 0; $i <= $myTable->lastrow; $i++)
    {
        $xml .= "<Row ss:AutoFitHeight=\"0\">\n";
        for($j = 0; $j <= $myTable->lastcolumn; $j++)
        {
            if($myTable->tablecolumns[$j]->get_cell_by_row_number($i) != null)
            {
                $cell = $myTable->tablecolumns[$j]->get_cell_by_row_number($i);
                
                $celltype = $cell->celltype;
                $cellvaluetype = get_cell_value_type($cell->cellvalue);
                
                $cellvaluetoprint;
                
                if ($cellvaluetype == "Number"
                        && $celltype == Cell::$celltypes["infocell"])
                {
                    $cellvaluetoprint = round($cell->cellvalue, 1);
                }
                elseif ($cellvaluetype == "Number")
                {
                    $cellvaluetoprint = round($cell->cellvalue, 0);
                }
                else
                {
                    $cellvaluetoprint = $cell->cellvalue;
                }
                
                $cellstyleid = "ss:StyleID=\"{$cell->cellstyle}\"";
                $xml .= "<Cell {$cellstyleid}><Data ss:Type=\"{$cellvaluetype}\">{$cellvaluetoprint}</Data></Cell>\n";
            } 
            else
            {
                $xml .= "<Cell />";
            }
        }
        $xml .= "</Row>\n";
    }
    
    $xml .= "</Table>\n";
    
    fwrite($output_file, $xml);
    
    $truerow++;
    $truerow++;
    $truerow++;
    
    return $truerow;
}

/**
 * Writes the xml footer to the output file.
 *
 * This function prints an xml footer for Microsoft office spreadsheet.
 *
 * @param resource $output_file The file to be written to.
 * @param int $truerow The row in the output file.
 *
 * @return void
 */
function print_footer($output_file, $truerow)
{

$xml=<<<_XML_
      <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <PageSetup>
    <Header x:Margin="0.3"/>
    <Footer x:Margin="0.3"/>
    <PageMargins x:Bottom="0.75" x:Left="0.7" x:Right="0.7" x:Top="0.75"/>
   </PageSetup>
   <Selected/>   
   <ProtectObjects>False</ProtectObjects>
   <ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
_XML_;

   
fwrite($output_file, $xml);
}

/**
 * Determines the value type of $input.
 *
 * This function attempts to determine the valye type of $input.
 * Currently it can only determine if $input is a number or a string.
 * If the data type can not be determined an empty string ("") will be
 * returned.
 *
 * @param object $input The input to determine the data type of.
 *
 * @return string The data type of the object. "Number" for number,
 *     "String" for string and "" for unknown.
 */
function get_cell_value_type($input)
{
    $value_type = "";
    
    if (is_numeric($input))
    {
        $value_type = "Number";
    }
    elseif (is_string($input))
    {
        $value_type = "String";
    }
    else
    {
        print "Error gettype function $value_type|\n";
    }
    
    return $value_type;
}

?>