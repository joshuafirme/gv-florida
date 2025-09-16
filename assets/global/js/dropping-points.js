(function ($) {
    "use strict"

    $('select[name=pickup]').on('change', function () {
        var counter_id = $(this).val();

        getDroppingPoints(counter_id);
    });
    
    $('select[name=pickup_point]').on('change', function () {
        var counter_id = $(this).val();

        getDroppingPoints(counter_id);
    });

    let pickup = $('select[name=pickup]').val();
    pickup = !pickup ? $('select[name=pickup_point]').val() : '';

    if (pickup) {
        getDroppingPoints(pickup);
    }

    function getDroppingPoints(counter_id) {
        let host = window.location.hostname;
        let url = '/trip/dropping-points/';

        if (host.includes('local')) {
            url = '/gv-florida/trip/dropping-points/';
        }

        fetch(url + counter_id)
            .then(response => response.json())
            .then(function (data) {
                console.log('data----', data)
                $('select[name=destination]').empty();
                $('select[name=dropping_point]').empty();
                
                let options = '';
                data.forEach(v => {
                    options += `<option value="${v.id}">${v.name}</option>`
                });
                $('select[name=destination]').append(options)
                $('select[name=dropping_point]').append(options)
            })
            .catch(error => console.error('Error:', error));
    }
})(jQuery)