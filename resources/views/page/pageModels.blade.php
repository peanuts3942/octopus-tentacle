@extends('app')

@section('content')

<main class="wrapper" id="page-models">

    <x-breadcrumb :items="[
        ['name' => t__('navigation.home'), 'url' => route('home')],
        ['name' => t__('pages.models.title'), 'url' => '#']
    ]" />

    <div class="head-title-container">
        <h1 class="head-title">{{ $textseo->models->h1 ?? t__('pages.models.title') }}</h1>
        <h2 class="head-title-sub">{{ $textseo->models->h2 ?? '' }}</h2>
    </div>

    <div class="tags-grid">
        @foreach ($channels as $channel)
            <a class="tag-name trans" href="{{ route('model.show', ['slug' => $channel->slug]) }}">
                <h3 class="tag-name-text trans">{{ $channel->name }}</h3>
            </a>
        @endforeach
    </div>

</main>

@endsection
