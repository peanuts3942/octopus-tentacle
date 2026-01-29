@extends('app')

@section('content')

<main class="wrapper" id="page-models">

    <x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'All Models', 'url' => '#']
    ]" />

    <div class="head-title-container">
        <h1 class="head-title">{{ $h1 ?? 'Models' }}</h1>
        <h2 class="head-title-sub">{{ $h2 ?? 'Browse all models' }}</h2>
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
