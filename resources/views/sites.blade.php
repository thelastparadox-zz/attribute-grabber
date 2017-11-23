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
                <h3>Sites</h3>
            </div>
                <div>
                    <table class="table table-bordered" data-toggle="table" data-pagination="true" data-search="true" data-height="400">
                        <thead>
                            <tr>
                                <th scope="col" data-sortable="true">Site ID</th>
                                <th scope="col" data-sortable="true">Site Name</th>
                                <th scope="col" data-sortable="true">URL</th>
                                <th scope="col" data-sortable="true"># of Categories</th>
                                <th scope="col" data-sortable="true">Total Products</th>
                                <th scope="col" data-sortable="true">Last Refreshed</th>
                                <th scope="col" data-sortable="true">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sites as $id => $site)
                                <tr>
                                    <td>{{ $site->id }}</td>
                                    <td>{{ $site->site_name }}</td>
                                    <td>{{ $site->start_url }}</td>
                                    <td>{{ $site->totalcategories }}</td>
                                    <td>{{ $site->totalproducts }}</td>
                                    <td>last_completed</td>
                                    <td>Actions</td>
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