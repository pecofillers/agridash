<?php

namespace Tests\Unit;

use App\Http\Controllers\RendimientoController;
use PHPUnit\Framework\TestCase;

class RendimientoControllerTest extends TestCase
{
    public function test_color_semaforo_handles_null_labor_gracefully(): void
    {
        $controller = new RendimientoController();
        $method = new \ReflectionMethod(RendimientoController::class, 'colorSemaforo');
        $method->setAccessible(true);

        $this->assertSame('#d32f2f', $method->invokeArgs($controller, [0.0, null]));
    }
}
