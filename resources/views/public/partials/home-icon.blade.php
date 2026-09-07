@php
    $iconValue = trim((string) ($name ?? ''));
    if ($iconValue === '') {
        $iconValue = 'generic';
    }

    if (str_starts_with($iconValue, '/') || str_starts_with($iconValue, 'http://') || str_starts_with($iconValue, 'https://') || str_starts_with($iconValue, 'data:image')) {
        $iconSource = $iconValue;
    } else {
        $iconName = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $iconValue) ?: 'generic');
        $iconRelativePath = 'images/home/icons/'.$iconName.'.svg';
        $iconSource = file_exists(public_path($iconRelativePath))
            ? '/'.$iconRelativePath
            : '/images/home/icons/generic.svg';
    }
@endphp
<img class="home-icon-image" src="{{ $iconSource }}" alt="" aria-hidden="true">
