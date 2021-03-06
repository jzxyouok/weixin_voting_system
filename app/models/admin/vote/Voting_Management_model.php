<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Voting_Management_model extends CI_Model{

	public function __construct()
    {
        $this->load->database();
    }
    
	public function getVM(){

		$query = $this->db->get('voting_management');

		return $query->result_array();

	}

	//添加活动分类
	public function add_voting_management(){

		//voting_management表
        $data = array(
		    'title' => $this->input->post('title',TRUE),
		    'description' => $this->input->post('description',TRUE),
		    'code' => md5(NOW()),
		    'date_start' => $this->input->post('date_start',TRUE),
		    'date_end' => $this->input->post('date_end',TRUE),
		    'status' => $this->input->post('status',TRUE),
		    'statusing' => $this->input->post('statusing',TRUE),
		);
		$this->db->insert('voting_management', $this->security->xss_clean($data));

		//获取最新插入的ID
		$vm_id = $this->get_voting_management_new_vm_id();

		//vm_vc表
		$data_vm_vc = array(
			'vm_id' => $this->security->xss_clean($vm_id),
			'vc_id' => $this->input->post('vc_id',TRUE)
		);
		$this->db->insert('vm_vc', $this->security->xss_clean($data_vm_vc));

		//vm_bp表
		$bps = $this->input->post('vm_bp',TRUE);
		$bp = explode(',',$bps);
		foreach ($bp as $bp_id) {
			$data_vm_bp = array(
				'vm_id' => $this->security->xss_clean($vm_id),
				'bp_id' => $this->security->xss_clean((int)$bp_id)
			);
			$this->db->insert('vm_bp', $this->security->xss_clean($data_vm_bp));
			
		}
		//vm_traffic表
		$data_vm_traffic = array(
			'vm_id' => $this->security->xss_clean($vm_id)
		);
		$this->db->insert('vm_traffic', $this->security->xss_clean($data_vm_traffic));
		
		return $vm_id;
	}

	//返回最新一条数据的ID
	public function get_voting_management_new_vm_id(){
		return $this->db->insert_id();
	}

	//根据ID返回数据
	public function get_voting_management_by_vm_id($vm_id){
		
		$query = $this->db->get_where('voting_management', array('vm_id' => $this->security->xss_clean((int)$vm_id)));

		return $query->row_array();
	}

	//活动预览
	public function get_ap_by_vm_id($vm_id){
		$this->db->select('*');
		$this->db->from('basic_personnel');
		$this->db->join('vm_bp','vm_bp.bp_id = basic_personnel.bp_id');
		$this->db->where('vm_id', $this->security->xss_clean((int)$vm_id));
		$query = $this->db->get();
		return $query->result_array();
	}

	//获取对应人员的照片
	public function get_bp_image_by_bp_id($bp_id){
		$query = $this->db->get_where('bp_image', array('bp_id' => $this->security->xss_clean((int)$bp_id)));

		return $query->row_array();
	}
	
	//活动访问流量+1
	public function get_vm_traffic_by_vm_id($vm_id){
		
		//取出当前活动对应的访问量
		$query = $this->db->get_where('vm_traffic', array('vm_id' => $this->security->xss_clean((int)$vm_id)))->result_array();
		
		//访问量+1
		foreach($query as $q){
			$vt_id = $q['vt_id'];
			$traffic = $q['traffic'];
		}
		
		//更新访问量
		$data = array(
			'traffic' => $this->security->xss_clean($traffic+1)
		);
		$this->db->where('vm_id', $this->security->xss_clean($vm_id));

		$this->db->update('vm_traffic', $data);
		//返回访问量
		$query = $this->db->get_where('vm_traffic', array('vm_id' => $this->security->xss_clean((int)$vm_id)));
		return $query->row_array();
	}

	//根据vm_id获取关联分类vm_vc数据
	public function get_vm_vc_by_vm_id($vm_id){
		$query = $this->db->get_where('vm_vc', array('vm_id' => $this->security->xss_clean((int)$vm_id)));
		return $query->row_array();
	}

	//根据vm_id获取关系人员vm_bp数据->再通过bp_id获取对应人名
	public function get_vm_bp_by_vm_id($vm_id){
		
		$this->db->select('basic_personnel.bp_id as id');
		$this->db->from('basic_personnel');
		$this->db->join('vm_bp','vm_bp.bp_id = basic_personnel.bp_id');
		$this->db->where('vm_id', $this->security->xss_clean((int)$vm_id));
		$query = $this->db->get();
		return $query->result_array();
	}

	//更新活动分类
	public function edit_voting_management($vm_id){

		$data = array(
			'title' => $this->input->post('title',TRUE),
		    'description' => $this->input->post('description',TRUE),
		    'date_start' => $this->input->post('date_start',TRUE),
		    'date_end' => $this->input->post('date_end',TRUE),
		    'status' => $this->input->post('status',TRUE),
		    'statusing' => $this->input->post('statusing',TRUE)
		);

		$this->db->where('vm_id', $this->security->xss_clean((int)$vm_id));

		$this->db->update('voting_management', $data);

		//vm_vc表
		$data_vm_vc = array(
			'vc_id' => $this->input->post('vc_id',TRUE)
		);
		$vm_vc = $this->get_vm_vc_by_vm_id($vm_id);
		if (isset($vm_vc)){
		    $vm_vc_id = $vm_vc['vm_vc_id'];
		}
		$this->db->where('vm_vc_id', $this->security->xss_clean((int)$vm_vc_id));

		$this->db->update('vm_vc', $data_vm_vc);

		//vm_bp表------先清空后插入
		$this->db->delete('vm_bp', array('vm_id' => $this->security->xss_clean((int)$vm_id)));

		$bps = $this->input->post('vm_bp',TRUE);
		$bp = explode(',',$bps);
		foreach ($bp as $bp_id) {
			$data_vm_bp = array(
				'vm_id' => $this->security->xss_clean($vm_id),
				'bp_id' => $this->security->xss_clean((int)$bp_id)
			);
			$this->db->insert('vm_bp', $this->security->xss_clean($data_vm_bp));
		}
	}


	//删除活动信息
	public function delete_voting_management_by_vm_id($vm_id){
		
		//删除活动信息主表
		$query = $this->db->delete('voting_management', array('vm_id' => $this->security->xss_clean($vm_id)));
		
		//删除活动信息与分类关联表
		$vm_vc = $this->get_vm_vc_by_vm_id($vm_id);
		if (isset($vm_vc)){
		    $vm_vc_id = $vm_vc['vm_vc_id'];
		    $this->db->delete('vm_vc', array('vm_vc_id' => $this->security->xss_clean($vm_vc_id)));
		}

		//删除活动信息与基础人员关联表
		$this->db->delete('vm_bp', array('vm_id' => $this->security->xss_clean((int)$vm_id)));

		return $query;
	}

	//自动更新活动状态，根据当前日期是否等于或者大于开始日期
	public function update_voting_management_by_now(){
		
		$vms = $this->db->get('voting_management')->result_array();

		date_default_timezone_set("Asia/Shanghai");
		
		foreach ($vms as $vm) {

			$now = date('Y-m-d H:i:s');

			if($vm['status'] == '1'){
				if(strtotime($vm['date_start']) == strtotime($now) || strtotime($vm['date_start']) < strtotime($now) && strtotime($vm['date_end']) > strtotime($now)){

					$data = array(
						'statusing' => $this->security->xss_clean('2')
					);
					$this->db->where('vm_id', $this->security->xss_clean($vm['vm_id']));

					$this->db->update('voting_management', $data);

				}else if(strtotime($vm['date_end']) < strtotime($now)){

					$data = array(
						'statusing' => $this->security->xss_clean('3')
					);
					$this->db->where('vm_id', $this->security->xss_clean($vm['vm_id']));

					$this->db->update('voting_management', $data);
				}
			}
		}
	}
	
	//用户投票
	public function add_votes_by_vm_bp($vm_id,$bp_id){
		
		//取出当前活动对应的用户的票数
		$query = $this->db->get_where('vm_bp', array('vm_id' => $this->security->xss_clean((int)$vm_id),'bp_id' => $this->security->xss_clean((int)$bp_id)))->result_array();
		
		//访问量+1
		foreach($query as $q){
			$vm_bp_id = $q['vm_bp_id'];
			$votes = $q['votes'];
		}
		
		//更新访问量
		$data = array(
			'votes' => $this->security->xss_clean($votes+1)
		);
		$this->db->where('vm_bp_id', $this->security->xss_clean($vm_bp_id));

		$this->db->update('vm_bp', $data);
		
	}
	
}