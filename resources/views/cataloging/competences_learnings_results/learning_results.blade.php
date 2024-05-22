@foreach ($learning_results as $learningResult)
    <div class="anidation-div" style="margin-left:1em;">
        <div class="flex">
            <input type="checkbox" class="element-checkbox learning-result-checkbox" id="{{ $learningResult['uid'] }}" data-type="learning-result">
            <label for="{{ $learningResult['uid'] }}" class="element-label">R:{{ $learningResult['name'] }}</label>
            <button class="edit-learning-result-btn" data-uid="{{ $learningResult['uid'] }}">{{ e_heroicon('pencil-square', 'solid') }}</button>
        </div>
        @if ($learningResult['description'])
            <p>{{ $learningResult['description'] }}</p>
        @endif
    </div>
@endforeach
