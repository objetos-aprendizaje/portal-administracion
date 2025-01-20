@foreach ($learning_results as $learningResult)
    <div class="anidation-div" style="margin-left:1em;">
        <div class="flex">
            <input type="checkbox" class="element-checkbox learning-result-checkbox" id="{{ $learningResult['uid'] }}" data-type="learning-result">
            <label for="{{ $learningResult['uid'] }}" class="element-label"><span class="font-bold">Resultado Aprendizaje: </span>{{ $learningResult['name'] }}</label>
            <button class="edit-learning-result-btn" data-uid="{{ $learningResult['uid'] }}">{{ eHeroicon('pencil-square', 'solid') }}</button>
        </div>
    </div>
@endforeach
