<?php

namespace Tests\Unit;

use Dompdf\Dompdf;
use Tests\TestCase;

class PublicPathConfigurationTest extends TestCase
{
    public function test_dompdf_resolves_the_deployment_public_path(): void
    {
        $this->assertDirectoryExists(public_path());
        $this->assertSame(realpath(public_path()), realpath(config('dompdf.public_path')));
        $this->assertInstanceOf(Dompdf::class, app('dompdf'));

        $pdf = app('dompdf.wrapper')->loadHTML('<h1>Reservation Slip</h1>')->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
