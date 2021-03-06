<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Events extends MY_Controller {
	var $language_id = '1';
	var $user_id = '';
	public function __construct() {
		parent::__construct();

		//Load Model
		$this -> load -> model('model_user');
		$this -> load -> model('model_account');
		$this -> load -> model('general_model', 'general');

		//Check User is Looged In or not
		if ($this -> user_id = $this -> session -> userdata('user_id')) {
			$this -> user_id = $this -> session -> userdata('user_id');			
		}
		
		$this -> language_id = $this -> session -> userdata('sess_language_id');
		$this -> load -> library('merchant');
		$this -> merchant -> load('paypal_express');
                
                
                $settings = array('username' => $this -> config -> item('username'), 'password' => $this -> config -> item('password'), 'signature' => $this -> config -> item('signature'), 'test_mode' => $this -> config -> item('test_mode'));                
		$this -> merchant -> initialize($settings);
	}
	
	

	/**
	 * Index Function :: Redirect to event list
	 * @access public
	 * @author Rajnish Savaliya
	 */
	public function index() {                
		$this->event_list();
	}
	/**
	 * Index Function :: Redirect to Upgrade Account.
	 * @access public
	 * @author Rajnish Savaliya
	 */
	public function details() {
			
		
		$data['page_title'] = translate_phrase('Events');
		$user = array();
		if($this -> user_id)
		{
			$this -> general -> set_table('user');
			$user_data = $this -> general -> get('user.*,
			CASE
						WHEN
							birth_date != "0000-00-00" 	
						THEN 
							TIMESTAMPDIFF(YEAR,`birth_date`,CURDATE())
					END as user_age
			', array('user_id' => $this -> user_id));

			//Signup Discount
			$user = $user_data['0'];
		}
		
		if ($return_to = $this -> input -> get('return_to')) {
			if ($tab = $this -> input -> get('tab')) {
				$return_to .= '#' . $tab;
			}
			$this -> session -> set_userdata('return_url', $return_to);
		}
		$data['form_para'] = '';
		
		if ($event_id = $this -> input -> get('id')) {			
			$this -> session -> set_userdata('event_id', $event_id);
			$data['form_para'] = '?id='.$event_id;
		}
		else
		{
			$this -> session -> unset_userdata('event_id');
		}
		
		//Check Whether it's free rsvp
		$is_free_rsvp = 1;		
		if($this->input->get('freeRSVP') === false)
		{
			$is_free_rsvp = 0;
		} 
		
		$ads_id = NULL;		
		if ($ad_id = $this -> input -> get('src')) {
			
			if($this -> general -> checkDuplicate(array('ad_id'=>$ad_id,'display_language_id'=>$this->language_id),'ad'))
			{
				$data['form_para'] .= '&src='.$ad_id;
				$ads_id = $ad_id;
				$this -> session -> set_userdata('ad_id', $ad_id);				
			}
		}
		else
		{
			$this -> session -> unset_userdata('ad_id');
		}
		
		$data['total_events_in_city'] = $this->_get_event_count();
		if($event_data = $this->_get_event_details())
		{
			if($event_id = $this -> session -> userdata('event_id'))
			{
				//Set event_ad.page_views=event_ad.page_views+1 based on URL (if URL doesn't contain ad_id, then update row in event_ad with ad_id=0 for current event_id)
				$event_ad_data['event_id'] = $event_id;
				$event_ad_data['ad_id'] = $ads_id?$ads_id:0;
				$event_ad_data['user_ip'] = $this->input->ip_address();
				$event_ad_data['date'] = SQL_DATETIME;
				$event_ad_data['user_id'] = $this->user_id;
				$event_data['ad_id'] = $event_ad_data['ad_id'];
				
				$this->general->set_table("event_ad");
				if(!$this->general->checkDuplicate($event_ad_data))
				{
                                    //get website_id from user table.and insert website id with event_ad
                                    //this should be only if user is logged in
                                   /* $this->general->set_table("user");
                                    $res = $this->general->get('website_id',array('user_id'=>$this->session->userdata('user_id')));
                                    if(!empty($res)){                                        
                                        $event_ad_data['website_id'] = $res[0]['website_id'];
                                    }   */
                                    
                                    
                                    $event_ad_data['website_id']=get_assets('website_id','0');
                                    //insert in event_ad table                                    
                                    $this->general->set_table("event_ad");
                                    $this->general->save($event_ad_data);
				}
			}
			$this->session->set_userdata('is_free_rsvp',$is_free_rsvp);
			if($is_free_rsvp == 1)
			{
				//If Free RSVP then 
				$post_data['user_id'] = $this->user_id;
				$post_data['ad_id'] = $ads_id;
				$post_data['event_id'] = $event_data['event_id'];
				$this -> session -> set_userdata('post_data', $post_data);
				if($this -> user_id)
				{
					//Insert data in event user table
					
					$condtion_event_user = array('user_id' => $this -> user_id,'event_id' => $post_data['event_id']);
					$this -> general -> set_table('event_user');
					if(!$this -> general -> checkDuplicate($condtion_event_user))
					{
						$post_data['rsvp_time'] = SQL_DATETIME;
						
						//Newly Added fields
						$post_data['agent_string'] = $this->agent->agent_string();							 
						$this -> general -> save($post_data);
					}

					redirect('/events/confirm_rsvp');
				}
				else {
					redirect('/apply');
				}
			}
			
//			if($event_data['ad_id'] == 12)
//			{
//				$data['page_title'] = "Fast Flirting on ".date(DATE_FORMATE,strtotime($event_data['event_start_time']));
//			}
//			else
			{
				$data['page_title'] = $event_data['event_name'].translate_phrase(" on ").date(DATE_FORMATE,strtotime($event_data['event_start_time']));
			}
			$this -> general -> set_table('event_user');
			if($this->user_id)
			{
				$data['user_event'] = $this -> general -> get("*",array('user_id' => $this->user_id,'event_id' => $event_data['event_id']));
			}
			else
			{
				$data['user_event'] = '';
			}

			$this -> general -> set_table('event_order');
			if($this->user_id)
			{
				if($data['user_order'] = $this -> general -> get("*",array('paid_by_user_id' => $this->user_id,'event_id' => $event_data['event_id'])))
				{
					$data['user_order']  = $data['user_order']['0'];
				}
			}
			else
			{
				$data['user_order'] = '';
			}
			
			//Set Discount Flag:
			$data['fb_like_discount'] = '0';
			$this -> session -> set_userdata('fb_like_discount', 'no');
			//Facebook Discount
			//$fql =  "SELECT uid FROM page_fan WHERE page_id = ".$this->config->item('page_id')." AND uid = me()";
			$fb_id = $this->facebook->getUser();
			$fql =  "SELECT uid FROM page_fan WHERE page_id = ".$this->config->item('page_id')." AND uid = ".$fb_id;
		
			$fb_params = array('method' => 'fql.query', 'query' => $fql);
			try {
				if($fb_data = $this -> facebook -> api($fb_params))
				{
					$this -> session -> set_userdata('fb_like_discount', 'yes');
					$data['fb_like_discount'] = '1';
				}
			} catch (Exception $e) {
				// Log Error
			}
				
			$data['signup_discount'] = '0';
			$this -> session -> set_userdata('signup_discount', 'no');
			if($user)
			{
				if($user['completed_application_step'] >= 7 )
				{
					$data['signup_discount'] = '1';
					$this -> session -> set_userdata('signup_discount', 'yes');
				}				
			}
			
			for ($i = date('Y'); $i < (date('Y') + 20); $i++) {
				$year[$i] = $i;
			}
			
			$data['year'] = $year;
			$data['month'] = $this -> model_user -> get_month();
			$data['ticket_packages'] = $this -> _get_ticket_package($event_data);
                        
			foreach($data['ticket_packages']  as $package)
			{
				if($package['is_default'])
				{
					$data['selected_key'] = $package['event_price_id'];
					$data['selected_package'] = $package;					
				}
			}
			
			//ticket left
			$this->general->set_table('event_order');
			$total_sold_tickets = $this -> general->get("sum(num_tickets) as total_tickets",array('event_id'=>$event_data['event_id']));
			$total_sold_tickets_guest = $this -> general->get("sum(num_tickets) as total_tickets",array('event_id'=>$event_data['event_id'],"paid_by_user_id "=>NULL));

			$this->general->set_table('event_user');
			$total_sold_tickets_to_users = $this -> general->get("",array('event_id'=>$event_data['event_id']));
			
			// count event_user + (count all num_tickets in event_order which paid_by_user_id = null)			
			//$sold_tickets = count($total_sold_tickets_to_users) + $total_sold_tickets_guest['0']['total_tickets'];
			$sold_tickets = $total_sold_tickets['0']['total_tickets'];
			$data['total_rsvped'] = count($total_sold_tickets_to_users);
			$data['total_left_ticket'] = $event_data['max_prepaid_tickets'] - $sold_tickets;
			if ($post_data = $this -> input -> post()) {
				
				
				if($data['user_order'])
				{
					$this -> session -> set_flashdata('paypal', translate_phrase("You have already purchased a discounted member ticket for this event. If you want to purchase tickets for your friends, please ask them to sign up as ").get_assets('name','DateTix').translate_phrase(" members to purchase their own discounted member tickets online."));
					redirect(base_url() . url_city_name() . '/event.html'.$data['form_para']);
				}			
				else if($post_data['no_of_unit'] <= $data['total_left_ticket'])
				{
					$is_correct_data = 1;
					if(!$this->user_id)
					{
						$this -> load -> library('form_validation');
						$this -> form_validation -> set_rules('user_email', translate_phrase('Email'), 'trim|required|valid_email|xss_clean');
						$this -> form_validation -> set_rules('name', translate_phrase('Name'), 'trim|required');
						$this -> form_validation -> set_rules('mobile_phone_number', translate_phrase('Mobile Number'), 'trim|required');
						if ($this -> form_validation -> run() == FALSE) {
							$is_correct_data = 0;
						}									
					}
					
					if($is_correct_data)
					{
						$selected_package = array();
						
						if($data['ticket_packages'])
						{
							foreach($data['ticket_packages'] as $ticket_package)
							{
								
								if($ticket_package['quantity'] == $post_data['no_of_unit'])
								{
									$selected_package = $ticket_package;
								}
							}
						}
						if($selected_package)
						{
							//echo $selected_package['total'];exit;
														
							$params['amount'] = preg_replace("/[^0-9]/", "", $selected_package['total']);
							//$params['amount'] = $selected_package['total'];
							//$params['amount'] = 1;
							$params['currency'] = $selected_package['currency'];
							
							//echo $selected_package['total'];exit;														
							//echo $params['amount'];exit;
														
							//$selected_package = no_of_unit;
							
							$params['description'] = isset($post_data['plan_name']) ? trim($post_data['plan_name']) : 'Plan';
//							if($ad_id == 12)
//							{
//								$params['description'] .= translate_phrase(" for Fast Flirting").translate_phrase(" on ").date(DATE_FORMATE,strtotime($event_data['event_start_time']));
//							}
//							else
							{
								$params['description'] .= translate_phrase(" for ").$event_data['event_name'].translate_phrase(" on ").date(DATE_FORMATE,strtotime($event_data['event_start_time']));
							}
							//$params['description'] .= ' (HK$'.$selected_package['total'].') '.translate_phrase(" for ").$event_data['event_name'].translate_phrase(" on ").date(DATE_FORMATE,strtotime($event_data['event_start_time']));
														
							$post_data['user_id'] = $this->user_id;
							$post_data['ad_id'] = $ads_id;
							
							$post_data['event_id'] = $event_data['event_id'];
							$this -> session -> set_userdata('post_data', array_merge($post_data, $params));
							
							
							$params['return_url'] = base_url() . 'events/success_payment';
							$params['cancel_url'] = base_url() . url_city_name() . '/event.html'.$data['form_para'];
							
                                                        $response = $this -> merchant -> purchase($params);
                                                       
							
							if ($response -> status() == Merchant_response::FAILED) {
								$this -> session -> set_flashdata('paypal', translate_phrase('Gatway Error - ' . $response -> message()));
								redirect($params['cancel_url']);
							}
						}
						else
						{
							$this -> session -> set_flashdata('paypal', translate_phrase("Sorry, You have selected wrong package."));
							redirect(base_url() . url_city_name() . '/event.html'.$data['form_para']);
						}
						
					}
					$data['wrong_form_data'] = $is_correct_data;
				}
				else
				{
					$this -> session -> set_flashdata('paypal', translate_phrase("Sorry, all prepaid tickets have been sold out. Please purchase your ticket(s) at the door when you attend the event."));
					redirect(base_url() . url_city_name() . '/event.html'.$data['form_para']);
				}
			}
			
			
			$data['event_user_schools'] = $this -> _get_event_users_schools($event_data);
			$data['event_user_companies'] = $this -> _get_event_users_companies($event_data);
			
		}
		
		$data['event_info'] = $event_data;
		
		$data['page_name'] = 'event/details';
		
		$data['ad_id'] = $ad_id;
        //echo "<pre>";print_r($data);exit;
		if($this->session->userdata('user_id'))
		{
			$data['user_data'] = $user;
			$this -> load -> view('template/editProfileTemplate', $data);
		}
		else {
			$this -> load -> view('template/default', $data);
		}
	}
	
	
	/**
	 * success_payment :: Callback function of Paypal Response - Insert DateTickets based on successfull transaction.
	 * @return : Event Page
	 * @param purchase_params [Session Variable (set in paypal call)]
	 * @access public
	 * @author Rajnish Savaliya
	 */
	public function success_payment() {
		$post_data = $this -> session -> userdata('post_data');
		
		
		if (isset($post_data['amount']) && $post_data['amount']) 
		{
			//Payment gateway parameters
			$params['amount'] = isset($post_data['amount']) ? preg_replace("/[^0-9]/", "", $post_data['amount']) : '';
			$params['currency'] = isset($post_data['currency']) ? trim($post_data['currency']) : 'USD';			
			$params['description'] = isset($post_data['description']) ? $post_data['description'] : 'Plan';
			$params['return_url'] = base_url() . 'account/success_payment';
			$params['cancel_url'] = base_url() . url_city_name() . '/events.html';
			
                        $response = $this -> merchant -> purchase_return($params);
			if($response -> status() == Merchant_response::COMPLETE)
			{
				$paypal_response = $this -> merchant -> purchase_details($params);
				$user_order['paypal_response'] = json_encode($paypal_response);
				$is_successful_order = 1;	
			
				$user_order['order_amount'] = $params['amount'];
				$user_order['num_tickets'] = isset($post_data['no_of_unit']) ? $post_data['no_of_unit'] : '';
				$user_order['currency_id'] = isset($post_data['currency_id']) ? $post_data['currency_id'] : '';
					
				//Order Entry [new data]
				if($this->user_id)
				{
					$user_order['paid_by_user_id'] = $this->user_id;
					$user_email_data = $this -> model_user -> get_user_email($this->user_id);
					$user_mail = $user_email_data['email_address'];
				}
				else
				{	
					$user_order['paid_by_name'] = $post_data['name'];
					$user_order['paid_by_mobile_phone_number'] = $post_data['mobile_phone_number'];
					$user_order['paid_by_email'] = $post_data['user_email'];
					$user_mail = $user_order['paid_by_email'];					
				}
					
				$user_order['ad_id'] = $post_data['ad_id'];
				$user_order['agent_string'] = $this->agent->agent_string();
				$user_order['event_id'] = $post_data['event_id'];
                //$user_order['website_id'] = get_assets('website_id');
				$user_order['order_time'] = SQL_DATETIME;
				//insert Order Record [IF FREE ticket then amount and currency is null]
				
				$this -> general -> set_table('event_order');
				if ($event_order_id = $this -> general -> simple_save($user_order)) 
				{
					$all_ids = array();
					$this -> general -> set_table('event_ticket');
					for($i=0;$i<$user_order['num_tickets'];$i++)
					{
						$event_ticket_data['event_order_id'] = $event_order_id;
						if($i==0)
						{
							if($this->user_id)
							{
								$event_ticket_data['user_id'] = $this->user_id;
							}							
							$event_ticket_data['invite_email_address'] = $user_mail;
						}
						else
						{
							$event_ticket_data['invite_email_address'] = NULL;							
						}
						//save ticket record for event
						$all_ids[] = $this -> general -> simple_save($event_ticket_data);
						unset($event_ticket_data);
						
					}
					
					//$this -> session -> unset_userdata('post_data');
					$event_data = $this->_get_event_details();
					//echo $is_free_rspv;exit;
					if($is_free_rspv)
					{
						$event_user_data['event_id'] = $event_data['event_id'];
						$event_user_data['user_id'] = $this->user_id;						
						$event_user_data['rsvp_time'] = $user_order['order_time'];
						
						//Newly Added fields
						$event_user_data['ad_id'] = $post_data['ad_id'];
						$event_user_data['agent_string'] = $this->agent->agent_string();
				
						$this -> general -> set_table('event_user');
						$this -> general -> save($event_user_data);
						
					}
					else {
						//SEND Order Confirmation Mail	(Only for Paid Event otherwise mail sent from confirm rsvp function)
						$data['btn_link'] = base_url() . url_city_name() . '/event-order-confirmation.html?rsvp_id='.$this->utility->encode($event_order_id);
						
						$subject = translate_phrase("Order confirmation: ").$user_order['num_tickets'].translate_phrase(" tickets to ").$event_data['event_name'];
						
						$data['email_title'] = translate_phrase("IMPORTANT: Please show or print this email as proof of your order when you enter the event venue.");
	
						$data['email_content'] = "Thanks for your order for ".$user_order['num_tickets'].translate_phrase(" tickets to ").$event_data['event_name'].translate_phrase(" on ").date(DATE_FORMATE,strtotime($event_data['event_start_time']))." at ".$event_data['name'].'! ';
						$data['email_content'] .= translate_phrase("Remember to send RSVP invitation emails to everyone who will be using these tickets by visiting")." <a href='".$data['btn_link']."'>".translate_phrase("this page")."</a>.";
						$data['email_content'] .= "<br/><br/>".translate_phrase('The event starts at ').date("g:i a",strtotime($event_data['event_start_time'])).translate_phrase(" on ").date(DATE_FORMATE,strtotime($event_data['event_start_time'])).translate_phrase(' and takes place at').':';
						$data['email_content'] .= "<br/><br/>".$event_data['name'];
						$data['email_content'] .= "<br/>".$event_data['address'];
						$data['email_content'] .= ", ".$event_data['neighborhood_name'];
						$data['email_content'] .= "<br/>".$event_data['city_name'];
						$data['email_content'] .= "<br/>".$event_data['phone_number'];
	
						if($all_ids)
							$data['email_content'] .= "<br/><br/><b>".translate_phrase("Your ticket IDs are: ").implode(", ",$all_ids)."</b><br/><br/>";
						
						$data['btn_text'] = translate_phrase("View Order");
						$email_template = $this -> load -> view('email/common', $data, true);
						$this -> datetix -> mail_to_user($user_mail, $subject, $email_template);
						
						//Send confirm RSVP MAIL
						$event_user_data['event_id'] = $event_data['event_id'];
						$event_user_data['user_id'] = $this->user_id;						
						$event_user_data['rsvp_time'] = $user_order['order_time'];
						
						//Newly Added fields
						$event_user_data['ad_id'] = $post_data['ad_id'];
						$event_user_data['agent_string'] = $this->agent->agent_string();
				
						$this -> general -> set_table('event_user');
						$this -> general -> save($event_user_data);
						
						$this->general->set_table("user");
						$user_profile_data = $this -> general -> get("user_id,first_name,password,facebook_id", array('user_id' => $this->user_id));
							
						$rsvp_subject = translate_phrase("Your RSVP has been confirmed");
												
						$email_data['btn_link'] = base_url() . url_city_name() . '/event.html?id='.$event_data['event_id'].'&src='.$post_data['ad_id'];
						$email_data['btn_text'] = translate_phrase("View Event Details");
						$email_data['email_title'] = translate_phrase('Hi ').$user_profile_data['0']['first_name'].translate_phrase(', your RSVP for ').$event_data['event_name'].translate_phrase(" on ").date(DATE_FORMATE,strtotime($event_data['event_start_time'])).translate_phrase(" has been confirmed.");
												
						//$email_data['email_content'] = translate_phrase('We will email you a list of your top matches 24-36 hours before the event, so keep checking your email as we get closer to the event!');
						//$email_data['email_content'] .= "<br/><br/>"
						$email_data['email_content'] .= translate_phrase('The event starts at ').date("g:i a",strtotime($event_data['event_start_time'])).translate_phrase(" on ").date(DATE_FORMATE,strtotime($event_data['event_start_time'])).translate_phrase(' and takes place at').':';
						$email_data['email_content'] .= "<br/><br/>".$event_data['name'];
						$email_data['email_content'] .= "<br/>".$event_data['address'];
						$email_data['email_content'] .= ", ".$event_data['neighborhood_name'];
						$email_data['email_content'] .= "<br/>".$event_data['city_name'];
						$email_data['email_content'] .= "<br/>".$event_data['phone_number']."<br/><br/>";
						
						$email_template = $this -> load -> view('email/common', $email_data, true);
						$this -> model_user -> send_email(INFO_EMAIL,$user_mail, $rsvp_subject, $email_template,"html","DateTix");
							
					}
					
					//$this -> session -> set_flashdata('paypal', translate_phrase('Thanks for purchaseing ').$params['description']);
					redirect(base_url() . url_city_name() . '/event-order-confirmation.html?rsvp_id='.$this->utility->encode($event_order_id));
				}
			} 
			else {
				$this -> session -> set_flashdata('paypal', $params['description'] . translate_phrase('Sorry Transaction is failed. Please try again!'));
			}
		}
		
		if ($return_url = $this -> session -> userdata('return_url')) {
			$this -> session -> unset_userdata('return_url');
			redirect(base_url() . url_city_name() . '/' . $return_url);
		} else {
			redirect(base_url() . url_city_name() . '/event.html');
		}
	}
	
	
	/**
	 * Confirm RSVP
	 * @return : Confirm RSVP  Page
	 * @access public
	 * @author Rajnish Savaliya
	 */
	public function confirm_rsvp()
	{
        
		$event_order_id = $this->utility->decode($this->input->get('rsvp_id'));	
		
		$is_free_rspv = $this->session->userdata('is_free_rsvp');
		
		$data['page_name'] = 'event/confirm_rsvp';
		if($this -> user_id)
		{
			$this -> general -> set_table('user');
			$user_data = $this -> general -> get('user.*,
			CASE
						WHEN
							birth_date != "0000-00-00" 	
						THEN 
							TIMESTAMPDIFF(YEAR,`birth_date`,CURDATE())
					END as user_age
			', array('user_id' => $this -> user_id));

			//Signup Discount
			$user = $user_data['0'];
		}
		
		$from_mail = INFO_EMAIL;
		$from_name = "DateTix";
		
		$data['event_info'] = $this->_get_event_details();
		if($event_order_id)
		{
			$this -> general -> set_table('event_order');
			if($data['event_order_data'] = $this -> general -> get("*",array('event_order_id'=>$event_order_id)))
			{
				$data['event_order_data'] = $data['event_order_data']['0'];
				
				$fields = array('ticket.*','ticket.user_id as confirmed_user','usr.first_name','usr.last_name','eu.rsvp_time');
				$from = 'event_ticket as ticket';
				$joins = array(
							'event_user as eu' => array('ticket.event_ticket_id = eu.event_ticket_id', 'left'), 
							'user as usr' => array('ticket.user_id = usr.user_id', 'left'), 
							);
				$where['ticket.event_order_id'] = $event_order_id;
				
				$data['event_attendee_data'] = $this -> model_user -> multijoins($fields, $from, $joins, $where);
				
			}
			
			if($data['event_order_data']['paid_by_user_id'])
			{
				$this -> general -> set_table('user');
				$temp = $this -> general -> get("first_name,last_name",array('user_id'=>$data['event_order_data']['paid_by_user_id']));
				if($user_email_data = $this -> model_user -> get_user_email($data['event_order_data']['paid_by_user_id']))
				{
					//$from_mail = $user_email_data['email_address'];
					$from_name = $temp['0']['first_name'].' '.$temp['0']['last_name'];
				}				
			}
			else
			{
				//$from_mail = $data['event_order_data']['paid_by_email'];
				$from_name = $data['event_order_data']['paid_by_mobile_phone_number'].' '.$data['event_order_data']['paid_by_mobile_phone_number'];
			}
			
			if ($event_id = $data['event_order_data']['event_id']) {
				$this -> session -> set_userdata('event_id', $event_id);
				$data['total_purchase_tickets'] = $data['event_order_data']['num_tickets'];
			}
			$data['event_order_id'] = $this->utility->encode($event_order_id);
		}
		else 
		{
			
			//FOR FREE RSVP 
			//set static data
			$data['event_order_data']['event_id'] = $data['event_info']['event_id'];
			$data['event_order_data']['paid_by_user_id'] = $this->user_id;
			$data['event_order_data']['currency_id'] = 0;
			$data['event_order_data']['num_tickets'] = 0;
			
			$num_tickets = 6;
			$data['total_purchase_tickets'] = $num_tickets;	
			$data['event_order_data']['num_tickets'] = $num_tickets;
			$data['event_order_data']['order_time'] = SQL_DATETIME;

			for($i=1; $i <= $num_tickets; $i++)
			{
				if($i == 1)
				{
					$this -> general -> set_table('user');
					$userData = $this -> general -> get("first_name,last_name",array('user_id'=>$this->user_id));
					if($userData)
					{
						$this -> general -> set_table('user_email');
						$userEmailData = $this -> general -> get("email_address",array('user_id'=>$this->user_id));
						
						if($userEmailData)
						{
							$data['event_attendee_data'][$i]['event_ticket_id'] = $i;
							$data['event_attendee_data'][$i]['confirmed_user'] = $this->user_id;
							$data['event_attendee_data'][$i]['user_id'] = $this->user_id;
							if($PostData = $this->session->flashdata('msg_invite_data'))
							{
								$data['event_attendee_data'][$i]['invite_email_address'] = $PostData['attendee_emails'][$i];
							}
							else 
							{
								$data['event_attendee_data'][$i]['invite_email_address'] = $userEmailData[0]['email_address'];
							}
							$data['event_attendee_data'][$i]['last_reminder_sent'] = '0000-00-00 00:00:00';
							$data['event_attendee_data'][$i]['first_name'] = $userData[0]['first_name'];
							$data['event_attendee_data'][$i]['last_name'] = $userData[0]['last_name'];
							$data['event_attendee_data'][$i]['rsvp_time'] = '';	
						}
					}
				}
				else 
				{
					$data['event_attendee_data'][$i]['event_ticket_id'] = $i;
					$data['event_attendee_data'][$i]['confirmed_user'] = '';
					$data['event_attendee_data'][$i]['user_id'] = '';
					if($PostData = $this->session->flashdata('msg_invite_data'))
					{
						$data['event_attendee_data'][$i]['invite_email_address'] = $PostData['attendee_emails'][$i];	
					}
					else 
					{
						$data['event_attendee_data'][$i]['invite_email_address'] = '';
					}
					$data['event_attendee_data'][$i]['last_reminder_sent'] = '0000-00-00 00:00:00';
					$data['event_attendee_data'][$i]['first_name'] = '';
					$data['event_attendee_data'][$i]['last_name'] = '';
					$data['event_attendee_data'][$i]['rsvp_time'] = '';
				}
			}
			
			$from_name = '';//user_first_name user_last_name
			$data['total_purchase_tickets'] = '';
			$data['event_order_id'] = '';//
		}
		//postData
		if($postData = $this->input->post())
		{
			if($postData['attendee_emails'])
			{
				$subject = translate_phrase("Hey, I just bought you a ticket to ").$data['event_info']['event_name'];
				foreach($postData['attendee_emails'] as $ticket_id=>$invite_email)
				{
					$this->general->set_table("user_email");
					if($invite_datetix_user_data = $this->general->get("",array('email_address'=>$invite_email)))
					{
                                                
						$tmp_user_id = $invite_datetix_user_data['0']['user_id'];
						/*
						$this->general->set_table("user");
						if($user_profile_data = $this -> general -> get("user_id,first_name,password,facebook_id", array('user_id' => $tmp_user_id)))
						{
							
                                                        $user_link = $this -> utility -> encode($tmp_user_id);
							if ($user_profile_data['0']['password']) {
								$user_link .= '/' . $user_profile_data['0']['password'];
							}
							$data['btn_link'] = base_url() . 'home/mail_action/' . $user_link . '?ticket_id='.$this->utility->encode($ticket_id);
                                                         
                                                    $data['btn_link'] = base_url() .'signin';
						}
                        else
                        {
                            $data['btn_link'] = base_url() .'apply';
                        }
						*/
                    	$data['btn_link'] = base_url() .'signin';
					}
                    else 
                    {
                        $data['btn_link'] = base_url() .'apply';
                    }
					
					$tricket_update = '';
					if($is_free_rspv)
					{
						$event_user_data['event_ticket_id'] = 0;
						$tricket_update = 1;	
						
						$redirect_url = base_url() . url_city_name() . '/event-order-confirmation.html'; 
					}
					else 
					{
						if(!empty($tmp_user_id))
						{
							$event_ticket_update_data['user_id'] = $tmp_user_id;	
						}
						else 
						{
							$event_ticket_update_data['user_id'] = NULL;	
						}
						$event_ticket_update_data['invite_email_address'] = $invite_email;
						$event_user_data['event_ticket_id'] = $ticket_id;
						
						$this -> general -> set_table('event_ticket');
						if($user_data = $this -> general -> update($event_ticket_update_data,array('event_ticket_id'=>$ticket_id)))
						{
							$tricket_update = 1;
						}
						$redirect_url = base_url() . url_city_name() . '/event-order-confirmation.html?rsvp_id='.$data['event_order_id']; 
					}	
					
					$event_user_data['event_id'] = $data['event_info']['event_id'];
					$event_user_data['user_id'] = $tmp_user_id;	
					
					if($this->user_id == $tmp_user_id)
					{
						$event_user_data['rsvp_time'] = $data['event_order_data']['order_time'];
						
						// [For Paid Event]CODE IS MOVED IN PAYPAL SUCCESS.....
                       	if($is_free_rspv)
						{
							$rsvp_subject = translate_phrase("Your RSVP has been confirmed");							
							$email_data['btn_link'] = base_url() . url_city_name() . '/event.html?id='.$data['event_info']['event_id'];
                                                        $email_data['btn_text'] = translate_phrase("View Event Details");
							$email_data['email_title'] = '';									
							$email_data['email_content'] = translate_phrase('IMPORTANT: Please show or print this email as proof of your RSVP when you enter the event venue.');
							$email_template = $this -> load -> view('email/common', $email_data, true);
							
							$this -> model_user -> send_email(INFO_EMAIL,$invite_email, $rsvp_subject, $email_template,"html","DateTix");
						}
					}
					
					//Newly Added fields
					$event_user_data['ad_id'] = $this -> session -> userdata('ad_id');
					$event_user_data['agent_string'] = $this->agent->agent_string();
					
					$this -> general -> set_table('event_user');//set event_user table
					$check_event_user_condition['user_id'] = $this->user_id;
					$check_event_user_condition['event_id'] = $data['event_info']['event_id'];
					
					if(!$this -> general -> checkDuplicate($check_event_user_condition))
					{
						$this -> general -> save($event_user_data);
					}
					unset($event_user_data);
					
					if(!empty($tricket_update))
					{
						if($from_mail)
						{
							$data['btn_text'] = translate_phrase("RSVP Now");
							$data['email_title'] = "";
							if(isset($event_user_data['ad_id']) && $event_user_data['ad_id'] == 12)
							{
								$data['poster_url'] = 'http://www.datetix.com/assets/images/events/6/6mnc.png';
							}
							else
							{
								$data['poster_url'] = $data['event_info']['poster_url'];
							}
							
							if($is_free_rspv)
							{
								$subject = translate_phrase('Hey, come to this party with me');
								
								$data['email_content'] = translate_phrase('Hey, want to come to ').$data['event_info']['event_name'].'?';
								$data['email_content'] .= "<br/><br/>".translate_phrase('The event starts at ').date("g:i a",strtotime($data['event_info']['event_start_time'])).translate_phrase(" on ").date(DATE_FORMATE,strtotime($data['event_info']['event_start_time'])).translate_phrase(' and takes place at').':';
								$data['email_content'] .= "<br/><br/>".$data['event_info']['name'];
								$data['email_content'] .= "<br/>".$data['event_info']['address'];
								$data['email_content'] .= ", ".$data['event_info']['neighborhood_name'];
								$data['email_content'] .= "<br/>".$data['event_info']['city_name'];
								$data['email_content'] .= "<br/>".$data['event_info']['phone_number'];
								$data['email_content'] .= "<br/><br/><b><font color=red>".translate_phrase('IMPORTANT: You still need to RSVP prior to attending the event by clicking the button below:')."</font></b><br/><br/>";
							}
							else 
							{
								$data['email_content'] = translate_phrase('Hey, I just bought you a ticket to ').$data['event_info']['event_name'].'!';
								$data['email_content'] .= "<br/><br/>".translate_phrase('The event starts at ').date("g:i a",strtotime($data['event_info']['event_start_time'])).translate_phrase(" on ").date(DATE_FORMATE,strtotime($data['event_info']['event_start_time'])).translate_phrase(' and takes place at').':';
								$data['email_content'] .= "<br/><br/>".$data['event_info']['name'];
								$data['email_content'] .= "<br/>".$data['event_info']['address'];
								$data['email_content'] .= ", ".$data['event_info']['neighborhood_name'];
								$data['email_content'] .= "<br/>".$data['event_info']['city_name'];
								$data['email_content'] .= "<br/>".$data['event_info']['phone_number'];
								$data['email_content'] .= "<br/><br/><b><font color=red>".translate_phrase('IMPORTANT: You still need to RSVP prior to attending the event by clicking the button below:')."</font></b><br/><br/>";
							}
							
							$email_template = $this -> load -> view('email/common', $data, true);
							
							//$this -> model_user -> send_email($from_mail,$invite_email, $subject, $email_template,"html",$from_name);
							$this -> model_user -> send_email($from_mail,$invite_email, $subject, $email_template,"html",$userData[0]['first_name'] . ' ' . $userData[0]['last_name']);
							//http://localhost/datetix/hongkong/apply.html?event_ticket_id=2L4XMGzWM5Q9vvAVpjoiYgni7r1ab54gzqzKmcWPl9c
						}
					}
                    unset($event_ticket_update_data);
				}
                                
				if($is_free_rspv)
				{
					$this -> session -> set_flashdata('msg_invite_data',$postData);	
				}
				$this -> session -> set_flashdata('success_msg_invite',translate_phrase('RSVP invitation emails successfully sent to above attendees. <b>Please remind each of them to RSVP for the event as soon as possible!</b>'));
				redirect($redirect_url);
			}				
		}
		
		$data['title'] = translate_phrase("Order");
		$data['page_title'] = translate_phrase("Order");
		if($this->session->userdata('user_id'))
		{
			$data['user_data'] = $user;
			$this -> load -> view('template/editProfileTemplate', $data);
		}
		else {
			$this -> load -> view('template/default', $data);
		}
	}
	
	/**
	 * Record Presence of User in event_user table
	 * @access public
	 * @author Rajnish Savaliya
	 */

	public function user_coming($event_id = 0)
	{
		$response['type'] = '0';
		if($event_id)
		{
			if($this->user_id)
			{
				$this -> general -> set_table('event_user');
				$insert_array = array('user_id' => $this->user_id,'event_id' => $event_id);
				if(!$this -> general -> checkDuplicate($insert_array))
				{
					$insert_array['rsvp_time'] = SQL_DATETIME;
					
					//Newly Added fields
					$insert_array['ad_id'] = $this -> session -> userdata('ad_id');
					$insert_array['agent_string'] = $this->agent->agent_string();
					 
					if($this -> general -> save($insert_array))
					{
						$response['type'] = '1';						
					}
					
					$fields = array('rsvp.*','e.*','v.*','n.description as neighborhood_name','ct.description as city_name');
					$from = 'event_ticket as rsvp';
					$joins = array(
							'event_order as ordr' => array('rsvp.event_order_id = ordr.event_order_id', 'inner'), 
							'event as e' => array('e.event_id = ordr.event_id', 'inner'), 
							'venue as v' => array('e.venue_id = v.venue_id', 'inner'), 
							'neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'inner'),
							'city as ct' => array('ct.city_id = n.city_id', 'inner'),
							'province as p' => array('p.province_id = ct.province_id', 'inner'),
							);				
					if($city_id = $this -> session -> userdata('sess_city_id'))
					{
						$where['ct.city_id'] = $city_id;
					}
					else
					{
						$where['ct.city_id'] = $this->config->item('default_city');
					}
							 
					
					$where['ct.display_language_id'] = $this -> language_id;
					$where['p.display_language_id'] = $this -> language_id;
					$where['n.display_language_id'] = $this -> language_id;
					
					$where['e.event_id'] = $event_id;
					if($data['event_info'] = $this -> model_user -> multijoins($fields, $from, $joins, $where))
					{
						$data['event_info'] = $data['event_info']['0'];
						$from_mail = INFO_EMAIL;
						$from_name = "DateTix";
				
						//echo "<pre>";print_r($data['event_info']);exit;
						if($user_email_data = $this -> model_user -> get_user_email($this->user_id))
						{
							$this -> general -> set_table('user');
							$user_data = $this -> general -> get("first_name", array('user_id' => $this->user_id));
		
							$rsvp_subject = translate_phrase("Your RSVP has been confirmed");								
							$email_data['email_title'] = '';
							$email_data['btn_link'] = base_url() . url_city_name() . '/event.html?id='.$data['event_info']['event_id'].'&src='.$post_data['ad_id'];
							$email_data['btn_text'] = translate_phrase("View Event Details");
							$email_data['email_title'] = translate_phrase('Hi ').$user_data['0']['first_name'].translate_phrase(', your RSVP for ').$data['event_info']['event_name'].translate_phrase(" on ").date(DATE_FORMATE,strtotime($data['event_info']['event_start_time'])).translate_phrase(" has been confirmed.");
							
							//$email_data['email_content'] = translate_phrase('We will email you a list of your top matches 24-36 hours before the event, so keep checking your email as we get closer to the event!');
							//$email_data['email_content'] .= "<br/><br/>"
							$email_data['email_content'] .= translate_phrase('The event starts at ').date("g:i a",strtotime($data['event_info']['event_start_time'])).translate_phrase(" on ").date(DATE_FORMATE,strtotime($data['event_info']['event_start_time'])).translate_phrase(' and takes place at').':';
							$email_data['email_content'] .= "<br/><br/>".$data['event_info']['name'];
							$email_data['email_content'] .= "<br/>".$data['event_info']['address'];
							$email_data['email_content'] .= ", ".$data['event_info']['neighborhood_name'];
							$email_data['email_content'] .= "<br/>".$data['event_info']['city_name'];
							$email_data['email_content'] .= "<br/>".$data['event_info']['phone_number']."<br/><br/>";
							$email_template = $this -> load -> view('email/common', $email_data, true);
							$this -> model_user -> send_email(INFO_EMAIL,$user_email_data['email_address'], $rsvp_subject, $email_template,"html","DateTix");
						}
					}					
				}
				else
				{
					$response['msg'] = translate_phrase("You're already coming!");
				}			
			}
			else
			{
				$response['msg'] = translate_phrase("You're not logged in, Please logged in and try again!");
			}
		}
		echo json_encode($response);
	}
	
	
	/**
	 * Save Event User [For Guest User]
	 * @access public
	 * @author Rajnish Savaliya
	 */
	public function bkp_save_guest_user_record()
	{
		$user_id = '';
		if($post_data = $this->input->post())
		{
			$this -> load -> library('form_validation');
			$this -> form_validation -> set_rules('user_email', 'Email', 'trim|required|valid_email|xss_clean|is_unique[user_email.email_address]');
			$this -> form_validation -> set_rules('first_name', 'First Name', 'trim|required');
			$this -> form_validation -> set_error_delimiters('', '');
			$this -> form_validation -> set_message('is_unique', translate_phrase('Email already exists'));
			if ($this -> form_validation -> run() == TRUE) {
				$user_data['first_name'] = $post_data['first_name']; 
				$user_data['last_name'] = $post_data['last_name'] ;
				$email_address = $post_data['user_email'];
				
				unset($post_data['user_email']);
				if($user_id = $this->model_user->insert_user($user_data))
				{
					$this -> general -> set_table('user_email');
					$insert_array = array('user_id' => $user_id, 'is_verified' => '0','created_date' => date('Y-m-d'), 'email_address' => $email_address);
					$id = $this -> general -> save($insert_array);
				}
			}	
		}
		return $user_id;
	}
	/**
	 * Get List of schools of event users
	 * @access public
	 * @author Rajnish Savaliya
	 */
	private function _get_event_users_schools($event_data)
	{
		$fields = array('sc.school_id', 'sc.school_name', 'sc.logo_url');
		$from = 'school as sc';
		$joins = array(
				'user_school as us' => array('us.school_id = sc.school_id', 'INNER'),
				'event_user as eu' => array('eu.user_id = us.user_id', 'INNER'),				
				);
		$condition['sc.display_language_id'] = $this->language_id;
		$condition['eu.event_id'] = $event_data['event_id'];		
		
		//$condition['sc.is_active'] = 1;
		//$condition['sc.is_featured'] = 1;

		$ordersby = 'sc.school_name asc';
	    return $this -> general -> multijoins($fields, $from, $joins, $condition, $ordersby, 'array',NULL,50,NULL,'where','sc.school_id');
		
		//echo $this->db->last_query();
		//echo "<pre>";print_r($school_datas);exit;
	}
	
	/**
	 * Get List of Company of event users
	 * @access public
	 * @author Rajnish Savaliya
	 */
	private function _get_event_users_companies($event_data)
	{
		$fields = array('c.company_id', 'c.company_name', 'c.logo_url');
		$from = 'company as c';
		$joins = array(
				'user_job as uj' => array('c.company_id = uj.company_id', 'INNER'),
				'event_user as eu' => array('eu.user_id = uj.user_id', 'INNER'),				
				);
		//$condition['c.display_language_id'] = $this->language_id;
		$condition['eu.event_id'] = $event_data['event_id'];		
		
		//$condition['sc.is_active'] = 1;
		//$condition['sc.is_featured'] = 1;

		$ordersby = 'c.company_name asc';
	    return $this -> general -> multijoins($fields, $from, $joins, $condition, $ordersby,'array',NULL,50,NULL,'where','c.company_id');
		
		//echo $this->db->last_query();
		//echo "<pre>";print_r($school_datas);exit;
	}
	
	
	/**
	 * Calculate Ticket Packages based on discount.
	 * @access public
	 * @author Rajnish Savaliya
	 */
	private function _get_ticket_package($event_data) {
		
		/*Ticket Package Changes
		 * 
		 * 
		$fields = array('price.*','crncy.description as currency');
		$from = 'event_price as price';
		$joins = array('currency as crncy' => array('price.currency_id = crncy.currency_id ', 'INNER'));
		$where['event_id'] = $event_data['event_id'];
		$where['crncy.display_language_id'] = $this -> language_id;
		$ticket_packages = $this -> model_user -> multijoins($fields, $from, $joins, $where, '', 'price.quantity asc');
		
		 * */
		
		
		$total_save_amount = $event_data['price_online'] - $event_data['price_online_discounted'];
		$save_per = round(100 - ($event_data['price_online_discounted'] * 100) / $event_data['price_online']);
		$package_price = $this->user_id?$event_data['price_online_discounted'] : $event_data['price_online'];
		
		$ticket_packages['0'] = array(
            'event_id' => $event_data['event_id'],
            'event_price_id'=>0,
            'currency_id' => $event_data['currency_id'],
            'quantity' => 1,
            'is_default' => 1,
            'early_bird_price' => '',
            'early_bird_deadline' => '',
            'currency' => $event_data['currency_description'],
            'per_date_price'=>$package_price, //Discounted price
            'total'=>$package_price,
            'name' => '1', //Package Name
            'save_per' => $save_per, // Save Percentage (%)
            'save_amount' => $total_save_amount , // Save amount
            'description' => translate_phrase('1 Ticket'), // Package Description
            'extra' => "", // Extra info
                   
		);
		if ($ticket_packages) {
			if ($this -> session -> userdata('fb_like_discount') == 'yes' && $event_data['event_id'] != 10) {
				//APPLY DateTix FB fan discount
				$discount = 0;
				switch($event_data['currency_id'])
				{
					case 1:
						$discount = 5;
						break;
					case 5:
						$discount = 7;
						break;
					case 6:
						$discount = 30;
						break;
					case 7:
						$discount = 0;
						break;
					case 11:
						$discount = 200;
						break;	
				}
				foreach ($ticket_packages as $key => $package) {
	
					if ($package['per_date_price'] && $ticket_packages[$key]['quantity']<99) {
						$package['per_date_price'] = round($package['per_date_price'] - $discount);
					}
					$ticket_packages[$key] = $package;
				}
			}
		}
		
		//echo $this->db->last_query();
		/*
		if ($ticket_packages) {
			
			//bind all data
			foreach ($ticket_packages as $key => $package) {

				$ticket_packages[$key]['name'] = $ticket_packages[$key]['quantity'];	
				if ($ticket_packages[$key]['quantity']<99)
				{
					$ticket_packages[$key]['description'] = $ticket_packages[$key]['quantity']==1?$ticket_packages[$key]['quantity'].translate_phrase(' ticket'):$ticket_packages[$key]['quantity'].translate_phrase(' tickets');
				}
				else if ($ticket_packages[$key]['quantity']==100)				
				{
					$ticket_packages[$key]['description'] = translate_phrase('Gold VIP Pass (includes one bottle of Grey Goose Vodka + 8 entry tickets + exclusive private VIP table and bottle service)');
				}
				elseif ($ticket_packages[$key]['quantity']==101)
				{
					$ticket_packages[$key]['description'] = translate_phrase('Diamond VIP Pass (includes one bottle of Grey Goose Vodka + one bottle of Moet Champagne + 12 entry tickets + exclusive private VIP table and bottle service)');
				}
				$ticket_packages[$key]['extra'] = '';	

				//$ticket_packages[$key]['per_date_price'] = $ticket_packages[$key]['price'];
				$ticket_packages[$key]['per_date_price'] = $event_data['price_door'];
				$ticket_packages[$key]['total'] = ""; 
				$ticket_packages[$key]['save_amount'] = "";
				$ticket_packages[$key]['save_per'] = "";
			}
			
			if ($this -> session -> userdata('signup_discount') == 'yes' && $event_data['max_prepaid_tickets'] > SMALL_EVENT_USER_LIMIT) {
				//APPLY DateTix member discount
				//$discount = 0.632;
				foreach ($ticket_packages as $key => $package) {				
					if ($package['per_date_price'] && $ticket_packages[$key]['quantity']<99) {
						//$package['per_date_price'] = round($package['per_date_price'] * $discount);
						$package['per_date_price'] = $package['price'];
					}
					$ticket_packages[$key] = $package;
				}			
			}
			
			if ($this -> session -> userdata('fb_like_discount') == 'yes' && $event_data['event_id'] != 10) {
				//APPLY DateTix FB fan discount
				$discount = 30;
				foreach ($ticket_packages as $key => $package) {

					if ($package['per_date_price'] && $ticket_packages[$key]['quantity']<99) {
						$package['per_date_price'] = round($package['per_date_price'] - $discount);
					}
					$ticket_packages[$key] = $package;
				}
			}
			
			//Calculate ticket percentage, save amount and total price
			foreach ($ticket_packages as $key => $package) {

				if ($ticket_packages[$key]['quantity']<99)
				{
					$ticket_packages[$key]['total'] = $ticket_packages[$key]['per_date_price'] * $ticket_packages[$key]['quantity'];
				}
				else
				{
					$ticket_packages[$key]['total'] = $ticket_packages[$key]['per_date_price'];
				}
				if ($event_data['price_door'] > 0 && $ticket_packages[$key]['quantity']<99)
				{
					$original_price = $event_data['price_door'] * $ticket_packages[$key]['quantity'];
				}
				else
				{
					$original_price = $ticket_packages[$key]['per_date_price'] * $ticket_packages[$key]['quantity'];
				}
				$actual_price = $ticket_packages[$key]['price'] * $ticket_packages[$key]['quantity'];
				//$actual_price = $ticket_packages[$key]['per_date_price'] * $ticket_packages[$key]['quantity'];
				$ticket_packages[$key]['save_amount'] = $original_price - $actual_price;
				$ticket_packages[$key]['save_per'] = round(100 - ($actual_price * 100) / $original_price);
				
			}
		}*/
		//echo "<pre>";print_r($ticket_packages);exit;
		return $ticket_packages;
	}
	
	
	/**
	 * Apply_discount :: Apply Discount on package Price.[Get More Ticket Page]
	 * @access public
	 * @author Rajnish Savaliya
	 */
	public function apply_fb_discount($is_fb_like) {
		if($is_fb_like)
		{
			$this -> session -> set_userdata('fb_like_discount', 'yes');
		}
		else
		{
			$this -> session -> set_userdata('fb_like_discount', 'no');
		}
		$event_data = $this->_get_event_details();
		$data['ticket_packages'] = $this -> _get_ticket_package($event_data);
		$this -> load -> view('event/load_packages', $data);
	}
	
	/**
	 * Fetch latest event from db.
	 * @access public
	 * @author Rajnish Savaliya
	 */
	private function _get_event_details()
	{
		
		$fields = array('v.*','e.*',
						'n.description as neighborhood_name','ct.description as city_name',
						'p.description as province_name','c.description as country_name',
						'crncy.currency_id', 'crncy.description as currency_description', 
						);
		$from = 'event as e';
		$joins = array(
					'venue as v' => array('e.venue_id = v.venue_id', 'inner'), 
					'neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'inner'),
					'city as ct' => array('ct.city_id = n.city_id', 'inner'),
					'province as p' => array('p.province_id = ct.province_id', 'inner'),
					'country as c' => array('p.country_id = c.country_id', 'LEFT'),
					'currency as crncy' => array('c.currency_id = crncy.currency_id ', 'LEFT')
				
				 );
		if($city_id = $this -> session -> userdata('sess_city_id'))
		{
				//$where['ct.city_id'] = $city_id;
		}
		else
		{
				//$where['ct.city_id'] = $this->config->item('default_city');
		}
				 
		
		$where['ct.display_language_id'] = $this -> language_id;
		$where['p.display_language_id'] = $this -> language_id;
		$where['crncy.display_language_id'] = $this -> language_id;
		
		if($event_id = $this -> session -> userdata('event_id'))
		{
			$where['e.event_id'] = $event_id;
		}
		else
		{
			$where['DATE(e.event_start_time) >='] = SQL_DATE;
		
		}
		$where['e.display_language_id'] = $this -> language_id;
		$where['c.display_language_id'] = $this -> language_id;
		$where['n.display_language_id'] = $this -> language_id;
		$where['v.display_language_id'] = $this -> language_id;
		if($event_data = $this -> model_user -> multijoins($fields, $from, $joins, $where,'','e.event_start_time asc'))
		{
			$event_info = $event_data['0'];
			
			$partner_discount = 0;
			if($url_segment = $this->input->get('url'))
			{
				$this -> general -> set_table('event_url');
				$event_url_condition['url'] = $url_segment;
				if($event_url_datas = $this -> general -> get("*",$event_url_condition,array('event_id'=>'desc'),1))
				{
					$partner_discount =	$event_url_datas['0']['discount_amount'];	
				}
			}
			
			$event_info['price_online'] -= $partner_discount;
			$event_info['price_online_discounted'] -= $partner_discount;
			
			//echo $this->db->last_query();
			$this->general->set_table('event_language');
			$event_lang_condition['event_id'] = $event_data['0']['event_id'];
			$event_lang_condition['display_language_id'] = $event_data['0']['display_language_id'];
			
			if($row_data = $this->general->get("",$event_lang_condition))
			{
				$event_info = array_merge($row_data['0'],$event_info);
			}
			return $event_info;
		}
		else
		{
			//echo $this->db->last_query();exit;
				return false;
		}
			
	}
	
	/**
	 * Fetch latest event from db.
	 * @access public
	 * @author Rajnish Savaliya
	 */
	private function _get_event_count()
	{
		
		$fields = array('e.event_id');
		$from = 'event as e';
		$joins = array(
					'venue as v' => array('e.venue_id = v.venue_id', 'inner'), 
					'neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'inner'),
					'city as ct' => array('ct.city_id = n.city_id', 'inner'),
				 );
		if($city_id = $this -> session -> userdata('sess_city_id'))
		{
				$where['ct.city_id'] = $city_id;
		}
		else
		{
				$where['ct.city_id'] = $this->config->item('default_city');
		}
		
		$where['ct.display_language_id'] = $this -> language_id;
		$where['n.display_language_id'] = $this -> language_id;
		return $this -> model_user -> multijoins($fields, $from, $joins, $where,'','e.event_start_time asc',NULL,NULL,'count');
	}

	/**
	* event_photos() : Function scan event_photos directory and show all images on the page.
	* @return : View
	* @access public
	* @author Rajnish Savaliya
	*/
	public function event_photos($event_id = 1)
	{
		$event_id = $this -> input -> get('id');		
		$this->general->set_table('event');		
		$fields = array('v.*','e.*',
						'n.description as neighborhood_name','ct.description as city_name',
						'p.description as province_name','c.description as country_name'
						);
		$from = 'event as e';
		$joins = array(
					'venue as v' => array('e.venue_id = v.venue_id', 'inner'), 
					'neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'inner'),
					'city as ct' => array('ct.city_id = n.city_id', 'inner'),
					'province as p' => array('p.province_id = ct.province_id', 'inner'),
					'country as c' => array('p.country_id = c.country_id', 'LEFT'),
					);
		
		$where['ct.display_language_id'] = $this->language_id;		
		$where['e.event_id'] = $event_id;
		$where['n.display_language_id'] = $this->language_id;
		//$where['e.display_language_id'] = $this->language_id;
		$where['p.display_language_id'] = $this -> language_id;
		$where['c.display_language_id'] = $this -> language_id;
		$data['event_info'] = array();
		if($event_data = $this -> model_user -> multijoins($fields, $from, $joins, $where,'','e.event_start_time asc'))
		{
			$data['form_para'] = '?id='.$event_id;
			
			$data['event_info'] = $event_data['0'];
			$this->load->helper('directory');
			$data['event_photos'] = $this->_getEventPhotos($event_id);			
		}
		
		$data['title'] = translate_phrase("Event Photos");
		$data['page_title'] = translate_phrase("Event Photos");
		$data['page_name'] = 'event/event_photo';
		if($this -> user_id)
		{
			$this -> general -> set_table('user');
			$user_data = $this -> general -> get('user.*,
			CASE
						WHEN
							birth_date != "0000-00-00" 	
						THEN 
							TIMESTAMPDIFF(YEAR,`birth_date`,CURDATE())
					END as user_age
			', array('user_id' => $this -> user_id));
			$data['user_data'] = $user_data['0'];
			$this -> load -> view('template/editProfileTemplate', $data);
		}
		else {
			$this -> load -> view('template/default', $data);
		}
	}
	
	private function _getEventPhotos($event_id)
	{
		$this->general->set_table('event_photos');		
		$condition['event_id'] = $event_id;
		$order_by['view_order'] = 'asc';
		$this->db->order_by('view_order=0, view_order');
		return $this->general->get("",$condition);
	}
	/**
	 * Get list of events
	 * @access public
	 * @author Rajnish Savaliya
	 */
	public function event_list()
	{
		$cur_year = '';//date('Y'); : Load all events
		
		/*for ($i = $cur_year; $i < (date('Y') + 20); $i++) {
			$data['year'][$i] = $i;
		}*/
		
		$this -> general -> set_table('event');
		if($years = $this -> general -> advance_get('YEAR(event_start_time) as year',array('DATE(event_start_time) <' => SQL_DATE),array('year'=>'desc'),'year'))
		{
			foreach($years as $item) {
				$data['year'][$item['year']] = $item['year'];
			}
		}
		$data['total_events_in_city'] = $this->_get_event_count();
		$fields = array('v.*','e.*',
						'n.description as neighborhood_name','ct.description as city_name',
						'p.description as province_name','c.description as country_name'
						);
		$from = 'event as e';
		$joins = array(
					'venue as v' => array('e.venue_id = v.venue_id', 'inner'), 
					'neighborhood as n' => array('v.neighborhood_id = n.neighborhood_id', 'inner'),
					'city as ct' => array('ct.city_id = n.city_id', 'inner'),
					'province as p' => array('p.province_id = ct.province_id', 'inner'),
					'country as c' => array('p.country_id = c.country_id', 'LEFT'),
				 );
		if($city_id = $this -> session -> userdata('sess_city_id'))
		{
				//$where['ct.city_id'] = $city_id;
		}
		else
		{
				//$where['ct.city_id'] = $this->config->item('default_city');
		}
		
		$where['ct.display_language_id'] = $this -> language_id;
		$where['p.display_language_id'] = $this -> language_id;
		$where['c.display_language_id'] = $this -> language_id;
		$where['n.display_language_id'] = $this -> language_id;
		$where['e.display_language_id'] = $this -> language_id;
		$where['v.display_language_id'] = $this -> language_id;
				
		//date is greater then todays and current year 
		$where['DATE(e.event_start_time) >='] = SQL_DATE;		
		$data['upcoming_event_data'] = $this -> model_user -> multijoins($fields, $from, $joins, $where,'','e.event_start_time asc');
		unset($where['DATE(e.event_start_time) >=']);
		
		//date is less means pass date and current year 
		$where['DATE(e.event_start_time) <'] = SQL_DATE;
		
		$data['post_data'] = 'no';
		if($year = $this->input->post('year'))
		{
			$data['post_data'] = 'yes';
			$cur_year = $year;
		}
		if($cur_year)
		{
			$where['YEAR(e.event_start_time)'] = $cur_year;	
		}
		
		$data['past_event_data'] = $this -> model_user -> multijoins($fields, $from, $joins, $where,'','e.event_start_time desc');
		$data['selected_year'] = $cur_year;
		//echo $this->db->last_query();
		//echo "<pre>";print_r($data);exit;
		
		$data['page_name'] = 'event/event_list';
		$data['page_title'] =  get_assets('name','DateTix').translate_phrase(" Events");
		if($this->session->userdata('user_id'))
		{
			$this -> general -> set_table('user');
			$user_data = $this -> general -> get('user.*,
			CASE
						WHEN
							birth_date != "0000-00-00" 	
						THEN 
							TIMESTAMPDIFF(YEAR,`birth_date`,CURDATE())
					END as user_age
			', array('user_id' => $this -> user_id));

			//Signup Discount
			$user = $user_data['0'];
			$data['user_data'] = $user;
			$this -> load -> view('template/editProfileTemplate', $data);
		}
		else {
			$this -> load -> view('template/default', $data);
		}
	}
	
	public function test_like_plugin()
	{
		$fb_id = $this->facebook->getUser();
		$fql =  "SELECT uid FROM page_fan WHERE page_id = ".$this->config->item('page_id')." AND uid = ".$fb_id;
		echo $fql;
		$params = array('method' => 'fql.query', 'query' => $fql);		
		try {
			if($fb_data = $this -> facebook -> api($params))
			{
				echo "<pre>";print_r($fb_data);
			}
		} catch (Exception $e) {
			// Log Error
			echo $e;
		}
		
	}
}
