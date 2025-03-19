<?php

namespace App\Commands;

use Exception;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class UpsertEnvFile extends Command 
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:set 
                                {key : The env key that you want to set } 
                                {value : The value to set the key to }
                                {--dont-create : If the key does not exist, do not create it }';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Update or set an env file value.';

    protected string $keyName;

    protected string $keyValue;

    protected bool $createNonExistingKeys = true;

    protected string $path;

    protected bool $found = false;

    public function handle(): void
    {
        $this->keyName = $this->argument('key');
        $this->keyValue = $this->argument('value');
        $this->createNonExistingKeys = ($this->option('dont-create')) ? false : true;

        $this
            ->ensureEnvFileExists()
            ->checkIfEnvFileHasKey()
            ->updateExistingKeysIfPresent()
            ->appendNewKeysIfNotPresent();

        return;
    }

    protected function ensureEnvFileExists()
    {
        $path = '.env';

        $fullPath = getcwd().DIRECTORY_SEPARATOR.$path;

        if (!file_exists($path)) {
            throw new Exception("Env file does not exist, cannot update key ".$this->keyName.". Path: ".$fullPath);
        }

        $this->path = $path;

        return $this;
    }

    protected function checkIfEnvFileHasKey()
    {
        if (Str::contains(file_get_contents($this->path), $this->keyName)) {
            $this->found = true;
        } else {
            $this->found = false;
        }

        return $this;
    }

    protected function updateExistingKeysIfPresent()
    {
        if (!$this->found) {
            return $this;   
        }

        
        $newFile = "";
        
        $handle = fopen($this->path, "r+");

        
        if (!$handle) {
            throw new Exception("Unable to open env file: ".$this->path);
        }

        try {
            while (!feof($handle)) {
                $buffer = fgets($handle); // Read a line.

                if (trim($buffer) == "") {
                    $newFile = $newFile;
                }

                $parts = explode("=", $buffer);

                if ($parts[0] <> $this->keyName) {
                    // Blank line, keep it to keep default spacing
                    $newFile = $newFile . $buffer;
                } else {
                    // Key found, overwrite it
                    if(trim($parts[0]) == $this->keyName) {
                        if (preg_match('/"/', $parts[1]) || strpos($this->keyValue, " ")) {
                            $buffer = "{$parts[0]}=" . "\"" . $this->keyValue . "\"\n";
                            $newFile = $newFile . $buffer;
                        } else {
                            $buffer = "{$parts[0]}=" . $this->keyValue . "\n";
                            $newFile = $newFile . $buffer;
                        }
                    }
                }

            }

            fclose($handle); // Close the file.
        } catch (Exception $e) {
            @fclose($handle); // Close the file

            throw $e;
        }
        
        file_put_contents($this->path, $newFile);

        $this->line("[Env] Updated {$this->keyName} to \"{$this->keyValue}\"");

        return $this;
    }

    protected function appendNewKeysIfNotPresent()
    {
        if ($this->found) {
            return $this;
        }

        if (strpos($this->keyValue, " ")) {
            // If the value contains a space, wrap it in quotes
            $str = "{$this->keyName}=" . "\"" . $this->keyValue . "\"\n";
        } else {
            // If the value does not contain a space, don't wrap it in quotes
            $str = "{$this->keyName}=" . $this->keyValue . "\n";
        }

        file_put_contents(
            $this->path,
            "\n{$str}",
            FILE_APPEND
        );

        $this->line("[Env] Upserted {$this->keyName} to \"{$this->keyValue}\"");

        return $this;
    }
    
    
}
