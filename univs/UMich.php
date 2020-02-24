<?php
class UMich {
	private $cookie = [];
	private $user_name = '';
	private $password = '';

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/UMich');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
			return;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://enrollmentconnect.umich.edu/account/login');
		curl_setopt($curl, CURLOPT_POST, 1);
		$u = urlencode($this->user_name);
		$p = urlencode($this->password);
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
		curl_setopt($curl, CURLOPT_URL,'https://enrollmentconnect.umich.edu/apply/status');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		curl_setopt($curl, CURLOPT_HEADER, 1);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$data = curl_exec($curl);
		$raw_data = strtolower(strip_tags($data));
		$data = strstr($data, '<a aria-controls="messages" aria-expanded="false" class="btn btn-dark stretched-link mmenu" data-target="#messages" data-toggle="collapse" href="#" role="button">');
		$data = strstr($data, 'data-toggle="collapse" type="button">');
		$ori_data = strip_tags($data);
		$data = substr(strstr($data, '</button>', true), 37);

		curl_close($curl);

		$ad = strstr($raw_data, 'congrat') || strstr($raw_data, 'accept') || strstr($raw_data, 'admit');
		$wl = strstr($raw_data, 'waiting list') || strstr($raw_data, 'wait list');
		$rej = strstr($raw_data, 'reject') || strstr($raw_data, 'denied') || strstr($raw_data, 'sorry');
		$cmplt = !strstr($raw_data, 'incomplete') && !strstr($raw_data, 'missing');

		if ($ad || $wl || $rej || trim($data) != ''){
			$return = ['sha' => md5($ori_data), 'data' => trim(strip_tags($data)),
				'cookie' => $this->cookie];
			if($ad) {
				$return['admitted'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if ($cmplt) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;
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