@extends('admin.layouts.master')
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/modules/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap-social/bootstrap-social.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/summernote/summernote-bs4.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap-timepicker/css/bootstrap-timepicker.min.css') }}">
@endsection

@section('main-content')

    <section class="section">
        <div class="section-header">
        <h1>{{ __('levels.restaurant') }}</h1>
            {{ Breadcrumbs::render('restaurant/edit') }}
        </div>

        <div class="section-body">
            <form action="{{ route('admin.restaurants.update', $restaurant) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('restaurant.restaurant_information') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-lg-12">
                                        <label for="name">{{ __('levels.name') }}</label> <span class="text-danger">*</span>
                                        <input id="name" type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name', $restaurant->name) }}">
                                        @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('levels.restaurant_address') }}</label> <span class="text-danger">*</span>
                                    <textarea name="restaurantaddress" class="form-control address-textarea-height @error('restaurantaddress') is-invalid @enderror" id="restaurantaddress">{{ old('restaurantaddress', $restaurant->address) }}</textarea>
                                    @error('restaurantaddress')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>{{ __('levels.description') }}</label>
                                    <textarea name="description" class="form-control address-textarea-height @error('description') is-invalid @enderror" id="description">{{ old('description', $restaurant->description) }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="restaurant_logo">{{ __('restaurant.logo') }}</label>
                                    <div class="custom-file">
                                        <input name="restaurant_logo" type="file"
                                               class="custom-file-input @error('restaurant_logo') is-invalid @enderror"
                                               id="restaurant_logo" onchange="readURL(this,'previewImage');">
                                        <label class="custom-file-label"
                                               for="restaurant_logo">{{ __('levels.choose_file') }}</label>
                                    </div>
                                    @if ($errors->has('restaurant_logo'))
                                        <div class="help-block text-danger">
                                            {{ $errors->first('restaurant_logo') }}
                                        </div>
                                    @endif
                                    <img class="img-thumbnail image-width mt-4 mb-3" id="previewImage"
                                         src="{{ $restaurant->logo }}" alt="{{ $restaurant->name }}" />
                                </div>
                                <div class="form-group">
                                    <label for="customFile">{{ __('restaurant.background_image') }}</label>
                                    <div class="custom-file">
                                        <input name="image" type="file"
                                               class="custom-file-input @error('image') is-invalid @enderror"
                                               id="customFile" onchange="readURL(this,'previewImage2');">
                                        <label class="custom-file-label"
                                               for="customFile">{{ __('levels.choose_file') }}</label>
                                    </div>
                                    @if ($errors->has('image'))
                                        <div class="help-block text-danger">
                                            {{ $errors->first('image') }}
                                        </div>
                                    @endif
                                    <img class="img-thumbnail image-width mt-4 mb-3" id="previewImage2"
                                         src="{{ $restaurant->image }}" alt="{{ $restaurant->name }}" />
                                </div>

                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('levels.restaurant') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-sm-6">
                                        <label>{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                                        <select name="status" class="form-control form-control-sm-custom @error('status') is-invalid @enderror">
                                            @foreach(trans('statuses') as $statusKey => $status)
                                                <option value="{{ $statusKey }}" {{ (old('status', $restaurant->status) == $statusKey) ? 'selected' : '' }}>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group col">
                                        <label>{{ __('levels.current_status') }}</label> <span class="text-danger">*</span>
                                        <select name="current_status" class="form-control form-control-sm-custom @error('current_status') is-invalid @enderror">
                                            @foreach(trans('current_statuses') as $current_statusKey => $current_status)
                                                <option value="{{ $current_statusKey }}" {{ (old('current_status', $restaurant->current_status) == $current_statusKey) ? 'selected' : '' }}>
                                                    {{ $current_status }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('current_status')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary" type="submit">{{ __('levels.submit') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">

                        <div class="card">
                            <div class="card-header">
                                <h5>{{ __('restaurant.restaurant_owner_information') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col">
                                        <label for="first_name">{{ __('levels.first_name') }}</label>
                                        <span class="text-danger">*</span>
                                        <input id="first_name" type="text" name="first_name" class="form-control form-control-sm @error('first_name') is-invalid @enderror" value="{{ old('first_name', $restaurant->user->first_name) }}">
                                        @error('first_name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>

                                    <div class="form-group col">
                                        <label for="last_name">{{ __('levels.last_name') }}</label>
                                        <span class="text-danger">*</span>
                                        <input id="last_name" type="text" name="last_name" class="form-control form-control-sm @error('last_name') is-invalid @enderror" value="{{ old('last_name', $restaurant->user->last_name) }}">
                                        @error('last_name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col">
                                        <label for="email">{{ __('levels.email') }}</label><span class="text-danger"> *</span>
                                        <input id="email" type="text" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror" value="{{ old('email', $restaurant->user->email) }}">
                                        @error('email')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>

                                    <div class="form-group col">
                                        <label for="username">{{ __('levels.username') }}</label>
                                        <input id="username" type="text" name="username" class="form-control form-control-sm @error('username') is-invalid @enderror" value="{{ old('username', $restaurant->user->username) }}">
                                        @error('username')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col">
                                        <label for="password">{{ __('levels.password') }}</label>
                                        <input id="password" type="password" name="password" class="form-control form-control-sm @error('password') is-invalid @enderror" value="{{ old('password') }}">
                                        @error('password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group col">
                                        <label for="phone">{{ __('levels.phone') }}</label><span class="text-danger"> *</span>
                                        <input id="phone" type="text" name="phone" class="form-control form-control-sm @error('phone') is-invalid @enderror" value="{{ old('phone', $restaurant->user->phone) }}">
                                        @error('phone')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">{{ __('levels.address') }}</label>
                                    <span class="text-danger">*</span>
                                    <textarea name="address" class="form-control address-textarea-height @error('address') is-invalid @enderror" id="address">{{ old('address', $restaurant->user->address) }}</textarea>
                                    @error('address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-row">
                                    <div class="form-group col">
                                        <label>{{ __('levels.deposit_amount') }}</label>
                                        <input type="number" step=".01" name="deposit_amount" class="form-control form-control-sm @error('deposit_amount') is-invalid @enderror" value="{{ old('deposit_amount', $restaurant->user->deposit_amount) }}">
                                        @error('deposit_amount')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>

                                    <div class="form-group col">
                                        <label>{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                                        <select name="userstatus" class="form-control form-control-sm-custom @error('userstatus') is-invalid @enderror">
                                            @foreach(trans('user_statuses') as $key => $userstatus)
                                                <option value="{{ $key }}" {{ (old('userstatus', $restaurant->user->status) == $key) ? 'selected' : '' }}>{{ $userstatus }}</option>
                                            @endforeach
                                        </select>
                                        @error('userstatus')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

@endsection

@section('scripts')
    <script src="{{ asset('assets/modules/select2/dist/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('assets/modules/bootstrap-timepicker/js/bootstrap-timepicker.min.js') }}"></script>
    <script async src="https://maps.googleapis.com/maps/api/js?key={{ setting('google_map_api_key') }}&libraries=places&callback=initMap"></script>
    <script src="{{ asset('js/restaurant/create.js') }}"></script>
@endsection
