<?php

namespace App\Services;

use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\Utils\FileSystem;

class FacebookService
{
	// APP_ID: 560280669714138
	// SECRET: f8ffcf41d083fe57dbd204ce9f49b0e6

	// GET_USER_ID
	// https://graph.facebook.com/v2.10/me?access_token={access_token}

	// USER_ID: 10162292496947009
	// USER_TOKEN: EAAH9koJK9toBO4VWUrldHB0zl6SESyOJKowmAJjuYTh98TuheJq3BMtSb1AXh8g3mMVV6o2eICHhFmgdCN4EURza3BADlIQZBAU2QtvZBzkuGjviTfqeinJ6FKwWixlEbZAnxwaTQ4EVPPGLVELWncD54RL5EwoZBotsjZBcTFrNIYt6okxNwNyQON3J631fizEayvK7BZCATqoCoZD
	// APP_ID:
	// APP_TOKEN: 560280669714138|Rx2HseP2RMoH1kZIHtJJDo9aIWo

	// GET_LONG_TERM_TOKEN
	// https://graph.facebook.com/v10.0/oauth/access_token?grant_type=fb_exchange_token&client_id=560280669714138&client_secret=f8ffcf41d083fe57dbd204ce9f49b0e6&fb_exchange_token=EAAH9koJK9toBO4VWUrldHB0zl6SESyOJKowmAJjuYTh98TuheJq3BMtSb1AXh8g3mMVV6o2eICHhFmgdCN4EURza3BADlIQZBAU2QtvZBzkuGjviTfqeinJ6FKwWixlEbZAnxwaTQ4EVPPGLVELWncD54RL5EwoZBotsjZBcTFrNIYt6okxNwNyQON3J631fizEayvK7BZCATqoCoZD

	// LONG_TERM_TOKEN:
	// EAAH9koJK9toBO1ZAp3LjioKHTdH8J8oM3xluS3KxYgLJv4a6yI06WZCVvzoVlLtpZBQF1uIjTvmscJxibLzs9wJg4ieH5xIw0XdPwzGkm1T4S4lW0ugwV7IWeZAEscF9FfQVMtcZBNT494AZAamPHo1H5wZBPcBsrIA4g1Gk88dw6zdBydeL7Or8AZDZD

	// GET_NON_EXPIRING_TOKEN
	// https://graph.facebook.com/v10.0/10162292496947009/accounts?access_token=EAAH9koJK9toBO1ZAp3LjioKHTdH8J8oM3xluS3KxYgLJv4a6yI06WZCVvzoVlLtpZBQF1uIjTvmscJxibLzs9wJg4ieH5xIw0XdPwzGkm1T4S4lW0ugwV7IWeZAEscF9FfQVMtcZBNT494AZAamPHo1H5wZBPcBsrIA4g1Gk88dw6zdBydeL7Or8AZDZD

	// NON_EXPIRE
	// EAAH9koJK9toBO2KQipEtNlhIwuqzS5zFpS0GzbnNR98JeGaArjtyioZCZAqqxvgOU6G8LaSJtPXA8D5EAmKk4841zQFif7H8ZABIObhVRNaPB25hlELXmhZA74IXAUY1PHL8DxLcgSDtZC0tfCIsdnTrSEOxUD7HqBTCgcZCeQOpJT96rgZBBRg5yztnWxd9gZDZD

    const
        APP_ID = 560280669714138,
        APP_SECRET = 'f8ffcf41d083fe57dbd204ce9f49b0e6',
        PROFILE_ID = 473892616346277,
        CACHE_DIR = TEMP_DIR . '/cache/facebook';

	private string $token;

    public function __construct(
		ParamService $paramService
    )
    {
        FileSystem::createDir(self::CACHE_DIR);
		$this->token = $paramService->getFbToken();
    }

    public function getEvents(): ArrayHash
    {
        $storage = new FileStorage(self::CACHE_DIR);
        $cache = new Cache($storage);

        $cacheKey = "facebook-events";

        $profileId = self::PROFILE_ID;

        $now = new DateTime();

        $token = $this->token;
        $url = "https://graph.facebook.com/v21.0/$profileId/events?access_token=" . $token . "&fields=id,name,description,start_time,end_time,place,cover&sort=start_time_ascending";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        $response = curl_exec($ch);
//		bdump($response);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL error: $error");
        }

        curl_close($ch);

        $data = json_decode($response, true);
//		bdump($data);

        if (isset($data['data'])) {
            $events = ArrayHash::from($data['data']);

            $events = array_filter((array)$events, function($event) use ($now) {
                $start = new DateTime($event->start_time);
                $end = new DateTime($event->start_time);

                return $start >= $now || $end >= $now;
            });

            $events = ArrayHash::from(array_map(fn($event) => [
                'id' => $event->id,
                'title' => $event->name,
                'start' => new DateTime($event->start_time),
                'end' => isset($event->end_time) ? new DateTime($event->end_time) : null,
                'text' => $event->description,
                'image' => $event->cover->source,
                'link' => "https://www.facebook.com/events/$event->id"
            ], $events));

            $cache->save($cacheKey, $events, [
                Cache::EXPIRE => '1 hour',
            ]);

            return $events;
        } else {
            bdump(json_encode($data));
            throw new \Exception("Facebook API error: " . json_encode($data));
        }
    }

}