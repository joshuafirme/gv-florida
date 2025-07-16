@php
	$captcha = loadCustomCaptcha();
@endphp
@if($captcha)
    <div class="col-lg-12">
        <div class="form--group">
                @php echo $captcha @endphp
            <div class="my-4">
                <input type="text"  name="captcha" class="form--control" placeholder="@lang('Enter Code')">
            </div>
        </div>
    </div>
@endif
