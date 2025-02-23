<?php

namespace App\FrontModule\Types;

use AllowDynamicProperties;
use App\FrontModule\Presenters\BasePresenter;
use App\Services\Params\Contact;

#[AllowDynamicProperties] class BaseTemplate extends \App\BaseModule\Templates\BaseTemplate
{
    public readonly BasePresenter $presenter;
    public Contact $contact;
    public array $socialLinks;
}