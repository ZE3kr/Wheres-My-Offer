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
	'UMich' => '',
	'UIUC' => '',
	'CMU' => '',
	'USC' => '',
	'WISC' => '',
	'UNC' => '',
	'OSU' => '',
	'UBC' => '',
	'Cornell' => '',
	'JHU' => '',
	'NYU' => '',
	'WUSTL' => '',
];
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="refresh" content="16" >
	<title>Where’s My Offer?</title>

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
	<h1>Where’s My Offer?</h1>
	<h2 class="mt-4">Real time status</h2>
	<small class="text-muted">This program retrieves the status from Portals every minute. Admission status is identified by keywords, which might lead to misjudgment.</small>
	<div class="list-group my-3">
		<?php foreach ($admitted_status as $univ => $status) { ?>
			<div class="list-group-item list-group-item-action <?php
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
					<small>Changed<?php
						if($status['time'] + 3600 > time()){
							echo ' <span class="badge badge-pill badge-success">';
							$ago = time() - $status['time'];
							if($ago < 90 ){
								echo 'in 1 minute';
							} else {
								echo floor($ago/60). ' minutes ago';
							}
							echo '</span>';
						} else if($status['time'] + 43200 > time()){
							echo ' at <span class="badge badge-pill badge-info">'.date('m-d H:i', $status['time']).'</span>';
						} else {
							echo ' at '.date('m-d H:i', $status['time']);;
						}
						?><br />Checked<?php
						if($status['updated_time'] + 43200 < time()){
							echo ' at <span class="badge badge-pill badge-danger">'.date('m-d H:i', $status['updated_time']).'</span>';
						} else if($status['updated_time'] + 3600 < time()){
							echo ' at <span class="badge badge-pill badge-info">'.date('m-d H:i', $status['updated_time']).'</span>';
						} else {
							$ago = time() - $status['updated_time'];
							if($ago < 90 ){
								echo ' in 1 minute';
							} else {
								echo ' '.floor($ago/60). ' minutes ago';
							}
						}
						?></small>
				</div>
				<p class="mb-1"><?php echo $status['data'] ?? 'N/A'; ?></p>
				<?php if(isset($status['email'])) {
					echo '<ul class="mb-1">';
					krsort($status['email']);
					$i = 0;
					foreach ($status['email'] as $time => $email){
						$email = str_replace(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], 'X', $email);
						if($time == $status['time']){ ?>
							<li><?php echo $email; ?></li>
						<?php } else { ?>
						<li><?php echo '<strong>'.date('m-d H:i', $time).'</strong>: '.$email; ?></li>
					<?php
						}
						if (++$i == 3) break;
					}
					echo '</ul>';
				} ?>
				<small><?php
					if(isset($status['admitted'])){
						echo 'Admitted';
					} else if (isset($status['reject'])) {
						echo 'Rejected';
					} else if (isset($status['waiting'])) {
						echo 'Waiting List';
					} else if (isset($status['complete'])) {
						echo 'Started Processing';
					} else if (isset($status['submitted'])) {
						echo 'Waiting Materials';
					} else {
						echo 'Waiting Submission';
					}
					?></small>
			</div>
		<?php } ?>
		<?php foreach ($translate as $univ => $status) { ?>
			<div class="list-group-item list-group-item-action list-group-item-light">
				<div class="d-flex w-100 justify-content-between">
					<h5 class="mb-1"><?php echo $univ.'<small> '.$status.'</small>'; ?></h5>
				</div>
				<small>Waiting Submission</small>
			</div>
		<?php } ?>
	</div>
	<footer class="mb-5"><a target="_blank" href="https://github.com/ZE3kr/Wheres-My-Offer">Available on Github</a></footer>
</main>
</body>
</html>
