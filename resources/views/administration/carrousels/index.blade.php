@extends('layouts.app')
@section('content')
    <div class="poa-container mb-8">
        <h2>Aprobación de objetos de aprendizaje en el slider principal</h2>

        <form id="big-courses-carrousels-form">
            @csrf

            @if ($coursesSlider->isEmpty() && $educationalProgramsSlider->isEmpty())
                <div class="alert alert-info">No hay cursos para mostrar</div>
            @else
                @foreach ($coursesSlider as $course)
                    <div class="checkbox mb-2">
                        <label for="big_{{ $course->uid }}" class="inline-flex relative items-center cursor-pointer">
                            <input type="checkbox" data-type="course" id="big_{{ $course->uid }}" name="{{ $course->uid }}"
                                {{ $course->featured_big_carrousel_approved ? 'checked' : '' }} class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>
                            <div class="checkbox-name">{{ $course->title }}</div>
                        </label>
                    </div>
                @endforeach

                @foreach ($educationalProgramsSlider as $educationalProgram)
                    <div class="checkbox mb-2">
                        <label for="big_{{ $educationalProgram->uid }}"
                            class="inline-flex relative items-center cursor-pointer">
                            <input type="checkbox" data-type="educational_program" id="big_{{ $educationalProgram->uid }}"
                                name="{{ $educationalProgram->uid }}"
                                {{ $educationalProgram->featured_slider_approved ? 'checked' : '' }} class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>
                            <div class="checkbox-name">{{ $educationalProgram->name }}</div>
                        </label>
                    </div>
                @endforeach
            @endif

            <button type="submit" class="btn btn-primary mt-4">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>
        </form>
    </div>

    <div class="poa-container">
        <h2>Aprobación de objetos de aprendizaje en el carrousel principal</h2>

        @if ($coursesCarrousel->isEmpty() && $educationalProgramsCarrousel->isEmpty())
            <div class="alert alert-info">No hay objetos de aprendizaje para mostrar</div>
        @else
            <form id="small-courses-carrousels-form">
                @csrf
                @foreach ($coursesCarrousel as $course)
                    <div class="checkbox mb-2">
                        <label for="small_{{ $course->uid }}" class="inline-flex relative items-center cursor-pointer">
                            <input type="checkbox" data-type="course" id="small_{{ $course->uid }}" name="{{ $course->uid }}"
                                {{ $course->featured_small_carrousel_approved ? 'checked' : '' }} class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>
                            <div class="checkbox-name">{{ $course->title }}</div>
                        </label>
                    </div>
                @endforeach

                @foreach ($educationalProgramsCarrousel as $educationalProgram)
                    <div class="checkbox mb-2">
                        <label for="small_{{ $educationalProgram->uid }}"
                            class="inline-flex relative items-center cursor-pointer">
                            <input type="checkbox" data-type="educational_program" id="small_{{ $educationalProgram->uid }}"
                                name="{{ $educationalProgram->uid }}"
                                {{ $educationalProgram->featured_main_carrousel_approved ? 'checked' : '' }}
                                class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>
                            <div class="checkbox-name">{{ $educationalProgram->name }}</div>
                        </label>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary mt-4">Guardar
                    {{ eHeroicon('paper-airplane', 'outline') }}</button>
            </form>
        @endif

    </div>
@endsection
