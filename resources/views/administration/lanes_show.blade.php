@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">
        <h2>Carriles a mostrar</h2>

        <p>Establece los carriles que deseas que el usuario visualice en el front.</p>

        <form id="lanes-show-form">
            <div class="checkbox mb-2 mt-4">
                <label for="lane_recents_courses" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['lane_recents_courses'] ? 'checked' : '' }} type="checkbox"
                        id="lane_recents_courses" name="lane_recents_courses" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Cursos más recientes</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="lane_recents_educational_programs"
                    class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['lane_recents_educational_programs'] ? 'checked' : '' }} type="checkbox"
                        id="lane_recents_educational_programs" name="lane_recents_educational_programs" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Programas formativos más recientes</div>
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
                    <div class="checkbox-name">Recursos educativos más recientes</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="lane_recents_itineraries" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['lane_recents_itineraries'] ? 'checked' : '' }} type="checkbox"
                        id="lane_recents_itineraries" name="lane_recents_itineraries" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Recomendación de itinerarios</div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>
    </div>
@endsection
