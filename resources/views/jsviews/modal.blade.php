<div id="dialog" title="{{ $title }}">
@foreach ($suggestions as $suggestion)
    <p>{{ $suggestion['suggested_match'] }}</p>
@endforeach
</div>