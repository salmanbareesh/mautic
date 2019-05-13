<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc. Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 * @created     12.9.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Cache\Simple\Psr6Cache;

/**
 * Class CacheProvider provides caching mechanism using adapters, it provides both PSR-6 and PSR-16.
 */
final class CacheProvider implements TagAwareAdapterInterface
{
    /**
     * @var TagAwareAdapterInterface
     */
    private $adapter;

    /**
     * @var Psr6Cache
     */
    private $psr16;

    public function setCacheAdapter(TagAwareAdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        if ($this->adapter instanceof PruneableInterface) {
            $this->adapter->prune();
        }
    }

    /**
     * @return TagAwareAdapterInterface
     */
    public function getCacheAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return Psr6Cache
     */
    public function getSimpleCache()
    {
        if (is_null($this->psr16)) {
            $this->psr16 = new Psr6Cache($this->getCacheAdapter());
        }

        return $this->psr16;
    }

    /**
     * @param $key
     *
     * @return CacheItem
     *
     * @throws InvalidArgumentException
     */
    public function getItem($key)
    {
        return $this->getCacheAdapter()->getItem($key);
    }

    /**
     * @return CacheItem[]|\Traversable
     *
     * @throws InvalidArgumentException
     */
    public function getItems(array $keys = [])
    {
        return $this->getCacheAdapter()->getItems($keys);
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    public function hasItem($key)
    {
        return $this->getCacheAdapter()->hasItem($key);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->getCacheAdapter()->clear();
    }

    public function deleteItem($key)
    {
        return $this->getCacheAdapter()->deleteItem($key);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *                       An array of keys that should be removed from the pool
     *
     * @throws invalidArgumentException
     *                                  If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *                                  MUST be thrown
     *
     * @return bool
     *              True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        return $this->getCacheAdapter()->deleteItems($keys);
    }

    /**
     * Persists a cache item immediately.
     *
     * @param cacheItemInterface $item
     *                                 The cache item to save
     *
     * @return bool
     *              True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        return $this->getCacheAdapter()->save($item);
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param cacheItemInterface $item
     *                                 The cache item to save
     *
     * @return bool
     *              False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->getCacheAdapter()->saveDeferred($item);
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *              True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        return $this->getCacheAdapter()->commit();
    }

    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @return bool True on success
     *
     * @throws InvalidArgumentException When $tags is not valid
     */
    public function invalidateTags(array $tags)
    {
        return $this->getCacheAdapter()->invalidateTags($tags);
    }
}
