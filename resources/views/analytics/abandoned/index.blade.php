@extends('layouts.app')
@section('content')

<div class="gap-12">
    <div class="poa-container w-full mb-8">
        <!--
        <div class="table-container mt-6">
            <div id="analytics-abandoned"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-abandoned'])
    -->
        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <p>
                Listado de cursos actualmente en realización, Muestra una relación de usuarios matriculados y aceptados
                en el curso, así como los posibles abandonos. Se considera que un usuario ha abandonado el curso si han
                pasado 30 días desde su último acceso.
            </p>
            <p>
                Si pasamos el cursor del ratón por la zona del gráfico, nos mostrará una leyenda con los datos generales.
            </p>
            <p>
                Si hacemos clic en al zona de abandonos (coloreada de rojo púrpura) obtendremos un listado de los alumnos que potencialmente han abandonado el programa formativo.
            </p>
            <div id="d3_graph"></div>
            <div id="d3_graph_x_axis"></div>
            <div class="flex justify-between">
                <h2 class="hidden" id="bnt-exportar-csv-title">Usuarios que han abandonado</h2>
                <div id="bnt-exportar-csv" class="hidden text-right mb-4">
                    <button id="export-csv" type="button" class="btn btn-icon" title="Exportar">{{e_heroicon('arrow-down-tray', 'outline')}}</button>
                </div>
            </div>
            <div id="analytics-abandoned-table-from-graph"></div>
        </div>
    </div>
</div>
@endsection
