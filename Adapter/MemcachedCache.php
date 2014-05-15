<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Cache\Adapter;

use Sonatra\Component\Cache\CacheElement;
use Sonatra\Component\Cache\Counter;

/**
 * Memcached Cache Adapter.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 *
 */
class MemcachedCache extends AbstractCache
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var \Memcached
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param string       $prefix  A prefix to avoid clash between instances
     * @param array<array> $servers The list of server (host, port and weight)
     */
    public function __construct($prefix, array $servers)
    {
        $this->prefix = $prefix;
        $this->client = new \Memcached();

        if (!array_key_exists(0, $servers)) {
            $servers = array($servers);
        }

        $this->client->addServers($servers);
    }

    /**
     * Gets the Memcached client.
     *
     * @return \Memcached
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = CacheElement::HOUR)
    {
        $key = $this->getCacheKey($key);
        $ttl = $ttl + ($ttl > 2592000 ? time() : 0);
        $createAt = new \DateTime();
        $element = new CacheElement($key, $value, $ttl, $createAt);

        $this->client->add($key, $element, $ttl);

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $keyPrefixed = $this->getCacheKey($key);
        $element = $this->client->get($keyPrefixed);

        if ($element instanceof CacheElement && !$element->isExpired()) {
            return $element;
        }

        return $this->createInvalidElement($key);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        $key = $this->getCacheKey($key);

        return false !== $this->client->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($key)
    {
        return $this->client->delete($this->getCacheKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll($prefix = null)
    {
        if (null === $prefix) {
            return $this->client->flush();
        }

        $success = true;
        $list = $this->client->getAllKeys();

        foreach ($list as $keyPrefixed) {
            if (0 === strpos($keyPrefixed, $this->prefix . $prefix)) {
                $res = $this->client->delete($keyPrefixed);
                $success = !$res && $success ? false : $success;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function setCounter(Counter $counter)
    {
        $key = $this->getCacheKey($counter->getName());

        $this->client->add($key, $counter->getValue());

        return $counter;
    }

    /**
     * {@inheritdoc}
     */
    public function getCounter($counter)
    {
        $key = $this->getCacheKey($counter);
        $value = 0;
        $rValue = $this->client->get($key);

        if (false !== $rValue) {
            $value = $rValue;
        }

        return new Counter($counter, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment($counter, $value = 1)
    {
        $counter = $this->transformCounter($counter);
        $key = $this->getCacheKey($counter->getName());

        $value = (int) $this->client->increment($key, $value);

        return new Counter($counter->getName(), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement($counter, $value = 1)
    {
        $counter = $this->transformCounter($counter);
        $key = $this->getCacheKey($counter->getName());

        $value = (int) $this->client->decrement($key, $value);

        return new Counter($counter->getName(), $value);
    }

    /**
     * Gets the cache key.
     *
     * @param string $key The cache key
     *
     * @return string
     */
    protected function getCacheKey($key)
    {
        return sprintf('%s%s', $this->prefix, $key);
    }
}