@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">
        <h2>Carriles a mostrar</h2>

        <p>Establece los carriles que deseas que el usuario visualice en el front.</p>

        <form id="lanes-show-form">
            <div class="checkbox mb-2 mt-4">
                <label for="lane_featured_courses" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['lane_featured_courses'] ? 'checked' : '' }} type="checkbox"
                        id="lane_featured_courses" name="lane_featured_courses" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Cursos destacados</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="lane_featured_educationals_programs"
                    class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['lane_featured_educationals_programs'] ? 'checked' : '' }} type="checkbox"
                        id="lane_featured_educationals_programs" name="lane_featured_educationals_programs" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Programas formativos destacados</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="lane_recents_educational_resources"
                    class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['lane_recents_educational_resources'] ? 'checked' : '' }} type="checkbox"
                        id="lane_recents_educational_resources" name="lane_recents_educational_resources" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Recursos educativos destacados</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="lane_featured_itineraries" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['lane_featured_itineraries'] ? 'checked' : '' }} type="checkbox"
                        id="lane_featured_itineraries" name="lane_featured_itineraries" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Recomendación de itinerarios</div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>

        </form>
    </div>
@endsection
