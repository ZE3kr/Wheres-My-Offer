<?php
class UIUC {
	private $cookie = [];
	private $user_name = '';
	private $password = '';

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/UIUC');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
			return;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://myillini.illinois.edu/IdentityManagement/Login');
		// curl_setopt($curl, CURLOPT_POST, 1);
		$u = $this->user_name;
		$p = $this->password;
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl);
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $data, $matches);
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$this->cookie = array_merge($this->cookie, $cookie);
		}
		$data = strstr($data, '<input name="__RequestVerificationToken" type="hidden" value="');
		$data = substr($data, 62, 155);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		$request = "Username=${u}&Password=${p}&__RequestVerificationToken=${data}";
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
		curl_setopt($curl, CURLOPT_URL,'https://myillini.illinois.edu/Apply/Application/Status');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$data = curl_exec($curl);
		$raw_data = $data;
		$data = strstr($data, '<strong>Status: </strong>');
		$ori_data = $data;
		$data2 = strstr($data, '<h2>Received Items</h2>');
		$data2 = strstr($data2, '</ul>', true);
		$data3 = strstr($data, '<h2>Missing Items</h2>');
		$data3 = strstr($data3, '</ul>', true);
		$data = substr(strstr($data, '</p>', true), 25);
		curl_close($curl);

		$data2 = strstr($data2, '<li>');
		$received = '';
		while($data2 != ''){
			$data2 = substr($data2, 4);
			$append = strstr($data2, '<em>', true);
			$append2 = strstr($append, '<br', true);
			if($append2){
				$append = $append2;
			}
			$received .= trim($append).'. ';
			$data2 = strstr($data2, '<li>');
		}
		if($received){
			$received = substr($received, 0, -2);
		}

		$data3 = strstr($data3, '<li>');
		$missing = '';
		while($data3 != ''){
			$data3 = substr($data3, 4);
			$append = strstr($data3, '<em>', true);
			$append2 = strstr($append, '<br', true);
			if($append2){
				$append = $append2;
			}
			$missing .= trim($append).'. ';
			$data3 = strstr($data3, '<li>');
		}
		if($missing){
			$missing = substr($missing, 0, -2);
		}

		$ad = strstr(strtolower($raw_data), 'congrat') || strstr(strtolower($raw_data), 're an illini');
		$wl = strstr(strtolower($raw_data), 'waiting list') || strstr(strtolower($raw_data), 'wait list') || strstr(strtolower($raw_data), 'defer');
		$rej = strstr(strtolower($raw_data), 'reject') || strstr(strtolower($raw_data), 'sorry');

		if ($ad || $wl || $rej || trim($data) != ''){
			$return = ['sha' => md5($ori_data), 'data' => trim($data),
				'cookie' => $this->cookie];
			if($ad) {
				$return['admitted'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if (!$missing) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;

			if($missing){
				$missing = ' <span class="alert-danger">'.trim($missing).'</span>';
			}
			if($received){
				$received = ' <span class="alert-success small">'.trim($received).'</span>';
			}
			$return['html'] = trim($data.$missing.$received);

			return $return;
		} else if (strstr(strtolower($raw_data), 'congrat')) {
			return ['sha' => md5($ori_data), 'data' => $data,
				'cookie' => $this->cookie, 'admitted' => true];
		}
		return NULL;
	}

	private function cookie_str(){
		foreach($this->cookie as $k => $v){ // this will fail if there are any more -public- variables declared in the class.
			$k = str_replace('_', '.', $k);
			$c[] = "$k=$v";
		}
		return implode('; ', $c);
	}
}