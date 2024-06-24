@if (!empty($competences))
    @foreach ($competences as $competence)
        <div class="anidation-div {{ $first_loop ? 'first' : '' }}" style="margin-left:{{ $first_loop ? 0 : 1 }}em;">

            <div class="flex">
                <input type="checkbox" class="element-checkbox competence-checkbox" id="{{ $competence['uid'] }}">
                <label for="{{ $competence['uid'] }}" class="element-label">{{ $competence['name'] }}</label>
                <button class="edit-competence-btn"
                    data-uid="{{ $competence['uid'] }}">{{ e_heroicon('pencil-square', 'solid') }}</button>
                @if ($competence['parent_competence_uid'])
                    <button class="add-learning-result-btn"
                        data-uid="{{ $competence['uid'] }}">{{ e_heroicon('plus', 'solid') }}</button>
                @endif
            </div>

            @if ($competence['description'])
                <p>{{ $competence['description'] }}</p>
            @endif

            @if (!empty($competence['subcompetences']))
                @include('cataloging.competences_learnings_results.competences', [
                    'competences' => $competence['subcompetences'],
                    'first_loop' => false,
                ])
            @endif

            @if (!empty($competence['learning_results']))
                @include('cataloging.competences_learnings_results.learning_results', [
                    'learning_results' => $competence['learning_results'],
                    'first_loop' => false,
                ])
            @endif
        </div>
    @endforeach
@endif
