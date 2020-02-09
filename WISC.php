<?php
class WISC {
	private $cookie = [];
	public function setup($cookie){
		$this->cookie = $cookie;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/WISC');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
		}
	}

	public function get_status(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://madison.sis.wisc.edu/psc/sissso_4/EMPLOYEE/SA/c/SCC_TASKS_FL.SCC_TASKS_TODOS_FL.GBL');
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
		$data = strstr($data, '<span class=\'ps-text\' id=\'PANEL_TITLElbl\'>To Do List</span>');
		$data = strstr($data, 'SCC_DRV_TASK_FL_SCC_TODO_SEL_PB$0\');"');
		$data = strstr(substr(strstr($data, '>'), 1), '</a>', true);

		curl_setopt($curl, CURLOPT_URL,'https://madison.sis.wisc.edu/psc/sissso/EMPLOYEE/SA/c/SAD_APPLICANT_FL.SAD_APPLICANT_FL.GBL?Page=SAD_APPL_STATUS_FL&Action=L');
		$data2 = curl_exec($curl);
		$raw_data2 = $data2;
		$data2 = strstr($data2, 'id=\'DERIVED_SAD_FL_SAD_ACAD_STATUS\'');
		$data2 = strstr(substr(strstr($data2, '>'), 1), '</span>', true);

		curl_close($curl);

		if ($data2 != ''){
			$return = ['sha' => md5($data).md5($data2), 'data' => $data2.'. '.$data,
				'cookie' => $this->cookie];
			if(strstr(strtolower($raw_data.$raw_data2), 'congrat')) {
				$return['accept'] = true;
			} else if (strstr(strtolower($raw_data.$raw_data2), 'waiting list') || strstr(strtolower($raw_data), 'wait list')){
				$return['waiting'] = true;
			} else if(strstr(strtolower($raw_data.$raw_data2), 'reject') || strstr(strtolower($raw_data), 'sorry')) {
				$return['reject'] = true;
			} else if (!strstr(strtolower($raw_data), 'incomplete')
				&& !strstr(strtolower($raw_data), 'missing')) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;
			return $return;
		} else if (strstr(strtolower($raw_data), 'congrat')) {
			return ['sha' => md5($data).md5($data2), 'data' => $data,
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
