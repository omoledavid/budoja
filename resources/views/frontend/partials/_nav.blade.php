<!--========== HEADER PART START ===========-->
<header class="heder-padding">
    <div class="header fixed sticky">
        <div class="container">
            <div class="header-content">

                <a href="{{ route('home') }}" class="header-logo">
                    <img src="@if (\Route::current()->getName() === ' home') {{ asset('images/' . setting('site_logo')) }}
                         @else {{ asset('images/' . setting('site_logo')) }} @endif"
                        data-sticky-logo="{{ asset('images/' . setting('site_logo')) }}" alt="logo">
                </a>

                @php
                    $href = 'javascript:void(0)';
                    if (!blank(session()->get('session_cart_restaurant'))) {
                        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
                        $href = $routeName != 'restaurant.show' ? route('restaurant.show', [session()->get('session_cart_restaurant')]) : 'javascript:void(0)';
                    }
                @endphp

                <div class="header-group">





                </div>
            </div>
        </div>
    </div>
</header>
<!--===== HEADER PART END ========-->
