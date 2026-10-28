@php
    $site = config('uc-color-run.site');
    $categories = config('uc-color-run.categories');
    $categoryKeys = array_keys($categories);
    $defaultCategory = $categoryKeys[0];
@endphp
@extends('uc-color-run.layout', ['pageTitle' => 'Race Category', 'bodyClass' => 'page-category'])

@section('content')
<section class="category" data-categories='@json($categories)'>
    <header class="section-head">
        <h1 class="title">RACE CATEGORY</h1>
        <p class="lead">{{ config('uc-color-run.lorem') }} Suspendisse ac nunc sed massa aliquet porttitor. Donec ut volutpat tortor. Donec aliquam felis sed enim hendrerit, sed porttitor neque venenatis. In hac habitasse platea dictumst.</p>
    </header>

    <div class="cat-grid" role="tablist">
        @foreach ($categoryKeys as $key)
            <button type="button"
                    class="cat-card{{ $key === $defaultCategory ? ' is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $key === $defaultCategory ? 'true' : 'false' }}"
                    data-key="{{ $key }}">
                <span class="cat-head">UC COLOR RUN</span>
                <span class="cat-body">
                    <span class="cat-img">GAMBAR<br>KALAU<br>ADA</span>
                    <span class="cat-label">{{ $key }}</span>
                </span>
            </button>
        @endforeach
    </div>

    <div class="info-grid">
        <article class="box">
            <h2 class="box-head">REGISTRATION FEE</h2>
            <div class="box-body fee-list" id="fee-list">
                @foreach ($categories[$defaultCategory]['fee'] as $fee)
                    <p>{{ $fee['label'] }}<br>Rp. {{ number_format($fee['price'], 0, ',', '.') }}</p>
                @endforeach
            </div>
        </article>

        <article class="box">
            <h2 class="box-head">RACE INFO</h2>
            <div class="box-body" id="race-info">
                <p>Race Day<br>{{ $site['date_label'] }}</p>
                <p>{!! implode('<br>', array_map('e', $categories[$defaultCategory]['info'])) !!}</p>
            </div>
        </article>
    </div>

    <div class="route" id="route">
        <span>RUTE LARI</span>
    </div>

    <div class="center">
        <a class="btn btn-pill" href="{{ $site['register_url'] }}">REGISTER NOW</a>
    </div>
</section>
@endsection
