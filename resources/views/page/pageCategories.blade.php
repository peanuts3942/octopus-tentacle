@extends('app')

@section('content')

<main class="wrapper" id="page-categories">

    <x-breadcrumb :items="[
        ['name' => t__('navigation.home'), 'url' => route('home')],
        ['name' => t__('pages.categories.title'), 'url' => '#']
    ]" />

    <div class="head-title-container">
        <h1 class="head-title">{{ $textseo->categories->h1 ?? t__('pages.categories.title') }}</h1>
        <h2 class="head-title-sub">{{ $textseo->categories->h2 ?? '' }}</h2>
    </div>

    <div class="tags-grid">
        @foreach ($categories as $category)
            <a class="tag-name trans" href="{{ route('category.show', ['slug' => $category->slug]) }}">
                <h3 class="tag-name-text trans">{{ $category->name }}</h3>
            </a>
        @endforeach
    </div>

</main>

@endsection
