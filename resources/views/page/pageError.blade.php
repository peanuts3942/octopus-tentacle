@extends('app')

@section('content')


<main class="wrapper" id="page-error">

    <div class="heading-container-error">
        <span class="heading-text">{{ $errorTitle }}</span>
        <p class="error-description">{{ $errorDescription }}</p>
    </div>

    <div class="videos-grid">
        @foreach ($videos as $video)
            @include('components.cardVideo', ['video' => $video])
        @endforeach
    </div>

    @include('components.pagination-simple', ['paginator' => $videos])

</main>

@endsection
