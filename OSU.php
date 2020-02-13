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
		curl_setopt($curl, CURLOPT_URL,'https://sis.erp.ohio-state.edu/psc/scsosucs/EMPLOYEE/BUCK/c/OAD_CUST_MENU.OAD_APPSTATUS_INFO.GBL?Page=OAD_APPLSTS_INFO&Action=U&ACAD_CAREER=UGRD&ADM_APPL_NBR=02132636&APPL_PROG_NBR=0&EFFDT=2020-02-07&EFFSEQ=1&EMPLID=500525907&STDNT_CAR_NBR=0');
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
		$data = strstr($data, '<!-- Begin HTML Area Name Undisclosed -->');
		$data = strstr($data, '<br/>');
		$data = strstr(substr($data, 5), '</div>', true);
		$data = str_replace('The listed materials with a status of incomplete are those that are still needed to complete this application.  Please note: items received within the last 10 days may not be reflected on this page.', 'Incomplete', $data);

		curl_setopt($curl, CURLOPT_URL,'https://sis.erp.ohio-state.edu/psc/scsosucs/EMPLOYEE/BUCK/c/CC_PORTFOLIO.SS_CC_TODOS.GBL');
		$data2 = curl_exec($curl);
		$data2 = strstr($data2, 'id=\'win0divSRVCIND_TODOSGP$0\'');
		$data2 = strstr($data2, '>');
		$data2 = strstr(substr($data2, 1), '</table>', true);
		$ori_data2 = $data2;
		
		$i = 0;
		$append = '';
		$data2 = strstr($data2, 'id=\'SRVC_LINK$'.$i.'\'');
		while($data2 != ''){
			$append .= strstr(substr(strstr($data2, '>'), 1), '</a>', true).'. ';
			$i++;
			$data2 = strstr($data2, 'id=\'SRVC_LINK$'.$i.'\'');
		}
		$data2 = trim(substr($append, 0, -2));

		curl_close($curl);

		$ad = strstr(strtolower($raw_data), 'congrat');
		$wl = strstr(strtolower($raw_data), 'waiting list') || strstr(strtolower($raw_data), 'wait list') || strstr(strtolower($raw_data), 'defer');
		$rej = strstr(strtolower($raw_data), 'reject') || strstr(strtolower($raw_data), 'sorry');

		if ($ad || $wl || $rej || trim($data) != ''){
			$return = ['sha' => md5($data).md5($ori_data2), 'data' => trim($data),
				'cookie' => $this->cookie];
			if($ad) {
				$return['admitted'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if (!$data2) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;
			
			if($data2){
				$data2 = ' <span class="alert-danger">'.trim($data2).'</span>';
			}
			$return['html'] = trim($data).$data2;
			
			return $return;
		} else if (strstr(strtolower($raw_data), 'congrat')) {
			return ['sha' => md5($data).md5($ori_data2), 'data' => $data,
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