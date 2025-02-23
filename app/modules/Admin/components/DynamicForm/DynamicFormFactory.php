<?php

namespace App\AdminModule\Factories;


use App\Components\DynamicForm;
use App\Services\FileStorage;
use App\Services\TusService;
use Nette\Http\Session;

class DynamicFormFactory
{
    private FileStorage $fileStorage;
    private TusService $tusService;
    private Session $session;

    public function __construct(
        FileStorage $fileStorage,
        TusService $tusService,
        Session $session
    )
    {
        $this->fileStorage = $fileStorage;
        $this->tusService = $tusService;
        $this->session = $session;
    }

    public function create(
        callable $onRender,
        callable $onSubmit,
        $caption = null,
        ?array $defaults = null,
        ?bool $isCompact = false
    )
    {
        return new DynamicForm(
            $onRender,
            $onSubmit,
            $this->fileStorage,
            $this->tusService,
            $this->session,
            $defaults,
            $caption,
            $isCompact
        );
    }
}