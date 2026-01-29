@extends('app')

@section('content')

<main class="wrapper" id="page-categories">

    <x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'All Categories', 'url' => '#']
    ]" />

    <div class="head-title-container">
        <h1 class="head-title">{{ $h1 ?? 'Categories' }}</h1>
        <h2 class="head-title-sub">{{ $h2 ?? 'Browse all categories' }}</h2>
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
