@extends('uc-color-run.layout', ['bodyClass' => 'page-regulation'])

@section('content')
<div class="logo">LOGO</div>

<section class="panel panel-doc">
    <h1 class="subtitle">{{ $pageTitle }}</h1>
    <p class="lead lead-narrow">{{ config('uc-color-run.lorem') }}</p>
    <hr class="divider">

    <div class="doc-body">
        @foreach ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>
</section>
@endsection
