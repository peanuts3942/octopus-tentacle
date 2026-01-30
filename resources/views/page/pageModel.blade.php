@extends('app')

@section('content')

<main class="wrapper" id="page-model">

    <x-breadcrumb :items="[
        ['name' => t__('navigation.home'), 'url' => route('home')],
        ['name' => t__('pages.models.title'), 'url' => route('model.index')],
        ['name' => $channel->name, 'url' => '#']
    ]" />

    <div class="head-title-container">
        <h1 class="head-title">{{ $channel->name }}</h1>
        @if($channel->translations->isNotEmpty() && $channel->translations->first()->short_description)
        <h2 class="head-title-sub">{{ str_replace('<description>', $channel->translations->first()->short_description, $textseo->model->h2) }}</h2>
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
