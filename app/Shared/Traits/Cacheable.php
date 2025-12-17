<?php

namespace App\Shared\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Cacheable Trait
 *
 * Provides caching helpers for Eloquent models.
 * Implements the application's caching strategy with consistent key patterns.
 *
 * Usage:
 * ```php
 * class Workout extends Model
 * {
 *     use Cacheable;
 *
 *     protected int $cacheTtl = 3600; // Optional: Override default TTL
 * }
 *
 * // In code:
 * $workout->cacheKey(); // "workout:uuid"
 * $workout->remember('key', fn() => $expensiveOperation);
 * $workout->invalidateCache();
 * ```
 *
 * Cache key pattern: {entity}:{id}
 */
trait Cacheable
{
    /**
     * Default cache TTL in seconds (1 hour).
     * Override in model: protected int $cacheTtl = 7200;
     */
    protected int $defaultCacheTtl = 3600;

    /**
     * Generate the primary cache key for this model instance.
     *
     * Pattern: {entity}:{id}
     * Example: "workout:550e8400-e29b-41d4-a716-446655440000"
     *
     * @return string
     */
    public function cacheKey(): string
    {
        return sprintf('%s:%s', $this->getCachePrefix(), $this->getKey());
    }

    /**
     * Generate a custom cache key for this model with a suffix.
     *
     * Pattern: {entity}:{id}:{suffix}
     * Example: "workout:uuid:compatible_gyms"
     *
     * @param string $suffix
     * @return string
     */
    public function cacheKeyWith(string $suffix): string
    {
        return sprintf('%s:%s', $this->cacheKey(), $suffix);
    }

    /**
     * Generate a cache key for a collection or query result.
     *
     * Pattern: {entity}:{filter}
     * Example: "trainer:uuid:workouts"
     *
     * @param string $filter
     * @return string
     */
    public static function collectionCacheKey(string $filter): string
    {
        $prefix = (new static)->getCachePrefix();
        return sprintf('%s:%s', $prefix, $filter);
    }

    /**
     * Remember a value in cache with automatic key generation.
     *
     * @param string $keySuffix
     * @param \Closure $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $keySuffix, \Closure $callback, ?int $ttl = null): mixed
    {
        $key = $this->cacheKeyWith($keySuffix);
        $ttl = $ttl ?? $this->getCacheTtl();

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Store a value in cache with automatic key generation.
     *
     * @param string $keySuffix
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function cacheValue(string $keySuffix, mixed $value, ?int $ttl = null): bool
    {
        $key = $this->cacheKeyWith($keySuffix);
        $ttl = $ttl ?? $this->getCacheTtl();

        return Cache::put($key, $value, $ttl);
    }

    /**
     * Retrieve a value from cache.
     *
     * @param string $keySuffix
     * @param mixed $default
     * @return mixed
     */
    public function getCached(string $keySuffix, mixed $default = null): mixed
    {
        $key = $this->cacheKeyWith($keySuffix);
        return Cache::get($key, $default);
    }

    /**
     * Invalidate all cache entries for this model instance.
     * Removes the primary cache key and common suffixes.
     *
     * @param array $additionalSuffixes Additional key suffixes to invalidate
     * @return void
     */
    public function invalidateCache(array $additionalSuffixes = []): void
    {
        // Invalidate primary key
        Cache::forget($this->cacheKey());

        // Invalidate common suffixes
        $commonSuffixes = ['relationships', 'computed', 'exercises', 'gyms'];
        $allSuffixes = array_merge($commonSuffixes, $additionalSuffixes);

        foreach ($allSuffixes as $suffix) {
            Cache::forget($this->cacheKeyWith($suffix));
        }
    }

    /**
     * Invalidate cache for a collection query.
     *
     * @param string $filter
     * @return void
     */
    public static function invalidateCollectionCache(string $filter): void
    {
        Cache::forget(static::collectionCacheKey($filter));
    }

    /**
     * Get the cache prefix for this model.
     * Uses snake_case of the model class name.
     *
     * @return string
     */
    protected function getCachePrefix(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Get the cache TTL for this model.
     *
     * @return int
     */
    protected function getCacheTtl(): int
    {
        return $this->cacheTtl ?? $this->defaultCacheTtl;
    }

    /**
     * Boot the trait.
     * Automatically invalidate cache on model updates and deletes.
     */
    protected static function bootCacheable(): void
    {
        static::updated(function ($model) {
            if (method_exists($model, 'invalidateCache')) {
                $model->invalidateCache();
            }
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'invalidateCache')) {
                $model->invalidateCache();
            }
        });
    }
}
