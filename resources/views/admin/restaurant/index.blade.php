@extends('admin.layouts.master')

@section('main-content')

<section class="section">
    <div class="section-header">
        <h1>{{ __('levels.restaurants') }}</h1>
        {{ Breadcrumbs::render('restaurants') }}
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">

                    @can('restaurants_create')
                    <div class="card-header d-flex justify-content-between">
                        <a href="{{ route('admin.restaurants.create') }}" class="btn btn-icon icon-left btn-primary"><i
                                class="fas fa-plus"></i> {{ __('restaurant.add_restaurant') }}</a>
                    </div>
                    @endcan

                    <div class="card-body">
                        <div class="row">
                            <form>
                                <textarea> Hello</textarea>
                            </form>
                        </div>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

@endsection



@section('css')
<link rel="stylesheet" href="{{ asset('assets/modules/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/modules/datatables.net-select-bs4/css/select.bootstrap4.min.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('assets/modules/datatables/media/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/modules/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('assets/modules/datatables.net-select-bs4/js/select.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/restaurant/index.js') }}"></script>
@endsection
