<?php

namespace ImLiam\EnvironmentSetCommand;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EnvironmentSetCommand extends Command
{
    public const COMMAND_NAME = 'env:set';
    public const ARGUMENT_KEY = 'key';
    public const ARGUMENT_VALUE = 'value';
    public const ARGUMENT_ENV_FILE = 'env_file';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature
        = self::COMMAND_NAME
        . '{' . self::ARGUMENT_KEY . ' : Key or key=value pair}'
        . '{' . self::ARGUMENT_VALUE . '? : Value}'
        . '{' . self::ARGUMENT_ENV_FILE . '? : Optional path to the .env file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set and save an environment variable in the .env file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            // Parse key and value arguments.
            [$key, $value] = $this->parseKeyValueArguments(
                $this->argument(self::ARGUMENT_KEY),
                $this->argument(self::ARGUMENT_VALUE)
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return;
        }

        $envFilePath = realpath($this->argument(self::ARGUMENT_ENV_FILE) ?? App::environmentFilePath());
        $content = file_get_contents($envFilePath);

        [$newEnvFileContent, $isNewVariableSet] = $this->setEnvVariable($content, $key, $value);
        if ($isNewVariableSet) {
            $this->info("A new environment variable with key '{$key}' has been set to '{$value}'");
        } else {
            $this->info("Environment variable with key '{$key}' has been updated to '{$value}'");
        }

        $this->writeFile($envFilePath, $newEnvFileContent);
    }

    /**
     * Set or update env-variable.
     *
     * @param string $envFileContent Content of the .env file.
     * @param string $key            Name of the variable.
     * @param string $value          Value of the variable.
     *
     * @return array [string newEnvFileContent, bool isNewVariableSet].
     */
    public function setEnvVariable(string $envFileContent, string $key, string $value): array
    {
        // For existed key.
        $oldKeyValuePair = $this->readKeyValuePair($envFileContent, $key);
        if ($oldKeyValuePair !== null) {
            return [str_replace($oldKeyValuePair, $key . '=' . $value, $envFileContent), false];
        }

        // For a new key.
        return [$envFileContent . "\n" . $key . '=' . $value . "\n", true];
    }

    /**
     * Read the "key=value" string of a given key from an environment file.
     * This function returns original "key=value" string and doesn't modify it.
     *
     * @param string $envFileContent
     * @param string $key
     *
     * @return string|null Key=value string or null if the key is not exists.
     */
    public function readKeyValuePair(string $envFileContent, string $key): ?string
    {
        // Match the given key at the beginning of a line
        if (preg_match("#^ *{$key} *= *[^\r\n]*$#imu", $envFileContent, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Determine what the supplied key and value is from the current command.
     *
     * @param string      $key
     * @param string|null $value
     *
     * @return string[]
     * @throws InvalidArgumentException
     */
    public function parseKeyValueArguments(string $key, ?string $value): array
    {
        // Parse "key=value" key argument.
        if ($value === null) {
            $parts = explode('=', $key, 2);
            if (count($parts) !== 2) {
                $key = $parts[0];
                $value = '';
            } else {
                [$key, $value] = $parts;
            }
        }

        $this->assertKeyIsValid($key);

        // If the value contains spaces but not is not enclosed in quotes.
        if (preg_match('#^[^\'"].*\s+.*[^\'"]$#um', $value)) {
            $value = '"' . $value . '"';
        }

        return [strtoupper($key), $value];
    }

    /**
     * Assert a given string is valid as an environment variable key.
     *
     * @param string $key
     *
     * @return bool Is key is valid.
     */
    public function assertKeyIsValid(string $key): bool
    {
        if (Str::contains($key, '=')) {
            throw new InvalidArgumentException('Invalid environment key ' . $key
                . "! Environment key should not contain '='");
        }

        if (!preg_match('/^[a-zA-Z_]+$/', $key)) {
            throw new InvalidArgumentException('Invalid environment key ' . $key
                . '! Only use letters and underscores');
        }

        return true;
    }

    /**
     * Overwrite the contents of a file.
     *
     * @param string $path
     * @param string $contents
     *
     * @return boolean
     */
    protected function writeFile(string $path, string $contents): bool
    {
        return (bool)file_put_contents($path, $contents, LOCK_EX);
    }
}
