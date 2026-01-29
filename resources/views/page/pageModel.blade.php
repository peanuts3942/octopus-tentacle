@extends('app')

@section('content')

<main class="wrapper" id="page-model">

    <x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'All Models', 'url' => route('model.index')],
        ['name' => $channel->name, 'url' => '#']
    ]" />

    <div class="head-title-container">
        <h1 class="head-title">{{ $channel->name }}</h1>
        @if(!empty($channel->description))
        <h2 class="head-title-sub">{{ $channel->description }}</h2>
        @endif
    </div>

    <div class="videos-grid">
        @foreach ($videos as $video)
            @include('components.cardVideo', ['video' => $video])
        @endforeach
    </div>

    @if(method_exists($videos, 'previousPageUrl'))
        @include('components.pagination-simple', ['paginator' => $videos])
    @endif

</main>

@endsection
