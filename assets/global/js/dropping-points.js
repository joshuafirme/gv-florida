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

    $('input[name=date_of_journey]').on('change', function () {
        let selectedPickup = $('select[name=pickup]').first().val();
        if (selectedPickup) {
            getDroppingPoints(selectedPickup);
        }
    });

    function getDroppingPoints(counter_id) {
        let host = window.location.hostname;
        let url = '/trip/dropping-points/';
        const pageParams = new URLSearchParams(window.location.search);
        const kioskId = pageParams.get('kiosk_id');
        const journeyDate = $('input[name=date_of_journey]').first().val()
            || pageParams.get('date_of_journey');

        // Preserve your local environment routing
        if (host.includes('local')) {
            url = '/gv-florida/trip/dropping-points/';
        }

        const requestParams = new URLSearchParams();
        if (kioskId) {
            requestParams.set('kiosk_id', kioskId);
        }
        if (journeyDate) {
            requestParams.set('date_of_journey', journeyDate);
        }
        const channelQuery = requestParams.toString() ? `?${requestParams.toString()}` : '';

        fetch(url + counter_id + channelQuery)
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
