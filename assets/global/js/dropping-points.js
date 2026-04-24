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
                $('select[name=destination]').empty();


                let options = '';
                options += `<option value="">--Dropping point--</option>`;

                data
                    .filter(v => v.end_to?.name)
                    .sort((a, b) => {
                        const viaA = extractFromKeyword(a.name, 'via ') || '';
                        const viaB = extractFromKeyword(b.name, 'via ') || '';

                        const nameA = `${a.end_to.name} ${viaA}`.trim();
                        const nameB = `${b.end_to.name} ${viaB}`.trim();

                        return nameA.localeCompare(nameB);
                    })
                    .forEach(v => {
                        const via = extractFromKeyword(v.name, 'via ');

                        const label = via ?
                            `${v.end_to.name} ${via}` :
                            v.end_to.name;

                        options += `<option value="${v.end_to.id}">${label}</option>`;
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

    function extractFromKeyword(text, keyword) {
        if (!text || !keyword) return null;

        const lowerText = text.toLowerCase();
        const lowerKeyword = keyword.toLowerCase();

        const pos = lowerText.indexOf(lowerKeyword);

        if (pos === -1) {
            return null; // keyword not found
        }

        // Preserve original casing from the source string
        return text.substring(pos);
    }
})(jQuery)