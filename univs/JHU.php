<?php
class JHU {
	private $cookie = [];
	private $user_name = '';
	private $password = '';

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/JHU');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
			return;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://admissions.jhu.edu/account/login');
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
		curl_setopt($curl, CURLOPT_URL,'https://admissions.jhu.edu/apply/status');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		curl_setopt($curl, CURLOPT_HEADER, 1);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$data = curl_exec($curl);
		$raw_data = strtolower(strip_tags($data));
		$data = strstr($data, '<div id="TR_Account Set Up">');
		$data = strstr($data, '<p>');
		$data = substr($data, 3);
		$ori_data = strstr($data, '<a class="std" href="/account/logout">log out</a>', true);
		$data = strstr($data, '</p>', true);
		$data = strip_tags($data);
		print_r($data);
		$data_html = str_replace([
			'Admissions notifications for transfer applicants will be released in May. ',
			'You&#x2019;ll receive an email closer to the decision release date with more information. '
		], '', $data);
		$data_html = str_replace('All admissions files are checked for completion; we will be in touch via email if any items are missing from your application.', 'Complete', $data_html);
		$data = str_replace('&#x2019;', '’', $data);

		curl_close($curl);

		$ad = strstr($raw_data, 'congrat');
		$wl = strstr($raw_data, 'waiting list') || strstr($raw_data, 'wait list');
		$rej = strstr($raw_data, 'reject') || strstr($raw_data, 'denied')
			|| strstr($raw_data, 'sorry') || strstr($raw_data, 'regret');

		if ($ad || $wl || $rej || trim($ori_data) != '') {
			$return = ['sha' => md5($ori_data), 'data' => trim($data),
				'cookie' => $this->cookie];
			if($ad) {
				$return['admitted'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if (strstr($data_html, 'Complete')) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;

			$return['html'] = trim($data_html);

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
