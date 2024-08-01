@foreach ($categories as $category)
    <option value="{{ $category['uid'] }}">
        @for($i = 0; $i < $level; $i++)
            -
        @endfor
        {{ $category['name'] }}
    </option>

    @if (isset($category['subcategories']) && count($category['subcategories']) > 0)
        @include('cataloging.certification_types.categories_options', [
            'categories' => $category['subcategories'], 'level' => $level + 1,
        ])
    @endif
@endforeach
