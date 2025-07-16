<?php
header("Content-Type:text/css");
$color = "#f0f"; // Change your Color Here

function checkhexcolor($color)
{
    return preg_match('/^#[a-f0-9]{6}$/i', $color);
}

if (isset($_GET['color']) and $_GET['color'] != '') {
    $color = "#" . $_GET['color'];
}

if (!$color or !checkhexcolor($color)) {
    $color = "#336699";
}
?>


:root{
--main-color: <?php echo $color; ?>;
}

.working-process-item .thumb {
box-shadow: 0 0 0 8px <?php echo $color; ?>1a;
background: <?php echo $color; ?>40;
}
.booking-table tbody tr .action-button-wrapper .print {
background: <?php echo $color; ?>20;
}

.form--control:focus {
border: 1px solid <?php echo $color; ?>e6;
}
.page-item.active .page-link, .form--control:focus ~ .input-group-append .input-group-text {
border-color: <?php echo $color; ?>;
}
.page-item .page-link, .info-item .icon, .input-group-append .input-group-text{
color:<?php echo $color; ?>;
}
.cookies-card__icon {
background-color: <?php echo $color; ?>21;
color: <?php echo $color; ?>4d;
}

.cookies-btn:hover {
border-color: <?php echo $color; ?>4d;
background-color: <?php echo $color; ?>4d;
}

.ticket-form .form--group .form--control {
border-color: <?php echo $color; ?>40;
}

.ticket-form .form--group .form--control:focus, .select2-container--default .select2-selection--single:focus {
border-color: <?php echo $color; ?>f2;
}
.ui-widget-header {
background: <?php echo $color; ?>e6;
}
.amenities-item:hover{
box-shadow: 0 0 10px 1px <?php echo $color; ?>40;
}
.amenities-item:hover .thumb {
color: <?php echo $color; ?>cc;
border-color: <?php echo $color; ?>99;
}
.faq-item .faq-title {
border: 1px solid <?php echo $color; ?>40;
}
.faq-item .faq-title .icon::after, .faq-item .faq-title .icon::before, .btn--base:hover, .cmn--btn:hover {
background: <?php echo $color; ?>e6;
}
.footer-widget .widget-title::after, .footer-widget .widget-title::before {
background: <?php echo $color; ?>b3;
}

.banner-section::before, .banner-section::after {
background: <?php echo $color; ?>4a;
}
.profile__content__edit .title, .booking-table thead tr, .page-item.active .page-link,.page-item:not(.disabled):hover .page-link, .cmn--card .card-header, .sub-menu li a:hover {
background: <?php echo $color; ?>;
}
.btn-check:focus+.btn, .btn--base:focus,.cmn--btn:focus {
box-shadow: 0 0 0 0.25rem <?php echo $color; ?>40;
}
.preloader-content {
border-top: 1px solid <?php echo $color; ?>50;
background: <?php echo $color; ?>7a;
background: -webkit-gradient(linear, left top, right top, from(<?php echo $color; ?>50), to(<?php echo $color; ?>64));
background: linear-gradient(to right, <?php echo $color; ?>50 0%, <?php echo $color; ?>64 100%);
filter: progid: DXImageTransform.Microsoft.gradient( startColorstr='<?php echo $color; ?>c8', endColorstr='<?php echo $color; ?>d6', GradientType=1);
}