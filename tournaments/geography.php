<?
	$cache = 'chr.txt';

	if (file_exists($cache)) {
		$geography = unserialize(file_get_contents($cache));
	} else {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, 'http://rating.chgk.info/tournaments.php');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
			'name' => iconv('UTF-8', 'Windows-1251', 'Чемпионат России')
		)));

		$tournaments = curl_exec($curl);

		curl_close($curl);

		$html = new DOMDocument();

		if ($html->loadHtml($tournaments)) {
			$xpath = new DOMXPath($html);
			$list = $xpath->query('//a[contains(@href, "tournament/")]');

			foreach ($list as $item) {
				$html = new DOMDocument();

				if ($html->loadHtmlFile('http://rating.chgk.info'.$item->getAttribute('href'))) {
					$xpath = new DOMXPath($html);

					$teams = $xpath->query('//table[@id="teams_table"]//a[contains(@href, "team/")]');
					$cities = $xpath->query('//table[@id="teams_table"]//a[contains(@href, "teams")]');

					foreach ($teams as $key => $team) {
						$team = str_replace('/team/', '', $team->getAttribute('href'));
						$city = trim($cities->item($key)->textContent);

						if ($city != 'сборная' && !in_array($team, $geography[$city]['teams']))
							$geography[$city]['teams'][] = $team;
					}
				}
			}
		}

		foreach ($geography as $city => $data) {
			foreach ($data['teams'] as $key => $id) {
				$team = json_decode(file_get_contents('http://rating.chgk.info/api/teams/'.$id.'.json'), true);
				$geography[$city]['teams'][$key] = $team[0]['name'];
			}

			$response = json_decode(file_get_contents('https://geocode-maps.yandex.ru/1.x/?'.http_build_query(array(
				'geocode' => $city,
				'format' => 'json',
				'results' => 1
			))), true);

			if ($response['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'])
				$geography[$city]['map'] = array_reverse(explode(' ', $response['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']));
		}

		file_put_contents($cache, serialize($geography));
	}

	foreach ($geography as $city => $data) {
		sort($data['teams']);

		$geography[$city]['teams'] = $data['teams'];
	}

	ksort($geography);

	ob_start();
?>

<!doctype html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
		<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU"></script>

		<title>География Чемпионата России по Что? Где? Когда?</title>

		<style>
			.map { height: 50vh; }
		</style>
	</head>

	<body>
		<div id="map" class="map"></div>

		<script>
			ymaps.ready(function() {
				var map = new ymaps.Map('map', {
						center: [55.76, 37.64],
						zoom: 7,
						controls: []
					}),

					objectManager = new ymaps.ObjectManager({
						clusterize: true
					}),

					geography = <?=json_encode($geography)?>,

					i = 0;

				map.controls.add('zoomControl');

				for (var city in geography) {
					objectManager.add({
						type: 'Feature',
						id: i++,

						geometry: {
							type: 'Point',
							coordinates: geography[city].map
						},

						properties: {
							iconCaption: city,
							balloonContent: '<h4>' + city + ' (' + geography[city].teams.length + ')</h4>' + geography[city].teams.join(', ')
						}
					});
				}

				map.geoObjects.add(objectManager);
				map.setBounds(map.geoObjects.getBounds());
			});
		</script>

		<div class="container mt-5 mb-5">
			<h1>География Чемпионата России по Что? Где? Когда?</h1>
			<p>По данным <a href="http://rating.chgk.info/" target="_blank">rating.chgk.info</a> на <?=date('d.m.Y', filemtime($cache))?></p>

			<table class="table table-striped">
				<? foreach ($geography as $city => $data) { ?>
				<tr>
					<td><b><?=$city?></b>&nbsp;(<?=count($data['teams'])?>)</td>
					<td><?=implode(', ', $data['teams'])?></td>
				</tr>
				<? } ?>
			</table>
		</div>
	</body>
</html>

<?
	file_put_contents('chr.html', ob_get_contents());
	ob_end_flush();
?>