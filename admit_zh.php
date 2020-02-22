<?php
header('Catch-Control: no-cache, must-revalidate, max-age=0, s-maxage=0');

if (!function_exists('array_key_first')) {
	function array_key_first(array $arr) {
		foreach($arr as $key => $unused) {
			return $key;
		}
		return NULL;
	}
}

$directory = '/opt/admit';
$scanned_directory = array_diff(scandir($directory), array('..', '.'));
$admitted_status = [];
foreach ($scanned_directory as $slug) {
	$content = json_decode(file_get_contents('/opt/admit/'.$slug), true);
	unset($content['cookie']);
	$admitted_status[$slug] = $content;
}

uasort($admitted_status, function ($a, $b) {
	return $a['time'] < $b['time'];
});

$my_score = json_decode(file_get_contents('/opt/bjut/scores/18080108'), true);
$user = json_decode(file_get_contents('/opt/bjut/users/18080108'), true);
$translate = [
	'UMich' => '密西根安娜堡',
	'UIUC' => '伊利诺伊香槟',
	'CMU' => '卡内基梅隆大学',
	'USC' => '南加州大学',
	'WISC' => '威斯康星麦迪逊',
	'UNC' => '北卡教堂山分校',
	'OSU' => '俄亥俄州立大学',
	'UBC' => '不列颠哥伦比亚',
];
$n_admit = 0;
$n_reject = 0;
$n_review = 0;
$n_waiting = 0;
$fire_work = false;
$latest_admit = 0;
$no_fire_work = false;
$latest_reject = 0;
foreach ($admitted_status as $univ => $status) {
	if(isset($status['other'])){
		continue;
	}
	if(isset($status['admitted'])){
		$n_admit++;
		if($status['time'] + 86400 > time()){
			$fire_work = true;
		}
		if($status['time'] > $latest_admit){
			$latest_admit = $status['time'];
		}
	} else if (isset($status['reject'])) {
		$n_reject++;
		if($status['time'] > $latest_reject){
			$latest_reject = $status['time'];
		}
	} else if (isset($status['complete'])) {
		$n_review++;
	} else if (isset($status['submitted'])) {
		$n_waiting++;
	}
}
if(isset($admitted_status[array_key_first($admitted_status)]['reject'])){
	$no_fire_work = true;
} else if ($latest_reject > $latest_admit){
	$no_fire_work = true;
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="refresh" content="<?php
	$r = 5 - date('s');
	while($r < 50) {
		$r += 60;
	}
	echo $r;
	?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>我的 Offer 呢?</title>

	<!-- Bootstrap core CSS -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<script src="js/jquery-3.4.1.slim.min.js"></script>
	<script src="js/bootstrap.bundle.min.js"></script>
	<script src="js/bootbox.min.js"></script>
	<style>
		.container {
			width: auto;
			padding: 0 15px;
		}
		.small, small {
			line-height: 1.2;
		}
		@media (min-width: 768px) {
			.list-group-horizontal-md.row .list-group-item {
				border-top-width: 0;
				border-left-width: 0;
				border-right-width: 1px;
				border-bottom-width: 1px;
				max-height: -moz-calc((100vh - 360px) / 6);
				max-height: -webkit-calc((100vh - 360px) / 6);
				max-height: calc((100vh - 360px) / 6);
				min-height: 150px;
				overflow-y: scroll;
				border-radius: 0!important;
			}
		}
		@media (min-width: 992px) {
			.list-group-horizontal-md.row .list-group-item {
				max-height: -moz-calc((100vh - 360px) / 4);
				max-height: -webkit-calc((100vh - 360px) / 4);
				max-height: calc((100vh - 360px) / 4);
			}
		}
		@media (min-width: 1200px) {
			.list-group-horizontal-md.row .list-group-item {
				max-height: -moz-calc((100vh - 360px) / 3);
				max-height: -webkit-calc((100vh - 360px) / 3);
				max-height: calc((100vh - 360px) / 3);
			}
		}
		@media (min-width: 1425px) {
			.container {
				max-width: 80vw;
			}
		}

		@media (max-width: 360px) {
			small.mt-1 {
				line-height: 1.2!important;
				margin-top: -2.5px!important;
			}
		}

		@media (max-width: 480px) {
			td small {
				display: none;
			}
			.sssmall-sm {
				font-size: 50%;
				padding-top: 10px!important;
			}
			.ssmall-sm {
				font-size: 64%;
				padding-top: 9px!important;
			}
			.small-sm {
				font-size: 80%;
				padding-top: 7px!important;
			}
		}
		p .alert-danger, p .alert-success {
			display: block;
		}
		p .alert-danger::before {
			content: "Missing: ";
		}
		p .alert-success::before { 
			content: "Received: ";
		}

		@media (prefers-color-scheme: dark) {
			body {
				background-color: #000;
			}
			body > * {
				-webkit-filter: invert(100%);
				filter: invert(100%);
			}
			.list-group-item-primary {
				color: #856404;
				background-color: #ffeeba;
			}
			.list-group-item-primary.list-group-item-action:hover, .list-group-item-primary.list-group-item-action:focus {
				color: #856404;
				background-color: #ffe8a1;
			}
			.list-group-item-primary.list-group-item-action.active {
				background-color: #856404;
				border-color: #856404;
			}
			.list-group-item-warning {
				color: #004085;
				background-color: #b8daff;
			}
			.list-group-item-warning.list-group-item-action:hover, .list-group-item-warning.list-group-item-action:focus {
				color: #004085;
				background-color: #9fcdff;
			}
			.list-group-item-warning.list-group-item-action.active {
				background-color: #004085;
				border-color: #004085;
			}
			
			.list-group-item-success {
				color: #721c24;
				background-color: #f5c6cb;
			}
			.list-group-item-success.list-group-item-action:hover, .list-group-item-success.list-group-item-action:focus {
				color: #721c24;
				background-color: #f1b0b7;
			}
			.list-group-item-success.list-group-item-action.active {
				background-color: #721c24;
				border-color: #721c24;
			}
			.list-group-item-danger {
				color: #155724;
				background-color: #c3e6cb;
			}
			.list-group-item-danger.list-group-item-action:hover, .list-group-item-danger.list-group-item-action:focus {
				color: #155724;
				background-color: #b1dfbb;
			}
			.list-group-item-danger.list-group-item-action.active {
				background-color: #155724;
				border-color: #155724;
			}
			
			.btn-primary {
				color: #212529;
				background-color: #ffc107;
				border-color: #ffc107;
			}
			.btn-primary:hover {
				color: #212529;
				background-color: #e0a800;
				border-color: #d39e00;
			}
			.btn-primary:focus, .btn-primary.focus {
				color: #212529;
				background-color: #e0a800;
				border-color: #d39e00;
			}
			.btn-primary.disabled, .btn-primary:disabled {
				color: #212529;
				background-color: #ffc107;
				border-color: #ffc107;
			}
			.btn-primary:not(:disabled):not(.disabled):active, .btn-primary:not(:disabled):not(.disabled).active,
			.show > .btn-primary.dropdown-toggle {
				color: #212529;
				background-color: #d39e00;
				border-color: #c69500;
			}
			.btn-warning {
				color: #fff;
				background-color: #007bff;
				border-color: #007bff;
			}
			.btn-warning:hover {
				color: #fff;
				background-color: #0069d9;
				border-color: #0062cc;
			}
			.btn-warning:focus, .btn-warning.focus {
				color: #fff;
				background-color: #0069d9;
				border-color: #0062cc;
			}
			.btn-warning.disabled, .btn-warning:disabled {
				color: #fff;
				background-color: #007bff;
				border-color: #007bff;
			}
			.btn-warning:not(:disabled):not(.disabled):active, .btn-warning:not(:disabled):not(.disabled).active,
			.show > .btn-warning.dropdown-toggle {
				color: #fff;
				background-color: #0062cc;
				border-color: #005cbf;
			}
			
			.btn-success {
				background-color: #dc3545;
				border-color: #dc3545;
			}
			.btn-success:hover {
				background-color: #c82333;
				border-color: #bd2130;
			}
			.btn-success:focus, .btn-success.focus {
				background-color: #c82333;
				border-color: #bd2130;
			}
			.btn-success.disabled, .btn-success:disabled {
				background-color: #dc3545;
				border-color: #dc3545;
			}
			.btn-success:not(:disabled):not(.disabled):active, .btn-success:not(:disabled):not(.disabled).active,
			.show > .btn-success.dropdown-toggle {
				background-color: #bd2130;
				border-color: #b21f2d;
			}
			.btn-danger {
				background-color: #28a745;
				border-color: #28a745;
			}
			.btn-danger:hover {
				background-color: #218838;
				border-color: #1e7e34;
			}
			.btn-danger:focus, .btn-danger.focus {
				background-color: #218838;
				border-color: #1e7e34;
			}
			.btn-danger.disabled, .btn-danger:disabled {
				background-color: #28a745;
				border-color: #28a745;
			}
			.btn-danger:not(:disabled):not(.disabled):active, .btn-danger:not(:disabled):not(.disabled).active,
			.show > .btn-danger.dropdown-toggle {
				background-color: #1e7e34;
				border-color: #1c7430;
			}
			
			.alert-danger {
				color: #155724;
				background-color: #d4edda;
				border-color: #c3e6cb;
			}
			.alert-danger hr {
				border-top-color: #b1dfbb;
			}
			.alert-danger .alert-link {
				color: #0b2e13;
			}
			.alert-success {
				color: #721c24;
				background-color: #f8d7da;
				border-color: #f5c6cb;
			}
			.alert-success hr {
				border-top-color: #f1b0b7;
			}
			.alert-success .alert-link {
				color: #491217;
			}
		}

		.pyro>.after,.pyro>.before{z-index:100;position:absolute;width:5px;height:5px;border-radius:50%;box-shadow:0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60,0 0 #f60;-moz-animation:1s bang ease-out infinite backwards,1s gravity ease-in infinite backwards,5s position linear infinite backwards;-webkit-animation:1s bang ease-out infinite backwards,1s gravity ease-in infinite backwards,5s position linear infinite backwards;-o-animation:1s bang ease-out infinite backwards,1s gravity ease-in infinite backwards,5s position linear infinite backwards;-ms-animation:1s bang ease-out infinite backwards,1s gravity ease-in infinite backwards,5s position linear infinite backwards;animation:1s bang ease-out infinite backwards,1s gravity ease-in infinite backwards,5s position linear infinite backwards}.pyro>.after{-moz-animation-delay:1.25s,1.25s,1.25s;-webkit-animation-delay:1.25s,1.25s,1.25s;-o-animation-delay:1.25s,1.25s,1.25s;-ms-animation-delay:1.25s,1.25s,1.25s;animation-delay:1.25s,1.25s,1.25s;-moz-animation-duration:1.25s,1.25s,6.25s;-webkit-animation-duration:1.25s,1.25s,6.25s;-o-animation-duration:1.25s,1.25s,6.25s;-ms-animation-duration:1.25s,1.25s,6.25s;animation-duration:1.25s,1.25s,6.25s}@-webkit-keyframes bang{to{box-shadow:55px -10.6666666667px #0026ff,-189px -332.6666666667px #f08,-150px -410.6666666667px #0fe,-90px -149.6666666667px #3f0,101px 80.3333333333px #0fd,-40px -414.6666666667px #00fff7,20px -410.6666666667px #0af,-212px 62.3333333333px #e600ff,-140px -269.6666666667px #00c8ff,-188px -245.6666666667px #7bff00,50px 55.3333333333px #0091ff,-39px -344.6666666667px #1e00ff,192px -37.6666666667px #c800ff,-190px -228.6666666667px #f05,-122px 77.3333333333px #f0e,54px -323.6666666667px #ff004d,248px -330.6666666667px #00ffc4,92px -75.6666666667px #4800ff,233px -290.6666666667px #00ffd9,-188px -228.6666666667px #ffd900,148px -244.6666666667px #40f,-16px -239.6666666667px #48ff00,-50px -277.6666666667px #3f0,-233px -9.6666666667px #ff0009,88px -63.6666666667px #9500ff,65px -306.6666666667px #f08,-14px 29.3333333333px #0bf,-51px -180.6666666667px #04ff00,246px -8.6666666667px #09ff00,1px -70.6666666667px #00a6ff,247px 71.3333333333px #fd0,-93px -336.6666666667px #ff3c00,-204px -58.6666666667px #fb0,-170px -28.6666666667px #40ff00,-151px -33.6666666667px #09ff00,100px -21.6666666667px #05f,-113px -360.6666666667px #002fff,-188px -389.6666666667px #0004ff,48px -251.6666666667px #00ff09,114px -233.6666666667px #bfff00,-186px -234.6666666667px #ffb700,149px -393.6666666667px #00ff9d,-104px -41.6666666667px #f90,-30px 43.3333333333px #00c8ff,220px -236.6666666667px #00ff3c,54px -256.6666666667px #ff4000,143px -147.6666666667px #f0a,-8px -47.6666666667px #9100ff,5px -92.6666666667px #4d00ff,176px 79.3333333333px #fc0,120px -324.6666666667px #0091ff}}@-moz-keyframes bang{to{box-shadow:55px -10.6666666667px #0026ff,-189px -332.6666666667px #f08,-150px -410.6666666667px #0fe,-90px -149.6666666667px #3f0,101px 80.3333333333px #0fd,-40px -414.6666666667px #00fff7,20px -410.6666666667px #0af,-212px 62.3333333333px #e600ff,-140px -269.6666666667px #00c8ff,-188px -245.6666666667px #7bff00,50px 55.3333333333px #0091ff,-39px -344.6666666667px #1e00ff,192px -37.6666666667px #c800ff,-190px -228.6666666667px #f05,-122px 77.3333333333px #f0e,54px -323.6666666667px #ff004d,248px -330.6666666667px #00ffc4,92px -75.6666666667px #4800ff,233px -290.6666666667px #00ffd9,-188px -228.6666666667px #ffd900,148px -244.6666666667px #40f,-16px -239.6666666667px #48ff00,-50px -277.6666666667px #3f0,-233px -9.6666666667px #ff0009,88px -63.6666666667px #9500ff,65px -306.6666666667px #f08,-14px 29.3333333333px #0bf,-51px -180.6666666667px #04ff00,246px -8.6666666667px #09ff00,1px -70.6666666667px #00a6ff,247px 71.3333333333px #fd0,-93px -336.6666666667px #ff3c00,-204px -58.6666666667px #fb0,-170px -28.6666666667px #40ff00,-151px -33.6666666667px #09ff00,100px -21.6666666667px #05f,-113px -360.6666666667px #002fff,-188px -389.6666666667px #0004ff,48px -251.6666666667px #00ff09,114px -233.6666666667px #bfff00,-186px -234.6666666667px #ffb700,149px -393.6666666667px #00ff9d,-104px -41.6666666667px #f90,-30px 43.3333333333px #00c8ff,220px -236.6666666667px #00ff3c,54px -256.6666666667px #ff4000,143px -147.6666666667px #f0a,-8px -47.6666666667px #9100ff,5px -92.6666666667px #4d00ff,176px 79.3333333333px #fc0,120px -324.6666666667px #0091ff}}@-o-keyframes bang{to{box-shadow:55px -10.6666666667px #0026ff,-189px -332.6666666667px #f08,-150px -410.6666666667px #0fe,-90px -149.6666666667px #3f0,101px 80.3333333333px #0fd,-40px -414.6666666667px #00fff7,20px -410.6666666667px #0af,-212px 62.3333333333px #e600ff,-140px -269.6666666667px #00c8ff,-188px -245.6666666667px #7bff00,50px 55.3333333333px #0091ff,-39px -344.6666666667px #1e00ff,192px -37.6666666667px #c800ff,-190px -228.6666666667px #f05,-122px 77.3333333333px #f0e,54px -323.6666666667px #ff004d,248px -330.6666666667px #00ffc4,92px -75.6666666667px #4800ff,233px -290.6666666667px #00ffd9,-188px -228.6666666667px #ffd900,148px -244.6666666667px #40f,-16px -239.6666666667px #48ff00,-50px -277.6666666667px #3f0,-233px -9.6666666667px #ff0009,88px -63.6666666667px #9500ff,65px -306.6666666667px #f08,-14px 29.3333333333px #0bf,-51px -180.6666666667px #04ff00,246px -8.6666666667px #09ff00,1px -70.6666666667px #00a6ff,247px 71.3333333333px #fd0,-93px -336.6666666667px #ff3c00,-204px -58.6666666667px #fb0,-170px -28.6666666667px #40ff00,-151px -33.6666666667px #09ff00,100px -21.6666666667px #05f,-113px -360.6666666667px #002fff,-188px -389.6666666667px #0004ff,48px -251.6666666667px #00ff09,114px -233.6666666667px #bfff00,-186px -234.6666666667px #ffb700,149px -393.6666666667px #00ff9d,-104px -41.6666666667px #f90,-30px 43.3333333333px #00c8ff,220px -236.6666666667px #00ff3c,54px -256.6666666667px #ff4000,143px -147.6666666667px #f0a,-8px -47.6666666667px #9100ff,5px -92.6666666667px #4d00ff,176px 79.3333333333px #fc0,120px -324.6666666667px #0091ff}}@-ms-keyframes bang{to{box-shadow:55px -10.6666666667px #0026ff,-189px -332.6666666667px #f08,-150px -410.6666666667px #009c90,-90px -149.6666666667px #3f0,101px 80.3333333333px #0fd,-40px -414.6666666667px #00fff7,20px -410.6666666667px #0af,-212px 62.3333333333px #e600ff,-140px -269.6666666667px #00c8ff,-188px -245.6666666667px #7bff00,50px 55.3333333333px #0091ff,-39px -344.6666666667px #1e00ff,192px -37.6666666667px #c800ff,-190px -228.6666666667px #f05,-122px 77.3333333333px #f0e,54px -323.6666666667px #ff004d,248px -330.6666666667px #00ffc4,92px -75.6666666667px #4800ff,233px -290.6666666667px #00ffd9,-188px -228.6666666667px #ffd900,148px -244.6666666667px #40f,-16px -239.6666666667px #48ff00,-50px -277.6666666667px #3f0,-233px -9.6666666667px #ff0009,88px -63.6666666667px #9500ff,65px -306.6666666667px #f08,-14px 29.3333333333px #0bf,-51px -180.6666666667px #04ff00,246px -8.6666666667px #09ff00,1px -70.6666666667px #00a6ff,247px 71.3333333333px #fd0,-93px -336.6666666667px #ff3c00,-204px -58.6666666667px #fb0,-170px -28.6666666667px #40ff00,-151px -33.6666666667px #09ff00,100px -21.6666666667px #05f,-113px -360.6666666667px #002fff,-188px -389.6666666667px #0004ff,48px -251.6666666667px #00ff09,114px -233.6666666667px #bfff00,-186px -234.6666666667px #ffb700,149px -393.6666666667px #00ff9d,-104px -41.6666666667px #f90,-30px 43.3333333333px #00c8ff,220px -236.6666666667px #00ff3c,54px -256.6666666667px #ff4000,143px -147.6666666667px #f0a,-8px -47.6666666667px #9100ff,5px -92.6666666667px #4d00ff,176px 79.3333333333px #fc0,120px -324.6666666667px #0091ff}}@keyframes bang{to{box-shadow:55px -10.6666666667px #0026ff,-189px -332.6666666667px #f08,-150px -410.6666666667px #0fe,-90px -149.6666666667px #3f0,101px 80.3333333333px #0fd,-40px -414.6666666667px #00fff7,20px -410.6666666667px #0af,-212px 62.3333333333px #e600ff,-140px -269.6666666667px #00c8ff,-188px -245.6666666667px #7bff00,50px 55.3333333333px #0091ff,-39px -344.6666666667px #1e00ff,192px -37.6666666667px #c800ff,-190px -228.6666666667px #f05,-122px 77.3333333333px #f0e,54px -323.6666666667px #ff004d,248px -330.6666666667px #00ffc4,92px -75.6666666667px #4800ff,233px -290.6666666667px #00ffd9,-188px -228.6666666667px #ffd900,148px -244.6666666667px #40f,-16px -239.6666666667px #48ff00,-50px -277.6666666667px #3f0,-233px -9.6666666667px #ff0009,88px -63.6666666667px #9500ff,65px -306.6666666667px #f08,-14px 29.3333333333px #0bf,-51px -180.6666666667px #04ff00,246px -8.6666666667px #09ff00,1px -70.6666666667px #00a6ff,247px 71.3333333333px #fd0,-93px -336.6666666667px #ff3c00,-204px -58.6666666667px #fb0,-170px -28.6666666667px #40ff00,-151px -33.6666666667px #09ff00,100px -21.6666666667px #05f,-113px -360.6666666667px #002fff,-188px -389.6666666667px #0004ff,48px -251.6666666667px #00ff09,114px -233.6666666667px #bfff00,-186px -234.6666666667px #ffb700,149px -393.6666666667px #00ff9d,-104px -41.6666666667px #f90,-30px 43.3333333333px #00c8ff,220px -236.6666666667px #00ff3c,54px -256.6666666667px #ff4000,143px -147.6666666667px #f0a,-8px -47.6666666667px #9100ff,5px -92.6666666667px #4d00ff,176px 79.3333333333px #fc0,120px -324.6666666667px #0091ff}}@-webkit-keyframes gravity{to{transform:translateY(200px);-moz-transform:translateY(200px);-webkit-transform:translateY(200px);-o-transform:translateY(200px);-ms-transform:translateY(200px);opacity:0}}@-moz-keyframes gravity{to{transform:translateY(200px);-moz-transform:translateY(200px);-webkit-transform:translateY(200px);-o-transform:translateY(200px);-ms-transform:translateY(200px);opacity:0}}@-o-keyframes gravity{to{transform:translateY(200px);-moz-transform:translateY(200px);-webkit-transform:translateY(200px);-o-transform:translateY(200px);-ms-transform:translateY(200px);opacity:0}}@-ms-keyframes gravity{to{transform:translateY(200px);-moz-transform:translateY(200px);-webkit-transform:translateY(200px);-o-transform:translateY(200px);-ms-transform:translateY(200px);opacity:0}}@keyframes gravity{to{transform:translateY(200px);-moz-transform:translateY(200px);-webkit-transform:translateY(200px);-o-transform:translateY(200px);-ms-transform:translateY(200px);opacity:0}}@-webkit-keyframes position{0%,19.9%{margin-top:10%;margin-left:40%}20%,39.9%{margin-top:40%;margin-left:30%}40%,59.9%{margin-top:20%;margin-left:70%}60%,79.9%{margin-top:30%;margin-left:20%}80%,99.9%{margin-top:30%;margin-left:80%}}@-moz-keyframes position{0%,19.9%{margin-top:10%;margin-left:40%}20%,39.9%{margin-top:40%;margin-left:30%}40%,59.9%{margin-top:20%;margin-left:70%}60%,79.9%{margin-top:30%;margin-left:20%}80%,99.9%{margin-top:30%;margin-left:80%}}@-o-keyframes position{0%,19.9%{margin-top:10%;margin-left:40%}20%,39.9%{margin-top:40%;margin-left:30%}40%,59.9%{margin-top:20%;margin-left:70%}60%,79.9%{margin-top:30%;margin-left:20%}80%,99.9%{margin-top:30%;margin-left:80%}}@-ms-keyframes position{0%,19.9%{margin-top:10%;margin-left:40%}20%,39.9%{margin-top:40%;margin-left:30%}40%,59.9%{margin-top:20%;margin-left:70%}60%,79.9%{margin-top:30%;margin-left:20%}80%,99.9%{margin-top:30%;margin-left:80%}}@keyframes position{0%,19.9%{margin-top:10%;margin-left:40%}20%,39.9%{margin-top:40%;margin-left:30%}40%,59.9%{margin-top:20%;margin-left:70%}60%,79.9%{margin-top:30%;margin-left:20%}80%,99.9%{margin-top:30%;margin-left:80%}}
	</style>
</head>
<body>
<?php if($fire_work && !$no_fire_work){ ?>
	<div class="pyro">
		<div class="before"></div><div class="after"></div>
	</div>
<?php } ?>
<main role="main" class="container mt-5">
	<h1>我的 Offer 呢?</h1>
	<p class="text-muted small float-right mt-4" style="text-align: right"><?php echo date('H:i'); ?></p>
	<h2 class="mt-4" id="status">实时录取情况
		<br/><button type="button" class="btn btn-success">
			录 <span class="badge badge-light"><?php echo $n_admit; ?></span>
		</button>
		<button type="button" class="btn btn-danger">
			拒 <span class="badge badge-light"><?php echo $n_reject; ?></span>
		</button>
		<button type="button" class="btn btn-primary">
			审 <span class="badge badge-light"><?php echo $n_review; ?></span>
		</button>
		<button type="button" class="btn btn-warning">
			等 <span class="badge badge-light"><?php echo $n_waiting; ?></span>
		</button>
	</h2>
	<p class="text-muted small">实时从邮箱和 Portal 获取最新状态, 判断可能有所偏差<br />点击学院/专业的缩写可查看全称</p>
	<button type="button" class="btn btn-info" id="reset" style="display:none;">
		重置
	</button>
	<div class="list-group my-3 list-group-horizontal-md row mx-0" id="statusList">
		<?php foreach ($admitted_status as $univ => $status) {
			if(isset($status['other'])){
				continue;
			}
			?>
			<div class="list-group-item list-group-item-action col-md-6 col-lg-4 col-xl-3 <?php
			if(isset($status['admitted'])){
				echo 'list-group-item-success';
			} else if (isset($status['reject'])) {
				echo 'list-group-item-danger';
			} else if (isset($status['waiting'])) {
				echo 'list-group-item-info';
			} else if (isset($status['complete'])) {
				echo 'list-group-item-primary';
			} else if (isset($status['submitted'])) {
				echo 'list-group-item-warning';
			}
			?>">
				<div class="d-flex w-100 justify-content-between">
					<h5 class="mb-1"><?php echo $univ.'<small> '.$translate[$univ].'</small>'; unset($translate[$univ]); ?></h5>
					<small style="text-align: right; line-height: 1.75;" class="mt-1"><?php if($status['time'] + 3600 > time()){
							echo '<span class="badge badge-pill badge-success" data-toggle="tooltip" data-placement="bottom" title="'.date('n.j H:i', $status['time']).'">';
							$ago = time() - $status['time'];
							if($ago < 120 ){
								echo '1 分钟内';
							} else {
								echo floor($ago/60). ' 分钟前';
							}
							echo '</span>';
						} else if($status['time'] + 86400 > time()){
							echo '<span class="badge badge-pill badge-info" data-toggle="tooltip" data-placement="bottom" title="'
								.floor((time() - $status['time'])/3600).' 小时前有变动">'.date('n.j H:i', $status['time']).'</span>';
						} else if($status['time'] + 31536000 < time()){
							echo '<strong>'.date('y.n.j H:i', $status['time']).'</strong>';
						} else {
							echo '<strong>'.date('n.j H:i', $status['time']).'</strong>';
						}?> <?php if(isset($status['admitted'])){
							echo '已录取';
						} else if (isset($status['reject'])) {
							echo '已拒绝';
						} else if (isset($status['waiting'])) {
							echo '候选中';
						} else if (isset($status['complete'])) {
							echo '审理中';
						} else if (isset($status['submitted'])) {
							echo '等资料';
						} else {
							echo '未提交';
						}
						if($status['updated_time'] + 604800 < time()){
							// Intended
						} else if(!isset($status['admitted']) && !isset($status['reject'])) {
							echo '<br />检查于';
							if($status['updated_time'] + 86400 < time()){
								echo ': <span class="badge badge-pill badge-danger" data-toggle="tooltip" data-placement="bottom" title="已有 '.
									floor((time() - $status['updated_time'])/86400).' 天未更新">'.date('n.j H:i', $status['updated_time']).'</span>';
							} else if($status['updated_time'] + 3600 < time()){
								echo ': <span class="badge badge-pill badge-warning" data-toggle="tooltip" data-placement="bottom" title="已有 '.
									floor((time() - $status['updated_time'])/3600).' 小时未更新">'.date('n.j H:i', $status['updated_time']).'</span>';
							} else {
								$ago = time() - $status['updated_time'];
								if($ago < 120 ){
									echo ' 1 分钟内';
								} else if ($ago < 360) {
									echo ' '.floor($ago/60). ' 分钟前';
								} else {
									echo ' <span class="badge badge-pill badge-info"  data-toggle="tooltip" data-placement="bottom" title="'.date('n.j H:i', $status['updated_time']).'">'.floor($ago/60). ' 分钟前</span>';
								}
							}
						}
						?></small>
				</div>
				<p class="mb-1"><?php echo $status['html'] ?? htmlspecialchars($status['data']); ?></p>
				<?php if(isset($status['email'])) {
					echo '<ul class="mb-1">';
					krsort($status['email']);
					foreach ($status['email'] as $time => $email){
						$email = preg_replace('/[0-9]{5,}/', 'XXXXX', $email);
						if($time == $status['time']){ ?>
							<li class="small"><?php echo $email; ?></li>
						<?php } else { ?>
						<li class="small"><?php echo '<strong>'.date('n.j H:i', $time).'</strong>: '.$email; ?></li>
					<?php
						}
					}
					echo '</ul>';
				} ?>
			</div>
		<?php } ?>
		<?php foreach ($translate as $univ => $status) { ?>
			<div class="list-group-item list-group-item-action list-group-item-light col-md-6 col-lg-4 col-xl-3">
				<div class="d-flex w-100 justify-content-between">
					<h5 class="mb-1"><?php echo $univ.'<small> '.$status.'</small>'; ?></h5>
					<small style="text-align: right; line-height: 1.75;" class="mt-1"><strong>未提交</strong></small>
				</div>
			</div>
		<?php } ?>
	</div>
	<h2>实时竞赛奖项</h2>
	<div class="list-group my-3 list-group-horizontal-md row mx-0">
		<?php foreach ($admitted_status as $univ => $status) {
			if(!isset($status['other'])){
				continue;
			}
			?>
			<div class="list-group-item list-group-item-action col-md-6 col-lg-4 col-xl-3 <?php
			if(isset($status['admitted'])){
				echo 'list-group-item-success';
			} else if (isset($status['reject'])) {
				echo 'list-group-item-danger';
			} else if (isset($status['complete'])) {
				echo 'list-group-item-primary';
			} else if (isset($status['submitted'])) {
				echo 'list-group-item-warning';
			}
			?>">
				<div class="d-flex w-100 justify-content-between">
					<h5 class="mb-1"><?php echo $univ.'<small> '.$translate_o[$univ].'</small>'; ?></h5>
					<small style="text-align: right; line-height: 1.75;" class="mt-1"><?php if($status['time'] + 3600 > time()){
							echo '<span class="badge badge-pill badge-success" data-toggle="tooltip" data-placement="bottom" title="'.date('n.j H:i', $status['updated_time']).'">';
							$ago = time() - $status['time'];
							if($ago < 120 ){
								echo '1 分钟内';
							} else {
								echo floor($ago/60). ' 分钟前';
							}
							echo '</span>';
						} else if($status['time'] + 86400 > time()){
							echo '<span class="badge badge-pill badge-info" data-toggle="tooltip" data-placement="bottom" title="'
								.floor((time() - $status['time'])/3600).' 小时前有变动">'.date('n.j H:i', $status['time']).'</span>';
						} else if($status['time'] + 31536000 < time()){
							echo '<strong>'.date('y.n.j H:i', $status['time']).'</strong>';
						} else {
							echo '<strong>'.date('n.j H:i', $status['time']).'</strong>';
						}?><?php if(isset($status['admitted'])){
							echo ' 成功';
						} else if (isset($status['reject'])) {
							echo ' 失败';
						} else if (isset($status['complete'])) {
							echo ' 完成';
						} else if (isset($status['submitted'])) {
							echo ' 等待';
						}
						if($status['updated_time'] + 604800 < time()){
							// Intended
						} else if(!isset($status['admitted']) && !isset($status['reject'])) {
							echo '<br />检查于';
							if($status['updated_time'] + 86400 < time()){
								echo ': <span class="badge badge-pill badge-danger" data-toggle="tooltip" data-placement="bottom" title="已有 '.
									floor((time() - $status['updated_time'])/86400).' 天未更新">'.date('n.j H:i', $status['updated_time']).'</span>';
							} else if($status['updated_time'] + 3600 < time()){
								echo ': <span class="badge badge-pill badge-warning" data-toggle="tooltip" data-placement="bottom" title="已有 '.
									floor((time() - $status['updated_time'])/3600).' 小时未更新">'.date('n.j H:i', $status['updated_time']).'</span>';
							} else {
								$ago = time() - $status['updated_time'];
								if($ago < 120 ){
									echo ' 1 分钟内';
								} else {
									echo ' '.floor($ago/60). ' 分钟前';
								}
							}
						}
						?></small>
				</div>
				<p class="mb-1"><?php echo $status['html'] ?? htmlspecialchars($status['data']); ?></p>
				<?php if(isset($status['email'])) {
					echo '<ul class="mb-1">';
					krsort($status['email']);
					foreach ($status['email'] as $time => $email){
						$email = preg_replace('/[0-9]{5,}/', 'XXXXX', $email);
						if($time == $status['time']){ ?>
							<li class="small"><?php echo $email; ?></li>
						<?php } else { ?>
							<li class="small"><?php echo '<strong>'.date('n.j H:i', $time).'</strong>: '.$email; ?></li>
							<?php
						}
					}
					echo '</ul>';
				} ?>
			</div>
		<?php } ?>
	</div>
	<footer class="mb-5"><a target="_blank" href="https://github.com/ZE3kr/Wheres-My-Offer">Available on Github</a></footer>
</main>
<script type="text/javascript">
	var message = '请耐心等待，持续关注本页面!';
	var buttons = {
		ok: {
			label: '关闭'
		}
	};
	$(document).ready(function() {
		$("#reset").hide().click(function () {
			$("#statusList .list-group-item").show().removeAttr('hidden');
			$(this).hide();
			sessionStorage.removeItem('tab');
		});
		var prevTab = sessionStorage.getItem('tab');
		if(prevTab){
			$("#statusList .list-group-item").hide().attr('hidden', 'hidden');
			$("#statusList .list-group-item.list-group-item-" + prevTab).show().removeAttr('hidden');
			$("#reset").show();
		}
		$("body").tooltip({ selector: '[data-toggle=tooltip]' });
		$("#status button.btn-success").click(function () {
			if($(this).find(".badge").first().text() === '0'){
				bootbox.alert({
					title: '尚无录取',
					message: message,
					buttons: buttons
				});
				return;
			}
			$("#statusList .list-group-item").hide().attr('hidden', 'hidden');
			$("#statusList .list-group-item.list-group-item-success").show().removeAttr('hidden');
			$("#reset").show();
			sessionStorage.setItem('tab', 'success');
		});
		$("#status button.btn-danger").click(function () {
			if($(this).find(".badge").first().text() === '0'){
				bootbox.alert({
					message: '尚无拒绝',
					buttons: buttons
				});
				return;
			}
			$("#statusList .list-group-item").hide().attr('hidden', 'hidden');
			$("#statusList .list-group-item.list-group-item-danger").show().removeAttr('hidden');
			$("#reset").show();
			sessionStorage.setItem('tab', 'danger');
		});
		$("#status button.btn-primary").click(function () {
			if($(this).find(".badge").first().text() === '0'){
				bootbox.alert({
					title: '尚无审理',
					message: message,
					buttons: buttons
				});
				return;
			}
			$("#statusList .list-group-item").hide().attr('hidden', 'hidden');
			$("#statusList .list-group-item.list-group-item-primary").show().removeAttr('hidden');
			$("#statusList .list-group-item.list-group-item-info").show().removeAttr('hidden');
			$("#reset").show();
			sessionStorage.setItem('tab', 'primary');
		});
		$("#status button.btn-warning").click(function () {
			if($(this).find(".badge").first().text() === '0'){
				bootbox.alert({
					title: '尚无等待',
					message: message,
					buttons: buttons
				});
				return;
			}
			$("#statusList .list-group-item").hide().attr('hidden', 'hidden');
			$("#statusList .list-group-item.list-group-item-warning").show().removeAttr('hidden');
			$("#reset").show();
			sessionStorage.setItem('tab', 'warning');
		});
	});
</script>
</body>
</html>
