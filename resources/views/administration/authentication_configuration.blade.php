@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">
        <h2>Configuración de CAS</h2>

        <div class="poa-form">
            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Activo</label>
                </div>

                <div class="content-container little">
                    <div class="checkbox mb-2">
                        <label for="learning-objects-appraisals-checkbox" class="inline-flex relative items-center cursor-pointer">
                            <input {{ $general_options['learning_objects_appraisals'] ? 'checked' : '' }} type="checkbox"
                                id="learning-objects-appraisals-checkbox" class="peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>

                        </label>
                    </div>
                </div>
            </div>


            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Host</label>
                </div>
                <div class="content-container little">
                    <input placeholder="cas.um.es"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Puerto</label>
                </div>
                <div class="content-container little">
                    <input placeholder="443"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Contexto</label>
                </div>
                <div class="content-container little">
                    <input placeholder="/cas"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>
        </div>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración de RedIris</h2>

        <div class="poa-form">
            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Activo</label>
                </div>

                <div class="content-container little">
                    <div class="checkbox mb-2">
                        <label for="learning-objects-appraisals-checkbox" class="inline-flex relative items-center cursor-pointer">
                            <input {{ $general_options['learning_objects_appraisals'] ? 'checked' : '' }} type="checkbox"
                                id="learning-objects-appraisals-checkbox" class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>

                        </label>
                    </div>
                </div>

            </div>


            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Host</label>
                </div>
                <div class="content-container little">
                    <input placeholder="cas.um.es"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Puerto</label>
                </div>
                <div class="content-container little">
                    <input placeholder="443"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Contexto</label>
                </div>
                <div class="content-container little">
                    <input placeholder="/cas"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>
        </div>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración de Facebook</h2>

        <div class="poa-form">
            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Activo</label>
                </div>

                <div class="content-container little">
                    <div class="checkbox mb-2">
                        <label for="learning-objects-appraisals-checkbox" class="inline-flex relative items-center cursor-pointer">
                            <input {{ $general_options['learning_objects_appraisals'] ? 'checked' : '' }} type="checkbox"
                                id="learning-objects-appraisals-checkbox" class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>

                        </label>
                    </div>
                </div>

            </div>


            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">ID de aplicación</label>
                </div>
                <div class="content-container little">
                    <input placeholder="1225468"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Clave secreta</label>
                </div>
                <div class="content-container little">
                    <input placeholder="cxEWCDFaxSddE23cz"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>
        </div>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración de Twitter</h2>

        <div class="poa-form">
            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Activo</label>
                </div>

                <div class="content-container little">
                    <div class="checkbox mb-2">
                        <label for="learning-objects-appraisals-checkbox" class="inline-flex relative items-center cursor-pointer">
                            <input {{ $general_options['learning_objects_appraisals'] ? 'checked' : '' }} type="checkbox"
                                id="learning-objects-appraisals-checkbox" class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>

                        </label>
                    </div>
                </div>

            </div>


            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">ID de aplicación</label>
                </div>
                <div class="content-container little">
                    <input placeholder="1225468"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Clave secreta</label>
                </div>
                <div class="content-container little">
                    <input placeholder="cxEWCDFaxSddE23cz"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>
        </div>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración de Google</h2>

        <div class="poa-form">
            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Activo</label>
                </div>

                <div class="content-container little">
                    <div class="checkbox mb-2">
                        <label for="learning-objects-appraisals-checkbox" class="inline-flex relative items-center cursor-pointer">
                            <input {{ $general_options['learning_objects_appraisals'] ? 'checked' : '' }} type="checkbox"
                                id="learning-objects-appraisals-checkbox" class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>

                        </label>
                    </div>
                </div>

            </div>


            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">ID de aplicación</label>
                </div>
                <div class="content-container little">
                    <input placeholder="1225468"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Clave secreta</label>
                </div>
                <div class="content-container little">
                    <input placeholder="cxEWCDFaxSddE23cz"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>
        </div>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración de Linkedin</h2>

        <div class="poa-form">
            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Activo</label>
                </div>

                <div class="content-container little">
                    <div class="checkbox mb-2">
                        <label for="learning-objects-appraisals-checkbox" class="inline-flex relative items-center cursor-pointer">
                            <input {{ $general_options['learning_objects_appraisals'] ? 'checked' : '' }} type="checkbox"
                                id="learning-objects-appraisals-checkbox" class="sr-only peer">
                            <div
                                class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                            </div>

                        </label>
                    </div>
                </div>

            </div>


            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">ID de aplicación</label>
                </div>
                <div class="content-container little">
                    <input placeholder="1225468"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <div class="field">
                <div class="label-container label-center">
                    <label for="company-name">Clave secreta</label>
                </div>
                <div class="content-container little">
                    <input placeholder="cxEWCDFaxSddE23cz"
                        class="poa-input" type="text" id="company-name" name="company-name" />
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ eHeroicon('paper-airplane', 'outline') }}</button>
        </div>
    </div>

@endsection
