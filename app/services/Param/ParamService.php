<?php


namespace App\Services;

use App\Services\Params\Contact;

class ParamService
{
    public function __construct(
        private readonly array $contact,
        private readonly array $social,
        private readonly array $links,
        private readonly array $keys,
        private readonly array $settings
    )
    { }

    public function getContact(): Contact
    {
        return new Contact($this->contact);
    }

    public function getSocial(): array
    {
        return $this->social;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getLink(string $key): string
    {
        return $this->links[$key];
    }

    public function getRecaptchaPublicKey(): string
    {
        return $this->keys['recaptcha']['public'];
    }

    public function getRecaptchaSecretKey(): string
    {
        return $this->keys['recaptcha']['secret'];
    }

	public function getFbToken(): string
	{
		return $this->keys['fbToken'];
	}
}