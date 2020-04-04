<?php
class CMU {
	private $cookie = [];
	public function setup($cookie){
		$this->cookie = $cookie;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/CMU');
		$prev = json_decode($prev, true);
		if(isset($prev['notified']) && $prev['notified'] == md5(json_encode($this->cookie))){
			unset($this->cookie);
			return;
		}
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
		}
	}

	public function get_status(){
		if(!isset($this->cookie) || !$this->cookie){
			return NULL;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://s3.andrew.cmu.edu/aio/wai');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$data = curl_exec($curl);
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $data, $matches);
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$this->cookie = array_merge($this->cookie, $cookie);
		}
		$raw_data = strtolower(strip_tags($data));
		$data2 = strstr($data,'<!-- Received Documents -->');
		$data2 = strstr($data2, '<!-- Sent Documents -->', true);
		$data = strstr($data, '<h3 class="page-title display-inline">Welcome, ');
		$ori_data = strip_tags($data);
		$data = substr($data, 38);
		$data = strstr($data, '</h3>', true);
		curl_close($curl);

		$list = [];
		$data2 = strstr($data2, 'data-title="DOCUMENT"');
		while($data2 != ''){
			$data2 = substr(strstr($data2, '>'), 1);
			$append = trim(strstr($data2, '</td>', true));
			$list[$append] = ($list[$append] ?? 0) + 1;
			$data2 = strstr($data2, 'data-title="DOCUMENT"');
		}

		foreach($list as $key => $i ) {
			if($i > 1){
				$data2 .= $key.' x'.$i.'; ';
			} else {
				$data2 .= $key.'; ';
			}
		}
		if($data2) {
			$data2 = substr($data2, 0, -2);
		}

		$ad = strstr($raw_data, 'congrat') || strstr($raw_data, 'accept') || strstr($raw_data, 'admit');
		$wl = strstr($raw_data, 'waiting list') || strstr($raw_data, 'wait list')
			|| strstr($raw_data, 'decision notification letter') || strstr($raw_data, 'view decision letter');
		$rej = strstr($raw_data, 'reject') || strstr($raw_data, 'denied')
			|| strstr($raw_data, 'sorry') || strstr($raw_data, 'regret');
		$cmplt = !strstr($raw_data, 'incomplete');

		if ($ad) {
			$data = trim('Admitted. '.$data);
		} else if ($wl) {
			$data = trim('Unknown Decision. '.$data);
		} else if($rej) {
			$data = trim('Rejected. '.$data);
		} else if ($cmplt) {
			$data = trim('Complete. '.$data);
		} else {
			$data = trim('Incomplete. '.$data);
		}

		if ($ad || $wl || $rej || trim($data) != ''){
			$return = ['sha' => md5($ori_data), 'data' => trim(strip_tags($data)),
				'cookie' => $this->cookie];
			if($data2){
				$return['html'] = trim(strstr($data, '.', true)).'<span class="alert-success small">'.$data2.'</span>';
			}
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