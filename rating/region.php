<?
	$cities = array(
		'Улан-Удэ',
		'Иркутск',
		'Красноярск',
		'Томск',
		'Новосибирск',
		'Тюмень',
		'Омск',
		'Кемерово',
		'Сургут',
		'Нижневартовск',
		'Барнаул',
		'Чита',
		'Якутск',
		'Хабаровск',
		'Владивосток',
		'Петропавловск-Камчатский',
		'Южно-Сахалинск'
	);

	foreach ($cities as $city) {
		$html = new DOMDocument();

		$params = array(
			'town' => iconv('UTF-8', 'Windows-1251', $city),
			'dont_show_all' => 'on',
			'dont_show_irregulars' => 'on'
		);

		$keys = array(
			1 => 'position',
			3 => 'rating',
			7 => 'name',
			8 => 'city'
		);

		if ($html->loadHtmlFile('http://rating.chgk.info/teams.php?'.http_build_query($params))) {
			$xpath = new DOMXPath($html);
			$list = $xpath->query('//table[@id="teams_table"]/tbody/tr');

			foreach ($list as $item) {
				$item = $xpath->query('./td', $item);

				foreach ($keys as $i => $key) {
					$value = trim(preg_replace('/[^\w\+\-, ]/u', null, $item->item($i)->textContent));

					switch ($key) {
						case 'position':
							$value = preg_replace('/\s+/', '-', $value);

							break;

						case 'rating':
							$value = intval($value);

							break;
					}

					$team[$key] = $value;
				}

				if ($team['rating'])
					$teams[] = $team;
			}
		}
	}

	usort($teams, function ($a, $b) {
		return $b['rating'] <=> $a['rating'];
	});

	$teams = array_slice($teams, 0, 100);

	$post = '🏆 Топ 100 команд спортивного #чтогдекогда Сибири и Дальнего Востока по данным rating.chgk.info на '.$date."\n\n".'Города: '.implode(', ', $cities)."\n";

	foreach ($teams as $i => $team)
		$post .= "\n".($i + 1).'. ('.$team['position'].'/'.$team['rating'].') '.$team['name'].', '.$team['city'];
?>