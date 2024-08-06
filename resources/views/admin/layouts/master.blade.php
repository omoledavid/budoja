<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('admin.layouts.components.head')

<body>
    <audio id="myAudio1">
        <source src="{{asset('beep.mp3')}}" type="audio/mpeg">
    </audio>

    <div id="app">
        <div class="main-wrapper">
            @include('admin.layouts.components.navigation')
            @include('admin.layouts.components.sidebar')

            <!-- Main Content -->
            <div class="main-content">
                @yield('main-content')
            </div>
            @include('admin.layouts.components.footer')
            @include('admin.layouts.components.modal')

        </div>
    </div>

    <div id="custom-width-modal" class="modal fade purchase-model" tabindex="-1" role="dialog"
        aria-labelledby="custom-width-modalLabel" aria-hidden="true">
        <div class="modal-dialog model-dialog-purchase">
            <div class="modal-content">
                <div class="modal-header push_modal p-2">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body p-3">
                    <h4 id="notificationTitle"></h4>
                    <p class="m-0" id="notificationBody"></p>
                </div>
                <div class="modal-footer ps-3 pe-3 pb-3 pt-0">
                    <button type="button" class="btn btn-primary"  data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @include('admin.layouts.components.script')

    @yield('footer-js')

</body>

</html>
