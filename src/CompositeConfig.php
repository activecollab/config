<?php

namespace ActiveCollab\ConfigRepository;

use ActiveCollab\ConfigRepository\Providers\ArrayAdapter;
use Exception;

class CompositeConfig
{
    /**
     * Config list.
     *
     * @var array
     */
    protected $configs = [];

    /**
     * Name prefix.
     *
     * @var
     */
    protected $prefix;
    /**
     * separator.
     *
     * @var
     */
    protected $separator = '/';

    /**
     * CompositeConfig constructor.
     * @param array $configs
     * @throws Exception
     */
    public function __construct(array $configs)
    {
        foreach ($configs as $config) {
            if ($config instanceof ConfigRepository) {
                $this->configs[] = $config;
            } elseif (is_array($config)) {
                $provider = new ArrayAdapter($config);
                $this->configs[] = new ConfigRepository($provider);
            } else {
                throw new Exception('Invalid config');
            }
        }
    }

    /**
     * @param ConfigRepository $config
     */
    public function addConfig(ConfigRepository $config)
    {
        $this->configs[] = $config;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getGlobal($name)
    {
        return $this->find($name);
    }

    /**
     * @param string $name
     * @param string $user_email
     * @return string
     */
    public function getUser($name, $user_email)
    {
        $name = $user_email.$this->separator.$name;
        if ($this->prefix !== null) {
            $name = $this->prefix.$this->separator.$name;
        }

        return $this->find($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function get($name)
    {
        if ($this->prefix !== null) {
            $name = $this->prefix.$this->separator.$name;
        }

        return $this->find($name);
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function set($name, $value)
    {
        $exists = false;
        foreach ($this->configs as $config) {
            if ($config->exists($name)) {
                $config->set($name, $value);
                $exists = true;
            }
        }
        //-------------------------------------------
        //If name not exists set value to last config
        //-------------------------------------------
        if (!$exists && count($this->configs) > 0) {
            $this->configs[count($this->configs) - 1]->set($name, $value);
        }
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setGlobal($name, $value)
    {
        foreach ($this->configs as $config) {
            if ($config->isGlobal()) {
                $config->set($name, $value);
            }
        }
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $name
     * @param null|string $default
     *
     * @return string
     */
    protected function find($name, $default = null)
    {
        foreach ($this->configs as $config) {
            if ($config->exists($name)) {
                return $config->get($name);
            }
        }

        return $default;
    }
}
