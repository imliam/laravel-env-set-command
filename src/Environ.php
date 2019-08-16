<?php

namespace ImLiam\EnvironmentSetCommand;

use InvalidArgumentException;
use Illuminate\Console\Command;

class Environ extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:set {key} {value?} {file=.env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set and save an environment variable in the .env file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            [$key, $value] = $this->getKeyValue();
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }

        $envFilePath = base_path($this->argument('file'));
        $contents = file_get_contents($envFilePath);

        $oldValue = static::getValue($contents, $key);


        static::set($key, $value, $envFilePath);


        if ($oldValue) {
            return $this->info("Environment variable with key '{$key}' has been changed from '{$oldValue}' to '{$value}'");
        }

        return $this->info("A new environment variable with key '{$key}' has been set to '{$value}'");
    }



    /**
     * Overwrite the contents of a file.
     *
     * @param string $path
     * @param string $contents
     * @return boolean
     */
    protected static function writeFile(string $path, string $contents): bool
    {
        $file = fopen($path, 'w');

        fwrite($file, $contents);

        return fclose($file);
    }

    /**
     * Get the old value of a given key from an environment file.
     *
     * @param string $envFile
     * @param string $key
     */
    public static function getValue(string $envFile, string $key): string
    {
        $contents = static::getFileContents($envFile, true);
        
        // Match the given key at the beginning of a line
        preg_match("/$key=(.*)$/", $contents, $matches);

        return $matches[1] ?? null;
    }
    
    public static function getFileContents($file, $create = false){
        if(!file_exists($file)){
            touch($file);
        }
        
        return file_get_contents($file);
    }
    

    /**
     * Determine what the supplied key and value is from the current command.
     *
     * @return array
     */
    protected function getKeyValue(): array
    {
        $key = $this->argument('key');
        $value = $this->argument('value');

        if (!$value) {
            $parts = explode('=', $key, 2);

            if (count($parts) !== 2) {
                throw new InvalidArgumentException('No value was set');
            }

            $key = $parts[0];
            $value = $parts[1];
        }

        if (!$this->isValidKey($key)) {
            throw new InvalidArgumentException('Invalid argument key');
        }

        if (!is_bool(strpos($value, ' '))) {
            $value = '"' . $value . '"';
        }

        return [strtoupper($key), $value];
    }

    /**
     * Check if a given string is valid as an environment variable key.
     *
     * @param string $key
     * @return boolean
     */
    protected static function validateKey(string $key): bool
    {
        if (str_contains($key, '=')) {
            throw new InvalidArgumentException("Environment key should not contain '='");
        }

        if (!preg_match('/^[a-zA-Z_]+$/', $key)) {
            throw new InvalidArgumentException('Invalid environment key. Only use letters and underscores');
        }

        return true;
    }

    public static function set($key, $value, $file_path)
    {
        static::validateKey($key);

        return static::updateOrCreate($key, $value, $file_path);
    }

    public static function updateOrCreate($key, $value, $file_path)
    {
        $contents = file_get_contents($file_path);
        $oldValue = static::getValue($file_path, $key);



        if (!is_null($oldValue)) {

            $contents = str_replace("{$key}={$oldValue}", "{$key}={$value}", $contents);

        } else {

            $contents = $contents . "\n{$key}={$value}\n";

        }


        return static::writeFile($file_path, $contents);
    }

    public static function clear($key, $file){
        static::set($key, null, $file);

    }


}
