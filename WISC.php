<?php
class WISC {
	private $user_name = '';
	private $password = '';
	private $curl;

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL, 'https://madison.sis.wisc.edu/psc/sissso_4/EMPLOYEE/SA/c/SCC_TASKS_FL.SCC_TASKS_TODOS_FL.GBL');
		// curl_setopt($this->curl, CURLOPT_POST, 1);
		$u = $this->user_name;
		$p = $this->password;
		curl_setopt($this->curl, CURLOPT_HEADER, 1);
		curl_setopt($this->curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.116 Safari/537.36');
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		$prev = file_get_contents('/opt/admit/WISC');
		$prev = json_decode($prev, true);
		if ($prev['updated_time'] + 300 > time()){
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, '/opt/cookies/WISC');
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, '/opt/cookies/WISC');
			return;
		} else {
			file_put_contents('/opt/cookies/WISC', '');
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, '/opt/cookies/WISC');
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, '/opt/cookies/WISC');
		}
		$data = curl_exec($this->curl);

		$RelayState = strstr($data, '<input type="hidden" name="RelayState" value="');
		$RelayState = str_replace('&#58;', ':', strstr(substr($RelayState, 46), '"', true));
		$SAMLResponse = strstr($data, '<input type="hidden" name="SAMLRequest" value="');
		$SAMLResponse = str_replace("\n", '', strstr(substr($SAMLResponse, 47), '"', true));

		curl_setopt($this->curl, CURLOPT_URL, 'https://login.wisc.edu/idp/profile/SAML2/POST/SSO');
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'RelayState='.urlencode($RelayState).'&SAMLRequest='.urlencode($SAMLResponse));
		$data = curl_exec($this->curl);

		$url = strstr($data, 'https://login.wisc.edu/idp/profile/SAML2/POST/SSO?execution=');
		$url = substr($url, 0, 64);

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'shib_idp_ls_exception.shib_idp_session_ss=&shib_idp_ls_success.shib_idp_session_ss=true&shib_idp_ls_value.shib_idp_session_ss=&shib_idp_ls_exception.shib_idp_persistent_ss=&shib_idp_ls_success.shib_idp_persistent_ss=true&shib_idp_ls_value.shib_idp_persistent_ss=&shib_idp_ls_supported=true&_eventId_proceed=');
		$data = curl_exec($this->curl);

		$url = strstr($data, 'https://login.wisc.edu/idp/profile/SAML2/POST/SSO?execution=');
		$url = substr($url, 0, 64);
		var_dump($url);
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'j_username='.
			$u.'&j_password='.urlencode($p).'&_eventId_proceed=');
		$data = curl_exec($this->curl);

		curl_setopt($this->curl, CURLOPT_URL, 'https://login.wisc.edu/idp/profile/SAML2/Redirect/SSO?execution=e1s3');
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'shib_idp_ls_exception.shib_idp_session_ss=&shib_idp_ls_success.shib_idp_session_ss=true&_eventId_proceed=');
		$data = curl_exec($this->curl);
		$RelayState = strstr($data, '<input type="hidden" name="RelayState" value="');
		$RelayState = str_replace('&#x3a;', ':', strstr(substr($RelayState, 46), '"', true));
		$SAMLResponse = strstr($data, '<input type="hidden" name="SAMLResponse" value="');
		$SAMLResponse = str_replace("\n", '', strstr(substr($SAMLResponse, 48), '"', true));

		curl_setopt($this->curl, CURLOPT_URL, 'https://madison.sis.wisc.edu/sissso/Shibboleth.sso/SAML2/POST');
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'RelayState='.urlencode($RelayState).'&SAMLResponse='.urlencode($SAMLResponse));
		$data = curl_exec($this->curl);

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, null);
	}

	public function get_status(){
		curl_setopt($this->curl, CURLOPT_URL,'https://madison.sis.wisc.edu/psc/sissso_4/EMPLOYEE/SA/c/SCC_TASKS_FL.SCC_TASKS_TODOS_FL.GBL');
		$data = curl_exec($this->curl);
		var_dump($data);
		$raw_data = $data;
		$data = strstr($data, '<span class=\'ps-text\' id=\'PANEL_TITLElbl\'>To Do List</span>');

		$i = 0;
		$append = '';
		$data = strstr($data, 'SCC_DRV_TASK_FL_SCC_TODO_SEL_PB$'.$i.'\');"');
		while($data != ''){
			$append .= strstr(substr(strstr($data, '>'), 1), '</a>', true).'. ';
			$i++;
			$data = strstr($data, 'SCC_DRV_TASK_FL_SCC_TODO_SEL_PB$'.$i.'\');"');
		}
		$data = trim(substr($append, 0, -2));

		curl_setopt($this->curl, CURLOPT_URL,'https://madison.sis.wisc.edu/psc/sissso/EMPLOYEE/SA/c/SAD_APPLICANT_FL.SAD_APPL_SELECT_FL.GBL');
		$data2 = curl_exec($this->curl);
		var_dump($data2);
		$raw_data2 = $data2;
		$data2 = strstr($data2, 'id=\'DERIVED_SAD_FL_SAD_ACAD_STATUS\'');
		$data2 = strstr(substr(strstr($data2, '>'), 1), '</span>', true);

		curl_close($this->curl);

		$r = strtolower(strip_tags($raw_data.$raw_data2));
		$ad = strstr($r, 'congrat') || strstr($r, 'accept') || strstr($r, 'admit');
		$wl = strstr($r, 'waiting list') || strstr($r, 'wait list');
		$rej = strstr($r, 'reject') || strstr($r, 'denied') || strstr($r, 'sorry');

		if ($ad || $wl || $rej || trim($data2) != ''){
			$return = ['sha' => md5($data).md5($data2), 'data' => trim(strip_tags($data2)),
				'cookie' => $this->cookie];
			if($ad) {
				$return['accept'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if (!$data) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;
			
			if($data){
				$data = '<span class="alert-danger">'.trim($data).'</span>';
			}
			$return['html'] = trim($data2.' '.$data);
			var_dump($return);
			return $return;
		} else if (strstr(strtolower($raw_data), 'congrat')) {
			return ['sha' => md5($data).md5($data2), 'data' => $data,
				'cookie' => $this->cookie, 'admitted' => true];
		}
		return NULL;
	}
}
