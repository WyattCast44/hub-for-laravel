<?php 

function step(string $name): object
{
    return new Class {
        public function composer(array $commands)
        {
            return $this;
        }
        public function npm(array $commands)
        {
            return $this;
        }
        public function artisan(array $commands)
        {
            return $this;
        }
        public function git(array $commands)
        {
            return $this;
        }
        public function migrate(bool $fresh = false, bool $seed = false)
        {
            return $this;
        }
        public function config(array $config)
        {
            return $this;
        }
        public function addTrait(string $trait)
        {
            return $this;
        }
        public function closure(Closure $closure)
        {
            return $this;
        }
    };
}

function compose(string $name): object
{
    return new Class {
        public function description(string $description)
        {
            return $this;
        }
        public function extend(string $recipe)
        {
            return $this;
        }
        public function env(array $env)
        {
            return $this;
        }
        public function steps(array $steps)
        {
            return $this;
        }

        public function in(string $path)
        {
            return $this;
        }
    };
}