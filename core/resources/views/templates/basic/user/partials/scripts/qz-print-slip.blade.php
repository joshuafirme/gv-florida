    <script src="{{ asset('assets/admin/js/vendor/qz-tray.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/qz-printer.js') }}"></script>
    <script>
        $(document).ready(function() {

            const BASE_URL = "{{ url('/') }}/";
            const id = "{{ $ticket->id }}";

            printVouch()

            function printVouch() {

                connectQZ()

                    .then(() => {


                        return getPrinter();
                    })

                    .then(printer => {
                        let btn = $('#printBtn');
                        let default_btn = btn.html();
                        btn.html("Printing...")
                        btn.prop('disabled', true)

                        let config = qz.configs.create(printer, {
                            scaleContent: true,
                            colorType: 'color'
                        });

                        fetch(BASE_URL + 'api/ticket/download/reservation-slip/' + id)
                            .then(res => res.json())
                            .then(data => {
                                btn.html(default_btn)
                                btn.prop('disabled', false)
                                qz.print(config, [{
                                    type: 'pdf',
                                    format: 'file',
                                    data: data.file_url,
                                    options: {
                                        autoRotate: true
                                    }
                                }]);
                            })
                            .catch(console.error);
                    })

                    .then(() => {

                        $('#status').text('✅ Printed successfully!');
                    })

                    .catch(err => {

                        console.error(err);

                        $('#status').text('❌ Error: ' + err);

                        alert('Print Error: ' + err);
                    });
            }


        });
    </script>
