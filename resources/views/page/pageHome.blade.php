@extends('app')

@section('content')

<main class="wrapper" id="page-home">

    <div class="head-title-container">
        <h1 class="head-title">{{ $h1 ?? 'Videos' }}</h1>
        <h2 class="head-title-sub">{{ $h2 ?? 'Discover our video collection' }}</h2>
    </div>

    <div class="videos-grid">
        @foreach ($videos as $video)
            @include('components.cardVideo', ['video' => $video])
        @endforeach
    </div>

    @if(method_exists($videos, 'links'))
        @include('components.pagination', ['paginator' => $videos])
    @endif

</main>

@endsection
