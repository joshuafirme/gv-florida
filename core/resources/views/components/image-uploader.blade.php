@props([
    'type' => null,
    'image' => null,
    'imagePath' => null,
    'size' => null,
    'name' => 'image',
    'id' => 'image-upload-input1',
    'accept' => '.png, .jpg, .jpeg',
    'required' => true,
    'darkMode'=>false
])
@php
    $size = $size ?? ($type ? getFileSize($type) : null);
    $imagePath = $imagePath ?? ($type ? getImage(getFilePath($type) . '/' . $image, $size) : null);
@endphp
<div {{ $attributes->merge(['class' => 'image--uploader']) }}>
    <div class="image-upload-wrapper">
        <div class="image-upload-preview {{ $darkMode ? 'bg--dark' : '' }}" style="background-image: url({{ $imagePath }})">
        </div>
        <div class="image-upload-input-wrapper">
            <input type="file" class="image-upload-input" name="{{ $name }}" id="{{ $id }}" accept="{{ $accept }}" @required($required)>
            <label for="{{ $id }}" class="bg--primary"><i class="la la-cloud-upload"></i></label>
        </div>
    </div>

        <div class="mt-2">
            <small class="mt-3 text-muted"> @lang('Supported Files:')
                <b>{{ $accept }}.</b> @if ($size) @lang('Image will be resized into') <b>{{ $size }}</b>@lang('px') @endif
            </small>
        </div>

</div>
