<?php 
/**
 * @author Karsan
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Dumper extends MY_Controller {

	function __construct() {
		parent::__construct();
		ini_set('memory_limit', '-1');   			
		ini_set('max_execution_time', 0);	
		set_time_limit(0);
		//this comment is for you nigga
		//erase it 
		//hahaha
		// $this->load->model('dumper_model','dumper_model');
	}

	public function create_zip($facility_code)
	{
		$sql_filepath = $this->setup_files($facility_code);
		$this->update_rollout_status($facility_code);
		$this->create_specific_tables($facility_code);
		$this->create_bat($facility_code);
		
		$expected_zip_sql_filepath = $facility_code.'/'.$facility_code.'.sql';

		$bat_filepath = 'tmp/'.$facility_code.'_install_db.bat';
		$expected_zip_bat_filepath = $facility_code.'/'.$facility_code.'_install_db.bat';

		$ini_filepath = 'offline/my.ini';
		$expected_ini_filepath = $facility_code.'/'.'my.ini';

		$expected_old_filepath = $facility_code.'/old/';

		$zip = new ZipArchive();
		$zip_name = $facility_code.'.zip';
		$zip->open($zip_name, ZipArchive::CREATE);

		$zip->addFile($sql_filepath, ltrim($expected_zip_sql_filepath,'/'));
		$zip->addFile($bat_filepath, ltrim($expected_zip_bat_filepath,'/'));
		$zip->addEmptyDir($expected_old_filepath);

		$zip->addFile($ini_filepath, ltrim($expected_ini_filepath,'/'));

		$zip->close();
		// ob_end_clean();
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		// header("Content-Length: ". filesize("$zip_name").";");
		header("Content-Disposition: attachment; filename=$zip_name");
		header("Content-type: application/zip"); 
		header("Content-Transfer-Encoding: binary");

		readfile($zip_name);

		unlink($sql_filepath);
		unlink($bat_filepath);
		unlink($zip_name);
		

		
	}

	public function setup_files($facility_code){
		$from = 'tmp/base_offline.sql';	
		$to = 'tmp/'.$facility_code.'.sql';
		if(!copy($from,$to)){
			return false;
		}
		return $to;
	}

	public function update_rollout_status($facility_code){
		$date = date('Y-m-d h:i:s');
		$sql ="update facilities set using_hcmp = '2',date_of_activation= '$date' where facility_code = '$facility_code'";
		$this->db->query($sql);
	}


	public function dump_db($facility_code,$db){
		$this->create_core_tables($facility_code,$db);		
	}

	public function gen_bat($facility_code){
		$db = 'hcmp_rtk';
		$this->create_bat($facility_code);
		$this->dump_db($facility_code,$db);
	}



public function create_bat($facility_code)
 	{ 		
		ini_set('memory_limit', '-1');   		
   		$filename = 'tmp/'.$facility_code.'_install_db.bat';
   		$resource_name = $facility_code.'.sql';
   		$header = '@echo OFF';
   		$header .= PHP_EOL;
   		$header .= 'set current=%~dp0';
   		$header .= PHP_EOL;

   		$header .= "set old_cnf=old\\";
   		$header .= PHP_EOL;
   		$header .= "set target_adt=C:\\xampp\\htdocs\\ADT";
   		$header .= PHP_EOL;
   		$header .= "set old_cnf_file=old\\my.ini";
   		$header .= PHP_EOL;
   		//$header .= "set new_cnf=resources\\mysql\\new\\";
   		$header .= PHP_EOL;
   		$header .= "set new_cnf_file=my.ini";
   		$header .= PHP_EOL;

   		$header .= "set target_cnf=C:\\xampp\\mysql\\bin\\";
   		$header .= PHP_EOL;
   		$header .= "set target_cnf_file=C:\\xampp\\mysql\\bin\\my.ini";
   		$header .= PHP_EOL;


   		$header .= "net stop Apache2.4";
   		$header .= PHP_EOL;
   		$header .= "net stop MySQL";
   		$header .= PHP_EOL;   

   		$header .= "move \"%target_cnf_file%\" \"%current%%old_cnf%\"";
   		$header .= PHP_EOL;   

   		$header .= "xcopy /s \"%current%%new_cnf_file%\" \"%target_cnf%\"";
   		$header .= PHP_EOL;   



   		$header .= "net start Apache2.4";
   		$header .= PHP_EOL;
   		$header .= "net start MySQL";
   		$header .= PHP_EOL;
   		$header .= "if not exist \"%target_adt%\" (";
   		$header .= PHP_EOL;
   		$data = "\tC:\\xampp\mysql\bin\mysql.exe -u root test<\"%current%\"$facility_code.sql";
   		$data.=PHP_EOL;
   		$data .= ") else (";
   		$data .= PHP_EOL;
   		$data .= "\tC:\\xampp\mysql\bin\mysql.exe -u root -proot test<\"%current%\"$facility_code.sql";
   		$data .= PHP_EOL;
 		$data .= ")";
 		$data .= PHP_EOL;
 		$header_end = "net stop Apache2.4";
   		$header_end .= PHP_EOL;
   		$header_end .= "net stop MySQL";
   		$header_end .= PHP_EOL;   

   		$header_end .= "del \"%target_cnf_file%\"";
   		$header_end .= PHP_EOL;   

   		$header_end .= "move \"%current%%old_cnf_file%\" \"%target_cnf%\"";
   		$header_end .= PHP_EOL;

   		$header_end .= "net start Apache2.4";
   		$header_end .= PHP_EOL;
   		$header_end .= "net start MySQL";
   		$header_end .= PHP_EOL;

   		$header_end .="pause";

 		$query = $header.$data.$header_end;
		$handle = fopen($filename, 'w');		
		$final_output_bat = $query;
		fwrite($handle, $final_output_bat);
		fclose($handle);
		// echo $handle;exit;	

		// header("Cache-Control: public");
		// header("Content-Description: File Transfer");
		// header("Content-Length: ". filesize("$filename").";");
		// header("Content-Disposition: attachment; filename=$filename");
		// header("Content-Type: application/octet-stream; "); 
		// header("Content-Transfer-Encoding: binary");

		echo "$final_output_bat";
 	}
   

 	
   function create_core_tables($start,$stop){
		$database = 'hcmp_rtk1';
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

		// $core_tables = array('access_level','assignments','comments','commodities','commodity_category','commodity_division_details','commodity_source','commodity_source_other','commodity_source_sub_category','commodity_sub_category','counties','county_drug_store_issues','county_drug_store_totals','county_drug_store_transaction_table','districts','drug_commodity_map','drug_store_issues','drug_store_totals','drug_store_transaction_table','email_listing','email_listing_new','facilities','git_log','issue_type','menu','malaria_drugs','rca_county','recepients','sub_menu','service_points','redistribution_data','receive_redistributions','log','log_monitor','db_sync','facility_order_status','facility_order_details_rejects','facility_order_details','dispensing_records','facility_loggins','facility_rollout_status','inventory','patient_issues','patients','selected_service_points','service_point_stock_physical','status','sync_updates','update_log','dispensing_totals');
		$core_tables = array('access_level','assignments','comments','commodity_division_details','commodity_source_other','commodity_source_sub_category','commodity_sub_category','counties','county_drug_store_issues','county_drug_store_totals','county_drug_store_transaction_table','districts','drug_commodity_map','drug_store_issues','drug_store_totals','drug_store_transaction_table','email_listing','email_listing_new','facilities','git_log','issue_type','menu','malaria_drugs');
		
		for ($i=0; $i <count($core_tables) ; $i++) { 
		
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

						for ($i=0; $i <count($result_fields) ; $i++) {
							$data = array_values($result_fields[$i]); 
							
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

   public function create_specific_tables($facility_code,$database=null){
   		$database = (isset($database)) ? $database : 'hcmp_rtk';
   		$mysqli = new mysqli("localhost", "root", "hPlaB", "hcmp_rtk");
   		// $mysqli = new mysqli("localhost", "root", "", "hcmp_rtk");
	   	 if (mysqli_connect_errno()) {
		    printf("Connect failed: %s", mysqli_connect_error());
		    exit();
		}
	  	ini_set('memory_limit', '-1');
   		// $filename ='db_hcmp.sql';	
   		$filename = 'tmp/'.$facility_code.'.sql';	   		
   		$query = '';
		$handle = fopen($filename, 'a');		


		$facility_specific_tables = array('facility_issues'=>'facility_code',
										  'commodity_source_other_prices'=>'facility_code',
										  'dispensing_stock_prices'=>'facility_code',
										  'facility_amc'=>'facility_code',
										  'facility_amc1'=>'facility_code',
										  'facility_stocks_temp'=>'facility_code',
										  'facility_transaction_table'=>'facility_code',
										  'malaria_data'=>'facility_id',
										  'patient_details'=>'facility_code',
										  'reversals'=>'facility_code',
										  'rh_drugs_data'=>'facility_code',
										  'service_point_stocks'=>'facility_code',
										  'facility_evaluation'=>'facility_code',
										  'facility_impact_evaluation'=>'facility_code',
										  'facility_monthly_stock'=>'facility_code',
										  'facility_orders'=>'facility_code',
										  'facility_stock_out_tracker'=>'facility_code',
										  'user'=>'facility',
										  'tuberculosis_data'=>'facility_code',
										  'requisitions'=>'facility',
										  'facility_stock_out_tracker'=>'facility_code',
										  'facility_stocks'=>'facility_code');
		
		
		
		
		foreach ($facility_specific_tables as $key => $value) {
			$table_name = $key;
			$where = "WHERE $value = '$facility_code'";			
			$query .= $this->create_inserts($table_name,$where,$mysqli);
		}
		//Add the insert statement for the dbsync_as of the day of creation
		$current_time = date('Y-m-d h:i:s');
		$query_dbsync="INSERT INTO db_sync VALUES (NULL,'$facility_code','$current_time');";
		$query.=$query_dbsync;
		
		
		$query.=$this->show_views($mysqli,'hcmp_rtk');
		$query.=$this->show_procedures($mysqli);

		$mysqli->close();
		
		fwrite($handle, $query);
		fclose($handle);

		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Length: ". filesize("$filename").";");
		header("Content-Disposition: attachment; filename=$filename");
		header("Content-Type: application/octet-stream; "); 
		header("Content-Transfer-Encoding: binary");
   }
  
  public function show_procedures($mysqli){
  	$sql_select_procedures = "show procedure status;";  
	$create_proc = '';
    $create_statements = '';
   	$rows_total = null;   	 
	if ($result = $mysqli->query($sql_select_procedures)) {
		$result_select = mysqli_fetch_all($result,MYSQLI_ASSOC);
		foreach ($result_select as $key => $value) {
		 	$proc_name = $value['Name'];
		 	$create_statements.="\nDROP PROCEDURE IF EXISTS $proc_name;\n";		 	
		 	$sql_creates = "SHOW CREATE PROCEDURE $proc_name";		 	
		 	if ($result_sql = $mysqli->query($sql_creates)) {		    	
		    	$result_creates = mysqli_fetch_all($result_sql,MYSQLI_ASSOC);	
		    	foreach ($result_creates as $proc => $procvalue) {
		    		$proc_create_stmt = $procvalue['Create Procedure'];
		    		$create_statements.="DELIMITER $$ \n";
		    		$create_statements.=$proc_create_stmt.';';
		    		$create_statements.="$$ \nDELIMITER;\n";
		    	}
		    }
		} 
	}

	return $create_statements;
  }

	public function show_views($mysqli,$database){
		$sql_select_procedures = "SELECT TABLE_SCHEMA, TABLE_NAME FROM information_schema.tables WHERE TABLE_TYPE LIKE 'VIEW' AND TABLE_SCHEMA LIKE '$database'";    	
		$create_proc = '';
		$create_statements = '';
			$rows_total = null;   	 
		if ($result = $mysqli->query($sql_select_procedures)) {
			$result_select = mysqli_fetch_all($result,MYSQLI_ASSOC);
			foreach ($result_select as $key => $value) {
			 	$proc_name = $value['TABLE_NAME'];
			 	$create_statements.="\nDROP PROCEDURE IF EXISTS $proc_name;\n";		 	
			 	$sql_creates = "SHOW CREATE VIEW $proc_name";		 	
			 	if ($result_sql = $mysqli->query($sql_creates)) {		    	
			    	$result_creates = mysqli_fetch_all($result_sql,MYSQLI_ASSOC);	
			    	foreach ($result_creates as $proc => $procvalue) {
			    		$proc_create_stmt = $procvalue['Create View'];
			    		$create_statements.=$proc_create_stmt.';';
			    	}
			    }
			} 
		}

		return $create_statements;
	}

public function create_inserts($table_name= null,$where=null,$mysqli)
   {
	ini_set('memory_limit', '-1');
   	$condition = ($where!=null) ? $where : '' ;
	$sql_create = "SHOW CREATE TABLE `$table_name`";  

	$fields_total = null;
	$create_table = '';
    $inserts = '';
   	$rows_total = null;   	 
	if ($result = $mysqli->query($sql_create)) {
		$column_types_array = array();		
		$sql_column_types = "SHOW COLUMNS FROM `$table_name`";
		if ($result_sql_columns = $mysqli->query($sql_column_types)) {			
			while($row = $result_sql_columns->fetch_assoc())
		    {
		    	foreach ($result_sql_columns as $rows => $types) {
					$type = $types['Type'];
				    $column_types_array[] = $type;
				}		        
		    }
		}

	    $row_cnt = $result->num_rows;	
	    while($row = $result->fetch_assoc())
	    {
	    	$create_table ="\n".$row['Create Table'].";\n\n";	 	//Write the Table Creates			    
		    // fwrite($handle, $create_table);  
	    }    
	    // $create_table = $result['Create Table'].";\n\n";	   
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

				
				for ($i=0; $i <count($result_fields) ; $i++) { 			
					$data = array_values($result_fields[$i]);   
					// echo "<pre>";print_r($data);die;		 	
			   	 	$values = '';
			   	 	$count = 0;
					foreach ($data as $keys => $row) {
						$row_types = $column_types_array[$keys];
						if (strpos($row_types, 'int') !== false) {
							$row = ($row!='') ?$row : 0 ;
						    $values .= ",$row";				
						}else{
							$row = ($row!='') ?$row : '' ;
							$row = addslashes($row);
					    	$values .= ",'$row'";
						}
						
					}
					$values = substr($values, 1); // remove leading ","
					$rows_total = '('.$values.')';
					$multi_values .= ','.$rows_total;
		   	 		
		   	 		
				}
				$multi_values = substr($multi_values, 1); // remove leading ","				
				$inserts .= 'INSERT INTO '.$table_name.' VALUES '.$multi_values.";\n";
				// echo $inserts;
		   	 }
	    }

	    /* close result set */
	    $result->close();
	}
   	 return $create_table.$inserts;

   }
}

 ?>
