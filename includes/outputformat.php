<?php
/* Handles output for text, the $type variable in the function calls specifies output method
 * the OF_TBL constant is used for HTML output
 * the OF_CSV constant is used for CSV output 
 * */

// starts page display, including the header file specified or specifying csv download headers
function output_start ($type, $page_title, $incfile){
    $log_user = &$GLOBALS["log_user"];
    if ($type==OF_TBL){
        require_once "$incfile";
        return "";
    }    
    elseif ($type==OF_CSV){
        set_time_limit(0);
        $output = fopen("php://output",'w') or die("Can't open php://output");
        header('Expires: 0');
        header('Cache-control: private');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');
        header('Content-type: text/csv');
        header("Content-disposition: attachment; filename=\"$page_title\"");
        return $output;
    }
    
}

// outputs text and a line break
function output_line($type, $text, $csv_display=true, $output){
    if ($type==OF_TBL){
        print "$text<br>\n";
    }
    elseif ($type==OF_CSV && $csv_display){
        fputcsv($output, array($text));
    }
}

// outputs line, plus html output has a "more info" link that will display third argument
function output_site_info($type, $title, $text, $output)
{
    if ($type==OF_TBL){
        if ($title) {
            print "<h2>$title</h2>\n";
        }
        if ($text) {
            $id=substr($title, 0,5).rand(1,100);
            print "<div id='$id' style='display:none'>$text</div><a name='#id' href='#id' id='moreinfo_$id' onclick=\"document.getElementById('$id').style.display='';document.getElementById('moreinfo_$id').style.display='none';\">More info</a>\n";
        }
    }    
    elseif ($type==OF_CSV){
        if ($title) {
            fputcsv($output,array($title));
        }
    }
}

//displays header row, html output opens table tag and displays sort arrows
// It's very important that the $units and $cols arrays have the same number of values, in the same order
function output_header($type, $cols, $sort=array(), $text="", $units="", $output){
    
    if ($type==OF_TBL){
        print "<table class=\"datatable\">\n";
        
        if (sizeof($cols)>0){
            $link = $_SERVER["REQUEST_URI"];
            $link = preg_replace("/&sortkey=.*&/", "&", $link);
            $link = preg_replace("/&sortdir=.*&/", "&", $link);
            $link = preg_replace("/&sortdir=.*/", "", $link);
            
            print "<TR>";
            print "<TH>$cols[0]</TH>";
            for ($i=1;$i<sizeof($cols);$i++) {
                
                // There will not always be a sort field or unit field 
                //  to match the corresponding columns field (esp. for precip).
                $theUnit = isset($units[$i]) ? $units[$i] : "";
                $theSort = isset($sort[$i]) ? $sort[$i] : "";
            
                if ($theSort) {
                    print "<TH>$cols[$i] ($theUnit) <a href=\"$link&sortkey=$sort[$i]&sortdir=DESC\"><img src=\"images/sort_arrow.png\"></a><a href=\"$link&sortkey=$sort[$i]&sortdir=ASC\"><img src=\"images/sort_arrowup.png\"></a></TH>";
                }
                else print "<TH>$cols[$i] ($theUnit)</TH>";
            }
            print "</TR>\n";
        }
    }        
    elseif ($type==OF_CSV){
        if ($text) {
            fputcsv($output,array($text));
        }
        $colsArray = array();
        
        // Each column and (if it exists) notes field gets a separate CSV column.
        if (sizeof($cols)>0){
            $colsArray[0] = "Site ID";
            $j = 1;
            for ($i=1;$i<(sizeof($cols));$i++)
            {
                if(isset($units[$i])) {
                    $colsArray[$j] = $cols[$i] . " " . $units[$i];
                } else{
                    $colsArray[$j] = $cols[$i];
                };
                $j++;
                $colsArray[$j] = $cols[$i] . " Notes";
                $j++;
            }
            fputcsv($output,$colsArray);
        }
    }    
}

