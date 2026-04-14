<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\UbicacionAlmacen;
use Illuminate\Database\Seeder;

class LogisticaSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            [
                'razon_social' => 'Textiles Técnicos del Norte S.A. de C.V.',
                'nombre_comercial' => 'TTN Textiles',
                'rfc' => 'TTN920301AB1',
                'tipo_proveedor' => 'Materia Prima',
                'direccion' => 'Parque Industrial No. 1200',
                'ciudad' => 'Monterrey',
                'estado' => 'Nuevo León',
                'codigo_postal' => '64000',
                'pais' => 'México',
                'telefono_principal' => '+52-81-2450-1100',
                'email_general' => 'ventas@ttntextiles.mx',
                'dias_credito' => 30,
                'limite_credito' => 120000,
                'descuento_porcentaje' => 5,
                'calificacion' => 4.8,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001',
                'notas' => 'Proveedor principal de textiles impermeables.',
            ],
            [
                'razon_social' => 'Estructuras Outdoor México S.A. de C.V.',
                'nombre_comercial' => 'EOM Components',
                'rfc' => 'EOM890215CD2',
                'tipo_proveedor' => 'Materia Prima',
                'direccion' => 'Av. Tecnológico 455',
                'ciudad' => 'Guadalajara',
                'estado' => 'Jalisco',
                'codigo_postal' => '44190',
                'pais' => 'México',
                'telefono_principal' => '+52-33-3650-8822',
                'email_general' => 'pedidos@eom.mx',
                'dias_credito' => 20,
                'limite_credito' => 90000,
                'descuento_porcentaje' => 4,
                'calificacion' => 4.6,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001, ISO 14001',
                'notas' => 'Especializado en varillas y aluminio estructural.',
            ],
            [
                'razon_social' => 'Herrajes Industriales del Bajío S.A. de C.V.',
                'nombre_comercial' => 'HIB México',
                'rfc' => 'HIB860912EF3',
                'tipo_proveedor' => 'Herrajes',
                'direccion' => 'Circuito de la Industria 78',
                'ciudad' => 'Querétaro',
                'estado' => 'Querétaro',
                'codigo_postal' => '76090',
                'pais' => 'México',
                'telefono_principal' => '+52-44-2210-4455',
                'email_general' => 'atencion@hib.mx',
                'dias_credito' => 15,
                'limite_credito' => 65000,
                'descuento_porcentaje' => 3.5,
                'calificacion' => 4.5,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001',
                'notas' => 'Surtido amplio de hebillas, anillos y cierres.',
            ],
            [
                'razon_social' => 'Polímeros y Espumas del Pacífico S.A. de C.V.',
                'nombre_comercial' => 'PESP',
                'rfc' => 'PEP900514GH4',
                'tipo_proveedor' => 'Materia Prima',
                'direccion' => 'Carretera Federal Km 12',
                'ciudad' => 'Puebla',
                'estado' => 'Puebla',
                'codigo_postal' => '72190',
                'pais' => 'México',
                'telefono_principal' => '+52-22-2144-7788',
                'email_general' => 'ventas@pesp.mx',
                'dias_credito' => 25,
                'limite_credito' => 70000,
                'descuento_porcentaje' => 4.5,
                'calificacion' => 4.4,
                'estatus' => 'Activo',
                'certificaciones' => 'RoHS',
                'notas' => 'Proveedor alterno para materiales espumados.',
            ],
            [
                'razon_social' => 'Empaques Sustentables de México S.A. de C.V.',
                'nombre_comercial' => 'ESM Pack',
                'rfc' => 'ESM910623IJ5',
                'tipo_proveedor' => 'Empaque',
                'direccion' => 'Boulevard Empresarial 900',
                'ciudad' => 'Toluca',
                'estado' => 'Estado de México',
                'codigo_postal' => '50220',
                'pais' => 'México',
                'telefono_principal' => '+52-72-2355-9021',
                'email_general' => 'cotizaciones@esmpack.mx',
                'dias_credito' => 35,
                'limite_credito' => 85000,
                'descuento_porcentaje' => 6,
                'calificacion' => 4.9,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001, FSC',
                'notas' => 'Material de empaque ecológico y biodegradable.',
            ],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::updateOrCreate(
                ['razon_social' => $proveedor['razon_social']],
                $proveedor
            );
        }

        $ubicaciones = [
            [
                'codigo_ubicacion' => 'ALM-REC-01',
                'nombre' => 'Área de Recepción Principal',
                'tipo' => 'Recepción',
                'seccion' => 'R1',
                'estante' => '0',
                'nivel' => '0',
                'capacidad_maxima' => 600,
                'capacidad_actual' => 0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'ALM-TXT-01',
                'nombre' => 'Estantería de Textiles',
                'tipo' => 'Estantería',
                'seccion' => 'T2',
                'estante' => '4',
                'nivel' => '2',
                'capacidad_maxima' => 450,
                'capacidad_actual' => 0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'ALM-PT-01',
                'nombre' => 'Zona de Producto Terminado',
                'tipo' => 'Zona Abierta',
                'seccion' => 'PT',
                'estante' => '0',
                'nivel' => '0',
                'capacidad_maxima' => 1500,
                'capacidad_actual' => 0,
                'activo' => true,
            ],
        ];

        foreach ($ubicaciones as $ubicacion) {
            UbicacionAlmacen::updateOrCreate(
                ['codigo_ubicacion' => $ubicacion['codigo_ubicacion']],
                $ubicacion
            );
        }
    }
}
