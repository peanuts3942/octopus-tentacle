<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PlayerAdOverlay extends Component
{
    public bool $isPremium;

    public array $items;

    public function __construct(bool $isPremium = false)
    {
        $this->isPremium = $isPremium;
        $this->items = preroll_items();
    }

    public function render(): View|Closure|string
    {
        return view('components.player-ad-overlay', [
            'isPremium' => $this->isPremium,
            'items' => $this->items,
        ]);
    }
}
