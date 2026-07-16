(function ($) {
    "use strict"

    $('select[name=pickup]').on('change', function () {
        var counter_id = $(this).val();
        if(counter_id) {
            getDroppingPoints(counter_id);
        } else {
            let $destination = $('select[name=destination]');
            let defaultOption = $destination.data('default-option') || '--Dropping point--';
            $destination.html(`<option value="">${defaultOption}</option>`).val('').trigger('change');
        }
    });

    let pickup = $('select[name=pickup]').val();

    if (pickup) {
        getDroppingPoints(pickup);
    }

    function getDroppingPoints(counter_id) {
        let host = window.location.hostname;
        let url = '/trip/dropping-points/';

        // Preserve your local environment routing
        if (host.includes('local')) {
            url = '/gv-florida/trip/dropping-points/';
        }

        fetch(url + counter_id)
            .then(response => response.json())
            .then(function (data) {
                let $destination = $('select[name=destination]');
                $destination.empty();

                let defaultOption = $destination.data('default-option') || '--Dropping point--';
                let options = `<option value="">${defaultOption}</option>`;

                // The backend now strictly returns an array of valid {id, name}
                data.forEach(v => {
                    options += `<option value="${v.id}">${v.name}</option>`;
                });

                $destination.append(options);

                // Re-select previously chosen destination if it exists in the URL
                const queryString = window.location.search;
                const urlParams = new URLSearchParams(queryString);
                
                setTimeout(() => {
                    let destination = urlParams.get('destination') || urlParams.get('selected_destination');
                    if (destination) {
                        $destination.val(destination).trigger("change");
                    } else {
                        $destination.val('').trigger('change');
                    }
                }, 1000); // Shorter timeout for a snappier UI response
            })
            .catch(error => console.error('Error fetching dropping points:', error));
    }
})(jQuery)
