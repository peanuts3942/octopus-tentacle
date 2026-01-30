# Cache et Traductions - Octopus Tentacle

Ce document décrit le système de cache Redis et le système de traductions implémenté dans octopus-tentacle.

---

## 1. Système de Cache Redis

### 1.1 Cache View (HTML complet)

Cache des pages rendues complètes. TTL : **5 minutes**.

Uniquement stocké pour :
- Page 1 (pas de pagination)
- Requêtes non-AJAX

| Route | Clé |
|-------|-----|
| Home | `cache:view:page:pageHome` |
| Video | `cache:view:page:pageVideo:{id}` |
| Models index | `cache:view:page:pageModels` |
| Model show | `cache:view:page:pageModel:{slug}` |
| Categories index | `cache:view:page:pageCategories` |
| Category show | `cache:view:page:pageCategory:{slug}` |

### 1.2 Cache Data (requêtes Eloquent)

Cache des données sérialisées. TTL : **1 heure**.

| Donnée | Clé |
|--------|-----|
| All videos (paginated) | `cache:data:allVideos:page:{page}` |
| Single video | `cache:data:video:{id}` |
| Related videos | `cache:data:relatedVideos:{id}:page:{page}` |
| All models | `cache:data:allModels` |
| Model | `cache:data:model:{slug}` |
| Model videos | `cache:data:model:{id}:videos:page:{page}` |
| All categories | `cache:data:allCategories` |
| Category | `cache:data:category:{slug}` |
| Category videos | `cache:data:category:{id}:videos:page:{page}` |

### 1.3 Cache Traductions

| Donnée | Clé | TTL |
|--------|-----|-----|
| TextSEO | `textseo_formatted_{locale}` | 24h |
| Static texts | `static_texts_{locale}` | 24h |
| Translated routes | `translated_routes_{locale}` | 24h |
| JS translations | `js_translations_{locale}` | 24h |

### 1.4 Utilisation Redis

```php
use Illuminate\Support\Facades\Redis;

// Lecture
$cached = Redis::get($cacheKey);
if ($cached) {
    $data = unserialize($cached);
}

// Écriture
Redis::setex($cacheKey, $ttl, serialize($data));

// Pour les vues (pas de serialize)
Redis::setex($viewCacheKey, $ttl, $htmlContent);
```

### 1.5 Vider le cache

```bash
# Accéder au Redis CLI
docker exec -it octopus-tentacle-redis redis-cli -a 123456789

# Voir toutes les clés
keys *

# Supprimer une clé spécifique
del "cache:view:page:pageHome"

# Supprimer par pattern
eval "for _,k in ipairs(redis.call('keys','cache:view:*')) do redis.call('del',k) end" 0
eval "for _,k in ipairs(redis.call('keys','cache:data:*')) do redis.call('del',k) end" 0

# Tout vider
flushall
```

---

## 2. Système de Traductions

### 2.1 Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                         TranslationHelper                    │
│  app/Helpers/TranslationHelper.php                          │
├─────────────────────────────────────────────────────────────┤
│  - getSeoTexts($locale)     → $textseo object               │
│  - getAll($locale)          → static texts array            │
│  - get($key, $locale)       → single translation            │
│  - getRoutes($locale)       → route segments array          │
│  - getJsTranslations()      → JS translations array         │
└─────────────────────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────┐
│                      Helper Functions                        │
│  app/helpers.php                                            │
├─────────────────────────────────────────────────────────────┤
│  t__($key, $replace, $locale)     → texte traduit           │
│  route_trans($key, $locale)       → segment route traduit   │
│  seo_meta($textseo, $page, $field, $replacements)           │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 TextSEO (SEO dynamique)

**Tables :** `textseo` + `textseo_translations`

**Modèles :**
- `App\Models\TextSeo`
- `App\Models\TextSeoTranslation`

**Champs par page :**
- `h1` - Titre principal
- `h2` - Sous-titre
- `meta_title` - Titre SEO
- `meta_description` - Description SEO

**Injection globale via ViewComposer :**

```php
// app/Providers/TextSeoServiceProvider.php
View::composer('*', function ($view) {
    $textseo = TranslationHelper::getSeoTexts($locale);
    $view->with('textseo', $textseo);
});
```

**Utilisation dans les vues :**

```blade
{{-- Accès direct --}}
<h1>{{ $textseo->home->h1 }}</h1>

{{-- Avec remplacement de variables --}}
{{ seo_meta($textseo, 'video', 'meta_title', ['name' => $video->title]) }}
```

