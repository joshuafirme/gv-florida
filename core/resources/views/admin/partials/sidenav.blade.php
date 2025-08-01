@php
    $sideBarLinks = json_decode($sidenav);
@endphp

<div class="sidebar bg--dark">
    <button class="res-sidebar-close-btn"><i class="las la-times"></i></button>
    <div class="sidebar__inner">
        <div class="sidebar__logo">
            <a href="{{ route('admin.dashboard') }}" class="sidebar__main-logo"><img src="{{ siteLogo('dark') }}"
                    alt="image"></a>
        </div>
        <div class="sidebar__menu-wrapper">
            <ul class="sidebar__menu">
                @foreach ($sideBarLinks as $key => $data)
                    @php
                        $menu_active = is_array($data->menu_active) ? $data->menu_active[0] : $data->menu_active;
                        $submenus_arr = is_array(@$data->submenu) ? array_column(@$data->submenu, 'route_name') : [];
                    @endphp
                    @if (@$data->header)
                        <li class="sidebar__menu-header">{{ __($data->header) }}</li>
                    @endif
                    @if (@$data->submenu && is_array($submenus_arr) && array_intersect($submenus_arr, $permissions))
                        <li class="sidebar-menu-item sidebar-dropdown">
                            <a href="javascript:void(0)" class="{{ menuActive(@$data->menu_active, 3) }}">
                                <i class="menu-icon {{ @$data->icon }}"></i>
                                <span class="menu-title">{{ __(@$data->title) }}</span>
                                @foreach (@$data->counters ?? [] as $counter)
                                    @if ($$counter > 0)
                                        <span class="menu-badge menu-badge-level-one bg--warning ms-auto">
                                            <i class="fas fa-exclamation"></i>
                                        </span>
                                        @break
                                    @endif
                                @endforeach
                            </a>
                            <div class="sidebar-submenu {{ menuActive(@$data->menu_active, 2) }} ">
                                <ul>
                                    @foreach ($data->submenu as $menu)
                                        @php
                                            $_menu_active = is_array($menu->menu_active)
                                                ? $menu->menu_active[0]
                                                : $menu->menu_active;
                                        @endphp
                                        @if (in_array($_menu_active, $permissions))
                                            @php
                                                $submenuParams = null;
                                                if (@$menu->params) {
                                                    foreach ($menu->params as $submenuParamVal) {
                                                        $submenuParams[] = array_values((array) $submenuParamVal)[0];
                                                    }
                                                }
                                            @endphp
                                            <li class="sidebar-menu-item {{ menuActive(@$menu->menu_active) }} ">
                                                <a href="{{ route(@$menu->route_name, $submenuParams) }}"
                                                    class="nav-link">
                                                    <i class="menu-icon las la-dot-circle"></i>
                                                    <span class="menu-title">{{ $menu->title }} </span>
                                                    @php $counter = @$menu->counter; @endphp
                                                    @if (@$$counter)
                                                        <span
                                                            class="menu-badge bg--info ms-auto">{{ @$$counter }}</span>
                                                    @endif
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @else
                        @php
                            $mainParams = null;
                            if (@$data->params) {
                                foreach ($data->params as $paramVal) {
                                    $mainParams[] = array_values((array) $paramVal)[0];
                                }
                            }
                        @endphp
                        @if (in_array($menu_active, $permissions))
                            <li class="sidebar-menu-item {{ menuActive(@$data->menu_active) }}">
                                <a href="{{ route(@$data->route_name, $mainParams) }}" class="nav-link ">
                                    <i class="menu-icon {{ $data->icon }}"></i>
                                    <span class="menu-title">{{ __(@$data->title) }}</span>
                                    @php $counter = @$data->counter; @endphp
                                    @if (@$$counter)
                                        <span class="menu-badge bg--info ms-auto">{{ @$$counter }}</span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    @endif
                @endforeach
            </ul>
        </div>
        <div class="version-info text-center text-uppercase">
            <span class="text--primary">{{ __(systemDetails()['name']) }}</span>
            <span class="text--success">@lang('V'){{ systemDetails()['version'] }} </span>
        </div>
    </div>
</div>
<!-- sidebar end -->

@push('script')
    <script>
        if ($('li').hasClass('active')) {
            $('.sidebar__menu-wrapper').animate({
                scrollTop: eval($(".active").offset().top - 320)
            }, 500);
        }
    </script>
@endpush
