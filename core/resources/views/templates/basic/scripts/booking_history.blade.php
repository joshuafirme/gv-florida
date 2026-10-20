<script>
    (function($) {
        'use strict';

        const modalElement = document.getElementById('infoModal');
        const tickets = @json($bookedTickets->getCollection()->keyBy('id')->all());
        const statusClasses = [
            'ticket-status--success',
            'ticket-status--warning',
            'ticket-status--danger',
            'ticket-status--info'
        ];

        function ticketFrom(element) {
            if (!element) return null;

            return tickets[String(element.dataset.ticketId)] || null;
        }

        function setStatus(selector, label, tone) {
            $(selector)
                .removeClass(statusClasses.join(' '))
                .addClass(`ticket-status--${tone}`)
                .text(label);
        }

        function setModalLink(selector, url) {
            $(selector)
                .attr('href', url || '#')
                .toggleClass('d-none', !url);
        }

        function openTicketDetails(ticket) {
            if (!ticket || !modalElement) return;

            $('#ticketDetailsTitle').text(ticket.pnr || '-');
            $('[data-ticket-detail]').each(function() {
                const key = this.dataset.ticketDetail;
                $(this).text(ticket[key] || '-');
            });
            setStatus('#ticketModalTicketStatus', ticket.ticket_status, ticket.ticket_tone);
            setStatus('#ticketModalPaymentStatus', ticket.payment_status, ticket.payment_tone);
            setModalLink('#ticketModalPay', ticket.can_pay ? ticket.pay_url : null);
            setModalLink('#ticketModalPrint', ticket.can_access_voucher ? ticket.print_url : null);
            setModalLink('#ticketModalVoucher', ticket.can_access_voucher ? ticket.download_url : null);
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }

        $(document).on('click', '.js-ticket-card', function(event) {
            if ($(event.target).closest('a, button').length) return;
            openTicketDetails(ticketFrom(this));
        });

        $(document).on('keydown', '.js-ticket-card', function(event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            if ($(event.target).closest('a, button').length) return;
            event.preventDefault();
            openTicketDetails(ticketFrom(this));
        });

        $(document).on('click', '.js-ticket-details-button', function(event) {
            event.stopPropagation();
            openTicketDetails(ticketFrom(this.closest('.js-ticket-card')));
        });

        $(document).on('click', '.js-ticket-expand', function(event) {
            event.stopPropagation();
            const button = $(this);
            const details = button.closest('.mobile-ticket-card').find('.mobile-ticket-card__details');
            const expanded = button.attr('aria-expanded') === 'true';

            button.attr('aria-expanded', String(!expanded));
            details.prop('hidden', expanded);
        });

        $(document).on('click', '.ticket-actions a, .ticket-actions button', function(event) {
            event.stopPropagation();
        });
    })(jQuery);
</script>
