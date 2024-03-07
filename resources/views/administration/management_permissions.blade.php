@extends('layouts.app')

@section('content')


    <div class="poa-container">
        <h2>Permisos a gestores</h2>

        <p class="mb-4">
            Aquí puedes definir los permisos que tendrán los usuarios que operen bajo el rol de gestor.
        </p>

        <form id="managers-permissions-form">
            <div class="checkbox mb-2">
                <label for="managers_can_manage_categories" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['managers_can_manage_categories'] ? 'checked' : '' }} type="checkbox"
                        id="managers_can_manage_categories" name="managers_can_manage_categories" class="sr-only peer" value="1">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Administrar categorías</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="managers_can_manage_course_types" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['managers_can_manage_course_types'] ? 'checked' : '' }} type="checkbox"
                        id="managers_can_manage_course_types" name="managers_can_manage_course_types" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Administrar tipos de cursos</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="managers_can_manage_educational_resources_types" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['managers_can_manage_educational_resources_types'] ? 'checked' : '' }} type="checkbox"
                        id="managers_can_manage_educational_resources_types" name="managers_can_manage_educational_resources_types" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Administrar tipos de recursos educativos</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="managers_can_manage_calls" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['managers_can_manage_calls'] ? 'checked' : '' }} type="checkbox"
                        id="managers_can_manage_calls" name="managers_can_manage_calls" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Administrar convocatorias</div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>

    </div>
@endsection
