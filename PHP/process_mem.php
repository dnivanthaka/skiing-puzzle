#!/usr/bin/php
<?php
//set_time_limit(0);

// Memory based version
//TODO Merge these two files and make one script which accept commandline parameters to switch into temp file and memory modes.
/*Results from running time ./process_mem.php (Typical)
real    4m50.467s
user    4m48.108s
sys     0m2.052s
*/

/* Define filter class to compute node count and difference calculation */
class nodediff_filter extends php_user_filter {
  function filter($in, $out, &$consumed, $closing)
  {
    while ($bucket = stream_bucket_make_writeable($in)) {
      $data_arr = explode(' ', $bucket->data);
      $bucket->data = strtoupper($bucket->data.' '.count($data_arr).' '.(int)((int)$data_arr[0] - (int)$data_arr[count($data_arr) - 1])."\n");
      $consumed += $bucket->datalen;
      stream_bucket_append($out, $bucket);
    }
    return PSFS_PASS_ON;
  }
}


$data = [];
$dimensions = 0;


function readCSVData($filename, &$dimensions, &$data, $header_dim = true, $is_mem = false){
  $row = 1;

  if(!$is_mem){
    $handle = fopen($filename, "r");
  }else{
    $handle = $filename;
  }
  
  if ($handle !== FALSE) {
      while (($line = fgetcsv($handle, 0, " ")) !== FALSE) {
	  $num = count($line);
	  //echo "$num fields in line $row: \n";
	  
	  if($header_dim && $row == 1){
	    $dimensions = $line;
	    $row++;
	    continue;
	  }
	  
	  $data[] = $line;
	  $row++;
      }
      
      if(!$is_mem){
	fclose($handle);
      }
  }
}


function findValue($data, $val){
  $row = 0;
  $i = 0;
  $search = [];
  
  foreach($data as $key=>$value){
    $result = array_keys($value, $val);
    if(is_array($result) && count($result) > 0){
      $search[$i++] = [$val, $row, $result[0]];
    }
    
    $row++;
  }
  
  //print_r($search);
  return $search;
}

function findMatching(&$data){
  $max_lines = [];
  $max_line = 0; $max_nodes = 0; $max_diff = 0;
  $cur_max_nodes = 0;
 
  foreach($data as $key=>$value){
    $cnt = count($value);
    if($cnt > 0){
	//print_r($value);
	$nodes = $value[$cnt - 2];
	$diff = $value[$cnt - 1];
	
	//echo $nodes.' '.$diff."\n";
	
	if( $nodes >= $cur_max_nodes ){
	    $cur_max_nodes = $nodes;
	  $max_lines[] = array($diff, $nodes, $value);
	}
    }
  }
  
  
  $max_ind = array_keys($max_lines, max($max_lines));
  return $max_lines[$max_ind[0]];
}

function parseData($dimensions, &$data, &$graphs, $search_start, $search_end, $temp_fp){    
  $search_val = 0;    
  for($i=$search_start; $i > $search_end; $i--){
    $search_val = $i;
    //echo $search_val."\n";
    $result = findValue($data, $search_val);
    //print_r($result);
    $y = 0;
    //$j = 0;

    foreach($result as $key=>$value){
      if(is_array($value) && count($value) > 0){
	  $pivot = $value;
	  //print_r($pivot);
	  $graphs = traverseNodes($dimensions, $data, $pivot, $temp_fp);
      }
      $y++;
    }
  }
}

function findLongestNodeCount(&$graphs){
  $count = 0;
  $i = 0;
  $result = [];
  
  foreach($graphs as $grkey=>$grvalue){
      if(count($grvalue) >= $count){
	$count = count($grvalue);
	$result[] = $grvalue;
      }
  }
  
  print_r($result);
}
//print_r($data);

function save_graphs($graphs){
  print_r($graphs);
}

function traverseNodes($dimensions, &$data, $pivot, $temp_fp){
    static $graphs, $i = 0;
    
    //print_r($pivot);

    $val = $pivot[0];
    $y   = $pivot[1];
    $x   = $pivot[2];
    
    $printOk = true;
    //echo $val;
    #Looking at top node
    if (($y - 1) >= 0 && (int)($data[$y - 1][$x]) < (int)($val)){
        //$graphs[$i][] = $val;
        $graphs .= $val.' ';
        traverseNodes($dimensions, $data, array($data[$y - 1][$x], ($y - 1), $x), $temp_fp);
        
        $printOk = false;
    }
    
    #Looking at bottom node
    if (($y + 1) < $dimensions[0] && (int)($data[$y + 1][$x]) < (int)($val)){
        //$graphs[$i][] = $val;
        $graphs .= $val.' ';
        traverseNodes($dimensions, $data, array($data[$y + 1][$x], ($y + 1), $x), $temp_fp);
        
        $printOk = false;
    }
    
    #Looking at right node
    if (($x + 1) < $dimensions[1] && (int)($data[$y][$x + 1]) < (int)($val)){
        //$graphs[$i][] = $val;
        $graphs .= $val.' ';
        traverseNodes($dimensions, $data, array($data[$y][$x + 1], $y, ($x + 1)), $temp_fp);
        
        $printOk = false;
    }
    
     #Looking at left node
    if (($x - 1) >= 0 && (int)($data[$y][$x - 1]) < (int)($val)){
        //$graphs[$i][] = $val;
        $graphs .= $val.' ';
        traverseNodes($dimensions, $data, array($data[$y][$x - 1], $y, ($x - 1)), $temp_fp);
        
        $printOk = false;
    }
    
    if($printOk){
        //$graphs[$i][] = $val;
	$i++;
	$graphs .= $val;
	$arr = explode(" ", $graphs);
	$node_count = count($arr);
	$node_diff = ((int)$arr[0] - (int)$arr[count($arr) - 1]);
	//$graphs .= $val."\n";
	
	//fwrite($temp_fp, $graphs.' '.$node_count.' '.$node_diff."\n");
	fwrite($temp_fp, $graphs);
	$graphs = "";
	
	//fwrite($temp_fp, $val."\n");
	//save_graphs($graphs);
	$i = 0;
    }
    //echo $val;
    //echo $max_nodes.' '.$max_diff."\n";
    
   return $graphs;
}

/* Register filter with PHP */
stream_filter_register("nodediff", "nodediff_filter")
    or die("Failed to register filter");

$graphs = [];

readCSVData("../Data/map.txt", $dimensions, $data);

//print_r($data);
print_r($dimensions);
//print_r(traverseNodes($dimensions, $data, array(8, 1, 2)));
//$fp = fopen('temp_php.txt', 'w+');
$fp = fopen('php://memory', 'w+');
/* Attach the registered filter */
stream_filter_append($fp, "nodediff", STREAM_FILTER_WRITE);
//echo 'XXX'.$fp;
parseData($dimensions, $data, $graphs, 1500, -1, $fp);
rewind($fp);
//echo $fp;
//print_r(explode(' ', '1 2 3 4'));
unset($data);
$data = [];
readCSVData($fp, $dimensions, $data, false, true);


fclose($fp);
//print_r($data);
print_r(findMatching($data));

