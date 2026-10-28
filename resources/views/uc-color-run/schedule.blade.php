@php($site = config('uc-color-run.site'))
@extends('uc-color-run.layout', ['pageTitle' => 'Race Schedule', 'bodyClass' => 'page-schedule'])

@section('content')
<div class="logo">LOGO</div>

<section class="panel">
    <h1 class="subtitle">Race Schedule</h1>
    <p class="lead lead-narrow">{{ config('uc-color-run.lorem') }}</p>

    <div class="countdown" id="countdown" data-target="{{ \Illuminate\Support\Carbon::parse($site['event_date'], 'Asia/Jakarta')->toIso8601String() }}">
        <div class="cd-item"><span class="cd-num" data-unit="days">00</span><span class="cd-label">Days</span></div>
        <span class="cd-sep">:</span>
        <div class="cd-item"><span class="cd-num" data-unit="hours">00</span><span class="cd-label">Hours</span></div>
        <span class="cd-sep">:</span>
        <div class="cd-item"><span class="cd-num" data-unit="minutes">00</span><span class="cd-label">Minutes</span></div>
        <span class="cd-sep">:</span>
        <div class="cd-item"><span class="cd-num" data-unit="seconds">00</span><span class="cd-label">Seconds</span></div>
    </div>

    <h2 class="cta-title">SEE YOU ON RACE DAY!</h2>
    <p class="cta-date">{{ $site['date_label'] }} &middot; {{ $site['name'] }}</p>
    <a class="btn btn-rect" href="{{ $site['register_url'] }}">Register Now</a>
</section>
@endsection
