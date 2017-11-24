<!-- Stored in resources/views/sites.blade.php -->

@extends('layouts.app')

@section('title', 'Upload Complete')

@section('scripts')
    @parent

@endsection

@section('sidebar')
    @parent
@endsection

@section('vue_templates')
    @parent
@endsection

@section('content')
<div class="row" id="CategoriesView">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel tile">
            <div class="row x_title">
                <h3>Crawlers</h3>
            </div>
                <div>
                    <table class="table table-bordered" data-toggle="table" data-pagination="true" data-search="true" data-height="400">
                        <thead>
                            <tr>
                                <th scope="col" data-sortable="true">Worker ID</th>
                                <th scope="col" data-sortable="true">Name</th>
                                <th scope="col" data-sortable="true">IP Address</th>
                                <th scope="col" data-sortable="true">Status</th>
                                <th scope="col" data-sortable="true">Stats</th>
                                <th scope="col" data-sortable="true">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection