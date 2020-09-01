<?php declare(strict_types=1);

namespace Nadybot\User\Modules;

use Nadybot\Core\JSONDataModel;

class Horoscope extends JSONDataModel {
	/**
	 * Date in Y-m-d format from which this horoscope is
	 */
	public string $date = "";

	/**
	 * The zodiac (Pisces, Sagittarius, etc.)
	 */
	public string $sunsign = "";

	/**
	 * The horoscope text
	 */
	public string $horoscope = "";

	public function isValid() {
		return strlen($this->date) > 0
			&& strlen($this->sunsign) > 0
			&& strlen($this->horoscope) > 0;
	}
}
