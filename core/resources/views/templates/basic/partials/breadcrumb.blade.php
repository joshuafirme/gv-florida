@php
    $content = getContent('breadcrumb.content', true);
@endphp
<section class="inner-banner bg_img" style="background: url({{ getImage('assets/images/frontend/breadcrumb/' . @$content->data_values->background_image, '1920x1288') }}) center">
    <div class="container">
        <div class="inner-banner-content">
            <h2 class="title">{{ __(@$pageTitle) }}</h2>
        </div>
    </div>
</section>
