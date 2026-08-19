<style>
    .developer-page {
        --developer-accent: #4634ff;
        color: #303642;
    }

    .developer-summary {
        align-items: center;
        background: #fff;
        border: 1px solid #e1e4e9;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
        max-width: 385px;
        padding: 18px 20px;
    }

    .developer-summary span,
    .developer-summary strong {
        display: block;
    }

    .developer-summary span {
        color: #737b89;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .developer-summary strong {
        font-size: 24px;
        line-height: 1.2;
        margin-top: 4px;
    }

    .developer-summary > i {
        align-items: center;
        background: rgba(70, 52, 255, .1);
        border-radius: 8px;
        color: var(--developer-accent);
        display: inline-flex;
        font-size: 29px;
        height: 58px;
        justify-content: center;
        width: 58px;
    }

    .developer-filters {
        background: #fff;
        border: 1px solid #e1e4e9;
        border-radius: 8px;
        margin-bottom: 20px;
        padding: 14px;
    }

    .developer-filters form {
        align-items: end;
        display: grid;
        gap: 10px;
        grid-template-columns: minmax(270px, 2fr) minmax(155px, .9fr) repeat(2, minmax(155px, .8fr)) auto auto auto;
    }

    .developer-filters--webhooks form {
        grid-template-columns: minmax(250px, 1.7fr) repeat(2, minmax(140px, .8fr)) repeat(2, minmax(145px, .75fr)) auto auto;
    }

    .developer-search {
        position: relative;
    }

    .developer-search i {
        color: #8992a1;
        font-size: 18px;
        left: 12px;
        pointer-events: none;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
    }

    .developer-search input,
    .developer-filters .form-control {
        border: 1px solid #d8dce3;
        border-radius: 7px;
        height: 42px;
        min-width: 0;
        width: 100%;
    }

    .developer-search input {
        padding: 8px 12px 8px 38px;
    }

    .developer-date label {
        color: #697181;
        display: block;
        font-size: 11px;
        font-weight: 700;
        margin: 0 0 4px;
        text-transform: uppercase;
    }

    .developer-filters .btn {
        align-items: center;
        display: inline-flex;
        height: 42px;
        justify-content: center;
        white-space: nowrap;
    }

    .developer-table-card {
        background: #fff;
        border: 1px solid #e1e4e9;
        border-radius: 8px;
        overflow: hidden;
    }

    .developer-table {
        margin-bottom: 0;
        min-width: 960px;
    }

    .developer-webhook-table {
        min-width: 1180px;
    }

    .developer-table th,
    .developer-table td {
        padding: 13px 14px;
        vertical-align: middle;
    }

    .developer-table td {
        color: #424a58;
        font-size: 12px;
    }

    .developer-table td > strong,
    .developer-table td > small {
        display: block;
    }

    .developer-table td > small {
        color: #77808f;
        margin-top: 4px;
    }

    .developer-transaction-id {
        color: var(--developer-accent);
        font-size: 13px;
    }

    .developer-transaction-id i {
        font-size: 17px;
        vertical-align: -2px;
    }

    .developer-amount {
        color: #252b37;
        font-size: 15px;
    }

    .developer-status-actions {
        align-items: center;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 7px;
    }

    .developer-details-button {
        align-items: center;
        background: transparent;
        border: 1px solid var(--developer-accent);
        border-radius: 5px;
        color: var(--developer-accent);
        display: inline-flex;
        font-size: 17px;
        height: 27px;
        justify-content: center;
        padding: 0;
        width: 31px;
    }

    .developer-details-button:hover,
    .developer-details-button[aria-expanded="true"] {
        background: var(--developer-accent);
        color: #fff;
    }

    .developer-detail-row > td {
        background: #f7f8fa;
    }

    .developer-detail-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin: 0;
        padding: 18px;
    }

    .developer-detail-grid > div {
        background: #fff;
        border: 1px solid #e2e5ea;
        border-radius: 6px;
        min-width: 0;
        padding: 10px 12px;
    }

    .developer-detail-grid dt,
    .developer-detail-grid dd {
        overflow-wrap: anywhere;
    }

    .developer-detail-grid dt {
        color: #7a8290;
        font-size: 10px;
        text-transform: uppercase;
    }

    .developer-detail-grid dd {
        font-size: 12px;
        font-weight: 600;
        margin: 3px 0 0;
    }

    .developer-identifiers strong {
        color: var(--developer-accent);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .developer-webhook-details {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        padding: 16px;
    }

    .developer-payment-payloads {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        padding: 0 18px 18px;
    }

    .developer-payment-payloads section {
        min-width: 0;
    }

    .developer-payment-payloads h4 {
        color: #596273;
        font-size: 12px;
        margin: 0 0 7px;
    }

    .developer-payment-payloads pre {
        background: #252a31;
        border: 1px solid #3c434d;
        border-radius: 7px;
        color: #f1f3f5;
        font-size: 11px;
        margin: 0;
        max-height: 420px;
        overflow: auto;
        padding: 14px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .developer-json-empty {
        background: #fff;
        border: 1px dashed #d6dae1;
        border-radius: 7px;
        color: #7a8290;
        font-size: 12px;
        padding: 18px;
    }

    .developer-webhook-details section {
        min-width: 0;
    }

    .developer-webhook-details h4 {
        color: #596273;
        font-size: 12px;
        margin: 0 0 7px;
    }

    .developer-webhook-details pre {
        background: #252a31;
        border: 1px solid #3c434d;
        border-radius: 7px;
        color: #f1f3f5;
        font-size: 11px;
        margin: 0;
        max-height: 360px;
        overflow: auto;
        padding: 14px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .developer-webhook-error,
    .developer-webhook-headers {
        grid-column: 1 / -1;
    }

    .developer-webhook-error h4 {
        color: #c0392b;
    }

    .developer-webhook-error pre {
        background: #fff5f4;
        border-color: #efb4ae;
        color: #a62c21;
    }

    .developer-empty-icon,
    .developer-table .text-muted > span {
        display: block;
    }

    .developer-empty-icon {
        font-size: 32px;
        margin-bottom: 6px;
    }

    .developer-pagination {
        border-top: 1px solid #e5e7eb;
        padding: 14px 16px;
    }

    @media (max-width: 1399px) {
        .developer-filters form,
        .developer-filters--webhooks form {
            grid-template-columns: minmax(240px, 2fr) repeat(3, minmax(140px, 1fr));
        }
    }

    @media (max-width: 767px) {
        .developer-summary {
            max-width: none;
        }

        .developer-filters form,
        .developer-filters--webhooks form,
        .developer-webhook-details,
        .developer-payment-payloads,
        .developer-detail-grid {
            grid-template-columns: 1fr;
        }

        .developer-webhook-error,
        .developer-webhook-headers {
            grid-column: auto;
        }
    }
</style>
