@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">
        <h2>Datos de perfil</h2>
        <form id="user-profile-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container">
                        <label for="image_input_file">Foto</label>
                    </div>
                    <div class="content-container">

                        <div class="poa-input-image">
                            <img id="photo_path_preview"
                                src="{{ $user->photo_path ?? '/data/images/default_images/no_image_attached.svg' }}" />

                            <span class="dimensions">*Dimensiones: Alto: 50px x Ancho: 300px. Formato: PNG, JPG. Tam. Máx.:
                                1MB</span>

                            <div class="select-file-container">
                                <input accept="image/*" type="file" id="photo_path" name="photo_path" class="hidden" />

                                <label for="photo_path" class="btn btn-rectangular w-[110px]">
                                    Subir {{ e_heroicon('arrow-up-tray', 'outline') }}
                                </label>

                                <span id="image-name" class="image-name text-[14px]">Ningún archivo seleccionado</span>
                            </div>

                        </div>
                    </div>

                </div>
                <div class="field">
                    <div class="label-container label-center">
                        <label for="first_name">Nombre</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $user->first_name }}" placeholder="Manuel" class="poa-input" type="text"
                            id="first_name" name="first_name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="last_name">Apellidos</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $user->last_name }}" placeholder="Pérez Gutiérrez" type="text" id="last_name"
                            name="last_name" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="nif">NIF</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $user->nif }}" placeholder="12345678X" type="text" id="nif"
                            class="poa-input" name="nif" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="department">Departamento</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $user->department }}" class="poa-input" placeholder="Dirección" type="text"
                            id="department" name="department" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="curriculum">Currículum</label>
                    </div>

                    <div class="content-container little">
                        <textarea value="{{ $user->curriculum }}" placeholder="" type="text" id="curriculum" name="curriculum"
                            class="poa-input" rows="6"></textarea>
                    </div>

                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="curriculum">Notificaciones generales</label>
                    </div>

                    <div class="content-container little">

                        <div class="checkbox mb-2">
                            <label for="general_notifications_allowed"
                                class="inline-flex relative items-center cursor-pointer">

                                <input type="checkbox" {{ $user->general_notifications_allowed ? 'checked' : '' }}
                                    class="sr-only peer" id="general_notifications_allowed"
                                    name="general_notifications_allowed">

                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>

                                <div class="checkbox-name">Notificaciones generales</div>
                            </label>
                        </div>

                        @foreach ($notification_types as $notification_type)
                            <div class="checkbox mb-2 ml-4">
                                <label for="{{ $notification_type->uid }}"
                                    class="inline-flex relative items-center cursor-pointer">

                                    <input type="checkbox" id="{{ $notification_type->uid }}"
                                        class="sr-only peer notification-type" value="{{ $notification_type->uid }}"
                                        {{ in_array($notification_type->uid, array_column($user->notificationsTypesPreferences->toArray(), 'uid')) ? 'checked' : '' }}>

                                    <div
                                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                    </div>
                                    <div class="checkbox-name">{{ $notification_type->name }}</div>
                                </label>
                            </div>
                        @endforeach

                    </div>

                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="curriculum">Notificaciones por email</label>
                    </div>

                    <div class="content-container little">
                        <div class="checkbox mb-2">
                            <label for="email_notifications_allowed"
                                class="inline-flex relative items-center cursor-pointer">

                                <input type="checkbox" {{ $user->email_notifications_allowed ? 'checked' : '' }}
                                    class="sr-only peer" id="email_notifications_allowed"
                                    name="email_notifications_allowed">

                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>

                                <div class="checkbox-name">Notificaciones generales</div>
                            </label>
                        </div>
                    </div>

                </div>

                <button type="submit" class="btn btn-primary">Guardar
                    {{ e_heroicon('paper-airplane', 'outline') }}</button>

            </div>
        </form>

    </div>
@endsection
