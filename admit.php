<?php
header('Catch-Control: no-cache, must-revalidate, max-age=0, s-maxage=0');

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

$translate = [
	'UMich' => '密西根安娜堡<br/>CoE - CS BSE',
	'UIUC' => '伊利诺伊香槟<br/>LAS - Maths&CS',
	'CMU' => '卡内基梅隆大学<br/>SCS - CS',
	'USC' => '南加州大学<br/>Viterbi - CS',
	'WISC' => '威斯康星麦迪逊<br/>CoE - CS/CE',
	'UNC' => '北卡教堂山分校<br/>CAS - CS',
	'OSU' => '俄亥俄州立大学<br/>CoE - CSE',
	'UBC' => '不列颠哥伦比亚<br/>Vancouver - Sci',
	'Cornell' => '康奈尔大学',
	'JHU' => '霍普金斯大学',
	'NYU' => '纽约大学',
	'WUSTL' => '圣路易斯华盛顿大学',
];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="refresh" content="16" >
	<title>我的 Offer 呢?</title>

	<!-- Bootstrap core CSS -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<style>
		.container {
			width: auto;
			max-width: 680px;
			padding: 0 15px;
		}
	</style>
</head>
<body>
<main role="main" class="container mt-5">
	<h1>我的 Offer 呢?</h1>
	<h2 class="mt-4">实时录取情况</h2>
	<p class="text-muted">所列时间为状态最后修改时间，本程序每分钟从 Portal，每小时从邮箱获取最新状态。录取状况使用关键词匹配，判断可能有所偏差。</p>
	<div class="list-group mb-3">
		<?php foreach ($admitted_status as $univ => $status) { ?>
			<div class="list-group-item list-group-item-action <?php
			if(isset($status['admitted'])){
				echo 'bg-success text-white';
			} else if (isset($status['reject'])) {
				echo 'bg-danger text-white';
			} else if (isset($status['waiting'])) {
				echo 'bg-info text-white';
			} else if (isset($status['complete'])) {
				// echo 'bg-light text-dark';
			} else if (isset($status['submitted'])) {
				echo 'bg-warning text-dark';
			}
			?>">
				<div class="d-flex w-100 justify-content-between">
					<h5 class="mb-1"><?php echo $univ.'<small> '.$translate[$univ].'</small>'; unset($translate[$univ]); ?></h5>
					<small>变动于<?php
						if($status['time'] + 3600 > time()){
							echo ' <span class="badge badge-pill badge-success">';
							$ago = time() - $status['time'];
							if($ago < 120 ){
								echo '1 分钟内';
							} else {
								echo number_format($ago/60). ' 分钟前';
							}
							echo '</span>';
						} else if($status['time'] + 43200 > time()){
							echo ': <span class="badge badge-pill badge-info">'.date('m-d H:i', $status['time']).'</span>';
						} else {
							echo ': '.date('m-d H:i', $status['time']);;
						}
						?><br />检查于<?php
						if($status['updated_time'] + 43200 < time()){
							echo ': <span class="badge badge-pill badge-danger">'.date('m-d H:i', $status['updated_time']).'</span>';
						} else if($status['updated_time'] + 3600 < time()){
							echo ': <span class="badge badge-pill badge-info">'.date('m-d H:i', $status['updated_time']).'</span>';
						} else {
							$ago = time() - $status['updated_time'];
							if($ago < 120 ){
								echo ' 1 分钟内';
							} else {
								echo ' '.number_format($ago/60). ' 分钟前';
							}
						}
						?></small>
				</div>
				<p class="mb-1"><?php echo $status['data'] ?? 'N/A'; ?></p>
				<small><?php
					if(isset($status['admitted'])){
						echo '已经录取';
					} else if (isset($status['reject'])) {
						echo '已经拒绝';
					} else if (isset($status['waiting'])) {
						echo '等待列表';
					} else if (isset($status['complete'])) {
						echo '开始审理';
					} else if (isset($status['submitted'])) {
						echo '等待资料';
					} else {
						echo '尚未提交';
					}
					?></small>
			</div>
		<?php } ?>
		<?php foreach ($translate as $univ => $status) { ?>
			<div class="list-group-item list-group-item-action bg-dark text-white">
				<div class="d-flex w-100 justify-content-between">
					<h5 class="mb-1"><?php echo $univ.'<small> '.$status.'</small>'; ?></h5>
				</div>
				<small>尚未提交</small>
			</div>
		<?php } ?>
	</div>
	<footer class="mb-5"><a target="_blank" href="https://github.com/ZE3kr/Univ-Admit">在 GitHub 上获取</a></footer>
</main>
</body>
</html>
