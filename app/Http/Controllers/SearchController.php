<?php

namespace App\Http\Controllers;

use App\Services\SearchServices;
use App\Services\VideoServices;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchController extends Controller
{
    public function __construct(
        private SearchServices $searchServices,
        private VideoServices $videoServices
    ) {}

    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));
        $page = $request->get('page', 1);
        $tentacleId = config('app.tentacle_id');

        if (empty($query)) {
            return view('page.pageSearch', [
                'query' => '',
                'videos' => new LengthAwarePaginator([], 0, 24),
                'channels' => [],
                'tags' => [],
                'totalVideos' => 0,
                'hasNoResults' => false,
                'feedVideos' => null,
            ]);
        }

        // Search via service (Meilisearch + SQL fallback)
        $results = $this->searchServices->search($query, $page);

        // Create paginator for videos
        $videosPaginator = new LengthAwarePaginator(
            $results['videos'],
            $results['totalVideos'],
            $results['perPage'],
            $results['currentPage'],
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // If no video results, get feed videos for "More videos" section
        $feedVideos = null;
        $hasNoResults = $results['totalVideos'] === 0;

        if ($hasNoResults) {
            $feedVideos = $this->videoServices->getFeedVideos($tentacleId, 1, $request);
            // Recreate paginator with home page path (no query params)
            if ($feedVideos instanceof LengthAwarePaginator) {
                $feedVideos = new LengthAwarePaginator(
                    $feedVideos->items(),
                    $feedVideos->total(),
                    $feedVideos->perPage(),
                    $feedVideos->currentPage(),
                    ['path' => '/page']
                );
            }
        }

        return view('page.pageSearch', [
            'query' => $query,
            'videos' => $videosPaginator,
            'channels' => $results['channels'],
            'tags' => $results['tags'],
            'totalVideos' => $results['totalVideos'],
            'hasNoResults' => $hasNoResults,
            'feedVideos' => $feedVideos,
        ]);
    }
}
