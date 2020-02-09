<?php
class CMU {
	private $cookie = [];
	public function setup($cookie){
		$this->cookie = $cookie;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/CMU');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
		}
	}

	public function get_status(){
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
		$raw_data = $data;
		$data = strstr($data, '<h3 class="page-title display-inline">Welcome, ');
		$ori_data = $data;
		$data = strstr(substr($data, 38), '</h3>', true);
		curl_close($curl);

		if ($data != ''){
			$return = ['sha' => md5($ori_data), 'data' => $data,
				'cookie' => $this->cookie];
			if(strstr(strtolower($raw_data), 'congrat')) {
				$return['admitted'] = true;
			} else if (strstr(strtolower($raw_data), 'waiting list') || strstr(strtolower($raw_data), 'wait list')){
				$return['waiting'] = true;
			} else if(strstr(strtolower($raw_data), 'reject') || strstr(strtolower($raw_data), 'sorry')) {
				$return['reject'] = true;
			} else if (!strstr(strtolower($raw_data), 'incomplete')) {
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