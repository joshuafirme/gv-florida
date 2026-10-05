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
                    <div class="kiosk-settings-preview__footer">
                        <div class="kiosk-settings-preview__benefits">
                            <span><i class="las la-chair"></i><b id="kioskPreviewBenefitOne">{{ old('benefit_one', $settings['benefit_one']) }}</b></span>
                            <span><i class="las la-shield-alt"></i><b id="kioskPreviewBenefitTwo">{{ old('benefit_two', $settings['benefit_two']) }}</b></span>
                            <span><i class="las la-map-marker-alt"></i><b id="kioskPreviewBenefitThree">{{ old('benefit_three', $settings['benefit_three']) }}</b></span>
                        </div>
                        <strong><i class="las la-hand-pointer"></i><span id="kioskPreviewButtonText">{{ old('button_text', $settings['button_text']) }}</span></strong>
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
        .kiosk-settings-preview::after{background:linear-gradient(180deg,rgba(255,255,255,.04) 35%,rgba(16,20,26,.78) 100%);content:'';inset:0;position:absolute}
        .kiosk-settings-preview>img{height:100%;object-fit:cover;width:100%}
        .kiosk-settings-preview__content{align-items:center;color:#fff;display:flex;inset:0;justify-content:center;padding:0 6% 6%;position:absolute;text-align:center;z-index:1}
        .kiosk-settings-preview__footer{align-self:flex-end;background:rgba(210,30,119,.94);border-radius:8px;padding:18px 20px;width:100%}
        .kiosk-settings-preview__benefits{align-items:start;display:grid;grid-template-columns:repeat(3,1fr);margin-bottom:18px}
        .kiosk-settings-preview__benefits span{align-items:center;border-right:1px solid rgba(255,255,255,.5);display:flex;flex-direction:column;font-size:12px;font-weight:700;gap:5px;padding:0 8px;text-transform:uppercase;white-space:pre-line}
        .kiosk-settings-preview__benefits span:last-child{border-right:0}
        .kiosk-settings-preview__benefits i{border:2px solid #fff;border-radius:50%;display:grid;font-size:20px;height:42px;place-items:center;width:42px}
        .kiosk-settings-preview__benefits b{font:inherit}
        .kiosk-settings-preview__footer>strong{align-items:center;background:#fff;border-radius:999px;color:var(--primary-color,#df2a82);display:inline-flex;font-size:20px;font-weight:900;gap:10px;justify-content:center;min-height:54px;padding:10px 30px;text-transform:uppercase}
        .kiosk-settings-preview__footer>strong i{background:var(--primary-color,#df2a82);border-radius:50%;color:#fff;display:grid;font-size:22px;height:36px;place-items:center;width:36px}
        @media(max-width:991px){.kiosk-settings-layout{grid-template-columns:1fr}.kiosk-settings-preview{justify-self:center;max-width:620px}}
        @media(max-width:575px){.kiosk-settings-preview__content{padding:0 12px 12px}.kiosk-settings-preview__footer{padding:14px 10px}.kiosk-settings-preview__benefits span{font-size:10px;padding:0 4px}.kiosk-settings-preview__benefits i{font-size:17px;height:36px;width:36px}.kiosk-settings-preview__footer>strong{font-size:16px;min-height:48px;padding:8px 20px}}
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
