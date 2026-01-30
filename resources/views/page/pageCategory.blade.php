@extends('app')

@section('content')

<main class="wrapper" id="page-category">

    <x-breadcrumb :items="[
        ['name' => t__('navigation.home'), 'url' => route('home')],
        ['name' => t__('pages.categories.title'), 'url' => route('category.index')],
        ['name' => $category->name, 'url' => '#']
    ]" />

    <div class="head-title-container">
        <h1 class="head-title">{{ $category->name }}</h1>
        <h2 class="head-title-sub">{{ $textseo->category->h2 ?? '' }}</h2>
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
