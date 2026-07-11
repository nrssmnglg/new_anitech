@extends('layouts.guest')

@section('title', 'Under Maintenance')

@section('body')
<div class="flex min-h-screen items-center justify-center bg-stone-100 px-6 py-10">
    <div class="w-full max-w-md rounded-3xl border border-black/5 bg-white p-10 text-center shadow-[0_20px_50px_rgba(0,0,0,0.08)]">
        <img src="{{ asset('figures/anitech-logo-official.svg') }}" alt="AniTech logo" class="mx-auto mb-5 h-16 w-auto">
        <h1 class="text-3xl font-extrabold text-[#1B4D3E]">Under Maintenance</h1>
        <p class="mt-3 text-sm leading-7 text-stone-600">
            The site is temporarily unavailable. Please check back soon.
        </p>
    </div>
</div>
@endsection
