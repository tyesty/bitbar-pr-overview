<?php
declare(strict_types=1);

namespace Bitbar;

class Cache
{
    /**
     * @var string[]
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * Cache constructor.
     *
     * Sets the cache file and loads the cache
     *
     * @param string $cacheFile The path to the cache file.
     */
    public function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;

        if (file_exists($cacheFile)) {
            $this->cache = unserialize(file_get_contents($this->cacheFile));
        } else {
            touch($this->cacheFile);
            $this->cache = [];
        }
    }

    /**
     * Returns a user from cache
     *
     * @param string $id The id which identifies the user in cache.
     * @return User|null
     */
    public function getUser(string $id): ?User
    {
        return $this->cache['users'][$id];
    }

    /**
     * Sets a user in cache.
     *
     * @param User $user The user to be cached.
     * @return void
     */
    public function setUser(User $user): void
    {
        $this->cache['users'][$user->id] = $user;
        $this->persist();
    }

    /**
     * Persists the cache.
     *
     * @return void
     */
    private function persist(): void
    {
        file_put_contents($this->cacheFile, serialize($this->cache));
    }
}
