<!-- Stored in resources/views/categories.blade.php -->

@extends('layouts.app')

@section('title', 'Upload Complete')

@section('scripts')
    @parent
    <link href="css/categories.css" rel="stylesheet">
    <script src="js/categories.js"></script>
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
            <h3>Categories</h3>
        </div>
            <div>
                <table class="table table-bordered" data-toggle="table" data-pagination="true" data-search="true" data-height="400">
                    <thead>
                        <tr>
                            <th scope="col" data-sortable="true">Category ID</th>
                            <th scope="col" data-sortable="true">Level 1 Name</th>
                            <th scope="col" data-sortable="true">Level 2 Name</th>
                            <th scope="col" data-sortable="true">Level 3 Name</th>
                            <th scope="col" data-sortable="true">Last Refreshed</th>
                            <th scope="col" data-sortable="true">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $id => $category)
                            <tr>
                                <td>{{ $category->id }}</td>
                                <td>{{ $category->level_1_name }}</td>
                                <td>{{ $category->level_2_name }}</td>
                                <td>{{ $category->level_3_name }}</td>
                                <td>{{ $category->last_refreshed }}</td>
                                <td><a href="/api/refresh_category/{{ $category->id }}" class="refreshLink"><i class="fa fa-refresh"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection