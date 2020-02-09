<?php
require_once "UIUC.php";
require_once "UMich.php";
require_once "USC.php";
require_once "UNC.php";
require_once "UBC.php";
require_once "OSU.php";
require_once "WISC.php";
require_once "CMU.php";

$uiuc = new UIUC();
$uiuc->setup('username', 'password');
$uiuc->login();
echo "\n\n\nUIUC: \n";
check_update( $uiuc->get_status(), 'UIUC');

$umich = new UMich();
$umich->setup('username', 'password');
$umich->login();
echo "\n\n\nUMich: \n";
check_update( $umich->get_status(), 'UMich');

$USC = new USC();
$USC->setup('username', 'password');
$USC->login();
echo "\n\n\nUSC: \n";
check_update( $USC->get_status(), 'USC' );

$UNC = new UNC();
$UNC->setup('username', 'password');
$UNC->login();
echo "\n\n\nUNC: \n";
check_update( $UNC->get_status(), 'UNC' );

$ubc = new UBC();
$ubc->setup([
	'JSESSIONID' => 'XXXXXXXX-XXXXXXXXXXXXXXX'
]);
$ubc->login();
echo "\n\n\nUBC: \n";
check_update($ubc->get_status(), 'UBC');

$osu = new OSU();
$osu->setup([
	'NSC_NX-DTPTV-TJT-TTM-WT' => 'ffffffffffffffffffffffffffffffffffffffffffff',
	'_shibsession_ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff'
			=> '_ffffffffffffffffffffffffffffffff',
	'PS_TOKEN'
			=> 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff',
	'hcwebprd02-10000-PORTAL-PSJSESSIONID' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
]);
$osu->login();
echo "\n\n\nOSU: \n";
check_update( $osu->get_status(), 'OSU' );

$cmu = new CMU();
$cmu->setup([
	'JSESSIONID' => 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF-xx.xxxx',
	'_shibsession_ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff'
			=> '_ffffffffffffffffffffffffffffffff'
]);
$cmu->login();
echo "\n\n\nCMU: \n";
check_update( $cmu->get_status(), 'CMU');

$wisc = new WISC();
$wisc->setup([
	'_shibsession_ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff'
			=> '_ffffffffffffffffffffffffffffffff',
	'badlands.doit.wisc.edu-0000-PORTAL-PSJSESSIONID'
			=> 'xxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxx!-00000000',
	'L4-LB.SISPRD-COOKIE' => 'ffffffffffffffffffffffffffffffffffffffffffff'
]);
$wisc->login();
echo "\n\n\nWISC: \n";
check_update($wisc->get_status(), 'WISC');

function check_update($result, $slug) {
	$prev = file_get_contents('/opt/admit/'.$slug);
	$prev = json_decode($prev, true);
	if(is_null($result) || !$result || !isset($result['sha'])){
		unset($prev['cookie']);
		file_put_contents('/opt/admit/'.$slug, json_encode($prev));
		return;
	}
	$result['data'] = trim($result['data']);
	$trim = $result;
	unset($trim['cookie']);
	var_dump( $trim );
	if(!$prev){
		$result['updated_time'] = $result['time'] = time();
		file_put_contents('/opt/admit/'.$slug, json_encode($result));
		return;
	}
	if(isset($prev['admitted']) || isset($prev['reject'])) {
		$result['updated_time'] = time();
		file_put_contents('/opt/admit/'.$slug, json_encode($result));
		return;
	}

	if($result['sha'] != $prev['sha']) {
		if(isset($result['admitted'])) {
			$append_data = '录取！'.$result['data'];
		}
		if(isset($result['reject'])) {
			$append_data = '拒绝！'.$result['data'];
		}
		$data = urlencode($append_data);
		file_get_contents("https://maker.ifttt.com/trigger/admit/with/key/YOUR_KEY?value1={$slug}&value2={$result['data']}");
		if (isset($result['admitted']) || isset($result['reject'])){
			// Special condition when admitted/rejected by a university.
			// e.g. Trigger a phone call, or send a tweet.
		}
		if(isset($prev['email'])){
			$result['email'] = $prev['email'];
		}
		$result['time'] = $result['updated_time'] = time();
		file_put_contents('/opt/admit/'.$slug, json_encode($result));
	} else {
		$prev['updated_time'] = time();
		if ( isset($result['cookie']) ){
			$prev['cookie'] = $result['cookie'];
		}
		file_put_contents('/opt/admit/'.$slug, json_encode($prev));
	}
}
