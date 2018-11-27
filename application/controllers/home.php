<?php
/*
 * @author Kariuki & Mureithi
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Home extends MY_Controller 
{
	function __construct() 
	{
		parent::__construct();
		$this -> load -> helper(array('form', 'url'));
		$this -> load -> library(array('hcmp_functions', 'form_validation'));
		// echo "<pre>";print_r(Malaria_Data::get_facility_stock_data(13041));die;
	}

	public function reset_(){
		$facility_code=$this -> session -> userdata('facility_id');

		$reset_facility_transaction_table = Doctrine_Manager::getInstance()->getCurrentConnection();
		$reset_facility_transaction_table->execute("DELETE FROM `facility_transaction_table` WHERE  facility_code=$facility_code; ");

		$reset_facility_stock_table = Doctrine_Manager::getInstance()->getCurrentConnection();
		$reset_facility_stock_table->execute("DELETE FROM `facility_stocks` WHERE  facility_code=$facility_code");

		$reset_facility_issues_table = Doctrine_Manager::getInstance()->getCurrentConnection();
		$reset_facility_issues_table->execute("DELETE FROM `facility_issues` WHERE  facility_code=$facility_code;");
		
		$reset_facility_issues_table = Doctrine_Manager::getInstance()->getCurrentConnection();
		$reset_facility_issues_table->execute("DELETE FROM `redistribution_data` WHERE  source_facility_code=$facility_code or receive_facility_code=$facility_code;");

		$facility_order_details_table = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("select id from `facility_orders` WHERE  facility_code=$facility_code;");

		foreach ( $facility_order_details_table as $key => $value) {
			$reset_facility_order_table = Doctrine_Manager::getInstance()->getCurrentConnection();
			$reset_facility_order_table->execute("DELETE FROM `facility_order_details` WHERE  order_number_id=$value[id]; ");	
		}
		$reset_facility_order_table = Doctrine_Manager::getInstance()->getCurrentConnection();
		$reset_facility_order_table->execute("DELETE FROM `facility_orders` WHERE  facility_code=$facility_code; ");
		
		$reset_facility_historical_stock_table = Doctrine_Manager::getInstance()->getCurrentConnection();
		$reset_facility_historical_stock_table->execute("DELETE FROM `facility_monthly_stock` WHERE  facility_code=$facility_code; ");
		
		$reset_facility_update_stock_first_temp = Doctrine_Manager::getInstance()->getCurrentConnection();
		$reset_facility_update_stock_first_temp->execute("DELETE FROM `facility_stocks_temp` WHERE  facility_code=$facility_code; ");
		
		
		$this->session->set_flashdata('system_success_message', 'Facility Stock Details Have Been Reset');
		redirect('home');
	}
	public function index() {	
		(!$this -> session -> userdata('user_id')) ? redirect('user'): null ;	

		$identifier = $this -> session -> userdata('user_indicator');
		// get user_id
		$user_id = $this-> session-> userdata('user_id');
		//get last log in
		$lastlogin = user::get_last_login($user_id);
	    //pass data to view
		$data['lastlogin'] = date("l, jS F Y g:i a", strtotime($lastlogin));
		
		//exit;
		switch ($identifier):
		case 'moh':
		$view = 'shared_files/template/dashboard_template_v';	
		break;
		case 'facility_admin':
		case 'facility':
				//check if password is default
		//get last order
		$lastorder = user::get_last_order($user_id);
		//pass data to view
		if(!empty($lastorder)){
			$data['lastorder'] = date("l, jS F Y ",strtotime($lastorder['last_order']));
			$data['order_no'] = $lastorder['order_no'];
			$data['commodity_name'] = $lastorder['commodity_name'];
			$data['quantity_ordered_pack'] = $lastorder['quantity_ordered_pack'];
			$data['quantity_ordered_unit'] = $lastorder['quantity_ordered_unit'];
			$data['order_total'] = $lastorder['order_total'];
		}
		else{
			$data['no_order'] = "N/A";
		}

		$lastissue = user::get_last_issue($user_id);

		if(!empty($lastissue)){
			$data['last_issue'] = date("l, jS F Y ",strtotime($lastissue['last_issue']));
			$data['commodity_name'] = $lastissue['commodity_name'];
			$data['qty_issued'] = $lastissue['qty_issued'];
			$data['issued_to'] = $lastissue['issued_to'];
		}
		else{
			$data['no_issue'] = "N/A";
		}
		$username = $this -> session -> userdata('user_email');

		$facility_id = $this -> session -> userdata('facility_id');

		$reply = User::getPass($username);
		$user_data = $reply -> toArray();

		$token = $user_data["password"];
		$default='123456';
		$data['identifier'] = $identifier;
		
		$salt = '#*seCrEt!@-*%';
		
		$password=( md5($salt . $default));	

		if ($token=="$password") {

				//$data['content_view'] = "shared_files/activation";
				//$this -> session -> set_flashdata('system_success_message', "This is a security measure.Please Change Your Password to Proceed.");
			$view = 'shared_files/enforce_change';

				//$this -> load -> view('shared_files/activation');

		} else {

			$last_synced = Facilities::get_days_from_last_sync($facility_id);
			// $last_synced = 10;
			if($last_synced > 7) {
				$view = "shared_files/template/template";
				$data['last_synced'] = $last_synced;
 				$data['content_view'] = "shared_files/synchronize_now";
			} else {
				$view = 'shared_files/template/template';
				$data['content_view'] = "facility/facility_home_v";
				$data['facility_dashboard_notifications']=$this->get_facility_dashboard_notifications_graph_data();
			}
			// echo "<pre>"; print_r($last_synced); echo "</pre>"; exit;
		}

		break;
		case 'recovery':
			$facility = $this -> session -> userdata('facility_id');
			$view = 'shared_files/template/template';
			$data['title'] = "User Management";
			$data['banner_text'] = "User Management";
			$data['current_user_id'] = $this-> session -> userdata('user_id');
			$data['content_view'] = "shared_files/user_recovery_v";
			$data['listing']= Users::get_user_list_facility($facility);
			
		break;
		case 'district':
		$data['content_view'] = "subcounty/subcounty_home_v";	
		$view = 'shared_files/template/template';
		break;
		case 'moh_user':
		$view = '';
		break;
		case 'scmlt':
		case 'rtk_county_admin':
		case 'allocation_committee':
		case 'rtk_partner_admin':
		case 'rtk_manager':
		case 'rtk_partner_admin':
		case 'rtk_partner_super':
		redirect('http://41.89.6.223/HCMP/user');
			//redirect('http://192.168.133.23/HCMP/user');
		break;
		case 'super_admin':
		$view = 'shared_files/template/dashboard_v';
		$data['content_view'] = "shared_files/template/super_admin_template";
		break;
			// case 'allocation_committee':
			// $view = '';
		break;	
		case 'county':
		$view = 'shared_files/template/template';
		$data['content_view'] = "subcounty/subcounty_home_v";
		break;

		endswitch;

		$data['title'] = "System Home";
		$data['banner_text'] = "Home";
		$this -> load -> view($view, $data);
	}

	public function test_fx() {
		
	}

	public function get_facility_dashboard_notifications_graph_data()
	{
    	//format the graph here
		$facility_code=$this -> session -> userdata('facility_id'); 
		$facility_stock_=facility_stocks::get_facility_stock_amc($facility_code);
	    // echo "<pre>";
	    // print_r($facility_stock_);die;
		$facility_stock_count=count($facility_stock_);
		$graph_data=array();
		$graph_data=array_merge($graph_data,array("graph_id"=>'container'));
		$graph_data=array_merge($graph_data,array("graph_title"=>'Facility stock level'));
		// $graph_data = array_merge($graph_data, array("color" => "['#4b0082','#FFF263', '#6AF9C4']"));
		$graph_data=array_merge($graph_data,array("graph_type"=>'bar'));
		$graph_data=array_merge($graph_data,array("graph_yaxis_title"=>'Total stock level  (values in packs)'));
		$graph_data=array_merge($graph_data,array("graph_categories"=>array()));
		$graph_data=array_merge($graph_data,array("series_data"=>array("Current Balance"=>array(),"AMC"=>array())));
		$graph_data['stacking']='normal';
		foreach($facility_stock_ as $facility_stock_):
			$category_name = $facility_stock_['commodity_name'].' ('.$facility_stock_['source_name'].')';
		$graph_data['graph_categories']=array_merge($graph_data['graph_categories'],array($category_name));	
		$graph_data['series_data']['Current Balance']=array_merge($graph_data['series_data']['Current Balance'],array((float) $facility_stock_['pack_balance']));
		$graph_data['series_data']['AMC']=array_merge($graph_data['series_data']['AMC'],array((float) $facility_stock_['amc']));	

		endforeach;
		//echo "<pre>";print_r($facility_stock_);echo "</pre>";exit;
		//create the graph here
		$faciliy_stock_data=$this->hcmp_functions->create_high_chart_graph($graph_data);
		$loading_icon=base_url('assets/img/no-record-found.png'); 
		$faciliy_stock_data=($facility_stock_count>0)? $faciliy_stock_data : "$('#container').html('<img src=$loading_icon>');" ;
    	//compute stocked out items
		$items_stocked_out_in_facility=count(facility_stocks::get_items_that_have_stock_out_in_facility($facility_code));
		//get order information from the db
		$facility_order_count_=facility_orders::get_facility_order_summary_count($facility_code);
		//echo "<pre>";print_r($facility_order_count_);echo "<pre>";exit;
		$facility_order_count=array();
		foreach($facility_order_count_ as $facility_order_count_){
			$facility_order_count[$facility_order_count_['status']]=$facility_order_count_['total'];
		}
    	//get potential expiries infor here
		$potential_expiries=Facility_stocks::potential_expiries($facility_code)->count();

    	//get actual Expiries infor here
		$actual_expiries=count(Facility_stocks::All_expiries($facility_code));
		//get items they have been donated for
		// $facility_donations=redistribution_data::get_all_active($facility_code)->count();
		//get items they have been donated and are pending
		// $facility_donations_pending=redistribution_data::get_all_active($facility_code,"to-me")->count();
		//get redistribution mismatch data
		$facility_redistribution_mismatches = redistribution_data::get_redistribution_mismatches_count($facility_code);
		//get stocks from v1
		$stocks_from_v1=0;
		if($facility_stock_count==0 && $facility_donations==0 && $facility_donations_pending==0 ){
		//$stocks_from_v1=count(facility_stocks::import_stock_from_v1($facility_code));	
		}
		// return array('facility_stock_count'=>$facility_stock_count,
		// 'faciliy_stock_graph'=>$faciliy_stock_data,
		// 'items_stocked_out_in_facility'=>$items_stocked_out_in_facility,
		// 'facility_order_count'=>$facility_order_count,
		// 'potential_expiries'=>$potential_expiries,
		// 'actual_expiries'=>$actual_expiries,
		// 'facility_donations'=>$facility_donations,
		// 'facility_donations_pending'=>$facility_donations_pending,//,'stocks_from_v1'=>$stocks_from_v1
		// 'facility_redistribution_mismatches'=>$facility_redistribution_mismatches
		// );	
		return array('facility_stock_count'=>$facility_stock_count,
			'faciliy_stock_graph'=>$faciliy_stock_data,
			'items_stocked_out_in_facility'=>$items_stocked_out_in_facility,
			'facility_order_count'=>$facility_order_count,
			'potential_expiries'=>$potential_expiries,
			'actual_expiries'=>$actual_expiries,
			'facility_donations'=>$facility_donations,
			'facility_donations_pending'=>$facility_donations_pending,//,'stocks_from_v1'=>$stocks_from_v1	
			);	
	}
	// return array('facility_stock_count'=>$facility_stock_count,
	// 'faciliy_stock_graph'=>$faciliy_stock_data,
	// 'items_stocked_out_in_facility'=>$items_stocked_out_in_facility,
	// 'facility_order_count'=>$facility_order_count,
	// 'potential_expiries'=>$potential_expiries,
	// 'actual_expiries'=>$actual_expiries,
	// 'facility_donations'=>$facility_donations,
	// 'facility_donations_pending'=>$facility_donations_pending,//,'stocks_from_v1'=>$stocks_from_v1
	// 'facility_redistribution_mismatches'=>$facility_redistribution_mismatches
	// );	
	/*return array('facility_stock_count'=>$facility_stock_count,
	'faciliy_stock_graph'=>$faciliy_stock_data,
	'items_stocked_out_in_facility'=>$items_stocked_out_in_facility,
	'facility_order_count'=>$facility_order_count,
	'potential_expiries'=>$potential_expiries,
	'actual_expiries'=>$actual_expiries,
	'facility_donations'=>$facility_donations,
	'facility_donations_pending'=>$facility_donations_pending,//,'stocks_from_v1'=>$stocks_from_v1	
	'facility_redistribution_mismatches'=>$facility_redistribution_mismatches
	);	
    }*/
	public function tester(){
			// $this->load->model('users');
		$view = 'shared_files/template/template';
		$data['content_view'] = "shared_files/under_maintenance";
		$this -> load -> view($view, $data);

			// $last_inserted = $this->users->set_report_access();
			// echo "<pre>This";print_r($last_inserted);echo "</pre>";exit;
			//$this->Users::set_report_access();
	}

	public function under_maintenance(){
		$data['title'] = "Under Maintenance";
		$data['banner_text'] = "System Use Statistics";
		$data['report_view'] = "shared_files/under_maintenance";
			// $data['sidebar'] = (!$this -> session -> userdata('facility_id')) ? "shared_files/report_templates/side_bar_sub_county_v" : "shared_files/report_templates/side_bar_v";
			// $data['content_view'] = "facility/facility_reports/reports_v";
		$data['active_panel'] = (!$this -> session -> userdata('facility_id')) ? "system_usage" : "system_usage";
			// $data['district_data'] = districts::getDistrict($this -> session -> userdata('county_id'));

		$view = 'shared_files/template/template';
		$data['content_view'] = "shared_files/under_maintenance";
		$this -> load -> view($view, $data);
	}

	public function report_issue() {
		$data['username'] = $this -> session -> userdata('full_name');
		$data['title'] = "Report an Issue";
		$data['banner_text'] = "Issue Tracker";
		$view = 'shared_files/template/template';
		$data['content_view'] = "shared_files/report_issue";
		$this -> load -> view($view, $data);
	}

	public function submit_issue() {
		// echo "<pre>"; print_r($_POST); exit;
		$issue_level = $this -> session -> userdata('user_type_id');
		$user_id = $this -> session -> userdata('user_id');
		$level = Users::get_user_type($issue_level);
		$issue_url = $_POST['issueurl'];
		$description = $_POST['description'];

		$db_file_name = "";
		if(isset($_FILES['issueimage']['name'])) {
			// Do File Upload
			$upload_path = 'issue_uploads/';
			
			$image_name = $_FILES['issueimage']['name'];
			$file_path = $upload_path;
			$config['upload_path'] = $upload_path;
			$config['allowed_types'] = '*';
			$config['file_name'] = $image_name;
			$this->load->library('upload'); 
			$this->upload->initialize($config);	

			if($this->upload->do_upload('issueimage'))
			{
				$this->session->set_flashdata('system_success_message', 'Issue Recorded');
			}
			else
			{
				echo "<pre>"; print_r($this->upload->display_errors()); echo "</pre>"; exit;
			   	$this->session->set_flashdata('error', 'Something went wrong');
			}
			$image_file_name = $image_name;

			$db_file_name = $image_file_name;
		}  else {
			$db_file_name = null;
		}
		$insert_issue = Doctrine_Manager::getInstance() -> getCurrentConnection();
		$insert_issue->execute("INSERT INTO reported_issues(submitted_by, user_level, issue_url, description, image_path) VALUES ('$user_id', '$level', '$issue_url', '$description', '$db_file_name')");
		redirect('home');
	}
}
