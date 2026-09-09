@extends('admin.layouts.app')

@section('panel')
    @php
        $heroVersion = file_exists($heroPath) ? filemtime($heroPath) : appVersion();
        $heroUrl = getImage($heroPath) . '?v=' . $heroVersion;
    @endphp
    <form method="POST" action="{{ route('admin.setting.kiosk.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="kiosk-settings-layout">
            <section class="card kiosk-settings-form">
                <div class="card-header">
                    <h5 class="mb-0">Kiosk Idle Screen</h5>
                </div>
                <div class="card-body">
                    <x-image-uploader class="w-100" :imagePath="$heroUrl" :required="false"
                        name="hero_image" id="kioskHeroImage" />

                    <div class="form-group mt-4">
                        <label>Headline</label>
                        <textarea class="form-control" name="headline" id="kioskHeadline" rows="2" required>{{ old('headline', $settings['headline']) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Tagline</label>
                        <input class="form-control" type="text" name="tagline" id="kioskTagline"
                            value="{{ old('tagline', $settings['tagline']) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Button Text</label>
                        <input class="form-control" type="text" name="button_text" id="kioskButtonText"
                            value="{{ old('button_text', $settings['button_text']) }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Feature 1</label>
                                <textarea class="form-control" name="benefit_one" id="kioskBenefitOne" rows="2" required>{{ old('benefit_one', $settings['benefit_one']) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Feature 2</label>
                                <textarea class="form-control" name="benefit_two" id="kioskBenefitTwo" rows="2" required>{{ old('benefit_two', $settings['benefit_two']) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Feature 3</label>
                                <textarea class="form-control" name="benefit_three" id="kioskBenefitThree" rows="2" required>{{ old('benefit_three', $settings['benefit_three']) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn--primary w-100 h-45">
                        <i class="las la-save"></i> Save Kiosk Settings
                    </button>
                </div>
            </section>

            <section class="kiosk-settings-preview" aria-label="Kiosk hero preview">
                <img id="kioskHeroPreview" src="{{ $heroUrl }}" alt="Kiosk hero preview">
                <div class="kiosk-settings-preview__content">
                    <div class="kiosk-settings-preview__headline">
                        <i class="las la-bus"></i>
                        <strong id="kioskPreviewHeadline">{{ old('headline', $settings['headline']) }}</strong>
                        <small id="kioskPreviewTagline">{{ old('tagline', $settings['tagline']) }}</small>
                    </div>
                    <div class="kiosk-settings-preview__footer">
                        <div class="kiosk-settings-preview__benefits">
                            <span id="kioskPreviewBenefitOne">{{ old('benefit_one', $settings['benefit_one']) }}</span>
                            <span id="kioskPreviewBenefitTwo">{{ old('benefit_two', $settings['benefit_two']) }}</span>
                            <span id="kioskPreviewBenefitThree">{{ old('benefit_three', $settings['benefit_three']) }}</span>
                        </div>
                        <b id="kioskPreviewButtonText">{{ old('button_text', $settings['button_text']) }}</b>
                    </div>
                </div>
            </section>
        </div>
    </form>
@endsection

@push('style')
    <style>
        .kiosk-settings-layout{align-items:start;display:grid;gap:24px;grid-template-columns:minmax(300px,.7fr) minmax(360px,1.3fr)}
        .kiosk-settings-form{border-radius:8px;overflow:hidden}
        .kiosk-settings-preview{aspect-ratio:4/5;background:#151a20;border-radius:8px;max-height:680px;overflow:hidden;position:relative;width:100%}
        .kiosk-settings-preview::after{background:linear-gradient(180deg,rgba(255,255,255,.1) 0%,rgba(255,255,255,.03) 38%,rgba(16,20,26,.68) 100%);content:'';inset:0;position:absolute}
        .kiosk-settings-preview>img{height:100%;object-fit:cover;width:100%}
        .kiosk-settings-preview__content{align-items:center;color:#fff;display:flex;flex-direction:column;inset:7% 7% 6%;justify-content:space-between;position:absolute;text-align:center;z-index:1}
        .kiosk-settings-preview__headline{align-items:center;display:flex;flex-direction:column}
        .kiosk-settings-preview__headline i{color:var(--primary-color,#df2a82);font-size:38px}
        .kiosk-settings-preview__headline strong{color:#20252c;font-size:74px;line-height:.82;margin-top:12px;white-space:pre-line}
        .kiosk-settings-preview__headline small{color:var(--primary-color,#df2a82);font-size:17px;font-weight:800;margin-top:18px;text-transform:uppercase}
        .kiosk-settings-preview__footer{width:100%}
        .kiosk-settings-preview__benefits{display:grid;grid-template-columns:repeat(3,1fr);margin-bottom:20px}
        .kiosk-settings-preview__benefits span{border-right:1px solid rgba(255,255,255,.5);font-size:12px;font-weight:700;padding:0 8px;text-transform:uppercase;white-space:pre-line}
        .kiosk-settings-preview__benefits span:last-child{border-right:0}
        .kiosk-settings-preview__footer b{background:#fff;border-radius:999px;color:var(--primary-color,#df2a82);display:inline-block;font-size:18px;font-weight:800;padding:14px 30px;text-transform:uppercase}
        @media(max-width:991px){.kiosk-settings-layout{grid-template-columns:1fr}.kiosk-settings-preview{justify-self:center;max-width:620px}}
        @media(max-width:575px){.kiosk-settings-preview__headline strong{font-size:54px}.kiosk-settings-preview__headline small{font-size:13px}.kiosk-settings-preview__benefits span{font-size:9px}.kiosk-settings-preview__footer b{font-size:15px;padding:12px 22px}}
    </style>
@endpush

@push('script')
    <script>
        document.getElementById('kioskHeroImage')?.addEventListener('change', function () {
            const file = this.files?.[0];
            if (!file) return;
            document.getElementById('kioskHeroPreview').src = URL.createObjectURL(file);
        });

        const kioskPreviewBindings = {
            kioskHeadline: 'kioskPreviewHeadline',
            kioskTagline: 'kioskPreviewTagline',
            kioskButtonText: 'kioskPreviewButtonText',
            kioskBenefitOne: 'kioskPreviewBenefitOne',
            kioskBenefitTwo: 'kioskPreviewBenefitTwo',
            kioskBenefitThree: 'kioskPreviewBenefitThree'
        };

        Object.entries(kioskPreviewBindings).forEach(([inputId, previewId]) => {
            document.getElementById(inputId)?.addEventListener('input', function () {
                document.getElementById(previewId).textContent = this.value;
            });
        });
    </script>
@endpush
