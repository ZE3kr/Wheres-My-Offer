<?php
class Purdue {
	private $user_name = '';
	private $password = '';
	private $curl;

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		return;
	}

	public function get_status(){
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL,'https://apply.purdue.edu/account/login?r=https%3a%2f%2fapply.purdue.edu%2fapply%2fstatus');
		//curl_setopt($this->curl, CURLOPT_POST, 1);
		$u = urlencode($this->user_name);
		$p = urlencode($this->password);
		curl_setopt($this->curl, CURLOPT_HEADER, 1);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		$request = "email=${u}&password=${p}";
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, '/opt/cookies/Purdue');
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, '/opt/cookies/Purdue');
		$data = curl_exec($this->curl);
		$raw_data = strip_tags($data);
		$data = substr(strstr($data, '<body><h3>'), 10);
		$ori_data = $data;
		$data1 = strip_tags(strstr($data, '</h3>', true));

		$data2 = $ori_data;
		$ori_data = strstr(strip_tags($ori_data), '<form action="/apply/statusHandler"', true);
		$received = '';
		$waiting = '';
		$data2 = strstr($data2, '<th colspan="2">Status</th>');
		for ($i = 0; $i < 2; $i++){
			$data2 = substr(strstr($data2, '<td>'), 4);
		}
		while($data2 != ''){
			$chk = strtolower(strstr($data2, '</td>', true));

			$data2 = substr(strstr($data2, '<td'), 3);
			$data2 = substr(strstr($data2, '>'), 1);
			$append = strip_tags(strstr($data2, '</td>', true));
			$append2 = strstr($append, ' for ', true);
			if($append2){
				$append = $append2;
			}

			if(strstr($chk, 'received') || strstr($chk, 'completed')){
				$received .= $append.'. ';
			} else {
				$waiting .= $append.'. ';
			}
			for ($i = 0; $i < 3; $i++){
				$data2 = substr(strstr($data2, '<td>'), 4);
			}
		}
		if($waiting){
			$waiting = substr($waiting, 0, -2);
		}
		if($received){
			$received = substr($received, 0, -2);
		}


		curl_setopt($this->curl, CURLOPT_URL,'https://www.admissions.purdue.edu/apply/closedprograms.php');
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, null);
		$closed = curl_exec($this->curl);

		$closed = strstr($closed, 'Transfer closed programs by term');
		$closed = substr(strstr($closed, '<tr>'), 4);
		$list = '';
		while ($closed != ''){
			$closed = substr(strstr($closed, '<th>'), 4);
			$name = strstr($closed, '</th>', true);
			for ($i = 0; $i < 3; $i++){
				$closed = substr(strstr($closed, '<td>'), 4);
			}
			$closed_chk = strstr($closed, '</td>', true);
			$closed_chk = substr(strstr($closed_chk, '<span class="sr-only">'), 22);
			$closed_chk = strstr($closed_chk, '</span>', true);
			if($closed_chk == 'closed' && strstr($name, 'Computer')) {
				$list .= trim($name) . '. ';
			}
			$closed = substr(strstr($closed, '<tr>'), 4);
		}

		$ad = strstr($raw_data, 'congrat');
		$wl = strstr($raw_data, 'waiting list') || strstr($raw_data, 'wait list');
		$rej = strstr($raw_data, 'reject') || strstr($raw_data, 'denied') || strstr($raw_data, 'sorry');

		if( $list ){
			$list = substr($list, 0, -2);
		}

		if ( $ad || $wl || $rej ||  trim($data) != '' ) {
			$return = ['sha' => md5($ori_data.$list), 'data' => $data1];

			if($ad) {
				$return['admitted'] = true;
			} else if ($wl){
				$return['waiting'] = true;
			} else if($rej) {
				$return['reject'] = true;
			} else if (!$waiting) {
				$return['complete'] = true;
			}
			$return['submitted'] = true;

			if(trim($waiting)){
				$waiting = ' <span class="alert-danger">'.trim($waiting).'</span>';
			}
			if($received){
				$received = ' <span class="alert-success small">'.trim($received).'</span>';
			}
			if(trim($list)){
				$list = ' <span class="alert-warning small">'.trim($list).'</span>';
			}
			$return['html'] = trim($data1.$waiting.$received.$list);

			return $return;
		}
		return NULL;
	}
}
