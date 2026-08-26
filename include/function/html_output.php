<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/


/* édition liens */
function tep_href_link($page = '', $parameters = '', $connection = 'NONSSL') 
{
    if ($page == '') 
    {
      die('</td></tr></table></td></tr></table><br><br><font color="#ff0000"><b>Error!</b></font><br><br><b>Unable to determine the page link!<br><br>Function used:<br><br>tep_href_link(\'' . $page . '\', \'' . $parameters . '\', \'' . $connection . '\')</b>');
    }
    
    if ($connection == 'NONSSL') 
    {
      $link = HTTP_SERVER;
    } 
    elseif ($connection == 'SSL') 
    {
      $link = HTTP_SERVER;
      /*
        if (ENABLE_SSL == 'true') 
        {
          $link = HTTPS_SERVER . DIR_WS_ADMIN;
        } 
        else 
        {
          $link = HTTP_SERVER . DIR_WS_ADMIN;
        }
        */
    } 
    else 
    {
      die('</td></tr></table></td></tr></table><br><br><font color="#ff0000"><b>Error!</b></font><br><br><b>Unable to determine connection method on a link!<br><br>Known methods: NONSSL SSL<br><br>Function used:<br><br>tep_href_link(\'' . $page . '\', \'' . $parameters . '\', \'' . $connection . '\')</b>');
    }
    
    $sid_string = '';
    if (session_status() == PHP_SESSION_ACTIVE && !isset($_COOKIE[session_name()])) {
        $sid_string = session_name() . '=' . session_id();
    }

    if ($parameters == '') {
        $link = $link . $page . ($sid_string ? '?' . $sid_string : '');
    } else {
        $link = $link . $page . '?' . $parameters . ($sid_string ? '&' . $sid_string : '');
    }

    while ( (substr($link, -1) == '&') || (substr($link, -1) == '?') ) $link = substr($link, 0, -1);

    return $link;
}





/********************************/
/* balises formulaire	     	*/
/********************************/

//input text
function tep_draw_input_field($name, $value = '', $parameters = '', $required = false, $type = 'text', $reinsert_value = true) 
{
    $field = '<input type="' . tep_output_string($type) . '" name="' . tep_output_string($name) . '"';

    if (isset($GLOBALS[$name]) && ($reinsert_value == true) && is_string($GLOBALS[$name])) 
    {
      $field .= ' value="' . tep_output_string(stripslashes($GLOBALS[$name])) . '"';
    } 
    elseif(tep_not_null($value)) 
    {
      $field .= ' value="' . tep_output_string($value) . '"';
    }

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    //if ($required == true) $field .= TEXT_FIELD_REQUIRED;

    return $field;

}

// input hidden
function tep_draw_hidden_field($name, $value = '', $parameters = '') 
{
	$field = '<input type="hidden" name="' . tep_output_string($name) . '"';

    if (tep_not_null($value)) 
    {
      $field .= ' value="' . tep_output_string($value) . '"';
    }
    elseif (isset($GLOBALS[$name]) && is_string($GLOBALS[$name])) 
    {
      $field .= ' value="' . tep_output_string(stripslashes($GLOBALS[$name])) . '"';
    }

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    return $field;
}

//input password
function tep_draw_password_field($name, $value = '', $parameters = '', $required = false) 
{
    $parameters_pass = $parameters;
    $parameters_pass .= ' maxlength="40"';
	
    $field = tep_draw_input_field($name, $value, $parameters_pass, $required, 'password', false);

    return $field;
}



//input liste défilante
function tep_draw_pull_down_menu_admin($name, $values, $default = '', $parameters = '') 
{
   	   
    $field = '<select name="' . tep_output_string($name) . '"';

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    for ($i=0, $n=sizeof($values); $i<$n; $i++) 
    {
      	$field .= '<option value="' . tep_output_string($values[$i]['id']) . '"';
      	if($default == $values[$i]['id']){$field .= ' SELECTED';}

      	$field .= '>' . tep_output_string(affichemots($values[$i]['text'],5), array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>';
    }
    $field .= '</select>';


    return $field;
}  




//input liste défilante ROOT
function tep_draw_pull_down_menu_admin_root($name, $values, $root='', $default = '', $parameters = '') 
{
    
	$field = '<select name="' . tep_output_string($name) . '"';

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';
	
	if (tep_not_null($root)) $field .= '<option value="0">['.$root.']</option>';

    for ($i=0, $n=sizeof($values); $i<$n; $i++) 
    {
      	$field .= '<option value="' . tep_output_string($values[$i]['id']) . '"';
      	if($default == $values[$i]['id']){$field .= ' SELECTED';}

      	$field .= '>' . tep_output_string(affichemots($values[$i]['text'],5), array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>';
    }
    $field .= '</select>';


    return $field;
}  




function tep_draw_input_rubrique($values) 
{
    $content = '';	
    
    for ($i=0;$i<sizeof($values);$i++) 
    {
      	$content .= $values[$i]['text'];
    }

    
    return $content;
}  





?>
