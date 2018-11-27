<?php 


class New_dumper extends MY_Controller {

	
	function __construct() {
		parent::__construct();
		ini_set('memory_limit', '-1');   			
		ini_set('max_execution_time', 0);	
		set_time_limit(0);		
	}


	function create_base_file(){		
		$database = 'hcmp_rtk';
   		$mysqli = new mysqli("localhost", "root", "hPlaB", "hcmp_rtk");
   		// $mysqli = new mysqli("localhost", "root", "", "hcmp_rtk");
	   	 if (mysqli_connect_errno()) {
		    printf("Connect failed: %s", mysqli_connect_error());
		    exit();
		}
	  	ini_set('memory_limit', '-1');   		
   		$filename = 'tmp/base_offline.sql';	
   		unlink($filename);
   		$header = "DROP DATABASE IF EXISTS `$database`;\n\nCREATE DATABASE `$database`;\n\nUSE `$database`;\n\n";
   		$query = '';
		$handle = fopen($filename, 'w');
		$mysqli->close();//die();
		
		$final_output = $header.$query;
		fwrite($handle, $final_output);
		fclose($handle);
		
	}

	function create_core_tables($start,$stop){
		$database = 'hcmp_rtk';
   		$mysqli = new mysqli("localhost", "root", "hPlaB", "hcmp_rtk");
   		// $mysqli = new mysqli("localhost", "root", "", "hcmp_rtk");
	   	 if (mysqli_connect_errno()) {
		    printf("Connect failed: %s", mysqli_connect_error());
		    exit();
		}
	  	ini_set('memory_limit', '-1');   

	  	$filename = 'tmp/base_offline.sql';	
	  	$query = '';
		$handle = fopen($filename, 'a');

		$core_tables = array('access_level','assignments','comments','commodities','commodity_category','commodity_division_details','commodity_source','commodity_source_other','commodity_source_sub_category','commodity_sub_category','counties','county_drug_store_issues','county_drug_store_totals','county_drug_store_transaction_table','districts','drug_commodity_map','drug_store_issues','drug_store_totals','drug_store_transaction_table','email_listing','email_listing_new','facilities','git_log','issue_type','menu','malaria_drugs','rca_county','recepients','sub_menu','service_points','redistribution_data','receive_redistributions','log','log_monitor','db_sync','facility_order_status','facility_order_details_rejects','facility_order_details','dispensing_records','facility_loggins','facility_rollout_status','inventory','patient_issues','patients','selected_service_points','service_point_stock_physical','status','sync_updates','update_log','dispensing_totals');
		
		for ($i=$start; $i <$stop ; $i++) { 
		// for ($i=0; $i <count($core_tables) ; $i++) { 
			
			$table_name = $core_tables[$i];

			$sql_create = "SHOW CREATE TABLE `$table_name`";  		

			$fields_total = null;
			$create_table = '';
		    $inserts = '';
		   	$rows_total = null;   
		   	if ($result = $mysqli->query($sql_create)) {
		   		
		   		while($row = $result->fetch_assoc())
			    {
			    	$create_table ="\n".$row['Create Table'].";\n\n";	 	//Write the Table Creates			    
				    fwrite($handle, $create_table);  
			    }

				$column_types_array = array();		
				$sql_column_types = "SHOW COLUMNS FROM `$table_name`";
				if ($result_sql_columns = $mysqli->query($sql_column_types)) {						
					while($row = $result_sql_columns->fetch_assoc())
				    {
				    	
						$type = $row['Type'];
					    $column_types_array[] = $type;
						
				    }
				}
				

			    $row_cnt = $result->num_rows;
			    
			    
			    $sql = "select distinct * FROM $table_name  $condition";	
			    $multi_values = '';
			   	if ($result_sql = $mysqli->query($sql)) {
			    	

			    	$result_fields = array();	
			    	while($row = $result_sql->fetch_assoc())
				    {
				    	$result_fields[] = $row;	        
				    }
				    
			    	
			   		if(count($result_fields)>0){
				  	 	$fields = array_keys($result_fields[0]); 				  	 	
				   	 	$string = '';
						foreach ($fields as $key => $value) {
							$string .= ",`$value`";
						}
						
						$string = substr($string, 1); // remove leading ","						
						$fields_total = '('.$string.')';

						$new_insert = 'INSERT INTO '.$table_name.$fields_total.' VALUES ';
						fwrite($handle, $new_insert);
						
						$count_r = 0;

						for ($j=0; $j <count($result_fields) ; $j++) {
							$data = array_values($result_fields[$j]); 
							
					  	 	$values = '';
					   	 	$count = 0;
							foreach ($data as $keys => $row) {

								$row_types = $column_types_array[$keys];
								if (strpos($row_types, 'int') !== false) {
									$row = ($row!='') ?$row : 0 ;
									if($count==0){
										$values .= "$row";				
									}else{
										$values .= ",$row";				
									}
								    
								}else{
									$row = ($row!='')?$row : '';									
									$row = addslashes($row);
									
									if($count==0){
										$values .= "'$row'";				
									}else{
										$values .= ",'$row'";				
									}							    	
								}
								$count++;								
							}

						
							if($count_r==0){
								$rows_total = '('.$values.')';									
							}else{
								$rows_total = ',('.$values.')';								
							}


							fwrite($handle, $rows_total);
							$count_r++;
						}
						fwrite($handle, ";\n\n");				
						
					}
			   }

		    /* close result set */
		    	$result->close();
			}	 
			// die;
		}

		$mysqli->close();//die();				
		
		fclose($handle);


	}




}



?>