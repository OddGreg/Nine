<?php namespace Nine\Collections;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Dotenv\Dotenv;
use Nine\Collections\Exceptions\InvalidEnvironmentKeyException;
use Nine\Collections\Interfaces\EnvironmentInterface;

class Environment implements EnvironmentInterface
{
    /** @var array */
    private $detectedEnvironment;

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
    public function __construct(string $dotEnvPath, string $key = 'APP_ENV')
    {
        // Environment depends on the feature set of josegonzalez\dotenv.
        (new Dotenv($dotEnvPath))->overload();

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
            'developing' => $this->get('APP_ENV', 'PRODUCTION') !== 'PRODUCTION',
            'app_key'    => $this->get('APP_KEY', '$invalid$this&key%must#be@changed'),
            'debugging'  => $this->get('DEBUG', FALSE),
            'testing'    => $this->get('TESTING', FALSE),
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
        return $key === '*' ? $this->detectedEnvironment : $this->queryEnv($key, $default);
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
        return NULL !== env(strtoupper($key), NULL);
        //return NULL !== $this->queryEnv($key, NULL);
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

        return $value;
    }

}
