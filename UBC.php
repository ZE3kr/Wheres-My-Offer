<?php
class UBC {
	private $cookie = [];
	public function setup($cookie){
		$this->cookie = $cookie;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/UBC');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
		}
	}

	public function get_status(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://ssc.adm.ubc.ca/sscportal/servlets/SRVApplicantStatus');
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
		$ori_data = strstr($data, '<td class="pageTitle">Application Status</td>');
		$data = strstr($data, '<td class="displayBoxFieldAlignTop">Status:</td>');
		$data = strstr($data, '<p>');
		$data = strstr(substr($data, 3), '</p>', true);
		curl_close($curl);

		if(strstr(strtolower($raw_data), 'congrat')) {
			return ['sha' => md5($ori_data), 'data' => '恭喜！确认录取。Congrats!', 'admitted' => true,
				'cookie' => $this->cookie];
		}
		if ($data != ''){
			$return = ['sha' => md5($ori_data), 'data' => $data,
				'cookie' => $this->cookie];
			if (strstr(strtolower($raw_data), 'waiting list')){
				$return['waiting'] = true;
			} else if(strstr(strtolower($raw_data), 'reject')) {
				$return['reject'] = true;
			} else if(!strstr($raw_data, 'The following information is required before an evaluation can be completed')){
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