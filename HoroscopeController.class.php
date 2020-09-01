<?php declare(strict_types=1);

namespace Nadybot\User\Modules;

use Nadybot\Core\{
	CommandReply,
	Http,
	HttpResponse,
	Nadybot,
};

/**
 * A command to give you your daily horoscope. Powered by Ganesha ;o)
 *
 * @author Nadyita (RK5)
 *
 * @Instance
 *
 * Commands this controller contains:
 *	@DefineCommand(
 *		command     = 'horoscope',
 *		accessLevel = 'all',
 *		description = 'Get your daily horoscope',
 *		help        = 'horoscope.txt'
 *	)
 */
class HoroscopeController {
	/**
	 * Name of the module.
	 * Set automatically by module loader.
	 */
	public string $moduleName;

	/** @Inject */
	public Nadybot $chatBot;

	/** @Inject */
	public Http $http;

	/**
	 * The URL to the horoscope API with the zodiac as placeholder
	 */
	public const HOROSCOPE_API = 'http://horoscope-api.herokuapp.com/horoscope/today/%s';

	/**
	 * An array of all Zodiac names, sorted by ecliptic longitude of the first point
	 * @var string[] ZODIACS
	 */
	public const ZODIACS = [
		'Aries',
		'Taurus',
		'Gemini',
		'Cancer',
		'Leo',
		'Virgo',
		'Libra',
		'Scorpio',
		'Sagittarius',
		'Capricorn',
		'Aquarius',
		'Pisces',
	];

	/**
	 * The !horoscope command retrieves a horoscope depending on the user id
	 *
	 * @HandlesCommand("horoscope")
	 * @Matches("/^horoscope$/i")
	 */
	public function horoscopeCommand(string $message, string $channel, string $sender, CommandReply $sendto, array $args): void {
		$userID = $this->chatBot->get_uid($sender);
		if (!$userID) {
			return;
		}
		$zodiac = static::ZODIACS[$userID % 12];
		$this->http
				->get(sprintf(static::HOROSCOPE_API, $zodiac))
				->withTimeout(5)
				->withCallback([$this, "sendHoroscope"], $sendto);
	}

	public function sendHoroscope(HttpResponse $response, CommandReply $sendto): void {
		if (isset($response->error)) {
			$msg = "There was an error getting today's horoscope: ".$response->error.". Please try again later.";
			$sendto->reply($msg);
			return;
		}
		$horoscope = new Horoscope();
		$horoscope->fromJSON(@json_decode($response->body));
		if (!$horoscope->isValid()) {
			$msg = 'It seems the horoscope-API we are using has changed. Please contact nadyita@hodorraid.org';
			$sendto->reply($msg);
			return;
		}
		$sendto->reply($horoscope->horoscope);
	}
}
