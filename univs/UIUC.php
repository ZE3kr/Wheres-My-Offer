<?php
class UIUC {
	private $cookie = [];
	private $user_name = '';
	private $password = '';

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/UIUC');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
			return;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://myillini.illinois.edu/IdentityManagement/Login');
		// curl_setopt($curl, CURLOPT_POST, 1);
		$u = urlencode($this->user_name);
		$p = urlencode($this->password);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl);
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $data, $matches);
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$this->cookie = array_merge($this->cookie, $cookie);
		}
		$data = strstr($data, '<input name="__RequestVerificationToken" type="hidden" value="');
		$data = strstr(substr($data, 62), '"', true);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		$request = "Username=${u}&Password=${p}&__RequestVerificationToken=${data}";
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		$result = curl_exec($curl);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$this->cookie = array_merge($this->cookie, $cookie);
		}
	}

	public function get_status(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://myillini.illinois.edu/Apply/Application/Status');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$data = curl_exec($curl);
		$raw_data = strtolower(strip_tags($data));
		$data = strstr($data, '<strong>Status: </strong>');
		$ori_data = strip_tags($data);
		$data2 = strstr($data, '<h2>Received Items</h2>');
		$data2 = strstr($data2, '</ul>', true);
		$data3 = strstr($data, '<h2>Missing Items</h2>');
		$data3 = strstr($data3, '</ul>', true);
		$data = substr(strstr($data, '</p>', true), 25);
		$data2 = strstr($data2, '<li>');
		$received = '';
		while($data2 != ''){
			$data2 = substr($data2, 4);
			$append = strstr($data2, '<em>', true);
			$append2 = strstr($append, '<br', true);
			if($append2){
				$append = $append2;
			}
			$received .= trim($append).'; ';
			$data2 = strstr($data2, '<li>');
		}

		$received_list = [];
		curl_setopt($curl, CURLOPT_URL,'https://myillini.illinois.edu/Apply/Application/GetChangeFormStatuses');
		$data_change = curl_exec($curl);
		$ori_data .= strip_tags($data_change);
		$data_change = strstr($data_change, '<li>');
		while($data_change != ''){
			$data_change = substr($data_change, 4);
			$append = strstr($data_change, '</li>', true);
			$append2 = strstr($append, '<br', true);
			if($append2){
				$append = $append2;
			}
			$append2 = strstr($append, ' - ', true);
			if($append2){
				$append = $append2;
			}
			if( !isset($received_list[trim($append)]) ){
				$received_list[trim($append)] = 0;
			}
			$received_list[trim($append)]++;
			$data_change = strstr($data_change, '<li>');
		}
		foreach ($received_list as $item => $i) {
			if( $i == 1 ){
				$received .= $item.'; ';
			} else {
				$received .= $item.' x'.$i.'; ';
			}
		}

		if($received){
			$received = substr($received, 0, -2);
		}

		$data3 = strstr($data3, '<li>');
		$missing = '';
		while($data3 != ''){
			$data3 = substr($data3, 4);
			$append = strstr($data3, '<em>', true);
			$append2 = strstr($append, '<br', true);
			if($append2){
				$append = $append2;
			}
			$missing .= trim($append).'; ';
			$data3 = strstr($data3, '<li>');
		}
		if($missing){
			$missing = substr($missing, 0, -2);
		}
		$ad = strstr($raw_data, 'congrat') || strstr($raw_data, 'accept') || strstr($raw_data, 'admit');
		$wl = strstr($raw_data, 'waiting list') || strstr($raw_data, 'wait list');
		$rej = strstr($raw_data, 'reject') || strstr($raw_data, 'denied')
			|| strstr($raw_data, 'sorry') || strstr($raw_data, 'regret');

		if ($ad) {
			include 'vendor/autoload.php';
			$parser = new \Smalot\PdfParser\Parser();

			curl_setopt($curl, CURLOPT_URL,'https://myillini.illinois.edu/Apply/Checklist/NOA?letterDesc=Transfer%20Evaluation');
			$pdf_data = curl_exec($curl);
			$pdf    = $parser->parseContent($pdf_data);

			$text = $pdf->getText();
			$earned = substr(strstr($text, 'EARNED: '), 8);
			$earned = strstr($earned, ' HOURS', true);
			$ori_data = $raw_data.$text;

			$data = 'Credit: '.trim($earned).' Hours';
		}

		curl_close($curl);

		if ($ad || $wl || $rej || trim($data) != ''){
			$return = ['sha' => md5($ori_data), 'data' => trim(strip_tags($data)),
				'cookie' => $this->cookie];
			if($ad) {
				$return['admitted'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if (!$missing) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;

			if($missing){
				$missing = ' <span class="alert-danger">'.trim($missing).'</span>';
			}
			if($received){
				$received = ' <span class="alert-success small">'.trim($received).'</span>';
			}
			$return['html'] = trim($data.$missing.$received);

			return $return;
		}
		return NULL;
	}

	private function cookie_str(){
		foreach($this->cookie as $k => $v){ // this will fail if there are any more -public- variables declared in the class.
			$k = str_replace('_', '.', $k);
			$c[] = "$k=$v";
		}
		return implode('; ', $c);
	}
}