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
		$raw_data = $data;
		$data = strstr($data, 'Logout</a></li>');
		$data = substr($data, 15);
		$ori_data = $data;
		$data = strstr($data, '<h3>Application Checklist</h3><v><html><head><title></title></head><body>');
		$data = strstr(substr($data, 73), '&#xA0;', true);

		curl_close($curl);

		if ($data != '') {
			$return = ['sha' => md5($ori_data), 'data' => $data,
				'cookie' => $this->cookie];
			if(strstr(strtolower($raw_data), 'congrat')) {
				$return['admitted'] = true;
			} else if (strstr(strtolower($raw_data), 'waiting list') || strstr(strtolower($raw_data), 'wait list')){
				$return['waiting'] = true;
			} else if(strstr(strtolower($raw_data), 'reject') || strstr(strtolower($raw_data), 'sorry')) {
				$return['reject'] = true;
			} else if (!strstr(strtolower($raw_data), 'incomplete')
				&& !strstr(strtolower($raw_data), 'missing')) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;
			return $return;
		} else if (strstr(strtolower($raw_data), 'congrat')) {
			return ['sha' => md5($ori_data), 'data' => $data,
				'cookie' => $this->cookie, 'admitted' => true];
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