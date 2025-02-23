<?php

namespace App\AdminModule\Factories;


use App\Components\FileManager;
use App\Model\FileRepository;
use App\Model\TagRepository;

class FileManagerFactory
{
    public function __construct(
        private FileRepository $fileRepository,
        private TagRepository $tagRepository,
        private DynamicFormFactory $dynamicFormFactory
    )
    {}

    public function create(
    ): FileManager
    {
        return new FileManager(
            $this->fileRepository,
            $this->tagRepository,
            $this->dynamicFormFactory
        );
    }
}