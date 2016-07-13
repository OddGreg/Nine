<?php namespace Nine\Collections;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use josegonzalez\Dotenv\Loader;
use Nine\Collections\Exceptions\InvalidEnvironmentKeyException;
use Nine\Collections\Interfaces\EnvironmentInterface;

class Environment implements EnvironmentInterface
{
    /** @var array */
    private $detectedEnvironment;

    /** @var Loader  */
    private $dotenv;

    /** @var string */
    private $environment;

    /** @var string */
    private $environmentKey;

    /**
     * Environment constructor.
     *
     * @param string $dotEnvPath
     * @param string $key
     */
    public function __construct(string $dotEnvPath = '.env', string $key = 'APP_ENV')
    {
        // Environment depends on the feature set of josegonzalez\dotenv.
        $this->dotenv = new Loader($dotEnvPath);
        $this->dotenv->parse()->toEnv(TRUE);

        $this->environmentKey = $key;
        $this->environment = $this->queryEnv($key);
    }

    /**
     * Get the current environment settings.
     *
     * > note: uses env() function from 'Nine/Library/helpers.php'.
     *
     * @return array - the environment settings data.
     * @throws InvalidEnvironmentKeyException
     */
    public function detectEnvironment() : array
    {
        if ( ! $this->has($this->environmentKey)) {
            throw new InvalidEnvironmentKeyException("Base environment setting ({$this->environmentKey}) not found.");
        }

        $this->detectedEnvironment = [
            'developing' => env('APP_ENV', 'PRODUCTION') !== 'PRODUCTION',
            'app_key'    => env('APP_KEY', '$invalid$this&key%must#be@changed'),
            'debugging'  => env('DEBUG', FALSE),
            'testing'    => env('TESTING', FALSE),
        ];

        return $this->detectedEnvironment;
    }

    /**
     * Get an item from storage by key.
     *
     * @param string $key - environment key; use '*' to get the entire environment.
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = NULL)
    {
        return $key === '*' ? $this->dotenv->toArray() : $this->queryEnv($key, $default);
    }

    /**
     * @return string
     */
    public function getEnvironmentKey()
    {
        return $this->environmentKey;
    }

    /**
     * Determine if an item exists by key.
     *
     * @param  mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        return NULL !== env($key, NULL);
    }

    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    private function queryEnv($key, $default = NULL)
    {

        // first check the internal registry
        if (isset($this->detectedEnvironment[$key])) {
            return $this->detectedEnvironment[$key];
        }

        $value = getenv($key);

        if ($value === FALSE) {
            return $this->resolveValue($default);
        }

        return $this->translateValue($this->stripBoundingQuotes($value));

    }

    /**
     *  Returns value of a variable. Resolves closures.
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    private function resolveValue($value)
    {
        return $value instanceof \Closure || is_callable($value) ? $value() : $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    private function stripBoundingQuotes($value)
    {
        return (strlen($value) > 1 && preg_match('/"/', $value)) ? substr($value, 1, -1) : $value;
    }

    /**
     * @param $value
     *
     * @return bool|null|string
     */
    private function translateValue($value)
    {
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return TRUE;

            case 'false':
            case '(false)':
                return FALSE;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return NULL;
        }
    }

}
