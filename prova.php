
<?php

$count_cards = 0;

define('PRINT_R', 1);
define('JSON',2);
define('SQLITE3',3);
$format = JSON;


//internal data, do not touch
$first = TRUE;
$db = NULL;
//end
$columns = array("name" => "name","number" =>"id","lore" => "desc","attribute"=>"attribute","type" =>"type","atk"=>"atk","def"=>"def","level"=>"level");

function write_header()
{
  global $format,$db,$columns;
  if($format == JSON)
    echo "[";
  if($format == SQLITE3)
   { 
	$db = new SQLite3('mysqlitedb.db');
	$query = "create table cards(";
	foreach ($columns as $c => $d)
	  $query .= "$d text, ";
	$query = substr($query,0,-2);
	$query .=");";
	$db->exec("drop table if exists cards;");
	$db->exec($query);
	
	
   }
}


function write_content($card)
{
  global $format,$first,$columns,$db;
  if($format == PRINT_R)
    print_r($card);
  if($format == JSON)
    if($first)
    {
      echo "\n". json_encode($card);
      $first = FALSE;
    }
    else
     { echo ",\n".json_encode($card);
    }
  if($format == SQLITE3)
  {
    $query = "insert into cards(";
    foreach ($columns as $c => $d)
	  $query .= "$d, ";
    $query = substr($query,0,-2);
	$query .=") values (";
    foreach ($columns as $c => $d)
	  $query .= "?, ";
    $query = substr($query,0,-2);
	$query .=");";
	
    $stmt = $db->prepare($query);
    $i=1;
    foreach ($columns as $c => $d)
	  $stmt->bindValue($i++,isset($card[$c])?$card[$c]:NULL,SQLITE3_TEXT);
    $stmt->execute();
    
    
   
  
  }
  
  
  
}
function write_footer()
{
  global $format;
  if($format == JSON)
     echo "\n]";
}



function clean_array($buf)
{
    global $count_cards;
    $count_cards++;

    $output = array();

    foreach ($buf as $key => $value)
    {

      if(strpos($key,"lore") !== FALSE)
      {
	$value = htmlspecialchars_decode($value);
	
	$temp = preg_replace('/\[\[([\w\s]+\|)?([\w\s\.]+)\]\]/', '${2}', $value); 
	$temp = preg_replace('/<br\s?\/?>/', "\n", $temp); 
	$value =$temp;
	
      }
      if(strpos($key,"sets") === FALSE)
      {
	$output[$key] = $value;
      }
    }  
    return $output;
}

function parse($buf)
{
      $output = array();
      preg_match_all("/\|(\w+)\s=\s(.*)\n/",$buf,$output);
      $risultato = array_combine($output[1],$output[2]);

      $risultato = clean_array($risultato);
      
      if(preg_match("/<title>(.+)<\/title>/",$buf,$output))
      {
	
	$risultato["name"] = $output[1];
      }
      else
      {
        print($buf);
      }
	//
      
      return $risultato;
}


function main()
{
    
global $parsed_cards;
    $handle = fopen("./yugioh_pages_current.xml", "r");
    if(!$handle)
	    die("errore");

    $status = 0;

    $buf = "";
   write_header();
    while (!feof($handle)) {
	$buffer = fgets($handle, 4096);
	if($buffer == "  <page>\n")
	    $status = 1;

	    $buf .= $buffer;
	if($buffer == "  </page>\n")
	{
	    $status = 0;
	    if(strpos($buf,"|lore ") >0)
	    {
		$r = parse($buf);
		write_content($r);	    
	    }

	    $buf = "";
	}
    }
    write_footer();
    fclose($handle);
    
    
}
main();
?>

