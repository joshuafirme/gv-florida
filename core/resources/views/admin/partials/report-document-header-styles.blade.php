@if ($pdfHeader ?? false)
    .report-document-header {
        display: table;
        width: 100%;
        margin: 0;
        padding: 0 0 4px;
        border-bottom: .6px solid #ccd2da;
    }

    .report-document-header__brand,
    .report-document-header__identity,
    .report-document-header__generated {
        display: table-cell;
        vertical-align: middle;
    }

    .report-document-header__brand {
        width: 27%;
        color: #11151d;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .report-document-header__identity {
        width: 48%;
    }

    .report-document-header__identity h1 {
        margin: 0 0 1px;
        color: #d92378;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.05;
    }

    .report-document-header__context,
    .report-document-header__filters {
        margin: 0;
        color: #586170;
        font-size: 6.3px;
        line-height: 1.15;
    }

    .report-document-header__filters {
        margin-top: 1px;
        color: #7d3158;
    }

    .report-document-header__generated {
        width: 25%;
        color: #697280;
        text-align: right;
    }

    .report-document-header__generated span,
    .report-document-header__generated strong {
        display: block;
    }

    .report-document-header__generated span {
        font-size: 5.5px;
        text-transform: uppercase;
    }

    .report-document-header__generated strong {
        margin-top: 1px;
        color: #303642;
        font-size: 6.5px;
        font-weight: 700;
    }
@else
    .report-document-header {
        align-items: center;
        display: grid;
        gap: 18px;
        grid-template-columns: minmax(190px, .8fr) minmax(300px, 1.5fr) minmax(170px, auto);
        margin: 0;
        padding: 0 0 10px;
        border-bottom: 1px solid #dfe3e8;
    }

    .report-document-header__brand {
        color: #11151d;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .report-document-header__identity h1 {
        margin: 0 0 2px;
        color: #d92378;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.1;
    }

    .report-document-header__context,
    .report-document-header__filters {
        margin: 0;
        color: #626b79;
        font-size: 10px;
        line-height: 1.3;
    }

    .report-document-header__filters {
        margin-top: 2px;
        color: #8c315d;
        font-weight: 600;
    }

    .report-document-header__generated {
        color: #707887;
        text-align: right;
    }

    .report-document-header__generated span,
    .report-document-header__generated strong {
        display: block;
    }

    .report-document-header__generated span {
        font-size: 9px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .report-document-header__generated strong {
        margin-top: 1px;
        color: #303642;
        font-size: 10px;
        font-weight: 700;
    }

    @media (max-width: 767px) {
        .report-document-header {
            gap: 5px;
            grid-template-columns: 1fr;
        }

        .report-document-header__generated {
            text-align: left;
        }
    }

    @media print {
        .report-document-header {
            gap: 10px;
            grid-template-columns: 1fr 1.6fr .8fr;
            padding-bottom: 4px;
        }

        .report-document-header__brand {
            font-size: 8px;
        }

        .report-document-header__identity h1 {
            margin-bottom: 1px;
            font-size: 12px;
        }

        .report-document-header__context,
        .report-document-header__filters,
        .report-document-header__generated strong {
            font-size: 6px;
            line-height: 1.1;
        }

        .report-document-header__generated span {
            font-size: 5px;
        }
    }
@endif
