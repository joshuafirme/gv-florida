(function ($) {
    "use strict"

    $('select[name=pickup]').on('change', function () {
        var counter_id = $(this).val();

        getDroppingPoints(counter_id);
    });

    let pickup = $('select[name=pickup]').val();

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

                let options = '';
                options += `<option value="">--Dropping point--</option>`
                data.forEach(v => {
                    options += `<option value="${v.id}">${v.name}</option>`
                });
                $('select[name=destination]').append(options)


                const queryString = window.location.search;
                const urlParams = new URLSearchParams(queryString);
                setTimeout(() => {
                    let destination = urlParams.get('destination');
                    destination = destination ? destination : urlParams.get('selected_destination');
                    
                    $('select[name=destination]').val(destination).trigger("change");
                }, 1800);
            })
            .catch(error => console.error('Error:', error));
    }
})(jQuery)