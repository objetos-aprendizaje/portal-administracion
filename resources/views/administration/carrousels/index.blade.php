@extends('layouts.app')
@section('content')
    <div class="poa-container mb-8">
        <h2>Aprobación de cursos en el slider principal</h2>

        <form id="big-courses-carrousels-form">
            @csrf

            @foreach ($courses_big_carrousel as $course)
                <div class="checkbox mb-2">
                    <label for="big_{{ $course['uid'] }}" class="inline-flex relative items-center cursor-pointer">
                        <input type="checkbox" id="big_{{ $course['uid'] }}" name="{{ $course['uid'] }}"
                            {{ in_array($course['uid'], $courses_big_carrousel_approved) ? 'checked' : '' }}
                            class="sr-only peer">
                        <div
                            class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                        </div>
                        <div class="checkbox-name">{{ $course['title'] }}</div>
                    </label>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary mt-4">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>
        </form>
    </div>

    <div class="poa-container">
        <h2>Aprobación de cursos en el carrousel principal</h2>
        <form id="small-courses-carrousels-form">
            @csrf

            @foreach ($courses_small_carrousel as $course)
                <div class="checkbox mb-2">
                    <label for="small_{{ $course['uid'] }}" class="inline-flex relative items-center cursor-pointer">
                        <input type="checkbox" id="small_{{ $course['uid'] }}" name="{{ $course['uid'] }}"
                            {{ in_array($course['uid'], $courses_small_carrousel_approved) ? 'checked' : '' }}
                            class="sr-only peer">
                        <div
                            class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                        </div>
                        <div class="checkbox-name">{{ $course['title'] }}</div>
                    </label>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary mt-4">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>
        </form>
    </div>
@endsection
