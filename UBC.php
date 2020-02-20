<?php
class UBC {
	private $cookie = [];
	public function setup($cookie){
		$this->cookie = $cookie;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/UBC');
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
		$raw_data = strtolower(strip_tags($data));
		$ori_data = strip_tags(strstr($data, '<td class="pageTitle">Application Status</td>'));
		$data = strstr($data, '<td class="displayBoxFieldAlignTop">Status:</td>');
		$data = strstr($data, '<p>');
		
		$missing = strstr($data, 'The following information is required before an evaluation can be completed');
		$missing = strstr($missing, '<ul>');
		$missing = strstr(substr($missing, 4), '</ul>', true);
		
		$missing = strstr($missing, '<li>');
		$missing_list = [];
		while($missing != ''){
			$missing = substr($missing, 4);
			$append = trim(strstr($missing, '</li>', true));
			$missing_list[$append] = true;
			$missing = strstr($missing, '<li>');
		}
		foreach($missing_list as $key => $_){
			$missing .= $key.'. ';
		}
		if($missing){
			substr($missing, 0, -2);
		}
		
		$received = strstr($data, 'The following materials have been received by the Admissions Office:');
		$received = strstr($received, '<ul>');
		$received = strstr(substr($received, 4), '</ul>', true);
		
		$received = strstr($received, '<li>');
		$received_list = [];
		while($received != ''){
			$received = substr($received, 4);
			$append = trim(strstr($received, '</li>', true));
			
			$append2 = strstr($append, ' from ', true);
			if($append2){
				$append = $append2;
			}
			$append2 = strstr($append, ' - ', true);
			if($append2){
				$append = $append2;
			}
			
			$received_list[$append] = ($received_list[$append] ?? 0) + 1;
			$received = strstr($received, '<li>');
		}
		foreach($received_list as $key => $i){
			if($i > 1){
				$received .= $key.' x'.$i.'. ';
			} else {
				$received .= $key.'. ';
			}
		}
		if($received){
			substr($received, 0, -2);
		}
		
		$data = strstr(substr($data, 3), '</p>', true);
		$data = str_replace('The following information is required before an evaluation can be completed:', 'Incomplete', $data);

		curl_close($curl);

		$ad = strstr($raw_data, 'congrat') || strstr($raw_data, 'accept') || strstr($raw_data, 'admit');
		$wl = strstr($raw_data, 'waiting list') || strstr($raw_data, 'wait list');
		$rej = strstr($raw_data, 'reject') || strstr($raw_data, 'denied') || strstr($raw_data, 'sorry');

		if (trim($ori_data) != ''){
			$return = ['sha' => md5($ori_data), 'data' => trim(strip_tags($data)),
				'cookie' => $this->cookie];
			if($ad) {
				$return['admitted'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if(!$missing){
				$return['complete'] = true;
			}
			$return['submitted'] = true;

			if($missing){
				$missing = ' <span class="alert-danger">'.trim($missing).'</span>';
			}
			if($received){
				$received = ' <span class="alert-success small">'.trim($received).'</span>';
			}
			$return['html'] = trim($data).$missing.$received;

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