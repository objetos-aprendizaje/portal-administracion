@extends('layouts.app')
@section('content')
    <div class="gap-12">
        <div class="poa-container w-full mb-8">
            <div class="flex gap-4">
                <div class="w-1/2">
                    <h2>Abandonos de cursos</h2>
                    <p>
                        Listado de cursos actualmente en realización. Muestra una relación de usuarios matriculados y
                        aceptados
                        en el curso, así como los posibles abandonos. Se considera que un usuario ha abandonado el curso si
                        han
                        pasado el número de días definido como umbral desde su último acceso.
                    </p>
                    <p>
                        Si pasamos el cursor del ratón por la zona del gráfico, nos mostrará una leyenda con los datos
                        generales.
                    </p>
                    <p class="mb-4">
                        Si hacemos clic en al zona de abandonos (coloreada de rojo púrpura) obtendremos un listado de los
                        alumnos que potencialmente han abandonado el programa formativo.
                    </p>
                </div>

                <div class="w-1/2">
                    <h3>Define el umbral de días:</h3>
                    <form id="threshold-abandoned-courses-form">
                        <input type="number" id="threshold_abandoned_courses" name="threshold_abandoned_courses"
                            placeholder="Umbral de días" class="poa-input"
                            value="{{ $general_options['threshold_abandoned_courses'] }}">

                        <button class="btn btn-primary mt-[20px]" type="submit">Guardar
                            {{ eHeroicon('paper-airplane', 'outline') }}</button>
                    </form>
                </div>
            </div>

            <div id="d3_graph"></div>
            <div id="d3_graph_x_axis"></div>
            <div class="flex justify-between mt-10">
                <h2 class="hidden" id="bnt-exportar-csv-title">Usuarios que han abandonado</h2>
                <div id="bnt-exportar-csv" class="hidden text-right mb-4">
                    <button id="export-csv" type="button" class="btn btn-icon"
                        title="Exportar">{{ eHeroicon('arrow-down-tray', 'outline') }}</button>
                </div>
            </div>

            <div class="table-container mt-6">
                <div id="analytics-abandoned-table-from-graph"></div>
            </div>

        </div>
    </div>
@endsection
