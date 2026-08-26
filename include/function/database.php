<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

function tep_db_close($sql_link)
{
    return mysqli_close($sql_link);
}


function tep_db_error($sql_link,$query) 
{ 
    die('<font color="#000000"><b>ERROR<br><br>' . $query . '<br><br><small><font color="#ff0000">[TEP STOP]</font></small><br><br></b></font>');
}


function tep_db_query($sql_link,$query) 
{
    $result = mysqli_query($sql_link,$query) or tep_db_error($sql_link,$query);
    return $result;
}
 
function tep_db_fetch_array($db_query) 
{
    return mysqli_fetch_array($db_query, MYSQLI_ASSOC);
} 
 

function tep_db_num_rows($db_query) 
{
    return mysqli_num_rows($db_query);
}

?>