@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">
        <h2>Configuración general del módulo de gestión</h2>

        <div class="poa-form">

            <form id="management-general-configuration-form">
                <div class="checkbox mb-2">
                    <label for="necessary_approval_resources" class="inline-flex relative items-center cursor-pointer">
                        <input {{ $general_options['necessary_approval_resources'] ? 'checked' : '' }} type="checkbox"
                            id="necessary_approval_resources" name="necessary_approval_resources" class="sr-only peer">
                        <div
                            class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                        </div>
                        <div class="checkbox-name">Aprobación necesaria de recursos</div>
                    </label>
                </div>

                <div class="checkbox mb-2">
                    <label for="necessary_approval_editions" class="inline-flex relative items-center cursor-pointer">
                        <input {{ $general_options['necessary_approval_editions'] ? 'checked' : '' }} type="checkbox"
                            id="necessary_approval_editions" name="necessary_approval_editions" class="sr-only peer">
                        <div
                            class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                        </div>
                        <div class="checkbox-name">Aprobación necesaria de ediciones</div>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary mt-4">Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

            </form>

        </div>


    </div>

    <div class="poa-container">
        <h2>Docentes con aprobación automática de recursos</h2>

        <p class="mb-4">Especifica una lista de docentes a los cuáles se le aprobarán automáticamente sus recursos</p>

        <select id="select-teacher" class="mb-4" name="teacher[]" multiple placeholder="Selecciona un docente..."
            autocomplete="off">
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher['uid'] }}"
                    {{ in_array($teacher['uid'], $uids_teachers_automatic_aproval_resources) ? 'selected' : '' }}>
                    {{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
            @endforeach
        </select>

        <button id="save-teachers-automatic-approval" type="button" class="btn btn-primary mt-4">Guardar
            {{ e_heroicon('paper-airplane', 'outline') }}</button>

    </div>
@endsection
