<div class="row">
    @foreach ($stoppages as $item)
        @if ($item[0] != $item[1])
            @php $sd = getStoppageInfo($item) @endphp
            <div class="col-md-4">
                <div class="form-group">
                    <label for="point-{{ $loop->iteration }}" required>{{ $sd[0]->name }} - {{ $sd[1]->name }}</label>
                    <div class="input-group">
                        <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                        <input type="number" class="form-control prices-auto numeric-validation" name="price[{{ $sd[0]->id }}-{{ $sd[1]->id }}]" required>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
<script>
    'use strict';
    (function($) {
        $(".numeric-validation").keypress(function(e) {
            var unicode = e.charCode ? e.charCode : e.keyCode
            if (unicode != 8 && e.key != '.' && unicode != 45) {
                if ((unicode < 2534 || unicode > 2543) && (unicode < 48 || unicode > 57)) {
                    return false;
                }
            }
        });
    })(jQuery)
</script>
