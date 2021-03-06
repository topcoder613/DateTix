<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_FB_Friend_api_model extends CI_Model {

	public function get_mutual_friends($user_id, $friend_id) {

		$mutual_facebook_ids = $this -> get_mutual_facebook_ids($user_id, $friend_id);

		$mutual_app_friends_count = 0;
		$mutual_friends = array();

		$CI = & get_instance();

		$CI -> load -> model('api/v1/user_api_model');

		foreach ($mutual_facebook_ids as $mutual_facebook_id) {

			unset($mutual_friend);

			// Get Facebook user data
			$mutual_friend['facebook'] = $this -> facebook -> api('/' . $mutual_facebook_id . '?fields=name,location,relationship_status');

			// Get DateTix user data, if available
			if ($CI -> user_api_model -> is_facebook_id_exists($mutual_facebook_id)) {

				$mutual_app_friends_count ++;
				$mutual_friend['datetix']['attributes'] = $this -> user_api_model -> get_user_by_facebook_id($mutual_facebook_id);
			}

			if (!empty($mutual_friend)) {
				$mutual_friends[] = $mutual_friend;
			}
		}

		return $mutual_friends;
	}

	private function get_mutual_facebook_ids($user_id, $friend_id) {

		$user_fb_friends = $this -> get_user_fb_friends_by_user_id($user_id);
		$friend_fb_friends = $this -> get_user_fb_friends_by_user_id($friend_id);

		$mutual_facebook_ids = array();
		if (!empty($user_fb_friends) && !empty($friend_fb_friends)) {

			foreach ($user_fb_friends as $user_fb_friend) {

				foreach ($friend_fb_friends as $friend_fb_friend) {

					if ($user_fb_friend['facebook_id'] == $friend_fb_friend['facebook_id']) {

						$mutual_facebook_ids[] = $user_fb_friend['facebook_id'];
					}
				}
			}
		}

		return $mutual_facebook_ids;
	}

	public function get_user_fb_friends_by_user_id($user_id) {

		$this -> db -> where('user_id', $user_id);
		$result = $this -> db -> get('user_fb_friend');

		return $result -> num_rows() > 0 ? $result -> result_array() : NULL;
	}
}
