<?php
	/**
	*
	*
	*
	*/
	
	require_once 'b24db.php';
	
	class BxRestException extends Exception {
		public function displayMessage($error_message, $is_ajax = false) {
			if ($is_ajax) {
				echo '<div class="alert alert-danger" role="alert">'.$error_message.'</div>';
			} else {
				$bitrix_error = $error_message;
				require_once 'b24view.php';
			}
		}
	}
	
	class BitrixApplication {
		protected $domain = '';
		protected $auth_id = '';
		protected $refresh_id = '';
		protected $expire = 0;
		protected $operation = 'default';
		protected $dbh;
		protected $is_ajax = false;
		
		
		public function init() {
			$this->dbh = DB::setConnection();
			try {
				$this->parseRequest();
				$this->proceedWork();
			} catch (BxRestException $e) {
				$e->displayMessage($e->getMessage(), $this->is_ajax);
			}
		}
		
		protected function proceedWork() {
			switch ($this->operation) {
				case 'test':
					$api_result = array();
					$task_items = array();
					$task_items_need = false;
					$rest_method = '';
					$rest_params = array();
					
					try {
						if (isset($_REQUEST['opt-project-item'])) {
							$task_items_need = true;
							$rest_method = 'task.item.list.json';
							$rest_params = array();
							$rest_params['ORDER'] = array('ID' => 'asc');
							$rest_params['FILTER'] = array('GROUP_ID' => $_REQUEST['opt-project-item']);
							$rest_params['PARAMS'] = array('NAV_PARAMS' => array('iNumPage' => 1));
							$rest_params['SELECT'] = array('ID');

							$bitrix_task_result = $this->prepareCall($rest_method, $rest_params);
							foreach ($bitrix_task_result['result'] as $bitrix_task) {
								$task_items[] = $bitrix_task['ID'];
							}
							
						}
						if (!$task_items_need || ($task_items_need && count($task_items) > 0)) {
							$rest_method = 'task.elapseditem.getlist.json';	
							$rest_params = array();
							$rest_params['ORDER'] = array('USER_ID' => 'ASC');
							$rest_params['FILTER'] = array();
							if (count($task_items) > 0) {
								$rest_params['FILTER']['TASK_ID'] = $task_items;
							}
							if (isset($_REQUEST['opt-date-from'])) {
								$rest_params['FILTER']['>=CREATED_DATE'] = implode('-', array_reverse(explode('.', $_REQUEST['opt-date-from'])));
							}
							if (isset($_REQUEST['opt-date-to'])) {
								$rest_params['FILTER']['<=CREATED_DATE'] = implode('-', array_reverse(explode('.', $_REQUEST['opt-date-to'])));
							}
						
							$bitrix_result = $this->prepareCall($rest_method, $rest_params);
							
							foreach ($bitrix_result['result'] as $result_arr) {
								$result_date = date('d.m.Y', strtotime($result_arr['CREATED_DATE']));
								$api_result[$result_arr['USER_ID']]['dates'][$result_date] += $result_arr['SECONDS'];
							}
							$users_arr = array_keys($api_result);
							
							if (count($users_arr) > 0) {
								$rest_method = 'user.get.json';	
								$rest_params = array();
								$rest_params['FILTER'] = array('ID' => $users_arr);
							
								$bitrix_user_result = $this->prepareCall($rest_method, $rest_params);
							
								foreach ($bitrix_user_result['result'] as $bitrix_user) {
									$api_result[$bitrix_user['ID']]['user_fio'] = $bitrix_user['LAST_NAME'] . ' ' . $bitrix_user['NAME'] . ' ' . $bitrix_user['SECOND_NAME'];
								}
							}
						}

						require_once 'b24result.php';
					} catch (BxRestException $e) {
						$e->displayMessage($e->getMessage(), $this->is_ajax);
					}
					break;
				default:
					$rest_method = 'sonet_group.get.json';
					$rest_params = array(
						'ORDER' => array('NAME' => 'ASC')
					);
					try {
						$bitrix_result = $this->prepareCall($rest_method, $rest_params);
					} catch (BxRestException $e) {
						$e->displayMessage($e->getMessage(), $this->is_ajax);
					}
					
					require_once 'b24view.php';
					break;
			}
		}
		
		protected function parseRequest() {		
			if (isset($_REQUEST['ajax_operation'])) {
				$this->operation = $_REQUEST['ajax_operation'];
			}
			$this->is_ajax = $this->operation == 'default' ? false : true;
			
			
			if (isset($_REQUEST['DOMAIN'], $_REQUEST['AUTH_ID'], $_REQUEST['REFRESH_ID'], $_REQUEST['AUTH_EXPIRES'])) {
				$this->setAuthParams($_REQUEST);
				$this->saveAuthParams();
			} else {
				if (isset($_REQUEST['DOMAIN'])) {
					$this->domain = $_REQUEST['DOMAIN'];
				} else {
					throw new BxRestException('Ajax call. Domain not found.');
				}
				
				$auth_db = $this->getAuthParams($this->domain);
				if (count($auth_db) == 4) {
					list($this->domain, $this->auth_id, $this->expire, $this->refresh_id) = $auth_db;
					if ($this->expire <= time()) {
						$auth_refresh = $this->refreshAuth();
						if (isset($auth_refresh['access_token'], $auth_refresh['expires_in'], $auth_refresh['refresh_token'])) {
							$this->setAuthParams(array('DOMAIN' => $this->domain, 'AUTH_ID' => $auth_refresh['access_token'], 'REFRESH_ID' => $auth_refresh['refresh_token'], 'AUTH_EXPIRES' => $auth_refresh['expires_in']));
							$this->saveAuthParams();
						} else {
							$refresh_error = isset($auth_refresh['error_description']) ? $auth_refresh['error_description'] : '';
							throw new BxRestException('Ajax call. Auth parameters refresh fail.<br>' . $refresh_error);
						}
					}
				} else {
					throw new BxRestException('Ajax call. Authorization params not found.');
				}
			}
		}
		
		protected function setAuthParams(array $auth_arr = array()) {
			$this->domain = $auth_arr['DOMAIN'];
			$this->auth_id = $auth_arr['AUTH_ID'];
			$this->refresh_id = $auth_arr['REFRESH_ID'];
			$this->expire = time() + $auth_arr['AUTH_EXPIRES'];
		}
		
		protected function saveAuthParams() {
			$save_query = 'INSERT INTO b24_portal_auth (domain,auth_id,auth_expires,refresh_id) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE
			auth_id=?, auth_expires=?, refresh_id=?';
			$query = $this->dbh->prepare($save_query);
			$query->execute(array($this->domain, $this->auth_id, $this->expire, $this->refresh_id, $this->auth_id, $this->expire, $this->refresh_id));
		}
		
		protected function getAuthParams($domain_param) {
			$auth_arr = array();
			$select_query = 'SELECT domain,auth_id,auth_expires,refresh_id FROM b24_portal_auth WHERE domain=?';
			$query = $this->dbh->prepare($select_query);
			if ($query->execute(array($domain_param))) {
				$auth_arr = $query->fetch(PDO::FETCH_NUM);
			}
			
			return $auth_arr;
		}
		
		protected function prepareCall($call_method, array $call_params = array()) {
			 $call_url = 'https://'.$this->domain.'/rest/'.$call_method;
			 $call_params['auth'] = $this->auth_id;
			 $call_result = $this->callRest($call_url, $call_params);
			 
			 return $call_result;
		}
		
		protected function refreshAuth() {
			$oauth_params = array(
				'grant_type'	=> 'refresh_token',
				'client_id'		=> DB::getValue('app_id'),
				'client_secret'	=> DB::getValue('app_secret_key'),
				'refresh_token'	=> $this->refresh_id
			);
			
			$oauth_result = $this->callRest(DB::getValue('oauth_url') . '?' . http_build_query($oauth_params));
			
			return $oauth_result;
		}
		
		
		protected function callRest($callUrl, array $call_params = array()) {
			$curl_options = array(
				CURLINFO_HEADER_OUT => 1,
				CURLOPT_VERBOSE => 1,
				CURLOPT_URL => $callUrl,
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_POST => 1,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_POSTFIELDS => http_build_query($call_params),
			);
			
			$curl = curl_init();
			curl_setopt_array($curl, $curl_options);
			$curlResult = curl_exec($curl);
			//$curlRequestInfo = curl_getinfo($curl); // сохраняем информацию о сеансе curl (необязательно, пригодится для обработки)
			//var_dump($curlRequestInfo);die;
			$curlErrorNumber = curl_errno($curl); // получаем код последней ошибки или 0, если все ОК
			$curlErrorMsg = curl_error($curl);
			curl_close($curl);
			if ($curlErrorNumber > 0) {
				$errorMsg = $curlErrorMsg . PHP_EOL . 'cURL error code: ' . $curlErrorNumber . PHP_EOL;
				throw new BxRestException($errorMsg);
			}
			
			//$tmpResult = $curlResult; // можно сохранить результат выполнения для последующей обработки
			$resultObj = json_decode($curlResult, true);
			$jsonErrorCode = json_last_error();
			if (is_null($resultObj)) {
				$errorMsg = 'Function json_decode error: ' . PHP_EOL . $jsonErrorCode . ' - ' . json_last_error_msg() . PHP_EOL;
				throw new BxRestException($errorMsg);
			}
			
			unset($curlResult);
			return $resultObj;
		}
	}
	
	$app = new BitrixApplication();
	$app->init();
?>