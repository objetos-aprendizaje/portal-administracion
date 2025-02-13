@extends('layouts.app')

@section('content')
    <div class="flex flex-col md:flex-row gap-12 mb-8 h-[385px]">
        <div class="poa-container w-full md:w-1/2">
            <div class="relative h-full flex justify-between flex-col">

                <h2 class="">Logo</h2>

                <div class="grow overflow-auto h-full">

                    <div class="accordion">
                        <div>
                            <div
                                class="accordion-header cursor-pointer px-[20px] py-[8px] bg-[#F5F6F4] rounded-[6px] flex justify-between items-center mb-[8px]">
                                <h4 class="m-0">Logo 1</h4>
                                <div class="w-[16px] rotate-icon">{{ eHeroicon('chevron-up', 'outline') }}</div>
                            </div>

                            <div class="accordion-uncollapsed container-logo-general">
                                <div class="accordion-body">
                                    <div class="flex gap-[32px] mb-[8px]">
                                        <div class="w-1/2" id="logo_container_poa_logo_1">
                                            @if ($general_options['poa_logo_1'])
                                                <img src="{{ asset($general_options['poa_logo_1']) }}" alt="logo menú" />
                                            @else
                                                <div class="bg-[#F5F6F4] flex items-center justify-center h-full">
                                                    <img src="{{ asset('data/images/default_images/no_logo_attached.svg') }}"
                                                        alt="logo menú" />
                                                </div>
                                            @endif

                                        </div>

                                        <div class="w-1/2">
                                            <button class="btn btn-primary mb-[14px] min-w-0 restore_poa_logo"
                                                data-field="poa_logo_1">Eliminar
                                                {{ eHeroicon('trash', 'outline') }}</button>

                                            <label for="poa_logo_1" class="btn btn-primary min-w-0">Subir
                                                {{ eHeroicon('arrow-up-tray', 'outline') }}</label>

                                            <input type="file" accept="image/*" name="poa_logo_1" id="poa_logo_1"
                                                hidden />

                                        </div>
                                    </div>

                                    <small class="text-center text-[#C7C7C7] mt-8">*Dimensiones recomendadas: Alto: 75px x
                                        Ancho: 215px.
                                        Formato: PNG,
                                        JPG. Tam. Máx.: 1MB</small>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div
                                class="accordion-header cursor-pointer px-[20px] py-[8px] bg-[#F5F6F4] rounded-[6px] flex justify-between items-center mb-[8px]">
                                <h4 class="m-0">Logo 2</h4>
                                <div class="w-[16px] rotate-icon">{{ eHeroicon('chevron-down', 'outline') }}</div>
                            </div>

                            <div class="accordion-collapsed container-logo-general">
                                <div class="accordion-body">
                                    <div class="flex gap-[32px] mb-[8px]">
                                        <div class="w-1/2" id="logo_container_poa_logo_2">
                                            @if ($general_options['poa_logo_2'])
                                                <img src="{{ asset($general_options['poa_logo_2']) }}" alt="logo menú" />
                                            @else
                                                <div class="bg-[#F5F6F4] flex items-center justify-center h-full">
                                                    <img src="{{ asset('data/images/default_images/no_logo_attached.svg') }}"
                                                        alt="no_logo_attached" />
                                                </div>
                                            @endif
                                        </div>

                                        <div class="w-1/2">
                                            <button class="btn btn-primary mb-[14px] min-w-0 restore_poa_logo"
                                                data-field="poa_logo_2">Eliminar
                                                {{ eHeroicon('trash', 'outline') }}</button>

                                            <label for="poa_logo_2" class="btn btn-primary min-w-0">Subir
                                                {{ eHeroicon('arrow-up-tray', 'outline') }}</label>

                                            <input type="file" accept="image/*" name="poa_logo_2" id="poa_logo_2"
                                                hidden />
                                        </div>
                                    </div>

                                    <small class="text-center text-[#C7C7C7] mt-8">*Dimensiones recomendadas: Alto: 75px x
                                        Ancho: 215px.
                                        Formato: PNG,
                                        JPG. Tam. Máx.: 1MB</small>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div
                                class="accordion-header cursor-pointer px-[20px] py-[8px] bg-[#F5F6F4] rounded-[6px] flex justify-between items-center mb-[8px]">
                                <h4 class="m-0">Logo 3</h4>
                                <div class="w-[16px] rotate-icon">{{ eHeroicon('chevron-down', 'outline') }}</div>
                            </div>

                            <div class="accordion-collapsed container-logo-general">
                                <div class="accordion-body">
                                    <div class="flex gap-[32px] mb-[8px]">
                                        <div class="w-1/2" id="logo_container_poa_logo_3">
                                            @if ($general_options['poa_logo_3'])
                                                <img src="{{ asset($general_options['poa_logo_3']) }}" alt="logo menú" />
                                            @else
                                                <div class="bg-[#F5F6F4] flex items-center justify-center h-full">
                                                    <img src="{{ asset('data/images/default_images/no_logo_attached.svg') }}"
                                                        alt="no_logo_attached" />
                                                </div>
                                            @endif
                                        </div>

                                        <div class="w-1/2">
                                            <button class="btn btn-primary mb-[14px] min-w-0 restore_poa_logo"
                                                data-field="poa_logo_3">Eliminar
                                                {{ eHeroicon('trash', 'outline') }}</button>

                                            <label for="poa_logo_3" class="btn btn-primary min-w-0">Subir
                                                {{ eHeroicon('arrow-up-tray', 'outline') }}</label>

                                            <input type="file" accept="image/*" name="poa_logo_3" id="poa_logo_3"
                                                hidden />
                                        </div>
                                    </div>

                                    <small class="text-center text-[#C7C7C7] mt-8">*Dimensiones recomendadas: Alto: 75px x
                                        Ancho: 215px.
                                        Formato: PNG,
                                        JPG. Tam. Máx.: 1MB</small>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>

            </div>

            <template id="no-logo-attached">
                <div class="bg-[#F5F6F4] flex items-center justify-center h-full">
                    <img src="{{ asset('data/images/default_images/no_logo_attached.svg') }}" alt="logo" />
                </div>
            </template>
        </div>

        <div class="poa-container w-full md:w-1/2 flex flex-col">
            <h2>Paleta de colores</h2>

            <div class="grow overflow-auto mb-4">
                <div class="color-definition">
                    <div class="coloris-button">
                        <input value="{{ $general_options['color_1'] }}" id="color-1" class="coloris cursor-pointer"
                            type="text" data-coloris>
                    </div>
                    <div>
                        <label for="color-1" class="text-primary font-roboto-bold">Color Primario (Títulos y
                            Botones):</label> Este color
                        se utiliza para los títulos principales (h1, h2, h3, h4) y el fondo de los botones. Modifica la
                        apariencia de los elementos más destacados y llamativos de la interfaz.
                    </div>
                </div>

                <div class="color-definition">
                    <div class="coloris-button">
                        <input value="{{ $general_options['color_2'] }}" id="color-2" class="coloris" type="text"
                            data-coloris>
                    </div>
                    <div>
                        <label for="color-2" class="text-primary font-roboto-bold">Color Secundario (Subtítulos y
                            Botones Hover):</label> Este color se utiliza para los títulos de nivel inferior (h5), el
                        efecto hover del fondo de los botones principales y el fondo del bloque de categorías.
                        Proporciona un contraste con el color primario y se utiliza en elementos interactivos y áreas de
                        contenido destacado.
                    </div>
                </div>

                <div class="color-definition">
                    <div class="coloris-button">
                        <input value="{{ $general_options['color_3'] }}" id="color-3" class="coloris" type="text"
                            data-coloris>
                    </div>
                    <div>
                        <label for="color-3" class="text-primary font-roboto-bold">Color Terciario (Textos
                            Principales):</label> Este color
                        se utiliza para los textos principales, incluyendo los párrafos (p) y las etiquetas (label).
                        Define la apariencia de la mayoría del texto en la interfaz
                    </div>
                </div>

                <div class="color-definition">
                    <div class="coloris-button">
                        <input value="{{ $general_options['color_4'] }}" id="color-4" class="coloris" type="text"
                            data-coloris>
                    </div>
                    <div>
                        <label for="color-4" class="text-primary font-roboto-bold">Color Cuaternario (Textos
                            Secundarios):</label>
                        Este color se utiliza para los textos secundarios que son más pequeños que los párrafos y menos
                        destacados. Se utiliza para proporcionar información adicional o secundaria, y para elementos de
                        la interfaz que no necesitan destacar tanto como los elementos principales.
                    </div>
                </div>


            </div>

            <div class="flex justify-center">
                <button id="update-colors-btn" class="btn btn-primary w-48">Guardar
                    {{ eHeroicon('paper-airplane', 'outline') }}</button>
            </div>


        </div>
    </div>


    <div class="poa-container mb-8">
        <h2>Configuración general</h2>

        <form id="general-configuration-form">
            @csrf
            <div class="checkbox mb-2">
                <label for="learning_objects_appraisals" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['learning_objects_appraisals'] ? 'checked' : '' }} type="checkbox"
                        id="learning_objects_appraisals" name="learning_objects_appraisals" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Activar valoraciones de objetos de aprendizaje</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="operation_by_calls" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['operation_by_calls'] ? 'checked' : '' }} type="checkbox"
                        id="operation_by_calls" name="operation_by_calls" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Funcionamiento en base a convocatorias</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="registration_active" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['registration_active'] ? 'checked' : '' }} type="checkbox"
                        id="registration_active" name="registration_active" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Activar registro en el portal web</div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>

        </form>


    </div>

    <div class="poa-container mb-8">
        <h2>Datos de la Universidad</h2>
        <form id="university-info-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="company_name">Nombre de la empresa</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['company_name'] }}" placeholder="Universidad de Madrid"
                            class="poa-input" type="text" id="company_name" name="company_name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="phone_number">Teléfono</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['phone_number'] }}" placeholder="693810382" type="text"
                            id="phone_number" class="poa-input" name="phone_number" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="cif">CIF</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['cif'] }}" placeholder="X12345678" type="text"
                            id="cif" class="poa-input" name="cif" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="fiscal_domicile">Domicilio fiscal</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['fiscal_domicile'] }}" class="poa-input"
                            placeholder="Gran Vía, 1" type="text" id="fiscal_domicile" name="fiscal_domicile" />
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar
                    {{ eHeroicon('paper-airplane', 'outline') }}</button>

            </div>
        </form>

    </div>

    <div class="poa-container mb-8">
        <h2>Configuración del servidor de correo SMTP</h2>

        <form id="email-server-form">
            @csrf
            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="smtp_server">Servidor SMTP (host)</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_server'] }}" class="poa-input"
                            placeholder="smtp.ejemplo.com" type="text" id="smtp_server" name="smtp_server" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="smtp_port">Puerto</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_port'] }}" class="poa-input" placeholder="587"
                            type="number" id="smtp_port" name="smtp_port" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="smtp_user">Usuario</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_user'] }}" class="poa-input" placeholder="tu_usuario"
                            type="text" id="smtp_user" name="smtp_user" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="smtp_password">Contraseña</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_password'] }}" class="poa-input" type="password"
                            id="smtp_password" name="smtp_password" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="smtp_address_from">Dirección de remitente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_address_from'] }}" class="poa-input"
                            placeholder="tu_usuario_from" type="text" id="smtp_address_from"
                            name="smtp_address_from" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="smtp_encryption">Tipo de encriptación</label>
                    </div>

                    <div class="content-container little">
                        <select id="smtp_encryption" name="smtp_encryption" class="poa-select w-full">
                            <option value="" @if ($general_options['smtp_encryption'] == '') selected @endif>Ninguna</option>
                            <option value="TLS" @if ($general_options['smtp_encryption'] == 'TLS') selected @endif>TLS</option>
                            <option value="SSL" @if ($general_options['smtp_encryption'] == 'SSL') selected @endif>SSL</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="smtp_name_from">Nombre de envío</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_name_from'] }}" class="poa-input"
                            placeholder="Portal de Objetos de Aprendizaje" type="text" id="smtp_name_from"
                            name="smtp_name_from" />
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar
                    {{ eHeroicon('paper-airplane', 'outline') }}</button>

            </div>

        </form>

    </div>

    <div class="poa-container mb-8">
        <h2>Scripts para Analytics</h2>

        <p class="mb-2">Insertar tus scripts personalizados que se cargarán en el

            &lt;head&gt;
            de tu sitio web. Utilízalo para integrar herramientas como Google Analytics, Google Tag Manager, el píxel
            de Facebook y otros servicios de seguimiento o análisis.
        </p>

        <textarea maxlength="1000" rows="7" id="scripts-input" class="poa-input">{{ $general_options['scripts'] }}</textarea>

        <button type="button" class="btn btn-primary" id="save-scripts-btn">Guardar
            {{ eHeroicon('paper-airplane', 'outline') }}</button>
    </div>

    <div class="poa-container mb-8">
        <h2>Redes sociales</h2>

        <form id="rrss-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="facebook_url">Facebook</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['facebook_url'] }}" placeholder="facebook.com"
                            class="poa-input" type="text" id="facebook_url" name="facebook_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="x_url">X</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['x_url'] }}" placeholder="x.com" class="poa-input"
                            type="text" id="x_url" name="x_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="youtube_url">YouTube</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['youtube_url'] }}" placeholder="youtube.com" class="poa-input"
                            type="text" id="youtube_url" name="youtube_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="instagram_url">Instagram</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['instagram_url'] }}" placeholder="instagram.com"
                            class="poa-input" type="text" id="instagram_url" name="instagram_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="telegram_url">Telegram</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['telegram_url'] }}" placeholder="telegram.me"
                            class="poa-input" type="text" id="telegram_url" name="telegram_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_url">Linkedin</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['linkedin_url'] }}" placeholder="linkedin.com"
                            class="poa-input" type="text" id="linkedin_url" name="linkedin_url" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>

        </form>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración del slider inicial</h2>

        <p class="mt-2 mb-4">
            Esta es la configuración del slider que se mostrará en la página principal del portal, junto con los sliders de
            los cursos.
        </p>

        <form id="carrousel-default-config-form">
            @csrf
            <div class="poa-form">
                <div class="field">
                    <div class="label-container">
                        <label for="carrousel_image_input_file">Imagen <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <div class="poa-input-image">
                            <img id="carrousel_image_path"
                                src="{{ $general_options['carrousel_image_path'] ? asset($general_options['carrousel_image_path']) : env('NO_IMAGE_SELECTED_PATH') }}"
                                alt="imagen por defecto carrousel" />


                            <div class="select-file-container">
                                <input accept="image/*" type="file" id="carrousel_image_input_file"
                                    name="carrousel_image_input_file" class="hidden" />

                                <div class="flex items-center gap-[20px]">
                                    <label for="carrousel_image_input_file" class="btn btn-rectangular">
                                        Subir {{ eHeroicon('arrow-up-tray', 'outline') }}
                                    </label>

                                    <span class="image-name link-label">Ningún archivo seleccionado</span>
                                </div>
                            </div>

                            <span class="dimensions">*Se recomienda subir imagen con aspecto panorámico con una resolución
                                mínima de: 1200px x 600px.
                                Formato: PNG, JPG, JPEG.</span>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="carrousel_title">Título <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input placeholder="Título" class="poa-input" type="text" id="carrousel_title"
                            value="{{ $general_options['carrousel_title'] }}" name="carrousel_title" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="carrousel_description">Descripción <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input placeholder="Descripción" value="{{ $general_options['carrousel_description'] }}"
                            class="poa-input" type="text" id="carrousel_description" name="carrousel_description" />
                    </div>
                </div>
                <div class="field">
                    <div class="label-container">
                        <label for="main_slider_color_font">Color <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <div class="coloris-button">
                            <input value="{{ $general_options['main_slider_color_font'] ?? '#fff' }}"
                                id="main_slider_color_font" name="main_slider_color_font" class="coloris" type="text"
                                data-coloris>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-carrousel-form-btn">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>
        </form>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración de la tipografía</h2>

        <p class="mt-2 mb-4">
            El portal emplea tres estilos de fuentes: Regular, Medium y Bold. Cada uno de estos estilos puede cargarse en
            cinco formatos distintos: .eot, .svg, .ttf, .woff y .woff2.
            Aunque se sugiere adjuntar las fuentes en todos los formatos para asegurar la compatibilidad con todos los
            navegadores, no es obligatorio hacerlo. Puedes elegir subir las fuentes en los formatos que prefieras.
        </p>

        <p>
            Sin embargo, ten en cuenta que si un navegador requiere un formato de fuente que no has subido, el navegador
            representará la web con la tipografía que tenga configurada por defecto.
        </p>

        <div class="poa-form">
            <div class="accordion">

                <div class="accordion-item group">
                    <div class="accordion-header">
                        <h3>Tipografía regular</h3>
                        <div class="rotate-icon">
                            {{ eHeroicon('chevron-down', 'outline') }}
                        </div>
                    </div>

                    <div class="accordion-uncollapsed">
                        <div class="accordion-body">

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="truetype_regular_file_path">Truetype (.ttf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="truetype_regular_file_path"
                                                data-font="truetype_regular_file_path" class="hidden input-font"
                                                name="truetype_regular_file_path" accept=".ttf">
                                            <label for="truetype_regular_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name truetype_regular_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>

                                    <div class="truetype_regular_file_path_buttons">
                                        <a id="url_truetype_regular_file_path" data-font="truetype_regular_file_path"
                                            class="{{ $general_options['truetype_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['truetype_regular_file_path'] ? '/' . $general_options['truetype_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="truetype_regular_file_path"
                                            class="{{ $general_options['truetype_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff_regular_file_path">Woff (.woff)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff_regular_file_path" class="hidden input-font"
                                                data-font="woff_regular_file_path" name="woff_regular_file_path"
                                                accept=".woff">
                                            <label for="woff_regular_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name woff_regular_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>

                                    <div class="woff_regular_file_path_buttons">
                                        <a id="url_woff_regular_file_path" data-font="woff_regular_file_path"
                                            class="{{ $general_options['woff_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff_regular_file_path'] ? '/' . $general_options['woff_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff_regular_file_path"
                                            class="{{ $general_options['woff_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff2_regular_file_path">Woff2 (.woff2)</label>
                                </div>

                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff2_regular_file_path" class="hidden input-font"
                                                name="woff2_regular_file_path" accept=".woff2"
                                                data-font="woff2_regular_file_path">
                                            <label for="woff2_regular_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name woff2_regular_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff2_regular_file_path_buttons">
                                        <a id="url_woff2_regular_file_path" data-font="woff2_regular_file_path"
                                            class="{{ $general_options['woff2_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff2_regular_file_path'] ? '/' . $general_options['woff2_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff2_regular_file_path"
                                            class="{{ $general_options['woff2_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="embedded_opentype_regular_file_path">embedded-opentype (.eot)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="embedded_opentype_regular_file_path"
                                                class="hidden input-font" name="embedded_opentype_regular_file_path"
                                                data-font="embedded_opentype_regular_file_path" accept=".eot">
                                            <label for="embedded_opentype_regular_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label embedded_opentype_regular_file_path">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="embedded_opentype_regular_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['embedded_opentype_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['embedded_opentype_regular_file_path'] ? '/' . $general_options['embedded_opentype_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['embedded_opentype_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="opentype_regular_input_file">opentype (.otf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="opentype_regular_input_file"
                                                class="hidden input-font" name="opentype_regular_input_file"
                                                accept=".otf" data-font="opentype_regular_input_file">
                                            <label for="opentype_regular_input_file"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label opentype_regular_input_file">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>

                                    <div class="opentype_regular_input_file_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['opentype_regular_input_file'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['opentype_regular_input_file'] ? '/' . $general_options['opentype_regular_input_file'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="opentype_regular_input_file"
                                            class="{{ $general_options['opentype_regular_input_file'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="svg_regular_file_path">svg (.svg)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="svg_regular_file_path" class="hidden input-font"
                                                name="svg_regular_file_path" accept=".svg"
                                                data-font="svg_regular_file_path">
                                            <label for="svg_regular_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label svg_regular_file_path">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="svg_regular_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['svg_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['svg_regular_file_path'] ? '/' . $general_options['svg_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="svg_regular_file_path"
                                            class="{{ $general_options['svg_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="accordion-item group">
                    <div class="accordion-header">
                        <h3>Tipografía medium</h3>
                        <div class="rotate-icon">
                            {{ eHeroicon('chevron-down', 'outline') }}
                        </div>
                    </div>

                    <div class="accordion-collapsed">
                        <div class="accordion-body">
                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="truetype_medium_file_path">Truetype (.ttf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="truetype_medium_file_path"
                                                class="hidden input-font" name="truetype_medium_file_path" accept=".ttf"
                                                data-font="truetype_medium_file_path">
                                            <label for="truetype_medium_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="truetype_medium_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['truetype_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['truetype_medium_file_path'] ? '/' . $general_options['truetype_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="truetype_medium_file_path"
                                            class="{{ $general_options['truetype_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff_medium_file_path">Woff (.woff)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff_medium_file_path" class="hidden input-font"
                                                name="woff_medium_file_path" accept=".woff"
                                                data-font="woff_medium_file_path">
                                            <label for="woff_medium_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff_medium_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['woff_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff_medium_file_path'] ? '/' . $general_options['woff_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff_medium_file_path"
                                            class="{{ $general_options['woff_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff2_medium_file_path">Woff2 (.woff2)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff2_medium_file_path" class="hidden input-font"
                                                name="woff2_medium_file_path" accept=".woff2"
                                                data-font="woff2_medium_file_path">
                                            <label for="woff2_medium_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff2_medium_file_path_buttons">
                                        <a id="woff2_medium_file_path" data-font="woff2_medium_file_path"
                                            class="{{ $general_options['woff2_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff2_medium_file_path'] ? '/' . $general_options['woff2_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff2_medium_file_path"
                                            class="{{ $general_options['woff2_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="embedded_opentype_medium_file_path">embedded-opentype (.eot)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="embedded_opentype_medium_file_path"
                                                class="hidden input-font" name="embedded_opentype_medium_file_path"
                                                accept=".eot" data-font="embedded_opentype_medium_file_path">
                                            <label for="embedded_opentype_medium_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="embedded_opentype_medium_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['embedded_opentype_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['embedded_opentype_medium_file_path'] ? '/' . $general_options['embedded_opentype_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="embedded_opentype_medium_file_path"
                                            class="{{ $general_options['embedded_opentype_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="opentype_medium_file_path">opentype (.otf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="opentype_medium_file_path"
                                                class="hidden input-font" name="opentype_medium_file_path" accept=".otf"
                                                data-font="opentype_medium_file_path">
                                            <label for="opentype_medium_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name opentype_medium_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="opentype_medium_file_path_buttons">
                                        <a id="opentype_medium_file_path" data-font="opentype_medium_file_path"
                                            class="{{ $general_options['opentype_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['opentype_medium_file_path'] ? '/' . $general_options['opentype_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="opentype_medium_file_path"
                                            class="{{ $general_options['opentype_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="svg_medium_file_path">svg (.svg)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="svg_medium_file_path" class="hidden input-font"
                                                name="svg_medium_file_path" accept=".svg"
                                                data-font="svg_medium_file_path">
                                            <label for="svg_medium_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name svg_medium_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="svg_medium_file_path_buttons">
                                        <a id="svg_medium_file_path" data-font="svg_medium_file_path"
                                            class="{{ $general_options['svg_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['svg_medium_file_path'] ? '/' . $general_options['svg_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="svg_medium_file_path"
                                            class="{{ $general_options['svg_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="accordion-item group">
                    <div class="accordion-header">
                        <h3>Tipografía bold</h3>
                        <div class="rotate-icon">
                            {{ eHeroicon('chevron-down', 'outline') }}
                        </div>
                    </div>

                    <div class="accordion-collapsed">
                        <div class="accordion-body">

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="truetype_bold_file_path">Truetype (.ttf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="truetype_bold_file_path" class="hidden input-font"
                                                name="truetype_bold_file_path" accept=".ttf"
                                                data-font="truetype_bold_file_path">
                                            <label for="truetype_bold_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name truetype_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="truetype_bold_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['truetype_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['truetype_bold_file_path'] ? '/' . $general_options['truetype_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="truetype_bold_file_path"
                                            class="{{ $general_options['truetype_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff_bold_file_path">Woff (.woff)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff_bold_file_path" class="hidden input-font"
                                                name="woff_bold_file_path" accept=".woff"
                                                data-font="woff_bold_file_path">
                                            <label for="woff_bold_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name woff_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff_bold_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['woff_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff_bold_file_path'] ? '/' . $general_options['woff_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff_bold_file_path"
                                            class="{{ $general_options['woff_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff2_bold_file_path">Woff2 (.woff2)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff2_bold_file_path" class="hidden input-font"
                                                name="woff2_bold_file_path" accept=".woff2"
                                                data-font="woff2_bold_file_path">
                                            <label for="woff2_bold_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name woff2_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff2_bold_file_path_buttons">
                                        <a id="woff2_bold_file_path" data-font="woff2_bold_file_path"
                                            class="{{ $general_options['woff2_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff2_bold_file_path'] ? '/' . $general_options['woff2_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff2_bold_file_path"
                                            class="{{ $general_options['woff2_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="embedded_opentype_bold_file_path">embedded-opentype (.eot)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="embedded_opentype_bold_file_path"
                                                class="hidden input-font" name="embedded_opentype_bold_file_path"
                                                accept=".eot" data-font="embedded_opentype_bold_file_path">
                                            <label for="embedded_opentype_bold_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="embedded_opentype_bold_file_path_buttons">
                                        <a id="embedded_opentype_bold_file_path"
                                            data-font="embedded_opentype_bold_file_path"
                                            class="{{ $general_options['embedded_opentype_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['embedded_opentype_bold_file_path'] ? '/' . $general_options['embedded_opentype_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="embedded_opentype_bold_file_path"
                                            class="{{ $general_options['embedded_opentype_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="opentype_bold_file_path">opentype (.otf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="opentype_bold_file_path" class="hidden input-font"
                                                name="opentype_bold_file_path" accept=".otf"
                                                data-font="opentype_bold_file_path">
                                            <label for="opentype_bold_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name opentype_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="opentype_bold_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['opentype_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['opentype_bold_file_path'] ? '/' . $general_options['opentype_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="opentype_bold_file_path"
                                            class="{{ $general_options['opentype_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="svg_bold_file_path">svg (.svg)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="svg_bold_file_path" class="hidden input-font"
                                                name="svg_bold_file_path" accept=".svg" data-font="svg_bold_file_path">
                                            <label for="svg_bold_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name svg_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="svg_bold_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['svg_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['svg_bold_file_path'] ? '/' . $general_options['svg_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="svg_bold_file_path"
                                            class="{{ $general_options['svg_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                </div>

            </div>


        </div>

    </div>

    <div class="poa-container mb-8">
        <h2>Configuración del módulo de recomendación</h2>

        <p class="mt-2">
            Se usará la API de OpenAI para la generación de etiquetas.
        </p>

        <p class="mt-2">
            Si hay cursos o recursos educativos que no tienen embeddings, puedes regenerarlos todos pulsando el botón de
            abajo.
        </p>

        @if ($general_options['last_regeneration_embeddings'])
            <p>
                Última regeneración: {{ formatDateTime($general_options['last_regeneration_embeddings']) }}
            </p>
        @endif

        <form id="openai-form">
            @csrf
            <div class="poa-form">
                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="enabled_recommendation_module">Activar módulo de recomendación</label>
                    </div>
                    <div class="content-container mt-1">
                        <div class="checkbox">
                            <label for="enabled_recommendation_module"
                                class="inline-flex relative items-center cursor-pointer">
                                <input {{ $general_options['enabled_recommendation_module'] ? 'checked' : '' }}
                                    type="checkbox" id="enabled_recommendation_module"
                                    name="enabled_recommendation_module" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="openai_key">Clave API <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input placeholder="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" class="poa-input"
                            type="text" id="openai_key" value="{{ $general_options['openai_key'] }}"
                            name="openai_key" />
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary" id="save-openai-btn">Guardar
                    {{ eHeroicon('paper-airplane', 'outline') }}</button>

                @if ($isPendingJobRegenerateEmbeddingsRunning)
                    <button type="button" class="btn btn-secondary" id="regenerate-embeddings-btn">Embeddings
                        regenerándose
                        {{ eHeroicon('exclamation-circle', 'outline') }}</button>
                @else
                    <button type="button" class="btn btn-secondary" id="regenerate-embeddings-btn">Regenerar embeddings
                        {{ eHeroicon('arrow-path', 'outline') }}</button>
                @endif

            </div>
        </form>

    </div>

    <div class="poa-container mb-8">
        <h2>Definición de textos de footer</h2>

        <p class="mt-2 mb-4">
            Configura los textos descriptivos del footer
        </p>

        <div class="poa-form">
            <form id="footer-texts-form">
                @csrf

                <div class="field">
                    <div class="label-container">
                        <label for="footer_text_1">Texto 1</label>
                    </div>
                    <div class="content-container little">
                        <textarea maxlength="1000" id="footer-text-1-content">{{ $general_options['footer_text_1'] }}</textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="footer_text_2">Texto 2</label>
                    </div>
                    <div class="content-container little">
                        <textarea maxlength="1000" id="footer-text-2-content">{{ $general_options['footer_text_2'] }}</textarea>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar
                        {{ eHeroicon('paper-airplane', 'outline') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
