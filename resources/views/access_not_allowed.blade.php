@extends('layouts.app')
@section('content')
    <div class="poa-container">
        <div class="alert alert-info">
            <div class="flex-none w-6">
                {{ eHeroicon('exclamation-circle', 'outline') }}
            </div>
            <div class="flex-grow">
                <h3>{{ $title }}</h3>
                <p>{{ $description }}</p>
            </div>
        </div>
    </div>
@endsection
