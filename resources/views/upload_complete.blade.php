<!-- Stored in resources/views/upload.blade.php -->

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
            <h3>Results of Upload</h3>
        </div>
            <div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Metric</th>
                            <th scope="col">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td scope="row">Input Data: Total rows available</td>
                            <td>{{ $results['total_rows'] }}</td>
                        </tr>
                        <tr>
                            <td scope="row">Input Data: Blank rows ignored</td>
                            <td>{{ $results['blank_rows'] }}</td>
                        </tr>
                        <tr>
                            <td scope="row">Input Data: Duplicate rows ignored</td>
                            <td>{{ $results['duplicate_rows'] }}</td>
                        </tr>
                        <tr class="table-success">
                            <td scope="row">Total new categories added to the database</td>
                            <td>{{ $results['total_inserts'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection