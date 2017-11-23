<!-- Stored in resources/views/welcome.blade.php -->

@extends('layouts.app')

@section('title', 'Upload Complete')

@section('sidebar')
    @parent
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel tile">
            <div class="row x_title">
                <h3>Upload Categories file</h3>
            </div>
                {!! Form::open(array('url' => 'add_categories', 'files' => true)) !!}
                <div class="form-group">{!! Form::file('categories_file') !!}</div>
                <div class="form-group">{!! Form::submit('Upload') !!}</div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@endsection
