<?php

namespace App\Services;

class PageValidator
{

	public function validate(string $url)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($response === false) {
			$error = curl_error($ch);
			curl_close($ch);
			bdump($error);

			return false;
		}

		if ($httpCode !== 200) {
			bdump($httpCode);
			return false;
		}

		if (preg_match('/Stránka nenalezena|Page not found|That’s an error/i', $response)) {
			return false;
		}

		curl_close($ch);

		bdump($response);
		bdump($httpCode);

		return true;
	}

}