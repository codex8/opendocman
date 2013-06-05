<?php
/*
Copyright (C) 2013  Stephen Lawrence Jr.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/*
 * Provide a spreadsheet report of all the files
 * 
 */

// check for session and $_REQUEST['id']
session_start();
if (!isset($_SESSION['uid']))
{
    header('Location:../index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}

include('../odm-load.php');

// open a connection to the database
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
// Check to see if user is admin
if(!$user_obj->isAdmin())
{
    header('Location:../error.php?ec=4');
    exit;
}

function cleanExcelData(&$str)
{    
    if (strstr($str, '"')) {
        $str = '"' . str_replace('"', '""', $str) . '"';
    }
    $str = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
}

// filename for download 
$filename = "file_report_" . date('Ymd') . ".csv";
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: text/csv; charset=UTF-16LE");
$out = fopen("php://output", 'w');
$flag = false;
$result = mysql_query("SELECT 
            {$GLOBALS['CONFIG']['db_prefix']}data.realname, 
            {$GLOBALS['CONFIG']['db_prefix']}data.id,
            {$GLOBALS['CONFIG']['db_prefix']}data.created,
            {$GLOBALS['CONFIG']['db_prefix']}user.username 
          FROM 
            {$GLOBALS['CONFIG']['db_prefix']}data 
          LEFT JOIN {$GLOBALS['CONFIG']['db_prefix']}user
            ON {$GLOBALS['CONFIG']['db_prefix']}user.id = {$GLOBALS['CONFIG']['db_prefix']}data.owner
                
          ") or die('Query failed!');
            
while (false !== ($row = mysql_fetch_assoc($result))) {
// display field/column names as first row 
    if (!$flag) {

        fputcsv($out, array_keys($row), ',', '"');
        $flag = true;
    }
 
    array_walk($row, 'cleanExcelData');
    fputcsv($out, array_values($row), ',', '"');
}

fclose($out);
exit;