<?php
class OSU {
	private $cookie = [];
	private $user_name = '';
	private $password = '';

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/OSU');
		$prev = json_decode($prev, true);

		$curl = curl_init();
		$ttl = 300;
		//if(isset($prev['admitted'])){
		//	$ttl = 900;
		//	curl_setopt($curl, CURLOPT_URL, 'https://buckeyelink.osu.edu/launch-task/all/transfer-credit-report?taskReferrerCenterId=1120');
		//} else {
			curl_setopt($curl, CURLOPT_URL, 'https://sis.erp.ohio-state.edu/psc/scsosucs/EMPLOYEE/BUCK/c/CC_PORTFOLIO.SS_CC_TODOS.GBL');
		//}
		// curl_setopt($curl, CURLOPT_POST, 1);
		$u = $this->user_name;
		$p = $this->password;
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.116 Safari/537.36');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		if ($prev['updated_time'] + $ttl > time()){
			curl_setopt($curl, CURLOPT_COOKIEJAR, '/opt/cookies/OSU');
			curl_setopt($curl, CURLOPT_COOKIEFILE, '/opt/cookies/OSU');
			return;
		} else {
			file_put_contents('/opt/cookies/OSU', '');
			curl_setopt($curl, CURLOPT_COOKIEJAR, '/opt/cookies/OSU');
			curl_setopt($curl, CURLOPT_COOKIEFILE, '/opt/cookies/OSU');
		}
		$data = curl_exec($curl);
		$url = strstr($data, 'https://webauth.service.ohio-state.edu/idp/profile/SAML2/Redirect/SSO?execution=');
		$url = substr($url, 0, 84);

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'shib_idp_ls_exception.shib_idp_session_ss=&shib_idp_ls_success.shib_idp_session_ss=true&_eventId_proceed=');

		$data = curl_exec($curl);

		$url = strstr($data, 'https://webauth.service.ohio-state.edu/idp/profile/SAML2/Redirect/SSO?execution=');
		$url = substr($url, 0, 84);

		curl_setopt($curl, CURLOPT_URL,$url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'j_username='.
			urlencode($u).'&j_password='.urlencode($p).'&donotcache=0&_eventId_proceed=Logging+in%2C+please+wait...');
		$data = curl_exec($curl);

		$RelayState = strstr($data, '<input type="hidden" name="RelayState" value="');
		$RelayState = str_replace('&#x3a;', ':', strstr(substr($RelayState, 46), '"', true));
		$SAMLResponse = strstr($data, '<input type="hidden" name="SAMLResponse" value="');
		$SAMLResponse = strstr(substr($SAMLResponse, 48), '"', true);

		curl_setopt($curl, CURLOPT_URL, 'https://sis.erp.ohio-state.edu/Shibboleth.sso/SAML2/POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'RelayState='.urlencode($RelayState).'&SAMLResponse='.urlencode($SAMLResponse));
		$data = curl_exec($curl);
	}

	public function get_status(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://sis.erp.ohio-state.edu/psc/scsosucs/EMPLOYEE/BUCK/c/SA_LEARNER_SERVICES.SS_ADM_APP_STATUS.GBL?Page=OAD_SS_APP_STATUS&Action=U&ExactKeys=Y&DERIVED_SSTSKEY=DERIVED_SSTSKEY');
		curl_setopt($curl, CURLOPT_COOKIEJAR, '/opt/cookies/OSU');
		curl_setopt($curl, CURLOPT_COOKIEFILE, '/opt/cookies/OSU');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$data = curl_exec($curl);
		$raw_data = strtolower(strip_tags($data));
		$data = strstr($data, 'id=\'DESCRSHORT$0\'');
		$data = strstr($data, '>');
		$data = strstr(substr($data, 1), '<', true);

		$ad = strstr($raw_data, 'congrat') || strstr($raw_data, 'accept') || strstr($raw_data, 'admit')
			|| (trim(strip_tags($data)) == 'Acceptance');
		$wl = strstr($raw_data, 'waiting list') || strstr($raw_data, 'wait list');
		$rej = strstr($raw_data, 'reject') || strstr($raw_data, 'denied')
			|| strstr($raw_data, 'sorry') || strstr($raw_data, 'regret');

		curl_setopt($curl, CURLOPT_URL,'https://sis.erp.ohio-state.edu/psc/scsosucs/EMPLOYEE/BUCK/c/CC_PORTFOLIO.SS_CC_TODOS.GBL');
		$data2 = curl_exec($curl);
		$data2 = strstr($data2, 'id=\'win0divSRVCIND_TODOSGP$0\'');
		$data2 = strstr($data2, '>');
		$data2 = strstr(substr($data2, 1), '</table>', true);
		$ori_data2 = strip_tags($data2);

		$i = 0;
		$append = '';
		$data2 = strstr($data2, 'id=\'SRVC_LINK$'.$i.'\'');
		while($data2 != ''){
			$append .= strstr(substr(strstr($data2, '>'), 1), '</a>', true).'; ';
			$i++;
			$data2 = strstr($data2, 'id=\'SRVC_LINK$'.$i.'\'');
		}
		$data2 = trim(substr($append, 0, -2));

		if($ad){
			include 'vendor/autoload.php';
			$parser = new \Smalot\PdfParser\Parser();

			curl_setopt($curl, CURLOPT_URL,'https://degreeaudit.osu.edu/selfservice/audit/readpdf.pdf');

			$pdf_data = curl_exec($curl);
			$pdf    = $parser->parseContent($pdf_data);

			$text = $pdf->getText();
			var_dump($text);
			$earned = substr(strstr($text, 'EARNED:'), 8);
			$earned = strstr($earned, 'HOURS', true);
			$ori_data2 .= $text;

			$data = 'Credit: '.trim($earned).' Hours';
		}

		curl_close($curl);

		if ($ad || $wl || $rej || trim($data) != ''){
			$return = ['sha' => md5($data).md5($ori_data2), 'data' => trim(strip_tags($data))];
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
		}
		return NULL;
	}
}