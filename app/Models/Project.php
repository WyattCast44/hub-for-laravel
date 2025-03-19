<?php

namespace App\Models;

class Project
{
    /**
     * The name of the project.
     *
     * This will be set in the env file of the project.
     */
    public string $name;

    /**
     * The path to the project.
     *
     * This will be made available to the recipe file.
     */
    public string $path;

    /**
     * The installer to use for the project.
     *
     * Valid values are: composer and laravel
     *
     * This will be made available to the recipe file.
     */
    public string $installer;

    public static function make()
    {
        return new self;
    }
}