**Remplacement automatique :**
- Toutes les occurrences de "BornToBeFuck" sont remplacées par `site_name` du tentacle au moment du cache.

### 2.3 Static Texts (textes UI)

**Table :** `static_texts`

**Modèle :** `App\Models\StaticText`

**Structure :**
```php
[
    'key' => 'navigation.home',
    'group' => 'navigation',
    'translations' => ['fr' => 'Accueil', 'en' => 'Home'],
    'description' => 'Lien vers la page d\'accueil'
]
```

**Utilisation :**

```blade
{{-- Simple --}}
{{ t__('navigation.home') }}

{{-- Avec remplacement --}}
{{ t__('video.views_count', ['count' => $video->views]) }}

{{-- Dans du PHP --}}
@php
    $message = t__('common.welcome');
@endphp
```

**Fallback chain :** locale demandée → 'en' → clé

### 2.4 Route Translations (segments URL)

**Table :** `translated_routes`

**Modèle :** `App\Models\TranslatedRoute`

**Utilisation :**

```php
$segment = route_trans('categories'); // 'categories' en EN, 'categories' en FR
```

### 2.5 JS Translations

**Injection dans le layout :**

```blade
{{-- resources/views/partials/js-translations.blade.php --}}
<script>
    window.translations = @json($jsTranslations);
    window.t = function(key) {
        return window.translations[key] || key;
    };
</script>
```

**Utilisation en JavaScript :**

```javascript
const label = window.t('time.today'); // "Aujourd'hui" ou "Today"
```

---

## 3. Traductions des Modèles

### 3.1 Channel Translations

**Table :** `channel_translations`

**Modèle :** `App\Models\ChannelTranslation`

**Champs :**
- `channel_id`
- `locale`
- `short_description`
- `long_description`
- `alt_thumbnail`
- `alt_banner`

**Relations dans Channel :**

```php
// Toutes les traductions
public function translations(): HasMany
{
    return $this->hasMany(ChannelTranslation::class);
}

// Traduction courante (avec fallback)
public function translation(): HasOne
{
    $locale = app()->getLocale();
    return $this->hasOne(ChannelTranslation::class)
        ->where('locale', $locale)
        ->withDefault(fn () => $this->translations()->first());
}
```

**Utilisation :**

```blade
{{-- Traduction courante --}}
{{ $channel->translation->short_description }}

{{-- Première traduction disponible --}}
{{ $channel->translations->first()->short_description }}
```

### 3.2 Video Translations

**Table :** `video_translations`

**Relation :** `$video->translation`

**Champs :** `title`, `slug`, `short_description`, `long_description`

### 3.3 Tag Translations

**Table :** `tag_translations`

**Relation :** `$tag->translation`

**Champs :** `name`, `slug`, `description`

---

## 4. Configuration

### 4.1 Redis (.env)

```env
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=123456789
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=0
REDIS_PREFIX=
CACHE_PREFIX=
```

### 4.2 Providers (bootstrap/providers.php)

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TextSeoServiceProvider::class,
];
```

### 4.3 Autoload (composer.json)

```json
{
    "autoload": {
        "files": [
            "app/helpers.php"
        ]
    }
}
```

---

## 5. Fichiers clés

| Fichier | Description |
|---------|-------------|
| `app/Helpers/TranslationHelper.php` | Logique centrale des traductions et cache |
| `app/helpers.php` | Fonctions globales `t__()`, `route_trans()`, `seo_meta()` |
| `app/Providers/TextSeoServiceProvider.php` | Injection de `$textseo` dans les vues |
| `resources/views/partials/js-translations.blade.php` | Traductions JS |
| `app/Models/TextSeo.php` | Modèle TextSEO |
| `app/Models/StaticText.php` | Modèle textes statiques |
| `app/Models/TranslatedRoute.php` | Modèle routes traduites |
| `app/Models/ChannelTranslation.php` | Traductions channels |

---

## 6. Breadcrumbs

Les breadcrumbs utilisent `t__()` pour les textes statiques :

```blade
<x-breadcrumb :items="[
    ['name' => t__('navigation.home'), 'url' => route('home')],
    ['name' => t__('pages.models.title'), 'url' => route('model.index')],
    ['name' => $channel->name, 'url' => '#']
]" />
```

---

## 7. Dates traduites

Utiliser `translatedFormat()` de Carbon pour les dates localisées :

```blade
{{ $video->published_at?->translatedFormat('j F Y') }}
{{-- Résultat : "27 janvier 2026" (FR) ou "January 27, 2026" (EN) --}}
```
