<?php

namespace Budabot\User\Modules;

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
 *		description = 'Get yor daily horoscope',
 *		help        = 'horoscope.txt'
 *	)
 */
class HoroscopeController {
	/**
	 * Name of the module.
	 * Set automatically by module loader.
	 * @var string $moduleName
	 */
	public $moduleName;

	/**
	 * @var \Budabot\Core\Budabot $chatBot
	 * @Inject
	 */
	public $chatBot;

	/**
	 * @var \Budabot\Core\Http $http
	 * @Inject
	 */
	public $http;

	/**
	 * The URL to the horoscope API with the zodiac as placeholder
	 * @var string HOROSCOPE_API
	 */
	const HOROSCOPE_API = 'http://horoscope-api.herokuapp.com/horoscope/today/%s';

	/**
	 * An array of all Zodiac names, sorted by ecliptic longitude of the first point
	 * @var string[] ZODIACS
	 */
	const ZODIACS = array(
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
	);

	/**
	 * The !horoscope command retrieves a horoscope depending on the user id
	 *
	 * @param string                     $message The received message
	 * @param string                     $channel Where was the message received ("tell", "priv" or "guild")
	 * @param string                     $sender  Name of the person sending the command
	 * @param \Budabot\Core\CommandReply $sendto  Object to send the reply with
	 * @param string[]                   $args    The arguments to the command. Empty as we don't accept any
	 * @return void
	 *
	 * @HandlesCommand("horoscope")
	 * @Matches("/^horoscope$/i")
	 */
	public function horoscopeCommand($message, $channel, $sender, $sendto, $args) {
		$userID = $this->chatBot->get_uid($sender);
		if ($userID === false) {
			return;
		}
		$zodiac = static::ZODIACS[$userID % 12];
		$this->http
				->get(sprintf(static::HOROSCOPE_API, $zodiac))
				->withTimeout(5)
				->withCallback(function($response) use ($sendto) {
					$this->sendHoroscope($response, $sendto);
					$sendto->reply($msg);
				});
	}

	/**
	 * @param \StdClass                  $response The received response
	 * @param \Budabot\Core\CommandReply $sendto  Object to send the reply with
	 * @return void
	 */
	public function sendHoroscope($response, $sendto) {
		if (isset($response->error)) {
			$msg = "There was an error getting today's horoscope: ".$response->error.". Please try again later.";
			$sendto->reply($msg);
			return;
		}
		$horoscope = new Horoscope($response->body);
		if (!$horoscope->isValid()) {
			$msg = 'It seems the horoscope-API we are using has changed. Please contact nadyita@hodorraid.org';
			$sendto->reply($msg);
			return;
		}
		$sendto->reply($horoscope->horoscope."\n");
	}
}

/**
 * Class to parse the horoscope
 */
class Horoscope {
	/**
	 * Date in Y-m-d format from which this horoscope is
	 * @var string $date
	 */
	public $date = "";

	/**
	 * The zodiac (Pisces, Sagittarius, etc.)
	 * @var string $sunsign
	 */
	public $sunsign = "";

	/**
	 * The horoscope text
	 * @var string $horoscope
	 */
	public $horoscope = "";

	public function __construct($json=false) {
		if ($json) {
			$this->set(json_decode($json, true));
		}
	}

	public function set($data) {
		foreach ($data as $key => $value) {
			if (property_exists(static::class, $key)) {
				$this->{$key} = $value;
			}
		}
	}

	public function isValid() {
		return strlen($this->date) > 0
			&& strlen($this->sunsign) > 0
			&& strlen($this->horoscope) > 0;
	}
}
