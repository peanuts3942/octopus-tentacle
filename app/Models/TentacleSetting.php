<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TentacleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tentacle_id',
        'name',
        'options',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tentacle(): BelongsTo
    {
        return $this->belongsTo(Tentacle::class);
    }

    /**
     * Récupère un setting avec cache Redis
     */
    public static function getCachedSetting(int $tentacleId, string $name): ?array
    {
        $cacheKey = "cache:tentacle:{$tentacleId}:setting:{$name}";

        try {
            $cachedSetting = Redis::get($cacheKey);

            if ($cachedSetting) {
                return json_decode($cachedSetting, true);
            }

            $setting = self::query()
                ->where('tentacle_id', $tentacleId)
                ->where('name', $name)
                ->where('is_active', true)
                ->first();

            if ($setting) {
                Redis::setex($cacheKey, 600, json_encode($setting->toArray()));

                return $setting->toArray();
            }

            return null;
        } catch (\Exception $e) {
            $setting = self::query()
                ->where('tentacle_id', $tentacleId)
                ->where('name', $name)
                ->where('is_active', true)
                ->first();

            return $setting?->toArray();
        }
    }

    /**
     * Récupère un setting avec filtrage par zone
     */
    public static function getCachedSettingWithZoneFilter(int $tentacleId, string $name, string $zone): ?array
    {
        $setting = self::getCachedSetting($tentacleId, $name);

        if (! $setting) {
            return null;
        }

        $filteredOptions = self::filterItemsByZoneAndDraft($setting['options'], $zone);

        return [
            'name' => $setting['name'],
            'options' => $filteredOptions,
            'description' => $setting['description'],
        ];
    }

    /**
     * Filtre les items selon la zone et le statut draft
     */
    public static function filterItemsByZoneAndDraft(array $options, string $zone): array
    {
        if (isset($options['items']) && is_array($options['items'])) {
            $validItems = array_filter($options['items'], function ($item) {
                if (isset($item['draft']) && $item['draft']) {
                    return false;
                }

                if (isset($item['isScheduledStart']) && $item['isScheduledStart'] && ! empty($item['scheduledStartTime'])) {
                    if (\Carbon\Carbon::parse($item['scheduledStartTime'])->isFuture()) {
                        return false;
                    }
                }

                if (isset($item['isScheduledEnd']) && $item['isScheduledEnd'] && ! empty($item['scheduledEndTime'])) {
                    if (\Carbon\Carbon::parse($item['scheduledEndTime'])->isPast()) {
                        return false;
                    }
                }

                return true;
            });

            $specificZoneItems = array_filter($validItems, function ($item) use ($zone) {
                return isset($item['zone']) && $item['zone'] === $zone;
            });

            if (! empty($specificZoneItems)) {
                $options['items'] = array_values($specificZoneItems);
            } else {
                $allZoneItems = array_filter($validItems, function ($item) {
                    return ! isset($item['zone']) || $item['zone'] === 'all';
                });
                $options['items'] = array_values($allZoneItems);
            }
        }

        return $options;
    }

    /**
     * Récupère les items filtrés pour un setting donné
     */
    public static function getFilteredItems(int $tentacleId, string $name, string $zone): array
    {
        $setting = self::getCachedSettingWithZoneFilter($tentacleId, $name, $zone);

        if (! $setting || ! isset($setting['options']['items'])) {
            return [];
        }

        return $setting['options']['items'];
    }

    /**
     * Récupère les paramètres de configuration pour un setting donné
     */
    public static function getSettings(int $tentacleId, string $name, string $zone): array
    {
        $setting = self::getCachedSettingWithZoneFilter($tentacleId, $name, $zone);

        if (! $setting || ! isset($setting['options']['settings'])) {
            return [];
        }

        return $setting['options']['settings'];
    }

    /**
     * Récupère un item aléatoire pour un setting donné
     */
    public static function getRandomItem(int $tentacleId, string $name, string $zone): ?array
    {
        $items = self::getFilteredItems($tentacleId, $name, $zone);

        if (empty($items)) {
            return null;
        }

        $randomIndex = array_rand($items);

        return $items[$randomIndex];
    }

    /**
     * Vérifie si un setting existe et est actif
     */
    public static function isSettingActive(int $tentacleId, string $name): bool
    {
        $setting = self::getCachedSetting($tentacleId, $name);

        return $setting !== null;
    }

    /**
     * Met à jour le cache Redis pour tous les settings d'un tentacle
     *
     * @return array Liste des settings mis en cache
     */
    public static function updateAllCache(int $tentacleId): array
    {
        try {
            $settings = self::query()
                ->where('tentacle_id', $tentacleId)
                ->where('is_active', true)
                ->get();

            $updatedSettings = [];

            foreach ($settings as $setting) {
                $cacheKey = "cache:tentacle:{$tentacleId}:setting:{$setting->name}";
                Redis::setex($cacheKey, 600, json_encode($setting->toArray()));
                $updatedSettings[] = $setting->name;
            }

            return $updatedSettings;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du cache des tentacle settings: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Invalide le cache pour un setting spécifique
     */
    public static function invalidateCache(int $tentacleId, string $name): bool
    {
        try {
            $cacheKey = "cache:tentacle:{$tentacleId}:setting:{$name}";

            return (bool) Redis::del($cacheKey);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'invalidation du cache: '.$e->getMessage());

            return false;
        }
    }
}
