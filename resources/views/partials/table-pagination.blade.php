<div class="pagination flex" data-table="{{ $table }}">

    <div class="flex items-center gap-3">
        <span>Mostrar</span>
        <select class="poa-select select-number-pages num-pages-selector">
            <option selected value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span>Filas</span>
    </div>

    <div class="flex gap-[40px]">
        <button type="button" class="pagination-btn btn-first-page" title="Ir a la primera página">
            {{ eHeroicon('chevron-double-left', 'solid') }}
        </button>
        <button type="button" class="pagination-btn btn-previous-page" title="Ir a la página anterior">
            {{ eHeroicon('chevron-left', 'solid') }}
        </button>

        <span class="counter page-info"></span>

        <input class="pages-show-input current-page" type="number" value="1">

        <button type="button" class="pagination-btn btn-next-page" title="Ir a la página siguiente">
            {{ eHeroicon('chevron-right', 'solid') }}
        </button>

        <button type="button" class="pagination-btn btn-last-page" title="Ir a la última página">
            {{ eHeroicon('chevron-double-right', 'solid') }}
        </button>
    </div>

</div>
