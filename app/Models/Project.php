<?php 

namespace App\Models;

class Project
{
    public string $name;

    public string $path;

    /**
     * The installer to use for the project.
     * 
     * Valid values are: composer and laravel
     * 
     * @var string
     */
    public string $installer;

    public static function make()
    {
        return new self();
    }
}
