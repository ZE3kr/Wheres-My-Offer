<?php
class MCM {
	private $user_name = '';
	private $password = '';

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		return;
	}

	public function get_status(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://www.comap.com/undergraduate/contests/mcm/login.php');
		curl_setopt($curl, CURLOPT_POST, 1);
		$u = urlencode($this->user_name);
		$p = urlencode($this->password);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$request = "login=1&email=${u}&password=${p}&login=Login";
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		$data = curl_exec($curl);
		$raw_data = $data;
		$data = strstr($raw_data, '<b>Electronic Solution Received:</b>');
		$ori_data = strstr($data, '</table>', true);
		$data = substr(strstr($data, '</td>', true), 36);
		$data = trim(strip_tags($data));

		$data2 = strstr($raw_data, '<b>Final Designation:</b>');
		$data2 = substr(strstr($data2, '</td>', true), 25);
		$data2 = trim(strip_tags($data2));

		if($data2 == '(unavailable)') {
			unset($data2);
		}

		curl_close($curl);

		if (trim($data) != ''){
			return ['sha' => md5($ori_data), 'data' => trim(strip_tags(isset($data2) ? $data2 : $data)),
				'html' => trim('<ul><li><strong>Submission</strong>: '.$data.'</li><li><strong>Designation</strong>: '.($data2 ?? 'N/A').'</li></ul>'),
				'other' => true, 'submitted' => true];
		}
		return NULL;
	}
}