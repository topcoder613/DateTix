<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Descriptive_Word_api_model extends CI_Model {

	public function get_descriptive_words($language_id = 1){
		$this->db->select('descriptive_word_id,description');
		$this->db->where('display_language_id',$language_id);
		$this->db->order_by('view_order','ASC');
		$result     = $this->db->get('descriptive_word');
		$results    = array();
		if($result->num_rows()>0){
			$results = $result->result_array();
		}
		return $results;
	}

}
