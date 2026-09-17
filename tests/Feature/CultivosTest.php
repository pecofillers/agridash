<?php

namespace Tests\Feature;

use App\Models\{Bloque, CicloSiembra, Produccion, Siembra, Ubicacion, Usuario, Variedad};
use App\Http\Controllers\ProduccionController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CultivosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // No migraciones históricas ni conexiones a la base de desarrollo.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $views = sys_get_temp_dir().DIRECTORY_SEPARATOR.'agridash_views_'.getmypid();
        if (!is_dir($views)) mkdir($views);
        config(['view.compiled' => $views]);
        Schema::create('dim_bloques', function (Blueprint $t) {
            $t->increments('ID_Bloque'); $t->string('Codigo_Bloque')->unique();
            $t->string('Nombre_Bloque'); $t->string('Estado')->default('ACTIVO'); $t->timestamps();
        });
        Schema::create('dim_variedades', function (Blueprint $t) {
            $t->increments('ID_Variedad'); $t->string('Nombre_Variedad'); $t->string('Color')->nullable();
        });
        Schema::create('dim_ubicaciones', function (Blueprint $t) {
            $t->integer('ID_Ubicacion')->primary(); $t->integer('ID_Bloque')->nullable();
            $t->string('Bloque'); $t->string('Nave'); $t->string('Cama');
            $t->string('Estado')->default('ACTIVA'); $t->softDeletes();
        });
        Schema::create('dim_ciclos_siembras', function (Blueprint $t) {
            $t->increments('ID_Ciclo_Siembra'); $t->integer('ID_Bloque');
            $t->integer('ID_Variedad'); $t->date('Fecha_Siembra'); $t->string('Estado'); $t->timestamps();
            $t->unique(['ID_Bloque', 'ID_Variedad', 'Fecha_Siembra']);
        });
        Schema::create('dim_siembras', function (Blueprint $t) {
            $t->increments('ID_Siembra'); $t->integer('ID_Ubicacion'); $t->integer('ID_Variedad');
            $t->integer('ID_Ciclo_Siembra'); $t->date('Fecha_Siembra');
            $t->integer('Cantidad_Plantas'); $t->string('Estado_Siembra')->default('SEMBRADA');
            foreach (['Fecha_Pinch', 'Fecha_Hormona', 'Fecha_Erradicacion'] as $c) $t->date($c)->nullable();
            $t->softDeletes();
        });
        Schema::create('fact_produccion', function (Blueprint $t) {
            $t->increments('ID_Produccion'); $t->integer('ID_Ubicacion'); $t->integer('ID_Siembra')->nullable();
            $t->integer('Anio'); $t->integer('Semana'); $t->integer('Bajas')->default(0);
            $t->integer('Total')->default(0); $t->softDeletes(); $t->unique(['ID_Ubicacion', 'Anio', 'Semana']);
        });
        Schema::create('dim_permisos_rol', function (Blueprint $t) {
            $t->increments('ID_Permiso'); $t->integer('ID_Rol'); $t->string('Modulo');
            $t->string('Submodulo'); $t->boolean('Permiso_Ver');
        });
        foreach (['historico_siembras', 'comparador-siembras'] as $sub) {
            DB::table('dim_permisos_rol')->insert(['ID_Rol' => 1, 'Modulo' => 'agronomia', 'Submodulo' => $sub, 'Permiso_Ver' => true]);
        }
        $user = new Usuario(['Nombre' => 'Prueba', 'Username' => 'prueba', 'ID_Rol' => 1]);
        $user->ID_Usuario = 1;
        $this->actingAs($user);
    }

    private function siembra(int $id = 1, string $fecha = '2020-12-21', int $plantas = 100): Siembra
    {
        $v = Variedad::firstOrCreate(['Nombre_Variedad' => 'Misty', 'Color' => 'Blue']);
        Ubicacion::firstOrCreate(['ID_Ubicacion' => $id], ['Bloque' => (string) $id, 'Nave' => '1', 'Cama' => (string) $id]);
        return Siembra::create(['ID_Ubicacion' => $id, 'ID_Variedad' => $v->ID_Variedad, 'Fecha_Siembra' => $fecha, 'Cantidad_Plantas' => $plantas]);
    }

    private function producir(Siembra $s, int $anio, int $semana, int $total): void
    {
        Produccion::create(['ID_Ubicacion' => $s->ID_Ubicacion, 'ID_Siembra' => $s->ID_Siembra,
            'Anio' => $anio, 'Semana' => $semana, 'Total' => $total]);
    }

    public function test_comparador_renderiza_y_alinea_anios_iso_con_53_semanas(): void
    {
        $a = $this->siembra();
        $b = $this->siembra(2, '2022-01-03', 200);
        $this->producir($a, 2020, 52, 0);
        $this->producir($a, 2020, 53, 50);
        $this->producir($a, 2021, 1, 100);
        $this->producir($b, 2022, 1, 100);
        $r = $this->get(route('agronomia.comparador-siembras', ['buscar' => 1, 'ciclos' => [$a->ID_Ciclo_Siembra, $b->ID_Ciclo_Siembra]]));
        $r->assertOk()->assertSee('Misty - Blue')->assertSee('2021-01');
        $data = $r->viewData('reporte');
        $this->assertSame([1, 2, 3], $data['semanas']);
        $this->assertEquals([0, .5, 1], $data['grafica'][0]['data']);
        $this->assertSame([.5, null, null], $data['grafica'][1]['data']);
    }

    public function test_historico_conserva_ceros_y_excluye_otras_siembras_de_la_cama(): void
    {
        $a = $this->siembra();
        $old = $this->siembra(1, '2019-01-01');
        $this->producir($old, 2019, 2, 999);
        $this->producir($a, 2020, 52, 0);
        $this->producir($a, 2020, 53, 0);
        $r = $this->get(route('agronomia.historico_siembras', ['siembra' => json_encode([$a->ID_Siembra])]));
        $r->assertOk()->assertSee('2020-52')->assertSee('2020-53');
        $this->assertSame(['2020-52' => 0, '2020-53' => 0], $r->viewData('reporte')['totalSemanas']);
    }

    public function test_rechaza_mezclar_ciclos_en_el_historico(): void
    {
        $a = $this->siembra(); $b = $this->siembra(2);
        $this->getJson(route('agronomia.historico_siembras', ['siembra' => json_encode([$a->ID_Siembra, $b->ID_Siembra])]))
            ->assertUnprocessable()->assertJsonValidationErrors('siembra');
        $this->getJson(route('agronomia.historico_siembras', ['siembra' => 'null']))
            ->assertUnprocessable();
    }

    public function test_asigna_bloque_y_reasigna_ciclo_al_editar_fecha(): void
    {
        $a = $this->siembra();
        $this->assertNotNull($a->ubicacion->ID_Bloque);
        $anterior = $a->ID_Ciclo_Siembra;
        $a->update(['Fecha_Siembra' => '2022-01-01']);
        $this->assertNotEquals($anterior, $a->ID_Ciclo_Siembra);
        $this->assertSame('2022-01-01', $a->ciclo->Fecha_Siembra->format('Y-m-d'));
        $b = $this->siembra(1, '2022-01-01');
        $this->assertEquals($a->ID_Ciclo_Siembra, $b->ID_Ciclo_Siembra);
    }

    public function test_comparador_denomindador_cero_no_fabrica_un_indice(): void
    {
        $a = $this->siembra(1, '2020-12-21', 0); $this->producir($a, 2020, 52, 5);
        $r = $this->get(route('agronomia.comparador-siembras', ['buscar' => 1, 'ciclos' => [$a->ID_Ciclo_Siembra]]));
        $r->assertOk();
        $this->assertNull($r->viewData('reporte')['grafica'][0]['data'][0]);
    }

    public function test_permisos_del_comparador_y_endpoint_del_historico(): void
    {
        $a = $this->siembra();
        $this->getJson(route('agronomia.siembras_bloque', ['bloque' => 1]))->assertOk()->assertJsonPath('0.ids.0', $a->ID_Siembra);
        DB::table('dim_permisos_rol')->where('Submodulo', 'comparador-siembras')->delete();
        $this->get(route('agronomia.comparador-siembras'))->assertForbidden();
    }

    public function test_sincronizacion_conserva_contrato_de_ceros_y_bloques(): void
    {
        $a = $this->siembra(); $b = $this->siembra(2);
        $this->producir($a, 2020, 52, 80); $this->producir($a, 2020, 53, 90);
        $this->producir($b, 2020, 52, 70);
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Nave 1');
        $sheet->fromArray([['CAMA 1', 'SEMANA', '2020 - 52'], [null, 'TOTAL', null], [null, 'BAJAS', 0]]);
        $path = tempnam(sys_get_temp_dir(), 'agridash_test_');
        try {
            (new Xlsx($book))->save($path);
            $method = new \ReflectionMethod(ProduccionController::class, 'procesarExcelDeBloque');
            $method->setAccessible(true);
            $controller = new ProduccionController();
            $method->invoke($controller, $path, '1');
            $method->invoke($controller, $path, '1');
            $this->assertSame(3, Produccion::count());
            $this->assertEquals(0, Produccion::where('ID_Ubicacion', 1)->where('Semana', 52)->value('Total'));
            $this->assertEquals(90, Produccion::where('ID_Ubicacion', 1)->where('Semana', 53)->value('Total'));
            $this->assertEquals(70, Produccion::where('ID_Ubicacion', 2)->value('Total'));
        } finally {
            unlink($path);
            $book->disconnectWorksheets();
        }
    }

    public function test_rutas_apuntan_a_metodos_existentes(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $controller = $route->getAction('controller');
            if (!is_string($controller) || !str_contains($controller, '@')) continue;
            [$class, $method] = explode('@', $controller, 2);
            $this->assertTrue(method_exists($class, $method), $controller);
        }
    }
}