// display an output row, with values in second variable, with output formatting in the third argument
// $type here is the same as $output_type in measurements_report.php.
function output_row ($type,$values_ar,$values_type, $output){
    $log_user   = &$GLOBALS["log_user"];
    $output_ar  = array();
    $notes_ar   = array();
    $edit_link  = isset($values_ar["edit_link"]) ? $values_ar["edit_link"] : null;
    unset($values_ar["edit_link"]);
    $values     = array_values($values_ar);
    
    for ($i=0;$i<sizeof($values_ar);$i++)
    {
        if(strpos($values[$i], "|")>0) 
        {
            list($values[$i],$notes_ar[$i]) = explode("|", $values[$i], 2);
        }
        $data_type=$values_type[$i];
        switch ($data_type) {
            case DATA_FLOAT2:
                if ($values[$i]===NULL ) {$output_ar[$i]=""; }
                        else {
                    if (strpos($values[$i], "<")===0)
                        $output_ar[$i] = sprintf("<%.2f",substr($values[$i],1));
                    else  
                        $output_ar[$i] = sprintf("%.2f",$values[$i]);
                }
            break;
            case DATA_FLOAT5:
                if ($values[$i]===NULL) {$output_ar[$i]="";}
                else {
                    if (strpos($values[$i], "<")===0)
                        $output_ar[$i] = sprintf("<%.5f",substr($values[$i],1));
                    else  
                        $output_ar[$i] = sprintf("%.5f",$values[$i]);
                }
            break;
            case DATA_DAYTIME:
                if ($values[$i]===NULL) {$output_ar[$i]="NULL";}
                else $output_ar[$i] = $values[$i];
            break;
            case DATA_INT:
                if ($values[$i]===NULL) {$output_ar[$i]="NULL";}
                else $output_ar[$i] = sprintf("%d",$values[$i]);
            break;
            case DATA_STRING:
            default:
                $output_ar[$i] = $values[$i];
                if ($type==OF_CSV){
                    if (preg_match("/,/",$values[$i]))
                        $output_ar[$i] = '"'.$values[$i].'"';
                }
            break;
        }
    }    
    if ($type==OF_TBL){
        // Looks like $notes_ar and $output_ar are keyed the same, so that $i references values from the same measurement in each
        // Commented out line prevents non logged in users from seeing notes 
        //if (!$log_user->is_logged_in()) {$notes_ar=array();}
        if (sizeof($output_ar)>0){
            print "<TR>";
            for ($i=0;$i<sizeof($output_ar);$i++)
            {
                $notesArr = isset($notes_ar[$i]) ? $notes_ar[$i] : null;
                print "<TD ".($notesArr ? "title=\"".$notesArr."\" class=\"datanote\">" : ">")."$output_ar[$i]</TD>";
            }
            //if ($log_user->is_logged_in()) {print "<TD><a href='add_measurement.php?action=edit&waterbodyid=".$_GET['waterbodyid']."&siteid=".$_GET['siteid']."&date=".$output_ar[0]."'>edit</a></TD>";}
            if ($edit_link && $log_user->is_logged_in())
            {
                print "<TD><a href=\"$edit_link\">edit</a></TD>";
            }
            print "</TR>\n";
        }
    }        
    elseif ($type==OF_CSV){
        if (sizeof($output_ar)>0){
            
            $lineArr    = array();
            $lineArr[0] = $output_ar[0];
            //print "$output_ar[0],";
            
            $j = 1;
            for ($i=1;$i<(sizeof($output_ar));$i++)
            {
                $lineArr[$j] = $output_ar[$i];
                $j++;
                $lineArr[$j] = isset($notes_ar[$i]) ? $notes_ar[$i] : "";
                $j++;
                
                //if(isset($notes_ar[$i])) {
                //    $lineArr[$j] = $notes_ar[$i];
                //    $j++;
                //}
            }
            fputcsv($output,$lineArr);
        }
    }
}
// closes table tag for html
function output_footer($type){
    if ($type==OF_TBL){
        print "</table>\n\n";
    }    
}
//includes the file to end the page
function output_end($type, $incfile){
    if ($type==OF_TBL){
        require_once $incfile;
    }    
}
?>