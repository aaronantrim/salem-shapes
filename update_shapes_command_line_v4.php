<?php 


if (isset($argv[1])) {

// set distance threshhold (in meters)
$distance_threshold = 1;

// borrowed from https://gist.github.com/jaywilliams/385876

function csv_to_array($filename='', $delimiter=',')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;
	
	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
		{
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}


// function to sort a multidimensional array by sql like order by clause
// from https://gist.github.com/tufanbarisyildirim/1220785

    /**
    * @name Mutlidimensional Array Sorter.
    * @author Tufan Barış YILDIRIM
    * @link http://www.tufanbarisyildirim.com
    * @github http://github.com/tufanbarisyildirim
    *
    * This function can be used for sorting a multidimensional array by sql like order by clause
    *
    * @param mixed $array
    * @param mixed $order_by
    * @return array
    */
    
    function sort_array_multidim(array $array, $order_by)
    {
        //TODO -c flexibility -o tufanbarisyildirim : this error can be deleted if you want to sort as sql like "NULL LAST/FIRST" behavior.
        if(!is_array($array[0]))
            throw new Exception('$array must be a multidimensional array!',E_USER_ERROR);
 
        $columns = explode(',',$order_by);
        foreach ($columns as $col_dir)
        {
            if(preg_match('/(.*)([\s]+)(ASC|DESC)/is',$col_dir,$matches))
            {
                if(!array_key_exists(trim($matches[1]),$array[0]))
                    trigger_error('Unknown Column <b>' . trim($matches[1]) . '</b>',E_USER_NOTICE);
                else
                {
                    if(isset($sorts[trim($matches[1])]))
                        trigger_error('Redundand specified column name : <b>' . trim($matches[1] . '</b>'));
 
                    $sorts[trim($matches[1])] = 'SORT_'.strtoupper(trim($matches[3]));
                }
            }
            else
            {
                throw new Exception("Incorrect syntax near : '{$col_dir}'",E_USER_ERROR);
            }
        }
 
        //TODO -c optimization -o tufanbarisyildirim : use array_* functions.
        $colarr = array();
        foreach ($sorts as $col => $order)
        {
            $colarr[$col] = array();
            foreach ($array as $k => $row)
            {
                $colarr[$col]['_'.$k] = strtolower($row[$col]);
            }
        }
       
        $multi_params = array();
        foreach ($sorts as $col => $order)
        {
            $multi_params[] = '$colarr[\'' . $col .'\']';
            $multi_params[] = $order;
        }
 
        $rum_params = implode(',',$multi_params);
        eval("array_multisort({$rum_params});");
 
 
        $sorted_array = array();
        foreach ($colarr as $col => $arr)
        {
            foreach ($arr as $k => $v)
            {
                $k = substr($k,1);
                if (!isset($sorted_array[$k]))
                    $sorted_array[$k] = $array[$k];
                $sorted_array[$k][$col] = $array[$k][$col];
            }
        }
 
        return array_values($sorted_array);
 
    }



function distance($lat1, $lon1, $lat2, $lon2) { 

	if (($lat1 == $lat2) && ($lon1 == $lon2)) {return 0;}

  $theta = $lon1 - $lon2; 
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
  $dist = acos($dist); 
  $dist = rad2deg($dist); 
  $miles = $dist * 60 * 1.1515;
  $meters = $miles * 1609.34;

  return $meters;

}




$input_file_path = $argv[1];
// $input_file = fopen($input_file_path, 'r');

    $raw_shape_data_array = csv_to_array($input_file_path);


$row_count = count($raw_shape_data_array);

$cleaned_shape_data = array();

$i = 0;

function add_to_cleaned_data_array($shape_id,$shape_pt_lat,$shape_pt_lon,$shape_pt_sequence,$old_shape_pt_sequence,$shape_dist_traveled=null) {
global $cleaned_shape_data;
array_push($cleaned_shape_data, array($shape_id,$shape_pt_lat,$shape_pt_lon,$shape_pt_sequence,$shape_dist_traveled,$old_shape_pt_sequence));
$next_shape_pt_sequence = $shape_pt_sequence+1;
return $next_shape_pt_sequence;

$GLOBALS['i']++;

}


if (isset($raw_shape_data_array)) {

$sorted_shape_data_array = sort_array_multidim($raw_shape_data_array,'shape_id ASC,shape_pt_sequence ASC');




$next_row_index = 0;
$last_shape_id = null;
$new_row = array();

foreach ($sorted_shape_data_array as $row_value) {

	if ($last_shape_id != $row_value['shape_id']) {
		$new_shape_pt_sequence = 0;
		}

	// set up $next_row array	

	$next_row_index = $i + 1;
	
	if (isset($sorted_shape_data_array[$next_row_index])) {	
		$next_row = array($sorted_shape_data_array[$next_row_index]['shape_id'],
		$sorted_shape_data_array[$next_row_index]['from_shape_pt_lat'],
		$sorted_shape_data_array[$next_row_index]['from_shape_pt_lon'],
		$sorted_shape_data_array[$next_row_index]['to_shape_pt_lat'],
		$sorted_shape_data_array[$next_row_index]['to_shape_pt_lon'],
		$sorted_shape_data_array[$next_row_index]['shape_pt_sequence']);
		}
	
	else {$next_row = array(null,null,null,null,null,null);
		}

	// set up the values for the row to be added
	
	$new_row['shape_id'] = $row_value['shape_id'];
	$new_row['shape_pt_sequence'] = $new_shape_pt_sequence;

	// now, if this is the first shape_pt_sequence for a shape, compare to the subsequent segment.
	if ($new_shape_pt_sequence == 0) {
	
		if (distance($row_value['from_shape_pt_lat'], $row_value['from_shape_pt_lon'], $next_row[3], $next_row[4]) < $distance_threshold) {
		
			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['to_shape_pt_lat'],
				$row_value['to_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
			
			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['from_shape_pt_lat'],
				$row_value['from_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);

			
		}
		
		else {
			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['from_shape_pt_lat'],
				$row_value['from_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
			
			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['to_shape_pt_lat'],
				$row_value['to_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);	
			}
		}
	
	else {
		
		// make sure that this is not a duplicate of the previous row, and only add this to the cleaned data if it is unique.
		// evaluate if the last added shape_pt values match the values for from_shape_pt in the current row, if so, then add the shape_to_values to the cleaned data.  if not, then assume the line is reversed, and add the from_shape_pt values


		$last_added_row = end($cleaned_shape_data);


// this was the previous approach
// $last_added_row = end($cleaned_shape_data);
		
		if (distance($row_value['from_shape_pt_lat'], $row_value['from_shape_pt_lon'], $last_added_row[1], $last_added_row[2]) < $distance_threshold) {
		
//			$to_from = 1;
			
			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['to_shape_pt_lat'],
				$row_value['to_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
			
			
			if (distance($row_value['from_shape_pt_lat'], $row_value['from_shape_pt_lon'],  $next_row[1], $next_row[2]) < $distance_threshold)
			
			{
				$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['from_shape_pt_lat'],
				$row_value['from_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
			}
			
			
			}
		
		elseif (distance($row_value['to_shape_pt_lat'], $row_value['to_shape_pt_lon'], $last_added_row[1], $last_added_row[2]) < $distance_threshold) {
			
			

			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['from_shape_pt_lat'],
				$row_value['from_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
			
			if (distance($row_value['to_shape_pt_lat'], $row_value['to_shape_pt_lon'],  $next_row[3], $next_row[4]) < $distance_threshold)
			
			{
				$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['to_shape_pt_lat'],
				$row_value['to_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
				
			}

	
//			$to_from = 0;
			
			}
			
		else {
		
			if (distance($row_value['to_shape_pt_lat'], $row_value['to_shape_pt_lon'], $last_added_row[1], $last_added_row[2]) < distance($row_value['from_shape_pt_lat'], $row_value['from_shape_pt_lon'],  $next_row[1], $next_row[2])) {

			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['to_shape_pt_lat'],
				$row_value['to_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
				
			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['from_shape_pt_lat'],
				$row_value['from_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
				
			  }
			  
			else {
				
			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['from_shape_pt_lat'],
				$row_value['from_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);

			$new_shape_pt_sequence=add_to_cleaned_data_array(
				$row_value['shape_id'],
				$row_value['to_shape_pt_lat'],
				$row_value['to_shape_pt_lon'],
				$new_shape_pt_sequence,
				$row_value['shape_pt_sequence']
				);
			
				}
			  
			}
		
	}


// set up $last_row array
	$last_shape_id = $row_value['shape_id'];
	
	$i++;
	}

}

if (isset($cleaned_shape_data)) {

echo 'shape_id,shape_pt_lat,shape_pt_lon,shape_pt_sequence,shape_dist_traveled,old_shape_pt_sequence
';

foreach ($cleaned_shape_data as $row_value) {
    echo $row_value[0].','.$row_value[1].','.$row_value[2].','.$row_value[3].','.$row_value[4].','.$row_value[5].'
';
}

}

}

else {
echo "No argument for a file location was provided.";
}


?>