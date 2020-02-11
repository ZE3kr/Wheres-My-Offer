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
			$received_list[$append] = true;
			$received = strstr($received, '<li>');
		}
		foreach($received_list as $key => $_){
			$received .= $key.'. ';
		}
		if($received){
			substr($received, 0, -2);
		}
		
		$data = strstr(substr($data, 3), '</p>', true);

		curl_close($curl);

		if (trim($data) != ''){
			$return = ['sha' => md5($ori_data), 'data' => trim($data).' '.trim($missing),
				'cookie' => $this->cookie];
			if(strstr(strtolower($raw_data), 'congrat')) {
				$return['admitted'] = true;
			} else if (strstr(strtolower($raw_data), 'waiting list') || strstr(strtolower($raw_data), 'wait list')){
				$return['waiting'] = true;
			} else if(strstr(strtolower($raw_data), 'reject') || strstr(strtolower($raw_data), 'sorry')) {
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