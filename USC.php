<?php
class USC {
	private $cookie = [];
	private $user_name = '';
	private $password = '';

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/USC');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
			return;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://applyto.usc.edu/account/login');
		curl_setopt($curl, CURLOPT_POST, 1);
		$u = $this->user_name;
		$p = $this->password;
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$request = "email=${u}&password=${p}";
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		$result = curl_exec($curl);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$this->cookie = array_merge($this->cookie, $cookie);
		}
	}

	public function get_status(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://applyto.usc.edu/apply/status');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		curl_setopt($curl, CURLOPT_HEADER, 1);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$data = curl_exec($curl);
		$raw_data = strtolower(strip_tags($data));
		$data = strstr($data, 'Logout</a></li>');
		$data = substr($data, 15);
		$ori_data = strstr($data, '<b>Withdraw Your Application</b>', true);
		$ori_data = preg_replace('/[0-9a-f-]{36}/', '', $ori_data);
		$ori_data = strstr($ori_data, '<form action="', true);
		$data = strstr($data, '<h3>Application Checklist</h3><v><html><head><title></title></head><body>');
		$data = strstr(substr($data, 73), '&#xA0;', true);
		$data_html = str_replace('Items marked as "Awaiting" are still necessary for review of your application.', '', $data);
		
		$data2 = $ori_data;
		$ori_data = strip_tags($ori_data);
		$received = '';
		$waiting = '';
		$data2 = strstr($data2, 'Status: ');
		while($data2 != ''){
			$chk = strtolower(strstr($data2, '"', true));
			
			$data2 = substr(strstr($data2, '</td>'), 5);
			$data2 = substr(strstr($data2, '</td>'), 5);
			
			$data2 = substr(strstr($data2, '>'), 1);
			$append = strstr($data2, '<', true);
			$append2 = strstr($append, ' for ', true);
			if($append2){
				$append = $append2;
			}
			
			if(strstr($chk, 'received') || strstr($chk, 'completed')){
				$received .= $append.'. ';
			} else {
				$waiting .= $append.'. ';
			}
			$data2 = strstr($data2, 'Status: ');
		}
		if($waiting){
			$waiting = substr($waiting, 0, -2);
		}
		if($received){
			$received = substr($received, 0, -2);
		}

		curl_close($curl);

		$ad = strstr($raw_data, 'congrat') || strstr($raw_data, 'accept') || strstr($raw_data, 'admitted');
		$wl = strstr($raw_data, 'waiting list') || strstr($raw_data, 'wait list');
		$rej = strstr($raw_data, 'reject') || strstr($raw_data, 'sorry');

		if ($ad || $wl || $rej || trim($data.$waiting) != '') {
			$return = ['sha' => md5($ori_data), 'data' => trim(strip_tags($data)),
				'cookie' => $this->cookie];
			if($ad) {
				$return['admitted'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if (!$waiting) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;
			
			if($waiting){
				$waiting = ' <span class="alert-danger">'.trim($waiting).'</span>';
			}
			if($received){
				$received = ' <span class="alert-success small">'.trim($received).'</span>';
			}
			$return['html'] = trim($data_html).$waiting.$received;
			
			return $return;
		}
		return NULL;
	}

	private function cookie_str(){
		foreach($this->cookie as $k => $v){ // this will fail if there are any more -public- variables declared in the class.
			$c[] = "$k=$v";
		}
		return implode('; ', $c);
	}
}