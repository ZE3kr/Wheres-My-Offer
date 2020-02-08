<?php
class OSU {
	private $cookie = [];
	public function setup($cookie){
		$this->cookie = $cookie;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/OSU');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
		}
	}

	public function get_status(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://sis.erp.ohio-state.edu/psc/scsosucs/EMPLOYEE/BUCK/c/SA_LEARNER_SERVICES.SS_ADM_APP_STATUS.GBL');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$data = curl_exec($curl);
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $data, $matches);
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$this->cookie = array_merge($this->cookie, $cookie);
		}
		$raw_data = $data;
		$data = strstr($data, 'id=\'DESCRSHORT$0\'');
		$data = strstr($data, '>');
		$data = strstr(substr($data, 1), '</a>', true);

		curl_setopt($curl, CURLOPT_URL,'https://sis.erp.ohio-state.edu/psc/scsosucs/EMPLOYEE/BUCK/c/CC_PORTFOLIO.SS_CC_TODOS.GBL');
		$data2 = curl_exec($curl);
		$data2 = strstr($data2, 'id=\'win0divSRVCIND_TODOSGP$0\'');
		$data2 = strstr($data2, '>');
		$data2 = strstr(substr($data2, 1), '</table>', true);
		curl_close($curl);

		if(strstr(strtolower($raw_data), 'congrat') || strstr(strtolower($raw_data), 'admitted')) {
			return ['sha' => md5($data).md5($data2), 'data' => '恭喜！确认录取。Congrats!', 'admitted' => true,
				'cookie' => $this->cookie];
		}
		if ($data != ''){
			return ['sha' => md5($data).md5($data2), 'data' => $data,
				'cookie' => $this->cookie];
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